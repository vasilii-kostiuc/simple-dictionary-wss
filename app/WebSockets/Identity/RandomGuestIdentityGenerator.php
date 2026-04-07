<?php

namespace App\WebSockets\Identity;

class RandomGuestIdentityGenerator implements GuestIdentityGeneratorInterface
{
    private const ADJECTIVES = ['Быстрый', 'Умный', 'Хитрый', 'Смелый', 'Ловкий', 'Мудрый', 'Дерзкий', 'Стремительный'];

    private const ANIMALS = ['Лис', 'Волк', 'Орёл', 'Ястреб', 'Лев', 'Тигр', 'Медведь', 'Сокол'];

    public function generateName(): string
    {
        return "Анонимный ".
            self::ADJECTIVES[array_rand(self::ADJECTIVES)].' '.
            self::ANIMALS[array_rand(self::ANIMALS)].' '.
            rand(100, 999);
    }

    public function generateAvatar(string $guestId): string
    {
        return 'https://api.dicebear.com/7.x/avataaars/svg?seed='.urlencode($guestId);
    }
}
