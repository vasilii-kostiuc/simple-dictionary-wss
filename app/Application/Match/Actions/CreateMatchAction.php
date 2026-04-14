<?php

namespace App\Application\Match\Actions;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\Match\MatchParticipant;

class CreateMatchAction
{
    public function __construct(
        private readonly SimpleDictionaryApiClientInterface $apiClient,
    ) {}

    /**
     * @param  MatchParticipant[]  $participants
     */
    public function execute(array $participants, array $matchParams): array
    {
        return $this->apiClient->createMatch(
            array_map(fn (MatchParticipant $p) => $p->toArray(), $participants),
            $matchParams,
        ) ?? [];
    }
}
