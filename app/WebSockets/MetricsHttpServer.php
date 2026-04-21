<?php

namespace App\WebSockets;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;

class MetricsHttpServer
{
    public function __construct(
        private readonly CollectorRegistry $registry,
        private readonly LoopInterface $loop,
    ) {}

    public function start(string $address = '0.0.0.0:9091', string $path = '/metrics'): void
    {
        $renderer = new RenderTextFormat;
        $registry = $this->registry;

        $http = new HttpServer(function (ServerRequestInterface $request) use ($registry, $renderer, $path): Response {
            if ($request->getMethod() !== 'GET' || $request->getUri()->getPath() !== $path) {
                return new Response(404, ['Content-Type' => 'text/plain; charset=utf-8'], 'Not Found');
            }

            return new Response(
                200,
                ['Content-Type' => RenderTextFormat::MIME_TYPE],
                $renderer->render($registry->getMetricFamilySamples()),
            );
        });

        $http->listen(new SocketServer($address, [], $this->loop));

        $nodeId = env('WSS_NODE_ID', gethostname());
        echo "[{$nodeId}] Metrics server listening on {$address}".PHP_EOL;
    }
}
