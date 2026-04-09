<?php

namespace App\Infrastructure\ApiClients;

use App\Application\Contracts\SimpleDictionaryApiClientInterface;
use App\Domain\Shared\DTO\ConnectedUser;
use App\Domain\MatchMaking\Enums\MatchType;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class GuzzleSimpleDictionaryApiClient implements SimpleDictionaryApiClientInterface
{
    private \GuzzleHttp\Client $client;

    private string $token;

    public function __construct(\GuzzleHttp\Client $client, string $token = '')
    {
        $this->client = $client;
        $this->token = $token;
    }

    public function getUserByToken(string $token): ?ConnectedUser
    {
        $response = $this->call('POST', 'auth/token/validate', [
            'json' => ['user_token' => $token],
        ]);

        if (empty($response)) {
            return null;
        }

        return new ConnectedUser(
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

    public function expireMatch(string|int $matchId): array
    {
        return $this->call('POST', "matches/{$matchId}/expire", [
            'json' => ['completed_by' => 'timer'],
        ]);
    }

    protected function call(string $method, string $uri, array $options = []): array
    {
        try {
            $options['headers'] = array_merge(
                $options['headers'] ?? [],
                ['Authorization' => 'Bearer '.$this->token]
            );

            $response = $this->client->request($method, $uri, $options);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {

                $body = json_decode((string) $response->getBody(), true);

                return $body['data'] ?? $body ?? [];
            }
        } catch (GuzzleException $e) {
            Log::warning('External API request failed', [
                'method' => $method,
                'uri' => $uri,
                'message' => $e->getMessage(),
            ]);
        }

        return [];
    }

    public function createMatch(array $participants, array $matchParams): array
    {
        $matchCreateData = [
            'language_from_id' => $matchParams['language_from_id'] ?? 2, // English by default
            'language_to_id' => $matchParams['language_to_id'] ?? 1, // Russian by default
            'match_type' => $matchParams['match_type'] ?? MatchType::Time->value,
            'match_type_params' => $matchParams,
            'participants' => $participants,
            'match_params' => $matchParams,
        ];

        $response = $this->call('POST', 'matches', ['json' => $matchCreateData]);
        return $response;
    }
}
