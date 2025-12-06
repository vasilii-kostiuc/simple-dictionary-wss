<?php

namespace App\WebSockets\Messages;

class TrainingCompletedMessage extends WebSocketMessage
{
    public function __construct($trainingId, $completed_at)
    {
        parent::__construct('training_completed', [
            'training_id' => $trainingId,
            'completed_at' => $completed_at,
        ]);
    }
}
