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
            'json' => ['user_token' => $token],
        ]);

        if (empty($response)) {
            return null;
        }

        info('Profile response: '.json_encode($response));

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
            'json' => ['completed_by' => 'timer'],
        ]);
    }

    protected function call(string $method, string $uri, array $options = []): array
    {
        info("API Call: {$method} {$uri} with options: ".json_encode($options));
        try {
            $options['headers'] = array_merge(
                $options['headers'] ?? [],
                ['Authorization' => 'Bearer '.$this->token]
            );

            $response = $this->client->request($method, $uri, $options);

            if ($response->getStatusCode() === 200) {
                $body = json_decode((string) $response->getBody(), true);

                return $body['data'] ?? $body ?? [];
            }
        } catch (GuzzleException $e) {
            info("API Error ({$uri}): ".$e->getMessage());
        }

        return [];
    }

    public function createMatch(array $participants, array $matchParams): array
    {
        $matchCreateData = [
            'lang_from_id' => $matchParams['lang_from_id'] ?? 1,//English by default
            'lang_to_id' => $matchParams['lang_to_id'] ?? 2,//Russian by default
            'match_type' => $matchParams['match_type'] ?? MatchType::Time->value,
            'match_type_params' => $matchParams['match_type_params'] ?? ['duration' => '5'],
            'participants' => $participants,
            'match_params' => $matchParams,
        ];


        $response = $this->call('POST', 'matches', [
            'json' => $matchCreateData,
        ]);

        return $response;
    }
}
