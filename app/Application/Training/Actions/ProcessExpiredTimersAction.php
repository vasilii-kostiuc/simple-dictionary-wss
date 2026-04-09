<?php

namespace App\Application\Training\Actions;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\Shared\Contracts\TimerStorageInterface;

class ProcessExpiredTimersAction
{
    public function __construct(
        private readonly TimerStorageInterface $timerStorage,
        private readonly SimpleDictionaryApiClientInterface $apiClient,
    ) {
    }

    public function execute(): void
    {
        $expiredTimers = $this->timerStorage->getExpiredTimers();

        if (empty($expiredTimers)) {
            return;
        }

        foreach ($expiredTimers as $timer) {
            $type = $timer['type'];
            $entityId = $timer['entity_id'];

            $this->apiClient->expire($entityId);
            $this->timerStorage->removeTimer($type, $entityId);
        }
    }
}
