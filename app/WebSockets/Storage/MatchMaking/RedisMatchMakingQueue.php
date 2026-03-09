<?php

namespace App\WebSockets\Storage\MatchMaking;

use Illuminate\Support\Facades\Redis;

class RedisMatchMakingQueue implements MatchMakingQueueInterface
{
    private const QUEUE_PREFIX = 'matchmaking:queue:';
    private const USER_DATA_PREFIX = 'matchmaking:user:';
    private const QUEUE_TTL = 300; // 5 минут

    public function add(string $userId, array $matchParams): void
    {
        $queueKey = $this->getQueueKey($matchParams);
        $userDataKey = $this->getUserDataKey($userId);
        
        // Сохраняем данные пользователя с параметрами матча
        $userData = [
            'userId' => $userId,
            'matchParams' => $matchParams,
            'timestamp' => time(),
        ];
        
        Redis::setex($userDataKey, self::QUEUE_TTL, json_encode($userData));
        
        // Добавляем пользователя в sorted set с временной меткой как score
        Redis::zadd($queueKey, time(), $userId);
        Redis::expire($queueKey, self::QUEUE_TTL);
    }

    public function remove(string $userId, array $matchParams): void
    {
        $queueKey = $this->getQueueKey($matchParams);
        $userDataKey = $this->getUserDataKey($userId);
        
        // Удаляем пользователя из очереди
        Redis::zrem($queueKey, $userId);
        Redis::del($userDataKey);
    }

    public function all(array $matchParams): array
    {
        $queueKey = $this->getQueueKey($matchParams);
        
        // Получаем всех пользователей из sorted set (по порядку времени добавления)
        return Redis::zrange($queueKey, 0, -1);
    }

    public function findMatch(string $userId, array $matchParams): ?string
    {
        $queueKey = $this->getQueueKey($matchParams);
        
        // Получаем первого пользователя в очереди (кроме текущего)
        $users = Redis::zrange($queueKey, 0, -1);
        
        foreach ($users as $candidateUserId) {
            if ($candidateUserId !== $userId) {
                // Найден матч - удаляем обоих из очереди
                Redis::zrem($queueKey, $userId, $candidateUserId);
                Redis::del($this->getUserDataKey($userId));
                Redis::del($this->getUserDataKey($candidateUserId));
                
                return $candidateUserId;
            }
        }
        
        return null;
    }

    public function clear(array $matchParams): void
    {
        $queueKey = $this->getQueueKey($matchParams);
        
        // Получаем всех пользователей для очистки их данных
        $users = Redis::zrange($queueKey, 0, -1);
        
        foreach ($users as $userId) {
            Redis::del($this->getUserDataKey($userId));
        }
        
        // Удаляем саму очередь
        Redis::del($queueKey);
    }

    public function count(array $matchParams): int
    {
        $queueKey = $this->getQueueKey($matchParams);
        return (int) Redis::zcard($queueKey);
    }

    private function getQueueKey(array $matchParams): string
    {
        // Создаем ключ на основе параметров матча
        ksort($matchParams);
        $paramsHash = md5(json_encode($matchParams));
        return self::QUEUE_PREFIX . $paramsHash;
    }

    private function getUserDataKey(string $userId): string
    {
        return self::USER_DATA_PREFIX . $userId;
    }
}
