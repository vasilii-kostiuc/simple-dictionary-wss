<?php

namespace App\ApiClients;

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

    public function validateToken(string $token): bool
    {
        $response = $this->call('POST', 'auth/token/validate', [
            'json' => ['user_token' => $token]
        ]);

        return !empty($response);
    }

    public function getProfile(string $token): array
    {
        return $this->call('GET', 'profile');
    }

    public function expire(string|int $trainingId): array
    {
        return $this->call('POST', "trainings/{$trainingId}/expire", [
            'json' => ['completed_by' => 'timer']
        ]);
    }

    protected function call(string $method, string $uri, array $options = []): array
    {
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
