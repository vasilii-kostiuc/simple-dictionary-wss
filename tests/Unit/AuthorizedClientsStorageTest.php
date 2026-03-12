<?php

namespace Tests\Unit;

use App\WebSockets\DTO\UserData;
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

    private function createMockConnection(int $resourceId = 1): ConnectionInterface
    {
        $conn = $this->createMock(ConnectionInterface::class);
        $conn->resourceId = $resourceId;
        return $conn;
    }

    private function makeUserData(int $id = 123): UserData
    {
        return new UserData(
            id: $id,
            name: 'User ' . $id,
            email: 'user' . $id . '@example.com',
            avatar: null,
        );
    }

    public function test_add_client_stores_user_data(): void
    {
        $conn = $this->createMockConnection(1);
        $userData = $this->makeUserData(123);

        $this->storage->add($conn, $userData);

        $this->assertEquals(123, $this->storage->getUserIdByConnection($conn));
        $this->assertSame($userData, $this->storage->getUserData($conn));
    }

    public function test_add_overwrites_existing_connection(): void
    {
        $conn = $this->createMockConnection(1);
        $userData1 = $this->makeUserData(123);
        $userData2 = $this->makeUserData(456);

        $this->storage->add($conn, $userData1);
        $this->storage->add($conn, $userData2);

        $this->assertEquals(456, $this->storage->getUserIdByConnection($conn));
    }

    public function test_add_different_connections_for_different_users(): void
    {
        $conn1 = $this->createMockConnection(1);
        $conn2 = $this->createMockConnection(2);
        $userData1 = $this->makeUserData(123);
        $userData2 = $this->makeUserData(456);

        $this->storage->add($conn1, $userData1);
        $this->storage->add($conn2, $userData2);

        $this->assertEquals(123, $this->storage->getUserIdByConnection($conn1));
        $this->assertEquals(456, $this->storage->getUserIdByConnection($conn2));
    }

    public function test_get_user_id_by_connection_returns_null_for_unknown(): void
    {
        $conn = $this->createMockConnection(99);

        $this->assertNull($this->storage->getUserIdByConnection($conn));
    }

    public function test_get_user_data_returns_null_for_unknown(): void
    {
        $conn = $this->createMockConnection(99);

        $this->assertNull($this->storage->getUserData($conn));
    }

    public function test_get_connection_by_user_id_returns_connection(): void
    {
        $conn = $this->createMockConnection(1);
        $userData = $this->makeUserData(123);

        $this->storage->add($conn, $userData);

        $this->assertSame($conn, $this->storage->getConnectionByUserId(123));
    }

    public function test_get_connection_by_user_id_returns_null_for_unknown(): void
    {
        $this->assertNull($this->storage->getConnectionByUserId(999));
    }

    public function test_remove_deletes_connection(): void
    {
        $conn = $this->createMockConnection(1);
        $userData = $this->makeUserData(123);

        $this->storage->add($conn, $userData);
        $this->storage->remove(123, $conn);

        $this->assertNull($this->storage->getUserIdByConnection($conn));
        $this->assertNull($this->storage->getUserData($conn));
    }

    public function test_remove_nonexistent_connection_does_nothing(): void
    {
        $conn = $this->createMockConnection(99);

        $this->storage->remove(999, $conn);

        $this->assertNull($this->storage->getUserIdByConnection($conn));
    }

    public function test_get_user_data_returns_correct_user_data(): void
    {
        $conn = $this->createMockConnection(1);
        $userData = new UserData(id: 42, name: 'Test', email: 'test@test.com', avatar: 'http://example.com/img.jpg');

        $this->storage->add($conn, $userData);

        $result = $this->storage->getUserData($conn);
        $this->assertEquals(42, $result->id);
        $this->assertEquals('Test', $result->name);
        $this->assertEquals('test@test.com', $result->email);
        $this->assertEquals('http://example.com/img.jpg', $result->avatar);
    }

    public function test_complex_scenario(): void
    {
        $conn1 = $this->createMockConnection(1);
        $conn2 = $this->createMockConnection(2);
        $conn3 = $this->createMockConnection(3);
        $userData1 = $this->makeUserData(100);
        $userData2 = $this->makeUserData(200);
        $userData3 = $this->makeUserData(300);

        $this->storage->add($conn1, $userData1);
        $this->storage->add($conn2, $userData2);
        $this->storage->add($conn3, $userData3);

        $this->assertEquals(100, $this->storage->getUserIdByConnection($conn1));
        $this->assertEquals(200, $this->storage->getUserIdByConnection($conn2));
        $this->assertEquals(300, $this->storage->getUserIdByConnection($conn3));

        $this->storage->remove(200, $conn2);

        $this->assertNull($this->storage->getUserIdByConnection($conn2));
        $this->assertNull($this->storage->getConnectionByUserId(200));

        $this->assertEquals(100, $this->storage->getUserIdByConnection($conn1));
        $this->assertEquals(300, $this->storage->getUserIdByConnection($conn3));
    }
}
