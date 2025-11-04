<?php

namespace App\WebSockets\Messages;

class WebSocketMessage
{
    public string $type;
    public mixed $data;

    public function __construct(string $type, array $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    public function __toString()
    {
        return json_encode([
            'type' => $this->type,
            'data' => $this->data,
        ]);
    }

}
