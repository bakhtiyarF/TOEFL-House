<?php

namespace App\Modules\PlatformServices\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rule Engine Service
 * Implements 02_BUSINESS_LOGIC_AND_DOMAIN_CONTRACT.md §7 exactly
 *
 * This is the single implementation every module calls.
 * Academic's PromotionService/AttendanceService and Finance & Payroll's
 * TuitionCalculationService/PayrollService all depend on this.
 */
class RuleEngineService
{
    /**
     * Evaluate rules for a given category and branch
     *
     * Per 02 §7.1: single forward pass, higher priority runs first,
     * rules are chain-aware (later rules see earlier rules' outputs)
     *
     * @return array{
     *   finalOutputs: array,
     *   isBlocked: bool,
     *   blockReason: ?string,
     *   warnings: string[],
     *   evaluations: array
     * }
     */
    public function evaluate(
        string $category,
        ?string $branchId,
        array $data,
        bool $dryRun = false
    ): array {
        $rules = $this->fetchActiveRules($category, $branchId);
        $runningData = $data;
        $finalOutputs = [];
        $warnings = [];
        $evaluations = [];
        $isBlocked = false;
        $blockReason = null;

        foreach ($rules as $rule) {
            $matched = $this->evaluateConditions($rule['conditions'], $runningData);

            $evaluations[] = [
                'rule_id' => $rule['id'],
                'rule_name' => $rule['name'],
                'matched' => $matched,
            ];

            if (!$matched) {
                continue;
            }

            foreach ($rule['actions'] as $action) {
                $result = $this->executeAction($action, $runningData, $dryRun);

                if ($result['type'] === 'block') {
                    $isBlocked = true;
                    $blockReason = $result['reason'] ?? 'Blocked by rule: ' . $rule['name'];
                    break 2; // Stop evaluating all further rules
                }

                if ($result['type'] === 'warn') {
                    $warnings[] = $result['message'];
                    continue;
                }

                // Merge outputs into running data (chain-aware) and final outputs
                if ($result['outputs']) {
                    foreach ($result['outputs'] as $key => $value) {
                        if (!str_starts_with($key, '__')) {
                            $finalOutputs[$key] = $value;
                            $runningData[$key] = $value;
                        }
                    }
                }
            }
        }

        // Log evaluation if not dry run
        if (!$dryRun) {
            $this->logEvaluations($evaluations, $category, $branchId, $data, $finalOutputs);
        }

        return [
            'finalOutputs' => $finalOutputs,
            'isBlocked' => $isBlocked,
            'blockReason' => $blockReason,
            'warnings' => $warnings,
            'evaluations' => $evaluations,
        ];
    }

    /**
     * Fetch active rules ordered by priority DESC, created_at ASC
     */
    private function fetchActiveRules(string $category, ?string $branchId): array
    {
        return DB::table('rule_definitions')
            ->where('category', $category)
            ->where('is_active', true)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('scope_branch_id');
                if ($branchId) {
                    $q->orWhere('scope_branch_id', $branchId);
                }
            })
            ->orderByDesc('priority')
            ->orderByAsc('created_at')
            ->get()
            ->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'conditions' => json_decode($rule->conditions, true) ?? [],
                    'actions' => json_decode($rule->actions, true) ?? [],
                    'priority' => $rule->priority,
                ];
            })
            ->toArray();
    }

    /**
     * Evaluate conditions — ALL must be true (AND semantics, 02 §7.1)
     */
    private function evaluateConditions(array $conditions, array $data): bool
    {
        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition, $data)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Evaluate a single condition against data
     */
    private function evaluateCondition(array $condition, array $data): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;
        $actual = $data[$field] ?? null;

        return match ($operator) {
            '==' => $actual == $value,
            '!=' => $actual != $value,
            '>' => is_numeric($actual) && is_numeric($value) && $actual > $value,
            '>=' => is_numeric($actual) && is_numeric($value) && $actual >= $value,
            '<' => is_numeric($actual) && is_numeric($value) && $actual < $value,
            '<=' => is_numeric($actual) && is_numeric($value) && $actual <= $value,
            'between' => is_array($value) && count($value) === 2
                && is_numeric($actual)
                && $actual >= $value[0] && $actual <= $value[1],
            'in' => is_array($value) && in_array($actual, $value),
            default => false,
        };
    }

    /**
     * Execute a single action (02 §7.2)
     */
    private function executeAction(array $action, array $data, bool $dryRun): array
    {
        $type = $action['type'] ?? '';

        return match ($type) {
            'set_value' => [
                'type' => 'output',
                'outputs' => [($action['targetKey'] ?? '') => $action['value'] ?? null],
            ],
            'add_discount' => [
                'type' => 'output',
                'outputs' => [
                    'discountPercent' => min(100, ($data['discountPercent'] ?? 0) + ($action['value'] ?? 0)),
                ],
            ],
            'calculate' => [
                'type' => 'output',
                'outputs' => [
                    ($action['targetKey'] ?? '') => $this->evaluateFormula(
                        $action['formula'] ?? '',
                        $data
                    ),
                ],
            ],
            'block' => [
                'type' => 'block',
                'reason' => $action['reason'] ?? 'Blocked',
            ],
            'warn' => [
                'type' => 'warn',
                'message' => $action['message'] ?? 'Warning',
            ],
            'notify' => $dryRun ? ['type' => 'noop'] : $this->handleNotify($action),
            'trigger_event' => $dryRun ? ['type' => 'noop'] : $this->handleTriggerEvent($action),
            default => ['type' => 'noop'],
        };
    }

    /**
     * Restricted arithmetic formula evaluator (02 §7.3)
     *
     * Safety guarantee: no arbitrary code execution path from a rule's formula string.
     * Only numeric fields from the data context are available as variables.
     * Unknown variables evaluate to 0. Parse errors return 0.
     */
    public function evaluateFormula(string $formula, array $data): float
    {
        if (empty(trim($formula))) {
            return 0.0;
        }

        try {
            // Replace variable names with their numeric values
            $expression = $formula;

            // Sort keys by length (longest first) to avoid partial replacements
            $keys = array_keys($data);
            usort($keys, fn($a, $b) => strlen($b) - strlen($a));

            foreach ($keys as $key) {
                $value = is_numeric($data[$key] ?? 0) ? (float)($data[$key] ?? 0) : 0;
                $expression = str_replace($key, (string)$value, $expression);
            }

            // Remove any remaining non-numeric, non-operator characters
            // (unknown variables become 0)
            $expression = preg_replace('/[a-zA-Z_]+/', '0', $expression);

            // Only allow safe characters: digits, operators, parentheses, dots, spaces
            if (!preg_match('/^[\d\s\+\-\*\/\(\)\.]+$/', $expression)) {
                return 0.0;
            }

            // Evaluate the safe arithmetic expression
            return $this->evaluateArithmetic($expression);
        } catch (\Throwable $e) {
            Log::warning('Rule formula evaluation failed', [
                'formula' => $formula,
                'error' => $e->getMessage(),
            ]);
            return 0.0;
        }
    }

    /**
     * Safe arithmetic evaluator — recursive descent parser
     * Only handles +, -, *, / and parentheses
     */
    private function evaluateArithmetic(string $expr): float
    {
        $expr = trim($expr);
        if (empty($expr)) return 0.0;

        // Tokenize
        $tokens = [];
        $i = 0;
        while ($i < strlen($expr)) {
            $ch = $expr[$i];
            if ($ch === ' ') {
                $i++;
                continue;
            }
            if (in_array($ch, ['+', '-', '*', '/', '(', ')'])) {
                $tokens[] = $ch;
                $i++;
            } elseif (is_numeric($ch) || $ch === '.') {
                $num = '';
                while ($i < strlen($expr) && (is_numeric($expr[$i]) || $expr[$i] === '.')) {
                    $num .= $expr[$i];
                    $i++;
                }
                $tokens[] = (float)$num;
            } else {
                $i++; // skip unknown
            }
        }

        $pos = 0;
        return $this->parseExpression($tokens, $pos);
    }

    private function parseExpression(array $tokens, int &$pos): float
    {
        $result = $this->parseTerm($tokens, $pos);

        while ($pos < count($tokens) && in_array($tokens[$pos] ?? '', ['+', '-'])) {
            $op = $tokens[$pos++];
            $right = $this->parseTerm($tokens, $pos);
            $result = $op === '+' ? $result + $right : $result - $right;
        }

        return $result;
    }

    private function parseTerm(array $tokens, int &$pos): float
    {
        $result = $this->parseFactor($tokens, $pos);

        while ($pos < count($tokens) && in_array($tokens[$pos] ?? '', ['*', '/'])) {
            $op = $tokens[$pos++];
            $right = $this->parseFactor($tokens, $pos);
            $result = $op === '*' ? $result * $right : ($right != 0 ? $result / $right : 0);
        }

        return $result;
    }

    private function parseFactor(array $tokens, int &$pos): float
    {
        if ($pos >= count($tokens)) return 0;

        $token = $tokens[$pos];

        if ($token === '(') {
            $pos++; // skip '('
            $result = $this->parseExpression($tokens, $pos);
            if ($pos < count($tokens) && $tokens[$pos] === ')') {
                $pos++; // skip ')'
            }
            return $result;
        }

        if ($token === '-') {
            $pos++;
            return -$this->parseFactor($tokens, $pos);
        }

        if (is_float($token) || is_int($token)) {
            $pos++;
            return (float)$token;
        }

        return 0;
    }

    private function handleNotify(array $action): array
    {
        // Queue notification — handled by NotificationService
        return ['type' => 'notify', 'channel' => $action['channel'] ?? 'system', 'message' => $action['message'] ?? ''];
    }

    private function handleTriggerEvent(array $action): array
    {
        // Fire domain event — handled by EventBusService
        return ['type' => 'trigger', 'event' => $action['event'] ?? ''];
    }

    private function logEvaluations(array $evaluations, string $category, ?string $branchId, array $context, array $results): void
    {
        foreach ($evaluations as $eval) {
            try {
                DB::table('rule_evaluation_logs')->insert([
                    'id' => (string)\Illuminate\Support\Str::uuid(),
                    'rule_id' => $eval['rule_id'],
                    'category' => $category,
                    'branch_id' => $branchId,
                    'matched' => $eval['matched'],
                    'context_json' => json_encode($context),
                    'result_json' => json_encode($results),
                    'dry_run' => false,
                    'evaluated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to log rule evaluation', ['error' => $e->getMessage()]);
            }
        }
    }
}
