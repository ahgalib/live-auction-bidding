# Tasks: GraphQL + Redis Backend Alignment

## Phase 1: Setup

- [X] T001 Set Redis as default cache store in `config/cache.php`.
- [X] T002 Set Redis defaults in `.env.example` for cache/session/queue.
- [X] T003 Add GraphQL endpoint route in `routes/web.php`.

## Phase 2: API Layer

- [X] T004 Implement GraphQL entry controller in `app/Http/Controllers/GraphqlController.php`.
- [X] T005 Add query handlers for `AuctionState` and `ServerTime`.
- [X] T006 Add mutation handlers for `PlaceBid` and `WithdrawBid`.
- [X] T007 Add auth-related operations (`Register`, `Login`, `OAuthToken`, `Me`, `Logout`).

## Phase 3: Validation

- [X] T008 Add GraphQL feature tests in `tests/Feature/GraphqlEndpointTest.php`.
- [X] T009 Run automated tests and fix regressions.

## Phase 4: Documentation

- [X] T010 Update API guide to GraphQL-first in `project-doc/bidding-api.md`.
- [X] T011 Add schema contract reference in `project-doc/graphql-schema.graphql`.

## Dependencies and Execution Rules

- GraphQL controller and route must exist before endpoint tests run.
- Redis defaults must be applied before deployment configuration updates.
- REST endpoints remain temporary compatibility layer until migration is complete.
