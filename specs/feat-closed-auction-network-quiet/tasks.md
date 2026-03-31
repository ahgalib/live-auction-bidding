# Tasks: Closed Auction Network Quieting

## Phase 1: Room Lifecycle
- [X] T001 Stop room fallback polling once auction becomes inactive.
- [X] T002 Skip visibility-triggered hard resync when auction is inactive.
- [X] T003 Unsubscribe realtime room channel after auction becomes inactive.

## Phase 2: Closed State Sync
- [X] T004 Limit closure flow to one final canonical fetch for winner state.

## Phase 3: Validation
- [X] T005 Run frontend build to verify no regressions.
