# Frontend Interface Contract: Auction Room

## Bootstrapping Endpoints
- `GET /api/auctions/{id}`
- `GET /api/server-time`

## Auth Endpoints
- `POST /api/register`
- `POST /api/login`
- `POST /api/oauth/token`
- `GET /api/me`
- `POST /api/logout`

## Bid Endpoints
- `POST /api/auctions/{id}/bid`
- `POST /api/auctions/{id}/withdraw-bid`

## Realtime Events (expected payloads)
- `BidPlaced`:
  - `auctionId`
  - `amount`
  - `endTime`
  - `bid` (event object)
- Presence updates:
  - live watcher count and connection state
