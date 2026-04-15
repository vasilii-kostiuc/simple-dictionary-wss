<?php

namespace App\Application\Match\Actions;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\Match\MatchParams;
use App\Domain\Match\MatchParticipant;

class CreateMatchAction
{
    public function __construct(
        private readonly SimpleDictionaryApiClientInterface $apiClient,
    ) {
    }

    /**
     * @param  MatchParticipant[]  $participants
     */
    public function execute(array $participants, MatchParams $matchParams): array
    {
        info('Creating match', [
            'participants' => array_map(fn ($p) => $p->toArray(), $participants),
            'match_params' => $matchParams->toArray(),
        ]);
        $match = $this->apiClient->createMatch(
            array_map(fn (MatchParticipant $p) => $p->toArray(), $participants),
            $matchParams,
        ) ?? [];

        info('Match created', ['match' => $match]);

        return $match;
    }
}
