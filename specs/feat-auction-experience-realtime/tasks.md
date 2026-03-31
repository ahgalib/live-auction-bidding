# Tasks: Auction Discovery + Admin Time Control + Realtime Notifications

## Phase 1: Backend Data + GraphQL
- [X] T001 Add auction purpose/description persistence field and model mapping.
- [X] T002 Add GraphQL `Auctions` query for active auction discovery.
- [X] T003 Extend GraphQL auction payload with description and participant count.
- [X] T004 Add admin GraphQL `AdjustAuctionTime` mutation with authorization checks.

## Phase 2: Realtime Broadcasting
- [X] T005 Convert `BidUpdated` to broadcast event with bidder-aware payload.
- [X] T006 Add/align broadcasting configuration for local Pusher-compatible runtime.

## Phase 3: Frontend UX
- [X] T007 Add authenticated auction list page and set post-login redirect to that page.
- [X] T008 Add admin time adjustment controls in admin auction page.
- [X] T009 Show product/purpose + participant count in list and room views.
- [X] T010 Show bidder notification for incoming external bids in room.

## Phase 4: Validation
- [X] T011 Add backend feature tests for auctions query and adjust-time mutation.
- [X] T012 Build frontend and run targeted backend GraphQL tests.
