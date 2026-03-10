# Frontend Setup and Environment

## Entry Routes
- `/` renders auction room for auction `1`.
- `/auction/{auctionId}` renders auction room for a specific auction.

## Required Environment Variables
- `VITE_GRAPHQL_ENDPOINT` (optional, defaults to `/graphql`)
- `VITE_PUSHER_APP_KEY` (optional for realtime)
- `VITE_PUSHER_APP_CLUSTER` (optional)
- `VITE_PUSHER_HOST` (optional)
- `VITE_PUSHER_PORT` (optional)
- `VITE_PUSHER_SCHEME` (`http` or `https`)

## Auth Flow in UI
- Register: `POST /api/register`
- Login: `POST /api/login`
- OAuth2 password grant: `POST /api/oauth/token`
- Token is stored in localStorage key `auction_access_token`.

## Notes
- If realtime env keys are absent, UI still works with REST bootstrap and bid actions.
- On reconnect or tab visibility restore, UI performs hard re-sync.
