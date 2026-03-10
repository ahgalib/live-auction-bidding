# Feature Specification: Realtime Auction Frontend

**Feature Branch**: `feat-frontend-realtime`  
**Created**: 2026-03-10  
**Status**: Draft  
**Input**: User description: "front-end/front-end-doc.md requirements"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Watch live auction state (Priority: P1)
As a bidder, I can see the current price, remaining time, and active participation in real time without refreshing.

**Why this priority**: Live visibility is core to auction fairness and participation.

**Independent Test**: Open two clients, push a bid update event, and verify both clients show new price/end time/watcher count quickly.

**Acceptance Scenarios**:
1. **Given** a user is on the auction room, **When** a valid bid is accepted, **Then** the latest price appears with a transition and the bid feed updates.
2. **Given** the tab was inactive and becomes active again, **When** visibility returns, **Then** auction state hard re-syncs.

---

### User Story 2 - Place bids with responsive UX (Priority: P1)
As a registered bidder, I can place bids with immediate feedback and see clear errors if outbid or unauthorized.

**Why this priority**: The bidding action is the highest-value interaction.

**Independent Test**: Submit valid and invalid bids and verify optimistic state, disabled controls, rollback, and toast feedback.

**Acceptance Scenarios**:
1. **Given** a logged-in bidder, **When** they place a valid bid, **Then** controls disable during request and UI confirms success.
2. **Given** a low bid response, **When** the API rejects the bid, **Then** optimistic state rolls back and an outbid message is shown.

---

### User Story 3 - Maintain accurate countdown across client clock drift (Priority: P2)
As a user with incorrect local time, I still see an accurate countdown aligned with server time.

**Why this priority**: Clock drift can break trust and timing fairness.

**Independent Test**: Simulate local clock offset and verify countdown remains aligned via server offset.

**Acceptance Scenarios**:
1. **Given** local clock is skewed, **When** the page syncs with server time, **Then** countdown uses server-adjusted time.
2. **Given** periodic sync runs, **When** drift changes, **Then** countdown remains stable and accurate.

### Edge Cases

- Burst bid updates (many events in short time) should not cause full-page jank.
- Real-time transport disconnect should show a visible connection-lost overlay.
- Missing token on bid action should produce clear auth guidance.
- Mobile bid controls must remain thumb-friendly and readable.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Frontend MUST be implemented with Vue 3 Composition API and `<script setup>`.
- **FR-002**: Frontend MUST maintain auction state in a centralized store with modular actions.
- **FR-003**: Frontend MUST synchronize local time with server time and compute countdown from offset-adjusted time.
- **FR-004**: Frontend MUST provide atomic components for countdown, live price display, bid action pad, and participation badge.
- **FR-005**: Frontend MUST subscribe to real-time bid events and update price, timer, and feed without page refresh.
- **FR-006**: Frontend MUST buffer incoming bid feed updates to reduce excessive re-renders during bursts.
- **FR-007**: Frontend MUST perform a hard re-sync when document visibility returns to `visible`.
- **FR-008**: Frontend MUST apply optimistic bid UI and rollback when bid request fails.
- **FR-009**: Frontend MUST display connection-lost status when real-time connection is unavailable.
- **FR-010**: Frontend MUST expose login/register/token workflow for registered users to place bids.
- **FR-011**: Frontend MUST support mobile-friendly bid controls and accessible outbid announcements.

### Key Entities *(include if feature involves data)*

- **AuctionViewState**: Current price, end time, active state, watcher count, and bid feed.
- **BidUIEvent**: Local and server-origin bid feed entry with status metadata (`loading`, `accepted`, `rejected`).
- **UserSession**: Auth token and current user profile for protected actions.
- **TimeSyncState**: Server-client offset and derived `now` reference.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 95% of incoming live price updates are rendered to users in under 300ms after receipt.
- **SC-002**: Countdown drift remains under 1 second during a 10-minute session on a skewed client clock.
- **SC-003**: Bid submission controls provide visible feedback within 100ms of user action.
- **SC-004**: During burst updates, UI remains responsive with no full-page lockups and user actions remain possible.
- **SC-005**: 100% of failed bid submissions show a clear reason and rollback the optimistic entry.

## Assumptions

- Backend bid endpoints and server-time endpoint are available.
- Real-time provider credentials are configured in environment when deployed.
- GraphQL schema may be introduced later; initial implementation can use existing backend endpoints for bootstrapping.
