<?php

namespace App\Infrastructure\Identity;

use App\Domain\Shared\Identity\ClientIdentity;
use App\Domain\Shared\Identity\GuestIdentityFactoryInterface;
use Illuminate\Support\Str;

class RandomGuestIdentityFactory implements GuestIdentityFactoryInterface
{
    private const ADJECTIVES = ['Быстрый', 'Умный', 'Хитрый', 'Смелый', 'Ловкий', 'Мудрый', 'Дерзкий', 'Стремительный'];

    private const ANIMALS = ['Лис', 'Волк', 'Орёл', 'Ястреб', 'Лев', 'Тигр', 'Медведь', 'Сокол'];

    public function create(?string $guestId = null): ClientIdentity
    {
        $guestId ??= (string) Str::uuid();

        return new ClientIdentity(
            id: null,
            name: $this->generateName(),
            email: '',
            avatar: $this->generateAvatar($guestId),
            guestId: $guestId,
        );
    }

    private function generateName(): string
    {
        return "Анонимный ".
            self::ADJECTIVES[array_rand(self::ADJECTIVES)].' '.
            self::ANIMALS[array_rand(self::ANIMALS)].' '.
            rand(100, 999);
    }

    private function generateAvatar(string $guestId): string
    {
        return 'https://api.dicebear.com/7.x/avataaars/svg?seed='.urlencode($guestId);
    }
}
