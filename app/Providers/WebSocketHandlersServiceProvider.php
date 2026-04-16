<?php

namespace App\Providers;

use App\WebSockets\Enums\ClientRequestType;
use App\WebSockets\Enums\ServerEventType;
use App\WebSockets\Handlers\Api\ApiMessageHandlerFactory;
use App\WebSockets\Handlers\Api\Match\MatchCompletedHandler;
use App\WebSockets\Handlers\Api\Match\MatchCreatedHandler;
use App\WebSockets\Handlers\Api\Match\MatchStartedHandler;
use App\WebSockets\Handlers\Api\Match\MatchStepGeneratedHandler;
use App\WebSockets\Handlers\Api\Match\MatchSummaryHandler;
use App\WebSockets\Handlers\Api\Training\TrainingCompletedApiHandler;
use App\WebSockets\Handlers\Api\Training\TrainingStartHandler;
use App\WebSockets\Handlers\Api\UnknownApiMessageHandler;
use App\WebSockets\Handlers\Client\AuthMessageHandler;
use App\WebSockets\Handlers\Client\AuthorizedMessageHandler;
use App\WebSockets\Handlers\Client\GuestAuthHandler;
use App\WebSockets\Handlers\Client\LinkMatchRoom\LinkMatchRoomJoinHandler;
use App\WebSockets\Handlers\Client\LinkMatchRoom\LinkMatchRoomLeaveHandler;
use App\WebSockets\Handlers\Client\MatchMaking\MatchMakingChallengeHandler;
use App\WebSockets\Handlers\Client\MatchMaking\MatchMakingJoinHandler;
use App\WebSockets\Handlers\Client\MatchMaking\MatchMakingLeaveHandler;
use App\WebSockets\Handlers\Client\MatchMaking\MatchMakingSubscribeHandler;
use App\WebSockets\Handlers\Client\MessageHandlerFactory;
use App\WebSockets\Handlers\Client\MessageHandlerInterface;
use App\WebSockets\Handlers\Client\Subscription\SubscribeMessageHandler;
use App\WebSockets\Handlers\Client\Subscription\UnsubscribeMessageHandler;
use App\WebSockets\Handlers\Client\UnknownMessageHandler;
use App\WebSockets\Handlers\Internal\InternalMessageHandlerFactory;
use App\WebSockets\Handlers\Internal\LinkMatchRoom\LinkMatchRoomParticipantsUpdatedHandler;
use App\WebSockets\Handlers\Internal\MatchMaking\MatchMakingJoinedHandler;
use App\WebSockets\Handlers\Internal\MatchMaking\MatchMakingLeftHandler;
use App\WebSockets\Handlers\Internal\MatchMaking\MatchMakingMatchedHandler;
use App\WebSockets\Handlers\Internal\MatchMaking\MatchMakingQueueUpdatedHandler;
use App\WebSockets\Handlers\Internal\RelayMessageHandler;
use App\WebSockets\Handlers\Internal\UnknownInternalMessageHandler;
use App\WebSockets\Storage\Clients\ClientRegistryInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class WebSocketHandlersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerApiFactory();
        $this->registerInternalFactory();
        $this->registerClientFactory();
    }

    private function registerApiFactory(): void
    {
        $this->app->singleton(ApiMessageHandlerFactory::class, function (Application $app) {
            return new ApiMessageHandlerFactory(
                handlers: [
                    ServerEventType::TrainingStarted->value => $app->make(TrainingStartHandler::class),
                    ServerEventType::TrainingCompleted->value => $app->make(TrainingCompletedApiHandler::class),
                    ServerEventType::MatchCreated->value => $app->make(MatchCreatedHandler::class),
                    ServerEventType::MatchStarted->value => $app->make(MatchStartedHandler::class),
                    ServerEventType::NextStepGenerated->value => $app->make(MatchStepGeneratedHandler::class),
                    ServerEventType::MatchSummary->value => $app->make(MatchSummaryHandler::class),
                    ServerEventType::MatchCompleted->value => $app->make(MatchCompletedHandler::class),
                ],
                unknownHandler: $app->make(UnknownApiMessageHandler::class),
            );
        });
    }

    private function registerInternalFactory(): void
    {
        $this->app->singleton(InternalMessageHandlerFactory::class, function (Application $app) {
            return new InternalMessageHandlerFactory(
                handlers: [
                    'wss.matchmaking.joined' => $app->make(MatchMakingJoinedHandler::class),
                    'wss.matchmaking.leaved' => $app->make(MatchMakingLeftHandler::class),
                    'wss.matchmaking.matched' => $app->make(MatchMakingMatchedHandler::class),
                    'wss.matchmaking.queue.updated' => $app->make(MatchMakingQueueUpdatedHandler::class),
                    'wss.link_match_room.joined' => $app->make(LinkMatchRoomParticipantsUpdatedHandler::class),
                    'wss.link_match_room.left' => $app->make(LinkMatchRoomParticipantsUpdatedHandler::class),
                    'wss.relay.send' => $app->make(RelayMessageHandler::class),
                ],
                unknownHandler: $app->make(UnknownInternalMessageHandler::class),
            );
        });
    }

    private function registerClientFactory(): void
    {
        $this->app->singleton(MessageHandlerFactory::class, function (Application $app) {
            $clientRegistry = $app->make(ClientRegistryInterface::class);

            $subscribeResolver = function (string $channel) use ($app, $clientRegistry): MessageHandlerInterface {
                $inner = match ($channel) {
                    'matchmaking.queue' => $app->make(MatchMakingSubscribeHandler::class),
                    default => $app->make(SubscribeMessageHandler::class),
                };

                return new AuthorizedMessageHandler($inner, $clientRegistry);
            };

            return new MessageHandlerFactory(
                handlers: [
                    ClientRequestType::Auth->value => $app->make(AuthMessageHandler::class),
                    ClientRequestType::GuestAuth->value => $app->make(GuestAuthHandler::class),
                    ClientRequestType::Unsubscribe->value => new AuthorizedMessageHandler($app->make(UnsubscribeMessageHandler::class), $clientRegistry),
                    ClientRequestType::MatchmakingJoin->value => new AuthorizedMessageHandler($app->make(MatchMakingJoinHandler::class), $clientRegistry),
                    ClientRequestType::MatchmakingLeave->value => new AuthorizedMessageHandler($app->make(MatchMakingLeaveHandler::class), $clientRegistry),
                    ClientRequestType::MatchmakingChallenge->value => new AuthorizedMessageHandler($app->make(MatchMakingChallengeHandler::class), $clientRegistry),
                    ClientRequestType::LinkMatchRoomJoin->value => new AuthorizedMessageHandler($app->make(LinkMatchRoomJoinHandler::class), $clientRegistry),
                    ClientRequestType::LinkMatchRoomLeave->value => new AuthorizedMessageHandler($app->make(LinkMatchRoomLeaveHandler::class), $clientRegistry),
                ],
                subscribeResolver: $subscribeResolver,
                unknownHandler: $app->make(UnknownMessageHandler::class),
            );
        });
    }
}
