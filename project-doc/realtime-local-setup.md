# Realtime Local Setup (Laravel + Pusher)

## Backend (`live-auction-bidding/.env`)

Set these values:

```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=ap2
PUSHER_SCHEME=https
PUSHER_PORT=443
```

Then run:

```bash
php artisan optimize:clear
php artisan serve
```

## Frontend (`auction-front-end/.env`)

Create `.env` from `.env.example` and set:

```env
VITE_BACKEND_URL=http://localhost:8000
VITE_GRAPHQL_ENDPOINT=/graphql
VITE_PUSHER_APP_KEY=your_key
VITE_PUSHER_APP_CLUSTER=ap2
VITE_PUSHER_HOST=
VITE_PUSHER_PORT=443
VITE_PUSHER_SCHEME=https
```

Then run:

```bash
npm run dev
```

## Verify Notifications

1. Open two browsers with two different users in the same auction room.
2. Place a bid in browser A.
3. Browser B should receive realtime bid update and bidder-name notification.
