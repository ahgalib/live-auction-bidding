# Feature Specification: Closed Auction Network Quieting

**Feature Branch**: `feat-closed-auction-network-quiet`  
**Created**: 2026-04-01  
**Status**: Draft  
**Input**: User request to stop hitting backend after an auction closes and handle the fix through spec-driven development.

## User Scenarios & Testing

### User Story 1 - Closed Auction Stops Syncing (Priority: P1)
As a bidder, once an auction is closed, the room should stop making unnecessary backend sync requests.

**Independent Test**: Open a room, let the auction close, then confirm no recurring GraphQL auction-state or server-time requests continue from the room.

### User Story 2 - Winner State Still Appears (Priority: P1)
As a participant, I still see the closed state and winner information after the final sync completes.

**Independent Test**: Let an auction expire and confirm the UI shows closed state and winner details without continued polling.

## Requirements

### Functional Requirements
- **FR-001**: Auction room must stop fallback polling after the auction becomes inactive.
- **FR-002**: Auction room must skip visibility-triggered resync once the auction is inactive.
- **FR-003**: Auction room must avoid unnecessary realtime subscription activity after the auction is inactive.

### Non-Functional Requirements
- **NFR-001**: The room may perform one final canonical fetch at close to hydrate winner data.
- **NFR-002**: The fix must not break active-auction live updates.

## Success Criteria
- **SC-001**: No recurring backend sync requests continue after the room transitions to closed.
- **SC-002**: Closed room still shows final winner and price correctly.
