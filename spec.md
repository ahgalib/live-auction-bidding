# Spec: Atomic Core Alignment (dev branch)

## Goal
Align this codebase to the auction master blueprint with spec-driven delivery:
- GraphQL-first runtime contracts for auction state, server time, auth, and bidding
- Redis lock-protected bid mutation path with anti-sniping
- Async MySQL bid audit persistence
- Realistic demo seed data for users and auctions

## Current Findings
- Bid locking and anti-sniping already exist in `AuctionBiddingService`.
- GraphQL endpoint exists, but frontend stores still depend on REST endpoints.
- Seed data is currently placeholder-only.
- Required demo fields (`title`, `min_increment`, `category`, `wallet_balance`) are not fully represented.
- `CONSTITUTION.md` was missing.

## Functional Requirements
1. Frontend data operations must use `/graphql` operations for:
   - `AuctionState`
   - `ServerTime`
   - `PlaceBid`
   - `Register`, `Login`, `OAuthToken`, `Me`, `Logout`
2. Demo seed data must include:
   - Users: Asadulla Galib, Rayhan Ahmed, Naimur Rahman
   - Auctions: iPhone 15 Pro Max, MacBook Air M2, Sony WH-1000XM5
3. Auction schema must store:
   - `title`, `starting_price`, `min_increment`, `current_price`, `end_time`, `category`
4. User schema must store:
   - `wallet_balance` and admin role mapping (`is_admin`)
5. All seeded and API-returned times must remain UTC ISO 8601.

## Non-Functional Requirements
- Preserve existing bid atomicity (`Cache::lock`) and async logging (`dispatchAfterResponse`).
- Keep compatibility with existing tests where possible and add/update tests for changed contracts.

## Out of Scope (this pass)
- Lighthouse package migration and schema directives (requires dependency change and full transport swap).
- Full subscription transport migration to GraphQL subscriptions.
