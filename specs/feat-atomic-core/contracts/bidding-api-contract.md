# Interface Contract: Auction Bidding Core

This project exposes a headless bidding interface with query, mutation, and subscription interactions.

## 1. Query: Auction State
- **Purpose**: Return current auction state for first page load and reconnect.
- **Input**:
  - `auctionId` (required)
- **Output**:
  - `auctionId`
  - `currentPrice`
  - `currentWinnerId`
  - `endTimeUtc`
  - `status`
  - `watcherCount`

## 2. Mutation: Place Bid
- **Purpose**: Accept or reject a bid atomically under high concurrency.
- **Input**:
  - `auctionId` (required)
  - `amount` (required, numeric)
  - `clientRequestId` (optional for idempotency)
- **Success Output**:
  - `accepted` = true
  - `newCurrentPrice`
  - `newWinnerId`
  - `effectiveEndTimeUtc`
  - `eventTimestamp`
- **Failure Output**:
  - `accepted` = false
  - `errorCode` in:
    - `LOW_BID`
    - `AUCTION_CLOSED`
    - `RATE_LIMIT_EXCEEDED`
  - `message`
  - `currentPrice`
  - `eventTimestamp`

## 3. Subscription: Bid Updated
- **Purpose**: Push bid and timer updates to live watchers.
- **Payload**:
  - `auctionId`
  - `currentPrice`
  - `winnerId`
  - `endTimeUtc`
  - `updateType` (bid_accepted, timer_extended, bid_withdrawn)
  - `eventTimestamp`

## 4. Mutation: Withdraw Bid (Admin)
- **Purpose**: Revert live state to previous valid bid event with audit trace.
- **Input**:
  - `auctionId`
  - `bidEventId`
  - `reason`
- **Output**:
  - `success`
  - `restoredPrice`
  - `restoredWinnerId`
  - `effectiveEndTimeUtc`
  - `eventTimestamp`

## 5. Query: Server Time
- **Purpose**: Provide server time for client countdown offset calculation.
- **Output**:
  - `serverTimeUtc`
  - `timestampPrecision`

## Contract Rules
- Equal-value concurrent bids produce exactly one success.
- All accepted or outbid outcomes are represented in immutable audit events.
- Subscription payloads represent the latest authoritative live state after each accepted update.
