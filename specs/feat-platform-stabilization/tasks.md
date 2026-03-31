# Tasks: Platform Stabilization + Admin Auction Management

## Phase 1: Session + Frontend Path Stabilization

- [X] T001 Fix active auth store token parsing/storage and protected session validation.
- [X] T002 Switch active bid room route to GraphQL-compatible auction room implementation.
- [X] T003 Align auction room auth wiring to the active auth store.

## Phase 2: Admin Backend Operations

- [X] T004 Add GraphQL admin query for auction listing.
- [X] T005 Add GraphQL admin mutations for auction create/update/delete.
- [X] T006 Enforce admin-only authorization in GraphQL admin operations.

## Phase 3: Admin Frontend Management UI

- [X] T007 Add admin auction management page for list/create/update/delete actions.
- [X] T008 Add router guards for admin-only route access.

## Phase 4: Contracts + Verification

- [X] T009 Update GraphQL schema doc with runtime-aligned fields and admin operations.
- [X] T010 Add backend feature tests for admin GraphQL auth and CRUD behavior.
- [X] T011 Run backend feature tests and validate no regressions in bid/session flow.
