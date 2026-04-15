<?php

namespace Tests\Unit;

use App\Domain\Match\MatchParams;
use App\Domain\MatchMaking\Enums\MatchType;
use App\Domain\MatchMaking\QueueEntry;
use App\Domain\Shared\Identity\ClientIdentity;
use App\Infrastructure\MatchMaking\RedisMatchMakingQueue;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RedisMatchMakingQueueTest extends TestCase
{
    private RedisMatchMakingQueue $queue;

    private MatchParams $defaultMatchParams;

    private ClientIdentity $user1;

    private ClientIdentity $user2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queue = new RedisMatchMakingQueue;

        $this->defaultMatchParams = new MatchParams(MatchType::Steps, 2, 1, []);

        $this->user1 = new ClientIdentity(
            id: 1,
            name: 'User One',
            email: 'user1@example.com',
            avatar: null,
        );

        $this->user2 = new ClientIdentity(
            id: 2,
            name: 'User Two',
            email: 'user2@example.com',
            avatar: null,
        );

        // Clean up Redis before each test
        $keys = Redis::keys('matchmaking:*');
        if (! empty($keys)) {
            Redis::del($keys);
        }
    }

    protected function tearDown(): void
    {
        $keys = Redis::keys('matchmaking:*');
        if (! empty($keys)) {
            Redis::del($keys);
        }
        parent::tearDown();
    }

    public function test_add_user_to_queue(): void
    {
        $this->queue->add($this->user1, $this->defaultMatchParams);

        $this->assertTrue($this->queue->isUserInQueue($this->user1->id));
        $this->assertEquals(1, $this->queue->count($this->defaultMatchParams));
    }

    public function test_add_multiple_users_to_queue(): void
    {
        $this->queue->add($this->user1, $this->defaultMatchParams);
        $this->queue->add($this->user2, $this->defaultMatchParams);

        $this->assertEquals(2, $this->queue->count($this->defaultMatchParams));
    }

    public function test_add_user_removes_existing_entry_before_adding(): void
    {
        $this->queue->add($this->user1, $this->defaultMatchParams);
        $this->queue->add($this->user1, $this->defaultMatchParams);

        $this->assertEquals(1, $this->queue->count($this->defaultMatchParams));
    }

    public function test_remove_user_from_queue(): void
    {
        $this->queue->add($this->user1, $this->defaultMatchParams);
        $this->queue->remove($this->user1->id);

        $this->assertFalse($this->queue->isUserInQueue($this->user1->id));
        $this->assertEquals(0, $this->queue->count($this->defaultMatchParams));
    }

    public function test_remove_nonexistent_user_does_not_throw(): void
    {
        $this->queue->remove(99999);

        $this->assertFalse($this->queue->isUserInQueue(99999));
    }

    public function test_all_returns_users_in_queue(): void
    {
        $this->queue->add($this->user1, $this->defaultMatchParams);
        $this->queue->add($this->user2, $this->defaultMatchParams);

        $result = $this->queue->all($this->defaultMatchParams);

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(QueueEntry::class, $result);
        $userIds = array_map(fn (QueueEntry $e) => $e->identity->id, $result);
        $this->assertContains($this->user1->id, $userIds);
        $this->assertContains($this->user2->id, $userIds);
    }

    public function test_all_returns_empty_array_when_queue_is_empty(): void
    {
        $result = $this->queue->all($this->defaultMatchParams);

        $this->assertEmpty($result);
    }

    public function test_all_queues_returns_users_from_all_queues(): void
    {
        $params1 = new MatchParams(MatchType::Steps, 2, 1, []);
        $params2 = new MatchParams(MatchType::Time, 2, 1, []);

        $this->queue->add($this->user1, $params1);
        $this->queue->add($this->user2, $params2);

        $result = $this->queue->allQueues();

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(QueueEntry::class, $result);
        $userIds = array_map(fn (QueueEntry $e) => $e->identity->id, $result);
        $this->assertContains($this->user1->id, $userIds);
        $this->assertContains($this->user2->id, $userIds);
    }

    public function test_find_match_returns_opponent_id(): void
    {
        $this->queue->add($this->user1, $this->defaultMatchParams);
        $this->queue->add($this->user2, $this->defaultMatchParams);

        $opponentId = $this->queue->findMatch($this->user1->id, $this->defaultMatchParams);

        $this->assertEquals($this->user2->id, $opponentId);
    }

    public function test_find_match_removes_both_users_from_queue(): void
    {
        $this->queue->add($this->user1, $this->defaultMatchParams);
        $this->queue->add($this->user2, $this->defaultMatchParams);

        $this->queue->findMatch($this->user1->id, $this->defaultMatchParams);

        $this->assertFalse($this->queue->isUserInQueue($this->user1->id));
        $this->assertFalse($this->queue->isUserInQueue($this->user2->id));
    }

    public function test_find_match_returns_null_when_no_opponent(): void
    {
        $this->queue->add($this->user1, $this->defaultMatchParams);

        $opponentId = $this->queue->findMatch($this->user1->id, $this->defaultMatchParams);

        $this->assertNull($opponentId);
    }

    public function test_clear_removes_all_users_from_queue(): void
    {
        $this->queue->add($this->user1, $this->defaultMatchParams);
        $this->queue->add($this->user2, $this->defaultMatchParams);

        $this->queue->clear($this->defaultMatchParams);

        $this->assertEquals(0, $this->queue->count($this->defaultMatchParams));
        $this->assertFalse($this->queue->isUserInQueue($this->user1->id));
        $this->assertFalse($this->queue->isUserInQueue($this->user2->id));
    }

    public function test_count_returns_correct_number(): void
    {
        $this->assertEquals(0, $this->queue->count($this->defaultMatchParams));

        $this->queue->add($this->user1, $this->defaultMatchParams);
        $this->assertEquals(1, $this->queue->count($this->defaultMatchParams));

        $this->queue->add($this->user2, $this->defaultMatchParams);
        $this->assertEquals(2, $this->queue->count($this->defaultMatchParams));
    }

    public function test_is_user_in_queue_returns_true_when_user_in_queue(): void
    {
        $this->queue->add($this->user1, $this->defaultMatchParams);

        $this->assertTrue($this->queue->isUserInQueue($this->user1->id));
    }

    public function test_is_user_in_queue_returns_false_when_user_not_in_queue(): void
    {
        $this->assertFalse($this->queue->isUserInQueue($this->user1->id));
    }

    public function test_extract_returns_user_data_and_removes_from_queue(): void
    {
        $this->queue->add($this->user1, $this->defaultMatchParams);

        $extracted = $this->queue->extract($this->user1->id);

        $this->assertNotNull($extracted);
        $this->assertInstanceOf(QueueEntry::class, $extracted);
        $this->assertEquals($this->user1->id, $extracted->identity->id);
        $this->assertEquals($this->user1->name, $extracted->identity->name);
        $this->assertEquals($this->user1->email, $extracted->identity->email);
        $this->assertEquals($this->defaultMatchParams, $extracted->matchParams);
        $this->assertFalse($this->queue->isUserInQueue($this->user1->id));
    }

    public function test_extract_returns_null_when_user_not_in_queue(): void
    {
        $extracted = $this->queue->extract(99999);

        $this->assertNull($extracted);
    }

    public function test_different_match_params_create_separate_queues(): void
    {
        $params1 = new MatchParams(MatchType::Steps, 2, 1, []);
        $params2 = new MatchParams(MatchType::Time, 2, 1, []);

        $this->queue->add($this->user1, $params1);
        $this->queue->add($this->user2, $params2);

        $this->assertEquals(1, $this->queue->count($params1));
        $this->assertEquals(1, $this->queue->count($params2));
    }
}
