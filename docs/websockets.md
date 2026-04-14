**WebSocket Endpoints & Outgoing Messages**

This document lists the WebSocket client endpoints (message handlers) and the message types the server sends to clients. It focuses on the most-used handlers and outgoing messages; let me know if you want the full low-level payload of every handler.

**WebSocket Endpoints (Client → Server)**
- **`LinkMatchRoomJoin`**: [app/WebSockets/Handlers/Client/LinkMatchRoom/LinkMatchRoomJoinHandler.php](app/WebSockets/Handlers/Client/LinkMatchRoom/LinkMatchRoomJoinHandler.php#L1)
  - Expected client payload: { "data": { "link_token": string, "match_params"?: object } }
  - Action: join a link match room, subscribe the connection to `link_match_room.{roomId}`, responds with `match_room.changed` for that room.

- **`LinkMatchRoomLeave`**: [app/WebSockets/Handlers/Client/LinkMatchRoom/LinkMatchRoomLeaveHandler.php](app/WebSockets/Handlers/Client/LinkMatchRoom/LinkMatchRoomLeaveHandler.php#L1)
  - Expected client payload: { "data": { "link_token": string } }
  - Action: leave room, unsubscribe connection, responds with `match_room.changed` for that room.

- **MatchMaking: Join / Leave / Subscribe / Challenge**
  - `MatchMakingJoin`: [app/WebSockets/Handlers/Client/MatchMaking/MatchMakingJoinHandler.php](app/WebSockets/Handlers/Client/MatchMaking/MatchMakingJoinHandler.php#L1)
    - Payload: { "data": { "match_type": string, "match_params"?: object } }
    - Action: enqueue user for matchmaking; responds with `matchmaking_join_success` or `error`.
  - `MatchMakingLeave`: [app/WebSockets/Handlers/Client/MatchMaking/MatchMakingLeaveHandler.php](app/WebSockets/Handlers/Client/MatchMaking/MatchMakingLeaveHandler.php#L1)
    - Payload: { "data": { ... } }
    - Action: remove user from matchmaking queue; responds with `matchmaking_leave_success`.
  - `MatchMakingSubscribe` / `MatchMakingChallenge`: handler files:
    - [app/WebSockets/Handlers/Client/MatchMaking/MatchMakingSubscribeHandler.php](app/WebSockets/Handlers/Client/MatchMaking/MatchMakingSubscribeHandler.php#L1)
    - [app/WebSockets/Handlers/Client/MatchMaking/MatchMakingChallengeHandler.php](app/WebSockets/Handlers/Client/MatchMaking/MatchMakingChallengeHandler.php#L1)

- **Subscription management**
  - `Subscribe`: [app/WebSockets/Handlers/Client/Subscription/SubscribeMessageHandler.php](app/WebSockets/Handlers/Client/Subscription/SubscribeMessageHandler.php#L1)
  - `Unsubscribe`: [app/WebSockets/Handlers/Client/Subscription/UnsubscribeMessageHandler.php](app/WebSockets/Handlers/Client/Subscription/UnsubscribeMessageHandler.php#L1)
  - Payloads: { "data": { "channel": string } }
  - Action: manage per-connection subscription to internal channel keys like `link_match_room.{roomId}`.

- **Auth / GuestAuth**
  - `AuthMessageHandler`: [app/WebSockets/Handlers/Client/AuthMessageHandler.php](app/WebSockets/Handlers/Client/AuthMessageHandler.php#L1)
  - `GuestAuthHandler`: [app/WebSockets/Handlers/Client/GuestAuthHandler.php](app/WebSockets/Handlers/Client/GuestAuthHandler.php#L1)

**Internal / Broker handlers (server→server messages, handled internally by nodes)**
- `LinkMatchRoomParticipantsUpdatedHandler` (internal broker message handler)
  - File: [app/WebSockets/Handlers/Internal/LinkMatchRoom/LinkMatchRoomParticipantsUpdatedHandler.php](app/WebSockets/Handlers/Internal/LinkMatchRoom/LinkMatchRoomParticipantsUpdatedHandler.php#L1)
  - Action: receives broker publications about joins/leaves on other nodes and broadcasts `match_room.changed` to local subscribers for `link_match_room.{roomId}`.

- Matchmaking internal handlers (queue updated, matched, etc):
  - See [app/WebSockets/Handlers/Internal/MatchMaking](app/WebSockets/Handlers/Internal/MatchMaking/MatchMakingMatchedHandler.php#L1)

**Outgoing Messages (Server → Client)**
- **`match_room.changed`** — class: [app/WebSockets/Messages/MatchRoom/MatchRoomChangedMessage.php](app/WebSockets/Messages/MatchRoom/MatchRoomChangedMessage.php#L1)
  - Event name: `match_room.changed`
  - Typical payload: { "room_id": string, "participants": array, ... }
  - Sent when: participants join/leave a link match room or its state changes; used for cross-node synchronized updates.

- **`matchmaking_join_success`** — class: [app/WebSockets/Messages/MatchMaking/MatchMakingJoinSuccessMessage.php](app/WebSockets/Messages/MatchMaking/MatchMakingJoinSuccessMessage.php#L1)
  - Event name: `matchmaking_join_success`
  - Payload: { "match_type": string, "match_params": object }

- **`matchmaking_leave_success`** — class: [app/WebSockets/Messages/MatchMaking/MatchMakingLeaveSuccessMessage.php](app/WebSockets/Messages/MatchMaking/MatchMakingLeaveSuccessMessage.php#L1)
  - Event name: `matchmaking_leave_success`
  - Payload: empty

- **`matchmaking.queue.updated`** — class: [app/WebSockets/Messages/MatchMaking/MatchMakingQueueUpdatedMessage.php](app/WebSockets/Messages/MatchMaking/MatchMakingQueueUpdatedMessage.php#L1)
  - Event name: `matchmaking.queue.updated`
  - Payload: { "queue": array }
  - Sent when: the server publishes changes to the matchmaking queue (size/contents).

- **Match lifecycle messages** (Match API → clients)
  - `match_created`: [app/WebSockets/Messages/Match/MatchCreatedMessage.php](app/WebSockets/Messages/Match/MatchCreatedMessage.php#L1)
    - Payload: match creation data (see class constructor)
  - `match_started`: [app/WebSockets/Messages/Match/MatchStartedMessage.php](app/WebSockets/Messages/Match/MatchStartedMessage.php#L1)
  - `next_step_generated`: [app/WebSockets/Messages/Match/NextStepGeneratedMessage.php](app/WebSockets/Messages/Match/NextStepGeneratedMessage.php#L1)
  - `match_completed`: [app/WebSockets/Messages/Match/MatchCompletedMessage.php](app/WebSockets/Messages/Match/MatchCompletedMessage.php#L1)

- **`error`** — class: [app/WebSockets/Messages/ErrorMessage.php](app/WebSockets/Messages/ErrorMessage.php#L1)
  - Event name: `error`
  - Payload: { "error": string, "client_payload": mixed }
  - Sent when: handlers throw domain or validation exceptions; client receives the error code and the original payload for debugging.

- **Subscription success messages**
  - `subscribe_success`: [app/WebSockets/Messages/Subscription/SubscribeSuccessMessage.php](app/WebSockets/Messages/Subscription/SubscribeSuccessMessage.php#L1)
  - `unsubscribe_success`: [app/WebSockets/Messages/Subscription/UnsubscribeSuccessMessage.php](app/WebSockets/Messages/Subscription/UnsubscribeSuccessMessage.php#L1)

**Examples**
- Join room request (client → server):
  - { "type": "link_match_room.join", "data": { "link_token": "abc-123", "match_params": {"difficulty":"easy"} } }
- Server response (server → client) on join / participant change:
  - Event: `match_room.changed`
  - Payload: { "room_id": "abc-123", "participants": [{"id":"1","user_id":1}, {"id":"guest-bob","guest_id":"bob"}] }

**Notes & Next steps**
- I focused on main client-facing handlers and the outgoing message classes under `app/WebSockets/Messages`. If you want, I can:
  - Expand each handler with exact payload schemas (by reading action classes and DTOs).
  - Add sample TypeScript client code for subscribing and handling `match_room.changed`.
  - Generate OpenAPI-like JSON schema for every message type.

If this looks good, I'll mark the documentation task done and add per-handler payload examples.
