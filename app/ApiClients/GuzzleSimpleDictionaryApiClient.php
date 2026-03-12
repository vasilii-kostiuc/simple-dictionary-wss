<?php

namespace App\ApiClients;

use App\WebSockets\DTO\UserData;
use GuzzleHttp\Exception\GuzzleException;

class GuzzleSimpleDictionaryApiClient implements SimpleDictionaryApiClientInterface
{
    private \GuzzleHttp\Client $client;
    private string $token;

    public function __construct(\GuzzleHttp\Client $client, string $token = '')
    {
        $this->client = $client;
        $this->token = $token;
    }

    public function getUserByToken(string $token): ?UserData
    {
        $response = $this->call('POST', 'auth/token/validate', [
            'json' => ['user_token' => $token]
        ]);

        if (empty($response)) {
            return null;
        }

        info("Profile response: " . json_encode($response));

        return new UserData(
            id: $response['id'],
            name: $response['name'] ?? '',
            email: $response['email'] ?? '',
            avatar: $response['avatar'] ?? null,
        );
    }

    public function expire(string|int $trainingId): array
    {
        return $this->call('POST', "trainings/{$trainingId}/expire", [
            'json' => ['completed_by' => 'timer']
        ]);
    }

    protected function call(string $method, string $uri, array $options = []): array
    {
        info("API Call: {$method} {$uri} with options: " . json_encode($options));
        try {
            $options['headers'] = array_merge(
                $options['headers'] ?? [],
                ['Authorization' => 'Bearer ' . $this->token]
            );

            $response = $this->client->request($method, $uri, $options);

            if ($response->getStatusCode() === 200) {
                $body = json_decode((string)$response->getBody(), true);
                return $body['data'] ?? $body ?? [];
            }
        } catch (GuzzleException $e) {
            info("API Error ({$uri}): " . $e->getMessage());
        }

        return [];
    }
}
