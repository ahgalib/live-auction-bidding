# Project Constitution: High-Performance Auction API

## 1. Executive Summary
A headless, high-concurrency GraphQL API built with Laravel, MySQL, and Redis. The system is designed to handle thousands of real-time bid mutations by using Redis as a high-speed buffer and Pusher for GraphQL Subscriptions.

## 2. API Architecture (The "Speed-Path")
- **Transport:** GraphQL (Lighthouse / GraphQL-Laravel).
- **Validation Layer:** Redis-based Atomic Locks (`Cache::lock`) to prevent MySQL deadlocks.
- **State Store:** Redis (Fast-access current bid and timer).
- **Audit Store:** MySQL (Permanent, immutable logs of every bid event).
- **Signals:** WebSockets via Pusher for real-time Subscriptions.

## 3. Database & Cache Design (MySQL + Redis)

- **MySQL Engine:** InnoDB with `B-Tree` indexing on `auction_id` and `created_at`.
- **Redis Key Strategy:** `auction:{id}:current_price` and `auction:{id}:lock`.
- **Integrity Rule:** Every successful Mutation must trigger a background `SyncToDatabase` job.

## 4. GraphQL Implementation Standards
- **Mutations:** Must be "Idempotent" where possible.
- **Subscriptions:** Every `placeBid` mutation must trigger a `BidUpdated` subscription.
- **Errors:** Standardized GraphQL error codes for `LOW_BID`, `AUCTION_CLOSED`, and `RATE_LIMIT_EXCEEDED`.

## 5. Performance Mandates
- **Memory First:** No `SELECT` queries to MySQL during the `placeBid` resolver.
- **Async Logging:** Use `dispatch_after_response()` for all MySQL `INSERT` operations.