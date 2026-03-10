# Tasks: Atomic Bid Core

## Phase 1: Setup

- [X] T001 Create auction and bid domain models (if missing) in `app/Models/`.
- [X] T002 Create migration(s) for `auctions` and `bid_logs` in `database/migrations/`.
- [X] T003 Create service scaffolding for bidding workflow in `app/Services/`.

## Phase 2: Tests First (TDD)

- [X] T004 [P] Add feature test for valid bid acceptance and low/equal bid rejection in `tests/Feature/`.
- [X] T005 [P] Add race-condition test for simultaneous equal bids in `tests/Feature/`.
- [X] T006 [P] Add anti-sniping extension test in `tests/Feature/`.
- [X] T007 [P] Add test for immutable audit logging after accepted/outbid events in `tests/Feature/`.
- [X] T008 [P] Add admin withdrawal restoration test in `tests/Feature/`.

## Phase 3: Core Implementation

- [X] T009 Implement bid validation and atomic lock acquisition flow in `app/Services/`.
- [X] T010 Implement deterministic tie handling (first accepted wins) in `app/Services/`.
- [X] T011 Implement anti-sniping timer extension logic in `app/Services/`.
- [X] T012 Implement live update event dispatch in `app/Events/` and related handlers.
- [X] T013 Implement asynchronous bid log persistence job in `app/Jobs/`.
- [X] T014 Implement server-time query/endpoint in `routes/` + controller/resolver.
- [X] T015 Implement admin withdrawal flow restoring previous valid bid state.

## Phase 4: Integration

- [X] T016 Wire request entry points for query/mutation/subscription contracts.
- [X] T017 Integrate Redis key lifecycle and closed-auction TTL cleanup.
- [X] T018 Integrate authorization rules for bidder and admin actions.
- [X] T019 Add operational logging/metrics for bid latency and broadcast latency.

## Phase 5: Polish & Validation

- [X] T020 Run full automated tests and fix regressions.
- [ ] T021 Validate success metrics under load-test scenario.
- [X] T022 Update project docs with bidding error semantics and client time-sync guidance.

## Dependencies and Execution Rules

- Complete phases in order.
- `[P]` tasks in the same phase can run in parallel if they do not edit the same files.
- Test tasks (T004-T008) must be completed before core implementation tasks (T009-T015).
- Tasks modifying the same service/module must run sequentially.
