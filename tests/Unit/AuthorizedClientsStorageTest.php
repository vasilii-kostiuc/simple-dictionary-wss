<?php

namespace Tests\Unit;

use App\WebSockets\Storage\Clients\AuthorizedClientsStorage;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;

class AuthorizedClientsStorageTest extends TestCase
{
    private AuthorizedClientsStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = new AuthorizedClientsStorage();
    }

    private function createMockConnection(): ConnectionInterface
    {
        return $this->createMock(ConnectionInterface::class);
    }

    public function test_add_client_creates_new_user_entry(): void
    {
        $userId = 123;
        $connection = $this->createMockConnection();

        $this->storage->add($userId, $connection);

        $this->assertTrue($this->storage->has($userId));
        $this->assertCount(1, $this->storage->get($userId));
    }

    public function test_add_multiple_connections_for_same_user(): void
    {
        $userId = 123;
        $connection1 = $this->createMockConnection();
        $connection2 = $this->createMockConnection();

        $this->storage->add($userId, $connection1);
        $this->storage->add($userId, $connection2);

        $this->assertTrue($this->storage->has($userId));
        $this->assertCount(2, $this->storage->get($userId));
    }

    public function test_add_same_connection_twice_does_not_duplicate(): void
    {
        $userId = 123;
        $connection = $this->createMockConnection();

        $this->storage->add($userId, $connection);
        $this->storage->add($userId, $connection);

        $this->assertCount(1, $this->storage->get($userId));
    }

    public function test_add_connections_for_different_users(): void
    {
        $userId1 = 123;
        $userId2 = 456;
        $connection1 = $this->createMockConnection();
        $connection2 = $this->createMockConnection();

        $this->storage->add($userId1, $connection1);
        $this->storage->add($userId2, $connection2);

        $this->assertTrue($this->storage->has($userId1));
        $this->assertTrue($this->storage->has($userId2));
        $this->assertCount(1, $this->storage->get($userId1));
        $this->assertCount(1, $this->storage->get($userId2));
    }

    public function test_get_returns_empty_array_for_nonexistent_user(): void
    {
        $result = $this->storage->get(999);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_returns_connections_for_user(): void
    {
        $userId = 123;
        $connection1 = $this->createMockConnection();
        $connection2 = $this->createMockConnection();

        $this->storage->add($userId, $connection1);
        $this->storage->add($userId, $connection2);

        $connections = $this->storage->get($userId);

        $this->assertCount(2, $connections);
        $this->assertContains($connection1, $connections);
        $this->assertContains($connection2, $connections);
    }

    public function test_remove_connection_from_user(): void
    {
        $userId = 123;
        $connection1 = $this->createMockConnection();
        $connection2 = $this->createMockConnection();

        $this->storage->add($userId, $connection1);
        $this->storage->add($userId, $connection2);

        $this->storage->remove($userId, $connection1);

        $this->assertTrue($this->storage->has($userId));
        $this->assertCount(1, $this->storage->get($userId));
        $this->assertContains($connection2, $this->storage->get($userId));
        $this->assertNotContains($connection1, $this->storage->get($userId));
    }

    public function test_remove_last_connection_removes_user_entry(): void
    {
        $userId = 123;
        $connection = $this->createMockConnection();

        $this->storage->add($userId, $connection);
        $this->storage->remove($userId, $connection);

        $this->assertFalse($this->storage->has($userId));
        $this->assertEmpty($this->storage->get($userId));
    }

    public function test_remove_nonexistent_connection_does_nothing(): void
    {
        $userId = 123;
        $connection1 = $this->createMockConnection();
        $connection2 = $this->createMockConnection();

        $this->storage->add($userId, $connection1);
        $this->storage->remove($userId, $connection2);

        $this->assertTrue($this->storage->has($userId));
        $this->assertCount(1, $this->storage->get($userId));
    }

    public function test_remove_from_nonexistent_user_does_nothing(): void
    {
        $connection = $this->createMockConnection();

        $this->storage->remove(999, $connection);

        $this->assertFalse($this->storage->has(999));
    }

    public function test_has_returns_false_for_nonexistent_user(): void
    {
        $this->assertFalse($this->storage->has(999));
    }

    public function test_has_returns_true_for_existing_user(): void
    {
        $userId = 123;
        $connection = $this->createMockConnection();

        $this->storage->add($userId, $connection);

        $this->assertTrue($this->storage->has($userId));
    }

    public function test_has_returns_false_after_removing_last_connection(): void
    {
        $userId = 123;
        $connection = $this->createMockConnection();

        $this->storage->add($userId, $connection);
        $this->storage->remove($userId, $connection);

        $this->assertFalse($this->storage->has($userId));
    }

    public function test_all_returns_empty_array_initially(): void
    {
        $result = $this->storage->all();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_all_returns_all_users_and_connections(): void
    {
        $userId1 = 123;
        $userId2 = 456;
        $connection1 = $this->createMockConnection();
        $connection2 = $this->createMockConnection();
        $connection3 = $this->createMockConnection();

        $this->storage->add($userId1, $connection1);
        $this->storage->add($userId1, $connection2);
        $this->storage->add($userId2, $connection3);

        $all = $this->storage->all();

        $this->assertCount(2, $all);
        $this->assertArrayHasKey($userId1, $all);
        $this->assertArrayHasKey($userId2, $all);
        $this->assertCount(2, $all[$userId1]);
        $this->assertCount(1, $all[$userId2]);
    }

    public function test_get_user_id_by_connection_returns_null_for_unknown_connection(): void
    {
        $connection = $this->createMockConnection();

        $result = $this->storage->getUserIdByConnection($connection);

        $this->assertNull($result);
    }

    public function test_get_user_id_by_connection_returns_correct_user_id(): void
    {
        $userId = 123;
        $connection = $this->createMockConnection();

        $this->storage->add($userId, $connection);

        $result = $this->storage->getUserIdByConnection($connection);

        $this->assertEquals($userId, $result);
    }

    public function test_get_user_id_by_connection_with_multiple_connections(): void
    {
        $userId1 = 123;
        $userId2 = 456;
        $connection1 = $this->createMockConnection();
        $connection2 = $this->createMockConnection();

        $this->storage->add($userId1, $connection1);
        $this->storage->add($userId2, $connection2);

        $this->assertEquals($userId1, $this->storage->getUserIdByConnection($connection1));
        $this->assertEquals($userId2, $this->storage->getUserIdByConnection($connection2));
    }

    public function test_get_user_id_by_connection_after_removal_returns_null(): void
    {
        $userId = 123;
        $connection = $this->createMockConnection();

        $this->storage->add($userId, $connection);
        $this->storage->remove($userId, $connection);

        $result = $this->storage->getUserIdByConnection($connection);

        $this->assertNull($result);
    }

    public function test_works_with_string_user_ids(): void
    {
        $userId = 'user_abc_123';
        $connection = $this->createMockConnection();

        $this->storage->add($userId, $connection);

        $this->assertTrue($this->storage->has($userId));
        $this->assertEquals($userId, $this->storage->getUserIdByConnection($connection));
        $this->assertCount(1, $this->storage->get($userId));
    }

    public function test_complex_scenario_multiple_users_and_operations(): void
    {
        $user1 = 100;
        $user2 = 200;
        $user3 = 300;

        $conn1User1 = $this->createMockConnection();
        $conn2User1 = $this->createMockConnection();
        $conn1User2 = $this->createMockConnection();
        $conn1User3 = $this->createMockConnection();

        // Add connections
        $this->storage->add($user1, $conn1User1);
        $this->storage->add($user1, $conn2User1);
        $this->storage->add($user2, $conn1User2);
        $this->storage->add($user3, $conn1User3);

        // Verify state
        $this->assertCount(3, $this->storage->all());
        $this->assertCount(2, $this->storage->get($user1));
        $this->assertCount(1, $this->storage->get($user2));
        $this->assertCount(1, $this->storage->get($user3));

        // Remove one connection from user1
        $this->storage->remove($user1, $conn1User1);
        $this->assertCount(1, $this->storage->get($user1));
        $this->assertTrue($this->storage->has($user1));

        // Remove all connections from user2
        $this->storage->remove($user2, $conn1User2);
        $this->assertFalse($this->storage->has($user2));

        // Verify final state
        $this->assertCount(2, $this->storage->all());
        $this->assertEquals($user1, $this->storage->getUserIdByConnection($conn2User1));
        $this->assertEquals($user3, $this->storage->getUserIdByConnection($conn1User3));
        $this->assertNull($this->storage->getUserIdByConnection($conn1User2));
    }
}
