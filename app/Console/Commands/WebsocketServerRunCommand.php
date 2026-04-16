<?php

namespace App\Console\Commands;

use App\WebSockets\TrainingWsRuntime;
use Illuminate\Console\Command;

class WebsocketServerRunCommand extends Command
{
    public function __construct(
        private readonly TrainingWsRuntime $trainingWsRuntime,
    ) {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:serve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run websocket server';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $nodeId = env('WSS_NODE_ID', gethostname());
        $this->info("[{$nodeId}] WebSocket server starting on 0.0.0.0:8080");

        $this->trainingWsRuntime->run();

        return self::SUCCESS;
    }
}
