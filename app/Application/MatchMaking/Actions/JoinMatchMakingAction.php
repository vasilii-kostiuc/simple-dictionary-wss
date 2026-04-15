<?php

namespace App\Application\MatchMaking\Actions;

use App\Application\MatchMaking\Events\MatchMakingJoinedEvent;
use App\Application\MatchMaking\Exceptions\MatchMakingException;
use App\Domain\Match\MatchParams;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Domain\MatchMaking\Enums\MatchType;
use App\Domain\Shared\Identity\ClientIdentity;

class JoinMatchMakingAction
{
    public function __construct(
        private readonly MatchMakingQueueInterface $matchMakingQueue,
    ) {
    }

    /**
     * @return array{matchType: MatchType, matchParams: MatchParams}
     *
     * @throws MatchMakingException
     */
    public function execute(ClientIdentity $identity, array $data): array
    {
        $matchType = MatchType::tryFrom($data['match_type'] ?? MatchType::Steps->value);

        if ($matchType === null) {
            throw new MatchMakingException('invalid_match_type');
        }

        $matchParams = new MatchParams(
            matchType: $matchType,
            languageFromId: $data['language_from_id'] ?? 2,
            languageToId: $data['language_to_id'] ?? 1,
            matchTypeParams: $data['match_params'] ?? [],
        );

        $this->matchMakingQueue->add($identity, $matchParams);

        event(new MatchMakingJoinedEvent($identity->getIdentifier(), $matchParams));

        return [
            'matchType' => $matchType,
            'matchParams' => $matchParams,
        ];
    }
}
