<?php

namespace App\ApiClients\Fake;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use App\WebSockets\DTO\UserData;
use Illuminate\Support\Facades\Http;

class FakeSimpleDictionaryApiClient implements SimpleDictionaryApiClientInterface
{
    public function getUserByToken(string $token): ?UserData
    {
        $id = crc32($token);

        return new UserData(
            id: $id,
            name: 'John Doe '.$id,
            email: 'john.doe'.$id.'@example.com',
            avatar: 'https://example.com/avatar.jpg',
        );
    }

    public function expire(string|int $trainingId): array
    {
        $apiUrl = env('API_BASE_URI', '').'send-to-wss';

        Http::post($apiUrl, [
            'channel' => 'training',
            'type' => 'training_completed',
            'data' => [
                'training_id' => '121',
                'completed_at' => now()->toIso8601String(),
            ],
        ]);

        return [];
    }
}
