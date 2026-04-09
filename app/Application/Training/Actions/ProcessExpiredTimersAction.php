<?php

namespace App\Application\Training\Actions;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\Shared\Contracts\TimerStorageInterface;
use Illuminate\Support\Facades\Log;

class ProcessExpiredTimersAction
{
    public function __construct(
        private readonly TimerStorageInterface $timerStorage,
        private readonly SimpleDictionaryApiClientInterface $apiClient,
    ) {
    }

    public function execute(): void
    {
        Log::info('Checking for expired training timers');

        $expiredTimers = $this->timerStorage->getExpiredTimers();

        if (empty($expiredTimers)) {
            return;
        }

        Log::info('Found expired timers', ['count' => count($expiredTimers)]);

        foreach ($expiredTimers as $timer) {
            $type = $timer['type'];
            $entityId = $timer['entity_id'];

            Log::info('Completing expired timer', [
                'type' => $type,
                'entity_id' => $entityId,
                'expired_at' => $timer['expires_at']->format('Y-m-d H:i:s'),
            ]);

            $this->apiClient->expire($entityId);
            $this->timerStorage->removeTimer($type, $entityId);
        }
    }
}
