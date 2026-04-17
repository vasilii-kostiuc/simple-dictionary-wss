<?php

namespace App\Application\Training\Actions;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\Shared\Contracts\TimerStorageInterface;
use App\Domain\Shared\Enums\TimerType;

class ProcessExpiredTimersAction
{
    public function __construct(
        private readonly TimerStorageInterface $timerStorage,
        private readonly SimpleDictionaryApiClientInterface $apiClient,
    ) {}

    public function execute(): void
    {
        while ($timer = $this->timerStorage->claimExpiredTimer()) {
            $type = $timer['type'];
            $entityId = $timer['entity_id'];

            match ($type) {
                TimerType::Training->value => $this->apiClient->expire($entityId),
                TimerType::Match->value => $this->apiClient->expireMatch($entityId),
                default => null,
            };

            $this->timerStorage->removeTimer($type, $entityId);
        }
    }
}
