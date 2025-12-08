<?php

namespace App\ApiClients\Fake;

use App\ApiClients\SimpleDictionaryApiClientInterface;
use VasiliiKostiuc\LaravelMessagingLibrary\Messaging\MessageBrokerFactory;
use Illuminate\Support\Facades\Http;
class FakeSimpleDictionaryApiClient implements SimpleDictionaryApiClientInterface
{


    public function validateToken(string $token): bool
    {
        return true;
    }

    public function getProfile(string $token): array
    {
        return ['id' => 42];
    }

    public function expire(string|int $trainingId): array
    {
        $apiUrl = env('API_BASE_URI', '') . 'send-to-wss';

        $response = Http::post($apiUrl, [
            'channel' => 'training',
            'type' => 'training_completed',
            'training_id' => '121',
            'completed_at' => now()->toIso8601String(),
        ]);
        return [];
    }
}
