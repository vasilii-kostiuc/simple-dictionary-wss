<?php

use Tests\Feature\WebSocketTestCase;

class TrainingStartTest extends WebSocketTestCase
{

    public function test_api_message_training_start()
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client);
        $this->subscribeClient($client, 'trainings.121');

        Log::info('Publishing message via external API endpoint');

        $apiUrl = env('API_BASE_URI', '') . 'send-to-wss';

        try {
            $response = Http::post($apiUrl, [
                'channel' => 'training',
                'type' => 'training_started',
                'training_id' => '121',
                'started_at' => now()->toIso8601String(),
                'completion_type' => \App\WebSockets\Enums\TrainingCompletionType::Time->value,
                'completion_type_params' => (object)['duration' => '0.5'],
            ]);

            $this->assertTrue($response->successful(), 'API request failed with status: ' . $response->status());
            Log::info('API request successful');
        } catch (\Exception $e) {
            $this->markTestSkipped('Cannot connect to API endpoint: ' . $apiUrl . '. Error: ' . $e->getMessage());
        }

        sleep(3);

        try {
            sleep(3);

            $message = $client->receive();

            $payload = json_decode($message->getPayload());
            Log::info('Received message: ' . $message->getPayload());
            $this->assertEquals('training_completed', $payload->type ?? null);
        } catch (\Exception $e) {
            Log::error('Failed to receive message: ' . $e->getMessage());
            $this->fail('No message received');
        }

        sleep(1);
    }
}
