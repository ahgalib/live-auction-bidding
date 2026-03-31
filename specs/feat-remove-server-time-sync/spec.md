# Feature Specification: Remove Server Time Sync Endpoint Usage

**Feature Branch**: `feat-remove-server-time-sync`  
**Created**: 2026-04-01  
**Status**: Draft  
**Input**: User request to stop all server-time checker endpoint usage and handle the change through spec-driven development.

## User Scenarios & Testing

### User Story 1 - No Server Time Requests From Frontend (Priority: P1)
As a user, the frontend should not call backend server-time endpoints during auction usage.

**Independent Test**: Open the app, use auction pages, and confirm no `ServerTime` GraphQL calls or `/api/server-time` requests are made.

### User Story 2 - Auction Timer Uses Local Clock Only (Priority: P1)
As a bidder, the countdown still renders without requiring backend time synchronization.

**Independent Test**: Load the auction room and confirm timer renders and updates using the auction end timestamp only.

## Requirements

### Functional Requirements
- **FR-001**: Frontend auction flow must not import or call the server-time composable for sync behavior.
- **FR-002**: GraphQL `ServerTime` operation must be removed from active backend routing.
- **FR-003**: Legacy REST `/api/server-time` endpoint must be removed.

### Non-Functional Requirements
- **NFR-001**: Frontend build must continue to pass.
- **NFR-002**: Backend feature tests and schema docs must align with removed server-time support.

## Success Criteria
- **SC-001**: No server-time endpoint is called during normal app usage.
- **SC-002**: Countdown still renders correctly from local browser time and auction end time.
