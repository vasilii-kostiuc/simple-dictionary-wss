# WSS Schemas

JSON Schema files for the WebSocket service are grouped by message direction:

- `client/` - payloads that clients send to WSS as `data`
- `server/` - payloads that WSS sends to clients as `data`
- `api/` - payloads that upstream API publishes to WSS brokers as `data`
- `internal/` - payloads that WSS nodes publish to each other as `data`

Notes:

- These schemas describe the `data` payload, not the outer `{ "type": "...", "data": ... }` envelope.
- The legacy root file [match_room_changed.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/match_room_changed.schema.json) is kept in place because it was already referenced directly.
- Some upstream match payloads are intentionally permissive because WSS forwards them almost as-is and only validates a minimal routing subset in code.

Main client request schemas:

- [client/auth.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/client/auth.schema.json)
- [client/guest_auth.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/client/guest_auth.schema.json)
- [client/subscribe.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/client/subscribe.schema.json)
- [client/unsubscribe.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/client/unsubscribe.schema.json)
- [client/matchmaking_join.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/client/matchmaking_join.schema.json)
- [client/matchmaking_leave.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/client/matchmaking_leave.schema.json)
- [client/matchmaking_challenge.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/client/matchmaking_challenge.schema.json)
- [client/link_match_room_join.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/client/link_match_room_join.schema.json)
- [client/link_match_room_leave.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/client/link_match_room_leave.schema.json)

Main server event schemas:

- [server/error.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/error.schema.json)
- [server/auth_success.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/auth_success.schema.json)
- [server/guest_auth_success.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/guest_auth_success.schema.json)
- [server/subscribe_success.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/subscribe_success.schema.json)
- [server/unsubscribe_success.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/unsubscribe_success.schema.json)
- [match_room_changed.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/match_room_changed.schema.json)
- [server/matchmaking_join_success.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/matchmaking_join_success.schema.json)
- [server/matchmaking_leave_success.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/matchmaking_leave_success.schema.json)
- [server/matchmaking_challenge_success.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/matchmaking_challenge_success.schema.json)
- [server/matchmaking_queue_updated.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/matchmaking_queue_updated.schema.json)
- [server/training_completed.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/training_completed.schema.json)
- [server/match_created.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/match_created.schema.json)
- [server/match_started.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/match_started.schema.json)
- [server/next_step_generated.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/next_step_generated.schema.json)
- [server/match_summary.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/match_summary.schema.json)
- [server/match_completed.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/server/match_completed.schema.json)

Broker integration schemas:

- [api/training_started.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/api/training_started.schema.json)
- [api/training_completed.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/api/training_completed.schema.json)
- [api/match_created.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/api/match_created.schema.json)
- [api/match_started.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/api/match_started.schema.json)
- [api/next_step_generated.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/api/next_step_generated.schema.json)
- [api/match_summary.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/api/match_summary.schema.json)
- [api/match_completed.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/api/match_completed.schema.json)
- [internal/matchmaking_joined.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/internal/matchmaking_joined.schema.json)
- [internal/matchmaking_leaved.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/internal/matchmaking_leaved.schema.json)
- [internal/matchmaking_matched.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/internal/matchmaking_matched.schema.json)
- [internal/matchmaking_queue_updated.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/internal/matchmaking_queue_updated.schema.json)
- [internal/link_match_room_joined.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/internal/link_match_room_joined.schema.json)
- [internal/link_match_room_left.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/internal/link_match_room_left.schema.json)
- [internal/relay_send.schema.json](/home/vasea/projects/simple-dictionary-wss/docs/schemas/internal/relay_send.schema.json)
