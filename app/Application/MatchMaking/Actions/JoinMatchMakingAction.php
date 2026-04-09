<?php

namespace App\Application\MatchMaking\Actions;

use App\Application\MatchMaking\Events\MatchMakingJoinedEvent;
use App\Application\MatchMaking\Exceptions\MatchMakingException;
use App\Domain\MatchMaking\Contracts\MatchMakingQueueInterface;
use App\Domain\Shared\DTO\ConnectedUser;
use App\Domain\MatchMaking\Enums\MatchType;

class JoinMatchMakingAction
{
    public function __construct(
        private readonly MatchMakingQueueInterface $matchMakingQueue,
    ) {
    }

    /**
     * @return array{matchType: MatchType, matchParams: array}
     * @throws MatchMakingException
     */
    public function execute(ConnectedUser $user, array $data): array
    {
        $matchType = MatchType::tryFrom($data['match_type'] ?? MatchType::Steps->value);

        if ($matchType === null) {
            throw new MatchMakingException('invalid_match_type');
        }

        $matchParams = ['match_type' => $matchType->value];
        $matchParams = array_merge($matchParams, $data['match_params'] ?? []);

        $this->matchMakingQueue->add($user, $matchParams);

        event(new MatchMakingJoinedEvent($user->getIdentifier(), $matchParams));

        return [
            'matchType' => $matchType,
            'matchParams' => $matchParams,
        ];
    }
}
