<?php

namespace App\Infrastructure\ApiClients\Fake;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\LinkMatch\LinkMatch;
use App\Domain\LinkMatch\LinkMatchStatus;
use App\Domain\Match\MatchParams;
use VasiliiKostiuc\PubSubBroker\Messaging\BrokerFactory;

class FakeSimpleDictionaryApiClient implements SimpleDictionaryApiClientInterface
{
    public function validateToken(string $token): array
    {
        $id = crc32($token);

        return [
            'id' => $id,
            'name' => 'John Doe '.$id,
            'email' => 'john.doe'.$id.'@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ];
    }

    public function expire(string|int $trainingId): array
    {
        app(BrokerFactory::class)->create()->publish('api.training', json_encode([
            'type' => 'training_completed',
            'data' => [
                'training_id' => $trainingId,
                'completed_at' => now()->toIso8601String(),
            ],
        ]));

        return [];
    }

    public function expireMatch(string|int $matchId): array
    {
        app(BrokerFactory::class)->create()->publish('api.match', json_encode([
            'type' => 'match_completed',
            'data' => [
                'id' => $matchId,
                'completed_at' => now()->toIso8601String(),
            ],
        ]));

        return [];
    }

    public function createMatch(array $participants, MatchParams $matchParams): array
    {
        return [
            'status' => 'success',
            'message' => 'Match created successfully',
            'data' => [
                'match_id' => rand(1000, 9999),
                'participants' => $participants,
                'match_params' => $matchParams->toArray(),
            ],
        ];
    }

    public function getLinkMatch(string $token): ?LinkMatch
    {
        return new LinkMatch(
            id: $token,
            token: $token,
            participantsLimit: 2,
            status: LinkMatchStatus::Pending,
            payload: [],
            matchId: null,
        );
    }
}
