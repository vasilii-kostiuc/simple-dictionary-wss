<?php

namespace Tests\Unit;

use App\Application\Auth\Actions\AuthenticateUserAction;
use App\Application\Auth\Exceptions\AuthException;
use App\Domain\Shared\Identity\ClientIdentity;
use App\Domain\Shared\Identity\UserIdentityResolverInterface;
use PHPUnit\Framework\TestCase;

class AuthenticateUserActionTest extends TestCase
{
    public function test_returns_identity_when_token_is_valid(): void
    {
        $identity = new ClientIdentity(
            id: 42,
            name: 'Test User',
            email: 'user@example.com',
            avatar: 'https://example.com/avatar.jpg',
        );

        $resolver = $this->createMock(UserIdentityResolverInterface::class);
        $resolver->expects($this->once())
            ->method('resolveByToken')
            ->with('valid-token')
            ->willReturn($identity);

        $action = new AuthenticateUserAction($resolver);

        $this->assertSame($identity, $action->execute('valid-token'));
    }

    public function test_throws_when_token_is_invalid(): void
    {
        $resolver = $this->createMock(UserIdentityResolverInterface::class);
        $resolver->expects($this->once())
            ->method('resolveByToken')
            ->with('invalid-token')
            ->willReturn(null);

        $action = new AuthenticateUserAction($resolver);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('invalid_token');

        $action->execute('invalid-token');
    }
}
