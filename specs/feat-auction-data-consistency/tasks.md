# Tasks: Auction Data Consistency + Bid Feed Persistence

## Phase 1: Backend Contract + Idempotency
- [X] T001 Extend `AuctionState` response with recent bid feed items.
- [X] T002 Add bid request idempotency handling in bidding service.
- [X] T003 Add backend regression tests for feed payload and idempotent replay.

## Phase 2: Frontend State Reliability
- [X] T004 Extend auction state query to hydrate bid feed after reload.
- [X] T005 Send unique `requestId` for each bid mutation.
- [X] T006 Remove duplicate realtime listener and dedupe incoming bid feed entries.

## Phase 3: Validation
- [X] T007 Run backend GraphQL feature tests.
- [X] T008 Run frontend production build.
