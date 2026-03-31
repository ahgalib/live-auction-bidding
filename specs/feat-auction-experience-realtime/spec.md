# Feature Specification: Auction Discovery + Admin Time Control + Realtime Bid Notifications

**Feature Branch**: `feat-auction-experience-realtime`  
**Created**: 2026-03-31  
**Status**: Draft  
**Input**: User request for auction list-first flow, admin time increase/decrease controls, richer auction information, and realtime bidder notifications with Laravel + Pusher.

## User Scenarios & Testing

### User Story 1 - Discover and Enter Auctions (Priority: P1)
As a bidder, after login I can see all active auctions and choose which one to join.

**Independent Test**: Login, land on auction list page, open any listed auction room.

**Acceptance Scenarios**:
1. Given authenticated user, when login succeeds, then app redirects to auction list page.
2. Given active auctions, when list page loads, then each card shows product/purpose info and can enter room.

### User Story 2 - Admin Can Adjust Running Auction Time (Priority: P1)
As an admin, I can increase or decrease a running auction end time from admin tools.

**Independent Test**: Admin updates running auction time by +/- minutes and sees updated `endTime`.

**Acceptance Scenarios**:
1. Given admin user, when applying positive minute delta, then end time increases.
2. Given admin user, when applying negative minute delta, then end time decreases safely.
3. Given non-admin user, when calling time-adjust operation, then API returns `FORBIDDEN`.

### User Story 3 - Realtime Bid Notifications Across Participants (Priority: P1)
As an auction participant, I receive realtime notifications when another user places a bid.

**Independent Test**: Two clients in same room; bid in client A triggers update + bidder notification in client B.

**Acceptance Scenarios**:
1. Given room subscribers, when bid accepted, then broadcast event includes bidder identity, amount, and updated end time.
2. Given frontend room listener, when event arrives, then UI feed/notice updates immediately.

## Requirements

### Functional Requirements
- **FR-001**: Backend must expose GraphQL query to list active auctions with summary fields for list view.
- **FR-002**: Auction detail response must include product/purpose info and participant count.
- **FR-003**: Admin GraphQL API must support explicit auction time adjustment by minute delta.
- **FR-004**: Bid updates must be broadcast via Laravel broadcasting with Pusher-compatible payload.
- **FR-005**: Frontend login flow must redirect to auction list page, not fixed auction id.

### Non-Functional Requirements
- **NFR-001**: Time sync and polling behavior should avoid unnecessary high-frequency server requests.
- **NFR-002**: Existing bid acceptance correctness and audit logging semantics must remain unchanged.

## Success Criteria
- **SC-001**: Users can pick any active auction from list immediately after login.
- **SC-002**: Admin can adjust running auction time without DB manual edits.
- **SC-003**: Bid notification payload identifies bidder and appears in other clients in near realtime.
