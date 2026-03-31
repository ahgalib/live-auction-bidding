# Feature Specification: Auction Data Consistency + Bid Feed Persistence

**Feature Branch**: `feat-auction-data-consistency`  
**Created**: 2026-04-01  
**Status**: Draft  
**Input**: User request to fix wrong timer behavior after bids, keep bid logs on reload, eliminate duplicate bid log entries, and run broader bug-fix validation.

## User Scenarios & Testing

### User Story 1 - Bid Feed Persists After Reload (Priority: P1)
As a bidder, when I reload the auction room during live bidding, I still see recent bid history.

**Independent Test**: Place bids, reload room, confirm recent accepted/withdrawn feed entries are shown.

### User Story 2 - Duplicate Bid Submission Protection (Priority: P1)
As a bidder, accidental duplicate submit/retry should not create duplicate accepted bid records.

**Independent Test**: Send identical bid mutation with same `requestId` twice and confirm one accepted audit record is created.

### User Story 3 - Realtime Feed Consistency (Priority: P1)
As a participant, incoming realtime events should not duplicate feed entries or regress visible state due to duplicate/out-of-order listeners.

**Independent Test**: Two-client bidding session confirms single feed entry per event and consistent state updates.

## Requirements

### Functional Requirements
- **FR-001**: `AuctionState` API must return recent bid feed entries for room hydration on page load.
- **FR-002**: `placeBid` flow must support request idempotency to avoid duplicate accepted log records.
- **FR-003**: Frontend subscription must avoid duplicate channel listeners and dedupe incoming feed items.

### Non-Functional Requirements
- **NFR-001**: Existing authorization and bid acceptance correctness must remain unchanged.
- **NFR-002**: Frontend build and backend GraphQL tests must pass after changes.

## Success Criteria
- **SC-001**: Bid feed remains visible after room reload.
- **SC-002**: Duplicate replay with same request ID does not create duplicate accepted bid logs.
- **SC-003**: Realtime feed does not show doubled entries from duplicate subscriptions.
