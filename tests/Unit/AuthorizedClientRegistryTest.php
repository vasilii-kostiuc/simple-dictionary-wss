<?php

namespace Tests\Unit;

use App\Domain\Shared\Identity\ClientIdentity;
use App\WebSockets\Storage\Clients\AuthorizedClientRegistry;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;

class AuthorizedClientRegistryTest extends TestCase
{
    private AuthorizedClientRegistry $clientRegistry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clientRegistry = new AuthorizedClientRegistry;
    }

    private function createMockConnection(int $resourceId = 1): ConnectionInterface
    {
        $conn = $this->createMock(ConnectionInterface::class);
        $conn->resourceId = $resourceId;

        return $conn;
    }

    private function makeIdentity(int $id = 123): ClientIdentity
    {
        return new ClientIdentity(
            id: $id,
            name: 'User '.$id,
            email: 'user'.$id.'@example.com',
            avatar: null,
        );
    }

    public function test_register_client_stores_identity(): void
    {
        $conn = $this->createMockConnection(1);
        $identity = $this->makeIdentity(123);

        $this->clientRegistry->register($conn, $identity);

        $this->assertEquals('123', $this->clientRegistry->getIdentifierByConnection($conn));
        $this->assertSame($identity, $this->clientRegistry->getIdentity($conn));
    }

    public function test_register_overwrites_existing_connection(): void
    {
        $conn = $this->createMockConnection(1);
        $identity1 = $this->makeIdentity(123);
        $identity2 = $this->makeIdentity(456);

        $this->clientRegistry->register($conn, $identity1);
        $this->clientRegistry->register($conn, $identity2);

        $this->assertEquals('456', $this->clientRegistry->getIdentifierByConnection($conn));
    }

    public function test_register_different_connections_for_different_identities(): void
    {
        $conn1 = $this->createMockConnection(1);
        $conn2 = $this->createMockConnection(2);
        $identity1 = $this->makeIdentity(123);
        $identity2 = $this->makeIdentity(456);

        $this->clientRegistry->register($conn1, $identity1);
        $this->clientRegistry->register($conn2, $identity2);

        $this->assertEquals('123', $this->clientRegistry->getIdentifierByConnection($conn1));
        $this->assertEquals('456', $this->clientRegistry->getIdentifierByConnection($conn2));
    }

    public function test_get_identifier_by_connection_returns_null_for_unknown(): void
    {
        $conn = $this->createMockConnection(99);

        $this->assertNull($this->clientRegistry->getIdentifierByConnection($conn));
    }

    public function test_get_identity_returns_null_for_unknown(): void
    {
        $conn = $this->createMockConnection(99);

        $this->assertNull($this->clientRegistry->getIdentity($conn));
    }

    public function test_get_connection_by_identifier_returns_connection(): void
    {
        $conn = $this->createMockConnection(1);
        $identity = $this->makeIdentity(123);

        $this->clientRegistry->register($conn, $identity);

        $this->assertSame([$conn], $this->clientRegistry->getConnectionsByIdentifier('123'));
    }

    public function test_get_identity_by_identifier_returns_identity(): void
    {
        $conn = $this->createMockConnection(1);
        $identity = $this->makeIdentity(123);

        $this->clientRegistry->register($conn, $identity);

        $this->assertSame($identity, $this->clientRegistry->getIdentityByIdentifier('123'));
    }

    public function test_get_connection_by_identifier_returns_empty_for_unknown(): void
    {
        $this->assertEmpty($this->clientRegistry->getConnectionsByIdentifier('999'));
    }

    public function test_forget_deletes_connection(): void
    {
        $conn = $this->createMockConnection(1);
        $identity = $this->makeIdentity(123);

        $this->clientRegistry->register($conn, $identity);
        $this->clientRegistry->forget($conn);

        $this->assertNull($this->clientRegistry->getIdentifierByConnection($conn));
        $this->assertNull($this->clientRegistry->getIdentity($conn));
    }

    public function test_forget_nonexistent_connection_does_nothing(): void
    {
        $conn = $this->createMockConnection(99);

        $this->clientRegistry->forget($conn);

        $this->assertNull($this->clientRegistry->getIdentifierByConnection($conn));
    }

    public function test_get_identity_returns_correct_identity(): void
    {
        $conn = $this->createMockConnection(1);
        $identity = new ClientIdentity(id: 42, name: 'Test', email: 'test@test.com', avatar: 'http://example.com/img.jpg');

        $this->clientRegistry->register($conn, $identity);

        $result = $this->clientRegistry->getIdentity($conn);
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
        $identity1 = $this->makeIdentity(100);
        $identity2 = $this->makeIdentity(200);
        $identity3 = $this->makeIdentity(300);

        $this->clientRegistry->register($conn1, $identity1);
        $this->clientRegistry->register($conn2, $identity2);
        $this->clientRegistry->register($conn3, $identity3);

        $this->assertEquals('100', $this->clientRegistry->getIdentifierByConnection($conn1));
        $this->assertEquals('200', $this->clientRegistry->getIdentifierByConnection($conn2));
        $this->assertEquals('300', $this->clientRegistry->getIdentifierByConnection($conn3));

        $this->clientRegistry->forget($conn2);

        $this->assertNull($this->clientRegistry->getIdentifierByConnection($conn2));
        $this->assertEmpty($this->clientRegistry->getConnectionsByIdentifier('200'));

        $this->assertEquals('100', $this->clientRegistry->getIdentifierByConnection($conn1));
        $this->assertEquals('300', $this->clientRegistry->getIdentifierByConnection($conn3));
    }
}
