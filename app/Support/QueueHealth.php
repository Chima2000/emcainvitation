<?php

namespace App\Support;

use App\QueueName;
use Illuminate\Queue\Failed\CountableFailedJobProvider;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * A quick look at the background queue for the admin: what is waiting, what failed, and whether a worker is running.
 */
class QueueHealth
{
    /** A job waiting longer than this means no worker is picking jobs up. */
    public const int StuckAfterSeconds = 120;

    /** @var list<string> */
    private const array InlineDrivers = ['sync', 'deferred', 'background', 'null'];

    public function __construct(private FailedJobProviderInterface $failedJobs) {}

    /**
     * @return array{connection: string, runsInline: bool, reachable: bool, queues: list<array{name: string, label: string, waiting: int}>, waiting: int, oldestWaitSeconds: int|null, failed: int, looksStuck: bool, workerCommand: string}
     */
    public function snapshot(): array
    {
        $connectionName = (string) config('queue.default');
        $runsInline = in_array(config("queue.connections.{$connectionName}.driver"), self::InlineDrivers, true);
        $queues = [];
        $oldestCreatedAt = null;
        $reachable = true;

        try {
            $connection = Queue::connection($connectionName);

            foreach (QueueName::cases() as $queue) {
                $waiting = $runsInline ? 0 : (int) $connection->pendingSize($queue->value);
                $createdAt = $runsInline ? null : $connection->creationTimeOfOldestPendingJob($queue->value);

                if ($createdAt !== null) {
                    $oldestCreatedAt = min($oldestCreatedAt ?? (int) $createdAt, (int) $createdAt);
                }

                $queues[] = ['name' => $queue->value, 'label' => $queue->label(), 'waiting' => $waiting];
            }
        } catch (Throwable $exception) {
            report($exception);
            $reachable = false;
        }

        $oldestWaitSeconds = $oldestCreatedAt === null ? null : max(0, now()->getTimestamp() - $oldestCreatedAt);

        return [
            'connection' => $connectionName,
            'runsInline' => $runsInline,
            'reachable' => $reachable,
            'queues' => $queues,
            'waiting' => array_sum(array_column($queues, 'waiting')),
            'oldestWaitSeconds' => $oldestWaitSeconds,
            'failed' => $this->failedCount(),
            'looksStuck' => ! $reachable || ($oldestWaitSeconds ?? 0) > self::StuckAfterSeconds,
            'workerCommand' => 'php artisan queue:work --queue='.QueueName::workerList().' --sleep=1 --tries=3 --max-time=3600',
        ];
    }

    private function failedCount(): int
    {
        if (! $this->failedJobs instanceof CountableFailedJobProvider) {
            return 0;
        }

        try {
            return $this->failedJobs->count();
        } catch (Throwable $exception) {
            report($exception);

            return 0;
        }
    }
}
