<?php

namespace Illuminate\Bus;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Bus\BatchFactory;
use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\PendingBatch;
use Illuminate\Bus\UpdatedBatchJobCounts;
use Illuminate\Support\Str;

class DynamoBatchRepository implements BatchRepository
{
    /**
     * The batch factory instance.
     *
     * @var \Illuminate\Bus\BatchFactory
     */
    protected $factory;

    /**
     * The database connection instance.
     *
     * @var DynamoDbClient
     */
    protected $dynamoDbClient;

    /**
     * The application name.
     *
     * @var string
     */
    protected $applicationName;

    /**
     * The table to use to store batch information.
     *
     * @var string
     */
    protected $table;

    /**
     * @var Marshaler
     */
    protected $marshaler;

    /**
     * @var int
     */
    protected $ttl;

    /**
     * @var string
     */
    protected $ttlAttribute;

    /**
     * Create a new batch repository instance.
     */
    public function __construct(
        BatchFactory $factory,
        DynamoDbClient $dynamoDbClient,
        string $applicationName,
        string $table,
        ?int $ttl,
        ?string $ttlAttribute
    ) {
        $this->factory = $factory;
        $this->dynamoDbClient = $dynamoDbClient;
        $this->applicationName = $applicationName;
        $this->table = $table;
        $this->marshaler = new Marshaler();
        $this->ttl = $ttl;
        $this->ttlAttribute = $ttlAttribute;
    }

    /**
     * Retrieve a list of batches.
     *
     * @param  int  $limit
     * @param  mixed  $before
     * @return \Illuminate\Bus\Batch[]
     */
    public function get($limit = 50, $before = null)
    {
        $condition = 'application = :application';
        if ($before) {
            $condition = 'application = :application AND id < :id';
        }

        $result = $this->dynamoDbClient->query([
            'TableName' => $this->table,
            'KeyConditionExpression' => $condition,
            'ExpressionAttributeValues' => array_filter([
                ':application' => ['S' => $this->applicationName],
                ':id' => array_filter(['S' => $before]),
            ]),
            'Limit' => $limit,
        ]);

        return array_map(
            fn ($b) => $this->toBatch($this->marshaler->unmarshalItem($b, mapAsObject: true)),
            $result['Items']
        );
    }

    /**
     * Retrieve information about an existing batch.
     *
     * @param  string  $batchId
     * @return \Illuminate\Bus\Batch|null
     */
    public function find(string $batchId)
    {
        if ($batchId === '') {
            return null;
        }

        $b = $this->dynamoDbClient->getItem([
            'TableName' => $this->table,
            'Key' => [
                'application' => ['S' => $this->applicationName],
                'id' => ['S' => $batchId],
            ],
        ]);

        if (!isset($b['Item'])) {
            return null;
        }

        $batch = $this->marshaler->unmarshalItem($b['Item'], mapAsObject: true);

        if ($batch) {
            return $this->toBatch($batch);
        }
    }

    /**
     * Store a new pending batch.
     *
     * @param  \Illuminate\Bus\PendingBatch  $batch
     * @return \Illuminate\Bus\Batch
     */
    public function store(PendingBatch $batch)
    {
        $id = (string) Str::orderedUuid();

        $batch = [
            'id' => $id,
            'name' => $batch->name,
            'total_jobs' => 0,
            'pending_jobs' => 0,
            'failed_jobs' => 0,
            'failed_job_ids' => [],
            'options' => $this->serialize($batch->options ?? []),
            'created_at' => time(),
            'cancelled_at' => null,
            'finished_at' => null,
        ];
        if (!is_null($this->ttl)) {
            $batch[$this->ttlAttribute] = time() + $this->ttl;
        }

        $this->dynamoDbClient->putItem([
            'TableName' => $this->table,
            'Item' => $this->marshaler->marshalItem(array_merge(['application' => $this->applicationName], $batch)),
        ]);

        return $this->find($id);
    }

    /**
     * Increment the total number of jobs within the batch.
     *
     * @param  string  $batchId
     * @param  int  $amount
     * @return void
     */
    public function incrementTotalJobs(string $batchId, int $amount)
    {
        $update = 'SET total_jobs = total_jobs + :val, pending_jobs = pending_jobs + :val';
        if ($this->ttl) {
            $update = "SET total_jobs = total_jobs + :val, pending_jobs = pending_jobs + :val, #{$this->ttlAttribute} = :ttl";
        }
        $this->dynamoDbClient->updateItem(array_filter([
            'TableName' => $this->table,
            'Key' => [
                'application' => ['S' => $this->applicationName],
                'id' => ['S' => $batchId],
            ],
            'UpdateExpression' => $update,
            'ExpressionAttributeValues' => array_filter([
                ':val' => ['N' => "$amount"],
                ':ttl' => array_filter(['N' => $this->getExpiryTime()]),
            ]),
            'ExpressionAttributeNames' => $this->ttlExpressionAttributeName(),
            'ReturnValues' => 'ALL_NEW',
        ]));
    }

    /**
     * Decrement the total number of pending jobs for the batch.
     *
     * @param  string  $batchId
     * @param  string  $jobId
     * @return \Illuminate\Bus\UpdatedBatchJobCounts
     */
    public function decrementPendingJobs(string $batchId, string $jobId)
    {
        $update = 'SET pending_jobs = pending_jobs - :inc';
        if ($this->ttl !== null) {
            $update = "SET pending_jobs = pending_jobs - :inc, #{$this->ttlAttribute} = :ttl";
        }
        $batch = $this->dynamoDbClient->updateItem(array_filter([
            'TableName' => $this->table,
            'Key' => [
                'application' => ['S' => $this->applicationName],
                'id' => ['S' => $batchId],
            ],
            'UpdateExpression' => $update,
            'ExpressionAttributeValues' => array_filter([
                ':inc' => ['N' => '1'],
                ':ttl' => array_filter(['N' => $this->getExpiryTime()]),
            ]),
            'ExpressionAttributeNames' => $this->ttlExpressionAttributeName(),
            'ReturnValues' => 'ALL_NEW',
        ]));
        $values = $this->marshaler->unmarshalItem($batch['Attributes']);

        return new UpdatedBatchJobCounts(
            $values['pending_jobs'],
            $values['failed_jobs']
        );
    }

    /**
     * Increment the total number of failed jobs for the batch.
     *
     * @param  string  $batchId
     * @param  string  $jobId
     * @return \Illuminate\Bus\UpdatedBatchJobCounts
     */
    public function incrementFailedJobs(string $batchId, string $jobId)
    {
        $update = 'SET failed_jobs = failed_jobs + :inc, failed_job_ids = list_append(failed_job_ids, :jobId)';
        if ($this->ttl !== null) {
            $update = "SET failed_jobs = failed_jobs + :inc, failed_job_ids = list_append(failed_job_ids, :jobId), #{$this->ttlAttribute} = :ttl";
        }
        $batch = $this->dynamoDbClient->updateItem(array_filter([
            'TableName' => $this->table,
            'Key' => [
                'application' => ['S' => $this->applicationName],
                'id' => ['S' => $batchId],
            ],
            'UpdateExpression' => $update,
            'ExpressionAttributeValues' => array_filter([
                ':jobId' => $this->marshaler->marshalValue([$jobId]),
                ':inc' => ['N' => '1'],
                ':ttl' => array_filter(['N' => $this->getExpiryTime()]),
            ]),
            'ExpressionAttributeNames' => $this->ttlExpressionAttributeName(),
            'ReturnValues' => 'ALL_NEW',
        ]));
        $values = $this->marshaler->unmarshalItem($batch['Attributes']);

        return new UpdatedBatchJobCounts(
            $values['pending_jobs'],
            $values['failed_jobs']
        );
    }

    /**
     * Mark the batch that has the given ID as finished.
     *
     * @param  string  $batchId
     * @return void
     */
    public function markAsFinished(string $batchId)
    {
        $update = 'SET finished_at = :timestamp';
        if ($this->ttl !== null) {
            $update = "SET finished_at = :timestamp, #{$this->ttlAttribute} = :ttl";
        }
        $this->dynamoDbClient->updateItem(array_filter([
            'TableName' => $this->table,
            'Key' => [
                'application' => ['S' => $this->applicationName],
                'id' => ['S' => $batchId],
            ],
            'UpdateExpression' => $update,
            'ExpressionAttributeValues' => array_filter([
                ':timestamp' => ['N' => (string) time()],
                ':ttl' => array_filter(['N' => $this->getExpiryTime()]),
            ]),
            'ExpressionAttributeNames' => $this->ttlExpressionAttributeName(),
        ]));
    }

    protected function getExpiryTime(): ?string
    {
        if ($this->ttl === null) {
            return null;
        }

        return (string) (time() + $this->ttl);
    }

    protected function ttlExpressionAttributeName(): array
    {
        if ($this->ttl === null) {
            return [];
        }

        return ["#{$this->ttlAttribute}" => $this->ttlAttribute];
    }

    /**
     * Cancel the batch that has the given ID.
     *
     * @param  string  $batchId
     * @return void
     */
    public function cancel(string $batchId)
    {
        $update = 'SET cancelled_at = :timestamp, finished_at = :timestamp';
        if ($this->ttl !== null) {
            $update = "SET cancelled_at = :timestamp, finished_at = :timestamp, #{$this->ttlAttribute} = :ttl";
        }

        $this->dynamoDbClient->updateItem(array_filter([
            'TableName' => $this->table,
            'Key' => [
                'application' => ['S' => $this->applicationName],
                'id' => ['S' => $batchId],
            ],
            'UpdateExpression' => $update,
            'ExpressionAttributeValues' => array_filter([
                ':timestamp' => ['N' => (string) time()],
                ':ttl' => array_filter(['N' => $this->getExpiryTime()]),
            ]),
            'ExpressionAttributeNames' => $this->ttlExpressionAttributeName(),
        ]));
    }

    /**
     * Delete the batch that has the given ID.
     *
     * @param  string  $batchId
     * @return void
     */
    public function delete(string $batchId)
    {
        $this->dynamoDbClient->deleteItem([
            'TableName' => $this->table,
            'Key' => [
                'application' => ['S' => $this->applicationName],
                'id' => ['S' => $batchId],
            ],
        ]);
    }

    /**
     * Execute the given Closure within a storage specific transaction.
     *
     * @param  \Closure  $callback
     * @return mixed
     */
    public function transaction(Closure $callback)
    {
        return $callback();
    }

    /**
     * Serialize the given value.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function serialize($value)
    {
        return serialize($value);
    }

    /**
     * Unserialize the given value.
     *
     * @param  string  $serialized
     * @return mixed
     */
    protected function unserialize($serialized)
    {
        return unserialize($serialized);
    }

    /**
     * Convert the given raw batch to a Batch object.
     *
     * @param  object  $batch
     * @return \Illuminate\Bus\Batch
     */
    protected function toBatch($batch)
    {
        return $this->factory->make(
            $this,
            $batch->id,
            $batch->name,
            (int) $batch->total_jobs,
            (int) $batch->pending_jobs,
            (int) $batch->failed_jobs,
            $batch->failed_job_ids,
            $this->unserialize($batch->options) ?? [],
            CarbonImmutable::createFromTimestamp($batch->created_at),
            $batch->cancelled_at ? CarbonImmutable::createFromTimestamp($batch->cancelled_at) : $batch->cancelled_at,
            $batch->finished_at ? CarbonImmutable::createFromTimestamp($batch->finished_at) : $batch->finished_at
        );
    }

    public function getDynamoClient(): DynamoDbClient
    {
        return $this->dynamoDbClient;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function createAwsDynamoTable(): void
    {
        $definition = [
            'TableName' => $this->table,
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'application',
                    'AttributeType' => 'S',
                ],
                [
                    'AttributeName' => 'id',
                    'AttributeType' => 'S',
                ],
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'application',
                    'KeyType' => 'HASH',
                ],
                [
                    'AttributeName' => 'id',
                    'KeyType' => 'RANGE',
                ],
            ],
            'BillingMode' => 'PAY_PER_REQUEST',
        ];
        $this->dynamoDbClient->createTable($definition);

        if ($this->ttl !== null) {
            $this->dynamoDbClient->updateTimeToLive([
                'TableName' => $this->table,
                'TimeToLiveSpecification' => [
                    'AttributeName' => $this->ttlAttribute,
                    'Enabled' => true,
                ],
            ]);
        }
    }

    public function deleteAwsDynamoTable(): void
    {
        $this->dynamoDbClient->deleteTable([
            'TableName' => $this->table,
        ]);
    }
}
