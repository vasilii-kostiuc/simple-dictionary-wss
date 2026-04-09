<?php

namespace Tests\Unit;

use App\Application\Auth\Actions\AuthenticateGuestAction;
use App\Domain\Shared\Identity\ClientIdentity;
use App\Domain\Shared\Identity\GuestIdentityFactoryInterface;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use PHPUnit\Framework\TestCase;

class AuthenticateGuestActionTest extends TestCase
{
    public function test_returns_existing_identity_from_registry_when_guest_id_is_known(): void
    {
        $existingIdentity = new ClientIdentity(
            id: null,
            name: 'Existing Guest',
            email: '',
            avatar: 'https://example.com/existing-avatar.svg',
            guestId: '11111111-1111-1111-1111-111111111111',
        );

        $clientRegistry = $this->createMock(ClientRegistryInterface::class);
        $guestIdentityFactory = $this->createMock(GuestIdentityFactoryInterface::class);

        $clientRegistry->expects($this->once())
            ->method('getIdentityByIdentifier')
            ->with('11111111-1111-1111-1111-111111111111')
            ->willReturn($existingIdentity);

        $guestIdentityFactory->expects($this->never())->method('create');

        $action = new AuthenticateGuestAction($clientRegistry, $guestIdentityFactory);

        $this->assertSame($existingIdentity, $action->execute('11111111-1111-1111-1111-111111111111'));
    }

    public function test_creates_identity_when_guest_id_is_unknown(): void
    {
        $newIdentity = new ClientIdentity(
            id: null,
            name: 'New Guest',
            email: '',
            avatar: 'https://example.com/new-avatar.svg',
            guestId: '22222222-2222-2222-2222-222222222222',
        );

        $clientRegistry = $this->createMock(ClientRegistryInterface::class);
        $guestIdentityFactory = $this->createMock(GuestIdentityFactoryInterface::class);

        $clientRegistry->expects($this->once())
            ->method('getIdentityByIdentifier')
            ->with('22222222-2222-2222-2222-222222222222')
            ->willReturn(null);

        $guestIdentityFactory->expects($this->once())
            ->method('create')
            ->with('22222222-2222-2222-2222-222222222222')
            ->willReturn($newIdentity);

        $action = new AuthenticateGuestAction($clientRegistry, $guestIdentityFactory);

        $this->assertSame($newIdentity, $action->execute('22222222-2222-2222-2222-222222222222'));
    }
}
