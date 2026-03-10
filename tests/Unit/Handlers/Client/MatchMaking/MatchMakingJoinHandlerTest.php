<?php

namespace Tests\Unit\Handlers\Client\MatchMaking;

use App\WebSockets\Enums\MatchType;
use App\WebSockets\Events\MatchMaking\MatchMakingJoinedEvent;
use App\WebSockets\Handlers\Client\MatchMaking\MatchMakingJoinHandler;
use App\WebSockets\Messages\MatchMaking\MatchMakingJoinSuccessMessage;
use App\WebSockets\Storage\Clients\ClientsStorageInterface;
use App\WebSockets\Storage\MatchMaking\MatchMakingQueueInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Tests\TestCase;

class MatchMakingJoinHandlerTest extends TestCase
{
    private ClientsStorageInterface $clientsStorage;
    private MatchMakingQueueInterface $matchMakingQueue;
    private Dispatcher $dispatcher;
    private ConnectionInterface $connection;
    private MatchMakingJoinHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientsStorage = Mockery::mock(ClientsStorageInterface::class);
        $this->matchMakingQueue = Mockery::mock(MatchMakingQueueInterface::class);
        $this->dispatcher = Mockery::mock(Dispatcher::class);
        $this->connection = Mockery::mock(ConnectionInterface::class);

        $this->handler = new MatchMakingJoinHandler(
            $this->clientsStorage,
            $this->matchMakingQueue,
            $this->dispatcher
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_adds_user_to_matchmaking_queue(): void
    {
        $userId = 'user-123';
        $payload = json_encode(['match_type' => 'steps']);

        $msg = $this->makeMessage($payload);

        $this->clientsStorage->shouldReceive('getUserIdByConnection')
            ->once()
            ->with($this->connection)
            ->andReturn($userId);

        $this->matchMakingQueue->shouldReceive('add')
            ->once()
            ->with($userId, ['match_type' => 'steps']);

        $this->dispatcher->shouldReceive('dispatch')
            ->once();

        $this->connection->shouldReceive('send')->once();

        $this->handler->handle($this->connection, $msg);
    }

    public function test_dispatches_matchmaking_joined_event(): void
    {
        $userId = 'user-456';
        $payload = json_encode(['match_type' => 'time']);

        $msg = $this->makeMessage($payload);

        $this->clientsStorage->shouldReceive('getUserIdByConnection')
            ->once()
            ->andReturn($userId);

        $this->matchMakingQueue->shouldReceive('add')->once();

        $this->dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(function ($event) use ($userId) {
                return $event instanceof MatchMakingJoinedEvent
                    && $event->userId === $userId
                    && $event->matchParams['match_type'] === 'time';
            }));

        $this->connection->shouldReceive('send')->once();

        $this->handler->handle($this->connection, $msg);
    }

    public function test_sends_success_message_to_client(): void
    {
        $userId = 'user-789';
        $payload = json_encode(['match_type' => 'steps']);

        $msg = $this->makeMessage($payload);

        $this->clientsStorage->shouldReceive('getUserIdByConnection')
            ->once()
            ->andReturn($userId);

        $this->matchMakingQueue->shouldReceive('add')->once();
        $this->dispatcher->shouldReceive('dispatch')->once();

        $this->connection->shouldReceive('send')
            ->once()
            ->with(Mockery::on(function (string $sent) {
                $data = json_decode($sent, true);
                return $data['type'] === 'matchmaking_join_success';
            }));

        $this->handler->handle($this->connection, $msg);
    }

    public function test_uses_default_match_type_when_not_provided(): void
    {
        $userId = 'user-000';
        $payload = json_encode([]);

        $msg = $this->makeMessage($payload);

        $this->clientsStorage->shouldReceive('getUserIdByConnection')
            ->once()
            ->andReturn($userId);

        $this->matchMakingQueue->shouldReceive('add')
            ->once()
            ->with($userId, Mockery::on(function (array $params) {
                return $params['match_type'] === MatchType::Steps->value;
            }));

        $this->dispatcher->shouldReceive('dispatch')->once();
        $this->connection->shouldReceive('send')->once();

        $this->handler->handle($this->connection, $msg);
    }

    public function test_merges_extra_match_params(): void
    {
        $userId = 'user-111';
        $payload = json_encode([
            'match_type' => 'time',
            'match_params' => ['difficulty' => 'hard', 'language' => 'en'],
        ]);

        $msg = $this->makeMessage($payload);

        $this->clientsStorage->shouldReceive('getUserIdByConnection')
            ->once()
            ->andReturn($userId);

        $this->matchMakingQueue->shouldReceive('add')
            ->once()
            ->with($userId, Mockery::on(function (array $params) {
                return $params['match_type'] === 'time'
                    && $params['difficulty'] === 'hard'
                    && $params['language'] === 'en';
            }));

        $this->dispatcher->shouldReceive('dispatch')->once();
        $this->connection->shouldReceive('send')->once();

        $this->handler->handle($this->connection, $msg);
    }

    private function makeMessage(string $payload): MessageInterface
    {
        $msg = Mockery::mock(MessageInterface::class);
        $msg->shouldReceive('getPayload')->andReturn($payload);
        return $msg;
    }
}
