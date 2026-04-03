<?php

namespace App\WebSockets\Timers;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\Storage\Timers\TimerStorageInterface;
use Illuminate\Support\Facades\Log;

class ExpiredTimerProcessor
{
    public function __construct(
        private readonly TimerStorageInterface $timerStorage,
        private readonly SimpleDictionaryApiClientInterface $simpleDictionaryApiClient,
    ) {
    }

    public function process(): void
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

            $this->simpleDictionaryApiClient->expire($entityId);
            $this->timerStorage->removeTimer($type, $entityId);
        }
    }
}
