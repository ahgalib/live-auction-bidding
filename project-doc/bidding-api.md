# Bidding API Behavior (GraphQL First)

## Bid Rejection Semantics
- `LOW_BID`: Submitted amount is less than or equal to current live amount.
- `AUCTION_CLOSED`: Auction is no longer active or has already ended.
- `RATE_LIMIT_EXCEEDED`: Reserved code for upstream rate-control rules.

## Time Synchronization Guidance
- Use `query ServerTime` to calculate server-client offset.
- Client countdown should be computed as:
  - `remaining = end_time_utc - (local_time_utc + offset)`
- Refresh server-time offset periodically to keep countdowns accurate.

## GraphQL Endpoint
- `POST /graphql`

## Core Operations
- `query AuctionState($id: ID!)`
- `query ServerTime`
- `mutation Register($name: String!, $email: String!, $password: String!)`
- `mutation Login($email: String!, $password: String!)`
- `mutation OAuthToken(...)`
- `query Me` (Bearer token required)
- `mutation Logout` (Bearer token required)
- `mutation PlaceBid($auctionId: ID!, $amount: Float!, $requestId: String)`
- `mutation WithdrawBid($auctionId: ID!, $reason: String)` (admin only)

Legacy REST endpoints in `routes/api.php` are compatibility-only and should be phased out.

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
