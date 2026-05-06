<?php

namespace App\Support;

use App\Models\Queue;
use Illuminate\Support\Facades\DB;

class QueueStatusPromoter
{
    public function promoteStaleCalledQueues(): int
    {
        $today = now()->toDateString();
        $threshold = now()->subMinute();
        $promoted = 0;

        Queue::query()
            ->whereDate('queue_date', $today)
            ->where('status', 'called')
            ->whereNotNull('called_at')
            ->where('called_at', '<=', $threshold)
            ->orderBy('called_at')
            ->cursor()
            ->each(function (Queue $queue) use (&$promoted): void {
                DB::transaction(function () use ($queue): void {
                    $now = now();

                    $activeCall = $queue->calls()
                        ->whereIn('status', ['called', 'serving'])
                        ->latest('called_at')
                        ->first();

                    $queue->update([
                        'status' => 'serving',
                        'started_at' => $now,
                    ]);

                    if ($activeCall) {
                        $activeCall->update([
                            'status' => 'serving',
                            'started_at' => $now,
                        ]);
                    }
                });

                $promoted++;
            });

        return $promoted;
    }

    public function promoteStaleServingQueues(): int
    {
        $today = now()->toDateString();
        $threshold = now()->subMinutes(3);
        $promoted = 0;

        Queue::query()
            ->whereDate('queue_date', $today)
            ->where('status', 'serving')
            ->whereNotNull('started_at')
            ->where('started_at', '<=', $threshold)
            ->orderBy('started_at')
            ->cursor()
            ->each(function (Queue $queue) use (&$promoted): void {
                DB::transaction(function () use ($queue): void {
                    $now = now();

                    $activeCall = $queue->calls()
                        ->whereIn('status', ['called', 'serving'])
                        ->latest('called_at')
                        ->first();

                    $queue->update([
                        'status' => 'completed',
                        'completed_at' => $now,
                    ]);

                    if ($activeCall) {
                        $activeCall->update([
                            'status' => 'completed',
                            'finished_at' => $now,
                        ]);
                    }
                });

                $promoted++;
            });

        return $promoted;
    }

    public function promoteQueueLifecycle(): void
    {
        $this->promoteStaleServingQueues();
        $this->promoteStaleCalledQueues();
    }
}
