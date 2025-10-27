<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Http;

Broadcast::channel('training.{id}', function (int $id, \Psr\Http\Client\ClientInterface $client) {
    $token = request()->bearerToken();
    $response = $client->get('/api/user', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ],
    ]);

    if($response->getStatusCode() !== 200) {
        return false;
    }

    $user = json_decode($response->getBody(), true);

    return true;
});
