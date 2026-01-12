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
        try {
            $response = $this->client->post('auth/token/validate', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
                'json' => ['user_token' => $token]
            ]);
            info('-------validate token response---');
            info($response->getStatusCode());
            info($response->getBody());
            info('-------validate token response end---');
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            info($e->getMessage());
            return false;
        }
    }

    public function getProfile(string $token): array
    {
        try {
            $response = $this->client->get('profile', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ]
            ]);
            if ($response->getStatusCode() === 200) {
                $body = json_decode((string)$response->getBody(), true);
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
            $response = $this->client->post("trainings/{$trainingId}/expire", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
                'json' => ['completed_by' => 'timer']
            ]);
            if ($response->getStatusCode() === 200) {
                $body = json_decode((string)$response->getBody(), true);
                return $body['data'];
            }
            return [];
        } catch (GuzzleException $e) {
            return [];
        }
    }

}
