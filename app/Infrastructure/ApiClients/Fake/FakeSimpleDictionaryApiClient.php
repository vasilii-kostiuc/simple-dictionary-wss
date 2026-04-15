<?php

namespace App\Infrastructure\ApiClients\Fake;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\LinkMatch\LinkMatch;
use App\Domain\LinkMatch\LinkMatchStatus;
use App\Domain\Match\MatchParams;
use Illuminate\Support\Facades\Http;

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
        $apiUrl = env('API_BASE_URI', '').'send-to-wss';

        Http::post($apiUrl, [
            'channel' => 'api.training',
            'type' => 'training_completed',
            'data' => [
                'training_id' => $trainingId,
                'completed_at' => now()->toIso8601String(),
            ],
        ]);

        return [];
    }

    public function expireMatch(string|int $matchId): array
    {
        $apiUrl = env('API_BASE_URI', '').'send-to-wss';

        Http::post($apiUrl, [
            'channel' => 'api.match',
            'type' => 'match_completed',
            'data' => [
                'id' => $matchId,
                'completed_at' => now()->toIso8601String(),
            ],
        ]);

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
