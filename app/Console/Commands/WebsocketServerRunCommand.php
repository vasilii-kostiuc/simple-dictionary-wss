<?php

namespace App\Console\Commands;

use App\WebSockets\MetricsHttpServer;
use App\WebSockets\TrainingWsRuntime;
use Illuminate\Console\Command;

class WebsocketServerRunCommand extends Command
{
    public function __construct(
        private readonly TrainingWsRuntime $trainingWsRuntime,
        private readonly MetricsHttpServer $metricsHttpServer,
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

        if (config('metrics.enabled', true)) {
            $host = config('metrics.host', '0.0.0.0');
            $port = config('metrics.port', 9091);
            $path = config('metrics.path', '/metrics');

            $this->metricsHttpServer->start("{$host}:{$port}", $path);
        }

        $this->trainingWsRuntime->run();

        return self::SUCCESS;
    }
}
