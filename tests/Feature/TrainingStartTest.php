<?php

use App\WebSockets\Enums\ServerEventType;
use Tests\Feature\WebSocketTestCase;
use VasiliiKostiuc\PubSubBroker\Messaging\BrokerFactory;

class TrainingStartTest extends WebSocketTestCase
{
    public function test_api_message_training_start()
    {
        $client = $this->createWebSocketClient();
        $this->authenticateClient($client);
        $this->subscribeClient($client, 'training.121');

        app(BrokerFactory::class)->create()->publish('api.training', json_encode([
            'type' => 'training_started',
            'data' => [
                'training_id' => '121',
                'started_at' => now()->toIso8601String(),
                'completion_type' => \App\Domain\Training\Enums\TrainingCompletionType::Time->value,
                'completion_type_params' => (object) ['duration' => '0.05'],
            ],
        ]));

        try {
            sleep(1);

            $message = $client->receive();

            $payload = json_decode($message->getPayload());
            Log::info('Received message: '.$message->getPayload());
            $this->assertEquals(ServerEventType::TrainingCompleted->value, $payload->type ?? null);
        } catch (\Exception $e) {
            Log::error('Failed to receive message: '.$e->getMessage());
            $this->fail('No message received');
        }

        sleep(1);
    }
}
