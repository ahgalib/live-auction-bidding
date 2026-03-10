# Bidding API Behavior

## Bid Rejection Semantics
- `LOW_BID`: Submitted amount is less than or equal to current live amount.
- `AUCTION_CLOSED`: Auction is no longer active or has already ended.
- `RATE_LIMIT_EXCEEDED`: Reserved code for upstream rate-control rules.

## Time Synchronization Guidance
- Use `GET /api/server-time` to calculate server-client offset.
- Client countdown should be computed as:
  - `remaining = end_time_utc - (local_time_utc + offset)`
- Refresh server-time offset periodically to keep countdowns accurate.

## Core Endpoints
- `POST /api/register`
- `POST /api/login`
- `POST /api/oauth/token` (OAuth2 password grant)
- `GET /api/me` (Bearer token required)
- `POST /api/logout` (Bearer token required)
- `POST /api/auctions/{auction}/bid`
- `POST /api/auctions/{auction}/withdraw-bid`
- `GET /api/auctions/{auction}`
- `GET /api/server-time`

## OAuth2 Password Grant Example
```json
{
  "grant_type": "password",
  "client_id": 1,
  "client_secret": "dev-client-secret-change-me",
  "username": "user@example.com",
  "password": "password123",
  "scope": "bid:write auction:read"
}
```

Use `Authorization: Bearer <access_token>` for protected endpoints.
