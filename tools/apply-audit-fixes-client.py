#!/usr/bin/env python3
"""
Apply the frontend audit fixes to a pristine checkout of toefl-house-v3/client.

Run from the repository root:

    python3 tools/apply-audit-fixes-client.py

Every substitution is asserted. If one does not apply, the script stops and
names it, so a silent partial application is impossible.

These are the 20 TypeScript errors that make `npm run build` fail (build is
`tsc -b && vite build`, so a type error blocks the production bundle entirely).
"""

import os
import sys

ROOT = os.path.normpath(os.path.join(os.path.dirname(os.path.abspath(__file__)),
                                     '..', 'toefl-house-v3', 'client'))

applied = []


def read(rel):
    with open(os.path.join(ROOT, rel), encoding='utf-8') as f:
        return f.read()


def write(rel, text):
    with open(os.path.join(ROOT, rel), 'w', encoding='utf-8') as f:
        f.write(text)


def sub(rel, old, new, count=1, why=''):
    s = read(rel)
    n = s.count(old)
    if n != count:
        sys.exit(f"FAIL: {rel}: expected {count} occurrence(s), found {n}\n---\n{old[:300]}\n---")
    write(rel, s.replace(old, new))
    applied.append(f"{rel}: {why}")


# ---------------------------------------------------------------------------
# 1. TeachersPage.tsx — six errors, one root cause: code was moved between
#    components without its dependencies.
# ---------------------------------------------------------------------------
TP = 'modules/people-hr/components/TeachersPage.tsx'

# `computed` is declared in TeacherSalaryPanel, not in TeachersPage, where this
# dialog handler lives. selectedTeacher.base_salary is the value already in scope.
sub(TP,
    "                    const due = (computed.total_due || selectedTeacher.base_salary || 0);",
    "                    // `computed` belongs to TeacherSalaryPanel's scope, not this\n"
    "                    // one; base_salary is the figure available here.\n"
    "                    const due = (selectedTeacher.base_salary || 0);",
    why="drop out-of-scope `computed` from the pay-salary dialog")

# TeacherSalaryPanel referenced three names declared in the parent component.
# It already receives `teacher` as a prop and can own the two hooks itself.
sub(TP,
    """function TeacherSalaryPanel({ teacherId, teacher }: { teacherId: string; teacher: any }) {
  const { data: salaryData } = useTeacherSalary(teacherId);""",
    """function TeacherSalaryPanel({ teacherId, teacher }: { teacherId: string; teacher: any }) {
  const { data: salaryData } = useTeacherSalary(teacherId);
  // This panel used to read `salaryHistory` and `payTeacherSalary` from the
  // parent component's scope, which does not exist here. Own them instead,
  // keyed on the teacherId prop.
  const { data: salaryHistory = [] } = useTeacherSalaryHistory(teacherId);
  const payTeacherSalary = usePayTeacherSalary();""",
    why="give TeacherSalaryPanel its own hooks")

sub(TP,
    """          <Button size="sm" variant="outline" onClick={() => {
            if (!selectedTeacher) return;
            const period = new Date().toISOString().slice(0,7);
            payTeacherSalary.mutate({
              teacherId: selectedTeacher.id,""",
    """          <Button size="sm" variant="outline" onClick={() => {
            if (!teacher) return;
            const period = new Date().toISOString().slice(0,7);
            payTeacherSalary.mutate({
              teacherId: teacher.id,""",
    why="use the `teacher` prop instead of the parent's selectedTeacher")

# ---------------------------------------------------------------------------
# 2. InventoryPage.tsx — `toast` used but never imported (sibling pages import it).
# ---------------------------------------------------------------------------
sub('modules/inventory/components/InventoryPage.tsx',
    "import { formatAmount } from '@shared/lib/utils';",
    "import { toast } from 'sonner';\nimport { formatAmount } from '@shared/lib/utils';",
    why="import toast (used by the refund handler)")

# ---------------------------------------------------------------------------
# 3. FinancePage.tsx — temporal dead zone: totalExpenses aliases a variable
#    declared 55 lines later.
# ---------------------------------------------------------------------------
sub('modules/finance-payroll/components/FinancePage.tsx',
    """  const totalIncome = liveTx.reduce((sum, t) => sum + t.amount, 0);
  const totalExpenses = totalExpensesLive; // now live from expenses + payroll ledger
  const netIncome = totalIncome - totalExpenses;""",
    """  const totalIncome = liveTx.reduce((sum, t) => sum + t.amount, 0);
  // totalExpenses/netIncome are derived below, next to totalExpensesLive:
  // reading it here is a temporal-dead-zone error.""",
    why="remove the TDZ read of totalExpensesLive")

sub('modules/finance-payroll/components/FinancePage.tsx',
    "  const totalExpensesLive = expenseTotal + payrollExpenseTotal;",
    """  const totalExpensesLive = expenseTotal + payrollExpenseTotal;
  const totalExpenses = totalExpensesLive; // live from expenses + payroll ledger
  const netIncome = totalIncome - totalExpenses;""",
    why="derive totalExpenses/netIncome after their input exists")

# ---------------------------------------------------------------------------
# 4. VisitorsPage.tsx — the API hooks are untyped, so `campaigns` is `unknown`
#    and `liveReadiness` is `{}`. Typed at the consumption site, matching the
#    `as any` style the rest of this codebase already uses.
# ---------------------------------------------------------------------------
sub('modules/crm-enrollment/components/VisitorsPage.tsx',
    "  const { data: campaigns = [] } = useCampaigns();",
    "  // A destructuring default does not widen useQuery's inferred type\n"
    "  // (unknown | any[] collapses to unknown), so cast after destructuring.\n"
    "  const { data: campaignsData } = useCampaigns();\n"
    "  const campaigns = (campaignsData as any[]) || [];",
    why="type campaigns (hook is untyped)")

sub('modules/crm-enrollment/components/VisitorsPage.tsx',
    "  const { data: liveReadiness, isLoading: readinessLoading } = useConversionReadiness(selectedVisitor?.id || null);",
    "  const { data: readinessData, isLoading: readinessLoading } = useConversionReadiness(selectedVisitor?.id || null);\n"
    "  const liveReadiness = readinessData as {\n"
    "    canConvert?: boolean;\n"
    "    placementCompleted?: boolean;\n"
    "    placementFeePaid?: boolean;\n"
    "    reasons?: string[];\n"
    "  } | null | undefined;",
    why="type liveReadiness instead of {}")

# ---------------------------------------------------------------------------
# 5. SettingsPage.tsx — same untyped-hook problem for `settings`.
# ---------------------------------------------------------------------------
sub('modules/platform-services/components/SettingsPage.tsx',
    "  const { data: settings = {} } = useSettings();",
    "  // Cast after destructuring: a default value does not widen the\n"
    "  // `unknown`/`{}` type that useQuery infers from the untyped API client.\n"
    "  const { data: settingsData } = useSettings();\n"
    "  const settings = (settingsData as Record<string, any>) || {};",
    why="type settings (hook is untyped)")

# ---------------------------------------------------------------------------
# 6. Rules-of-hooks violations (10 oxlint errors). Each is a real React
#    correctness bug: hooks called in a different order between renders give
#    stale state or a crash, and a hook called inside a callback never
#    subscribes correctly.
# ---------------------------------------------------------------------------

# 6a. Early return *before* the hooks, so the hook order differs between the
#     production and development renders.
sub('shared/components/RoleWorkspaceValidator.tsx',
    """export function RoleWorkspaceValidator() {
  if (import.meta.env.PROD) return null;

  const { user, hasPermission } = useAuth();""",
    """export function RoleWorkspaceValidator() {
  // Hooks must run unconditionally. Short-circuiting before them changes the
  // hook order between prod and dev renders, so the check lives in a wrapper
  // and the hooks live in the inner component.
  if (import.meta.env.PROD) return null;

  return <RoleWorkspaceValidatorInner />;
}

function RoleWorkspaceValidatorInner() {
  const { user, hasPermission } = useAuth();""",
    why="move the prod short-circuit out of the hook path")

# 6b. `useX ? useX() : fallback` — the guard never fires because the hooks are
#     statically imported, but the ternary still makes the call conditional.
sub('shared/components/GenerativeQuickActions.tsx',
    """  const { data: liveDonors = [] } = useDonors ? useDonors() : { data: [] } as any;
  const { data: liveDonations = [] } = useDonations ? useDonations() : { data: [] } as any;
  const { data: liveCampaigns = [] } = useCampaigns ? useCampaigns() : { data: [] } as any;
  const { data: liveCerts = [] } = useCertificates ? useCertificates() : { data: [] } as any;""",
    """  // These four hooks are statically imported above, so the `x ? x() : ...`
  // guard can never take the fallback branch -- and the ternary makes the call
  // conditional, which breaks the rules of hooks.
  const { data: liveDonors = [] } = useDonors();
  const { data: liveDonations = [] } = useDonations();
  const { data: liveCampaigns = [] } = useCampaigns();
  const { data: liveCerts = [] } = useCertificates();""",
    why="call the four guarded hooks unconditionally")

# 6c. Hook called inside an onClick, even though the mutation is already hoisted
#     to the component body as `createExamResult`.
sub('modules/academic/components/ClassesPage.tsx',
    """                              // Use the hook mutation directly
                              // @ts-ignore - runtime
                              useCreateExamResult().mutate({""",
    """                              // Use the mutation already hoisted to the component
                              // body. Calling a hook inside a callback breaks the
                              // rules of hooks (and the @ts-ignore hid it).
                              createExamResult.mutate({""",
    why="use the hoisted createExamResult instead of calling the hook in onClick")

print(f"\n{len(applied)} client fixes applied:\n")
for a in applied:
    print("  -", a)
