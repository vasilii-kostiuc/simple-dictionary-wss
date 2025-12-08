<?php

namespace App\ApiClients;

use GuzzleHttp\Exception\GuzzleException;

class GuzzleSimpleDictionaryApiClient implements SimpleDictionaryApiClientInterface
{
    private \GuzzleHttp\Client $client;

    public function __construct(\GuzzleHttp\Client $client)
    {
        $this->client = $client;
    }

    public function validateToken(string $token): bool
    {
        try {
            $response = $this->client->get('auth/token/validate', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ]
            ]);
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            return false;
        }

    }

    public function getProfile(string $token): array
    {
        try {
            $response = $this->client->get('profile', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ]
            ]);
            if ($response->getStatusCode() === 200) {
                $body = json_decode((string) $response->getBody(), true);
                return $body['data'];
            }
            return [];
        } catch (GuzzleException $e) {
            return [];
        }
    }


    public function expire(string|int $trainingId): array
    {
        try {
            $response = $this->client->post("trainings/{$trainingId}/expire", ['json' => ['completed_by' => 'timer']]);
            if ($response->getStatusCode() === 200) {
                $body = json_decode((string) $response->getBody(), true);
                return $body['data'];
            }
            return [];
        } catch (GuzzleException $e) {
            return [];
        }
    }

}
