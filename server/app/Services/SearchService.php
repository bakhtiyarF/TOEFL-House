<?php

namespace App\Services;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

/**
 * Search Service
 *
 * Provides advanced search functionality using Elasticsearch.
 */
class SearchService
{
    protected Client $client;
    protected string $indexPrefix;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts([config('services.elasticsearch.host')])
            ->setBasicAuthentication(
                config('services.elasticsearch.username'),
                config('services.elasticsearch.password')
            )
            ->build();

        $this->indexPrefix = config('services.elasticsearch.index_prefix', 'toeflhouse');
    }

    /**
     * Index a document.
     */
    public function index(string $type, string $id, array $data, ?string $tenantId = null): bool
    {
        try {
            $index = $this->getIndexName($type, $tenantId);

            $params = [
                'index' => $index,
                'id' => $id,
                'body' => array_merge($data, [
                    'indexed_at' => now()->toIso8601String(),
                    'tenant_id' => $tenantId,
                ]),
            ];

            $response = $this->client->index($params);

            Log::info("Document indexed", [
                'type' => $type,
                'id' => $id,
                'index' => $index,
            ]);

            return $response['result'] === 'created' || $response['result'] === 'updated';
        } catch (\Exception $e) {
            Log::error("Failed to index document", [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Delete a document.
     */
    public function delete(string $type, string $id, ?string $tenantId = null): bool
    {
        try {
            $index = $this->getIndexName($type, $tenantId);

            $params = [
                'index' => $index,
                'id' => $id,
            ];

            $response = $this->client->delete($params);

            Log::info("Document deleted", [
                'type' => $type,
                'id' => $id,
                'index' => $index,
            ]);

            return $response['result'] === 'deleted';
        } catch (\Exception $e) {
            Log::error("Failed to delete document", [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Search documents.
     */
    public function search(string $type, array $query, ?string $tenantId = null, int $from = 0, int $size = 20): array
    {
        try {
            $index = $this->getIndexName($type, $tenantId);

            // Add tenant filter if tenant_id is provided
            if ($tenantId) {
                $query = [
                    'bool' => [
                        'must' => $query['bool']['must'] ?? [],
                        'filter' => array_merge(
                            $query['bool']['filter'] ?? [],
                            [['term' => ['tenant_id' => $tenantId]]]
                        ),
                    ],
                ];
            }

            $params = [
                'index' => $index,
                'body' => [
                    'query' => $query,
                    'from' => $from,
                    'size' => $size,
                ],
            ];

            $response = $this->client->search($params);

            return [
                'hits' => collect($response['hits']['hits'])->map(function ($hit) {
                    return [
                        'id' => $hit['_id'],
                        'score' => $hit['_score'],
                        'data' => $hit['_source'],
                    ];
                })->toArray(),
                'total' => $response['hits']['total']['value'],
                'took' => $response['took'],
            ];
        } catch (\Exception $e) {
            Log::error("Search failed", [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return [
                'hits' => [],
                'total' => 0,
                'took' => 0,
            ];
        }
    }

    /**
     * Full-text search.
     */
    public function fullTextSearch(string $type, string $queryString, array $fields, ?string $tenantId = null, int $from = 0, int $size = 20): array
    {
        $query = [
            'bool' => [
                'must' => [
                    [
                        'multi_match' => [
                            'query' => $queryString,
                            'fields' => $fields,
                            'type' => 'best_fields',
                            'fuzziness' => 'AUTO',
                        ],
                    ],
                ],
            ],
        ];

        return $this->search($type, $query, $tenantId, $from, $size);
    }

    /**
     * Search students.
     */
    public function searchStudents(string $queryString, ?string $tenantId = null, int $from = 0, int $size = 20): array
    {
        $fields = [
            'full_name^3',
            'student_code^2',
            'email^2',
            'phone',
            'father_name',
        ];

        return $this->fullTextSearch('students', $queryString, $fields, $tenantId, $from, $size);
    }

    /**
     * Search teachers.
     */
    public function searchTeachers(string $queryString, ?string $tenantId = null, int $from = 0, int $size = 20): array
    {
        $fields = [
            'full_name^3',
            'email^2',
            'phone',
            'specialization^2',
            'qualification',
        ];

        return $this->fullTextSearch('teachers', $queryString, $fields, $tenantId, $from, $size);
    }

    /**
     * Search classes.
     */
    public function searchClasses(string $queryString, ?string $tenantId = null, int $from = 0, int $size = 20): array
    {
        $fields = [
            'name^3',
            'code^2',
            'description',
            'teacher_name^2',
        ];

        return $this->fullTextSearch('classes', $queryString, $fields, $tenantId, $from, $size);
    }

    /**
     * Index a student.
     */
    public function indexStudent($student, ?string $tenantId = null): bool
    {
        $data = [
            'full_name' => $student->full_name,
            'student_code' => $student->student_code,
            'email' => $student->email,
            'phone' => $student->phone,
            'father_name' => $student->father_name,
            'status' => $student->status,
            'branch_id' => $student->branch_id,
            'created_at' => $student->created_at?->toIso8601String(),
        ];

        return $this->index('students', $student->id, $data, $tenantId);
    }

    /**
     * Index a teacher.
     */
    public function indexTeacher($teacher, ?string $tenantId = null): bool
    {
        $data = [
            'full_name' => $teacher->full_name,
            'email' => $teacher->email,
            'phone' => $teacher->phone,
            'specialization' => $teacher->specialization,
            'qualification' => $teacher->qualification,
            'status' => $teacher->status,
            'branch_id' => $teacher->branch_id,
            'created_at' => $teacher->created_at?->toIso8601String(),
        ];

        return $this->index('teachers', $teacher->id, $data, $tenantId);
    }

    /**
     * Index a class.
     */
    public function indexClass($class, ?string $tenantId = null): bool
    {
        $data = [
            'name' => $class->name,
            'code' => $class->code,
            'description' => $class->description,
            'teacher_name' => $class->teacher->full_name ?? null,
            'status' => $class->status,
            'branch_id' => $class->branch_id,
            'created_at' => $class->created_at?->toIso8601String(),
        ];

        return $this->index('classes', $class->id, $data, $tenantId);
    }

    /**
     * Create index with mappings.
     */
    public function createIndex(string $type, ?string $tenantId = null): bool
    {
        try {
            $index = $this->getIndexName($type, $tenantId);

            $mappings = $this->getMappings($type);

            $params = [
                'index' => $index,
                'body' => [
                    'settings' => [
                        'number_of_shards' => 1,
                        'number_of_replicas' => 1,
                        'analysis' => [
                            'analyzer' => [
                                'default' => [
                                    'type' => 'standard',
                                ],
                            ],
                        ],
                    ],
                    'mappings' => $mappings,
                ],
            ];

            $response = $this->client->indices()->create($params);

            Log::info("Index created", [
                'index' => $index,
                'type' => $type,
            ]);

            return $response['acknowledged'];
        } catch (\Exception $e) {
            Log::error("Failed to create index", [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get index name.
     */
    protected function getIndexName(string $type, ?string $tenantId = null): string
    {
        $index = $this->indexPrefix . '_' . $type;
        
        if ($tenantId) {
            $index .= '_' . $tenantId;
        }

        return strtolower($index);
    }

    /**
     * Get mappings for a type.
     */
    protected function getMappings(string $type): array
    {
        $mappings = [
            'students' => [
                'properties' => [
                    'full_name' => ['type' => 'text', 'analyzer' => 'standard'],
                    'student_code' => ['type' => 'keyword'],
                    'email' => ['type' => 'keyword'],
                    'phone' => ['type' => 'keyword'],
                    'father_name' => ['type' => 'text'],
                    'status' => ['type' => 'keyword'],
                    'branch_id' => ['type' => 'keyword'],
                    'tenant_id' => ['type' => 'keyword'],
                    'created_at' => ['type' => 'date'],
                    'indexed_at' => ['type' => 'date'],
                ],
            ],
            'teachers' => [
                'properties' => [
                    'full_name' => ['type' => 'text', 'analyzer' => 'standard'],
                    'email' => ['type' => 'keyword'],
                    'phone' => ['type' => 'keyword'],
                    'specialization' => ['type' => 'text'],
                    'qualification' => ['type' => 'text'],
                    'status' => ['type' => 'keyword'],
                    'branch_id' => ['type' => 'keyword'],
                    'tenant_id' => ['type' => 'keyword'],
                    'created_at' => ['type' => 'date'],
                    'indexed_at' => ['type' => 'date'],
                ],
            ],
            'classes' => [
                'properties' => [
                    'name' => ['type' => 'text', 'analyzer' => 'standard'],
                    'code' => ['type' => 'keyword'],
                    'description' => ['type' => 'text'],
                    'teacher_name' => ['type' => 'text'],
                    'status' => ['type' => 'keyword'],
                    'branch_id' => ['type' => 'keyword'],
                    'tenant_id' => ['type' => 'keyword'],
                    'created_at' => ['type' => 'date'],
                    'indexed_at' => ['type' => 'date'],
                ],
            ],
        ];

        return $mappings[$type] ?? [];
    }

    /**
     * Bulk index documents.
     */
    public function bulkIndex(string $type, array $documents, ?string $tenantId = null): array
    {
        try {
            $index = $this->getIndexName($type, $tenantId);
            $params = ['body' => []];

            foreach ($documents as $doc) {
                $params['body'][] = [
                    'index' => [
                        '_index' => $index,
                        '_id' => $doc['id'],
                    ],
                ];
                $params['body'][] = array_merge($doc['data'], [
                    'indexed_at' => now()->toIso8601String(),
                    'tenant_id' => $tenantId,
                ]);
            }

            $response = $this->client->bulk($params);

            $success = collect($response['items'])->filter(function ($item) {
                return !isset($item['index']['error']);
            })->count();

            $failed = count($documents) - $success;

            Log::info("Bulk indexing completed", [
                'type' => $type,
                'total' => count($documents),
                'success' => $success,
                'failed' => $failed,
            ]);

            return [
                'success' => $success,
                'failed' => $failed,
                'errors' => collect($response['items'])
                    ->filter(function ($item) {
                        return isset($item['index']['error']);
                    })
                    ->pluck('index.error')
                    ->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error("Bulk indexing failed", [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => 0,
                'failed' => count($documents),
                'errors' => [$e->getMessage()],
            ];
        }
    }
}
