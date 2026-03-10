# Feature Specification: Atomic Bid Core

**Feature Branch**: `feat-atomic-core`  
**Created**: 2026-03-10  
**Status**: Draft  
**Input**: User description: "Project overview in `project-doc/overview.md` for the Velocity auction engine"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Place a Live Bid Reliably (Priority: P1)

As a bidder, I can place a bid on an active auction and receive an immediate accept/reject decision that is correct even when many users bid at once.

**Why this priority**: Bidding correctness is the core business function and directly affects trust, financial outcomes, and dispute risk.

**Independent Test**: Can be fully tested by submitting bids concurrently to the same auction and verifying only valid bids are accepted in a consistent order.

**Acceptance Scenarios**:

1. **Given** an active auction with current high bid of 450, **When** a bidder submits a bid of 500, **Then** the bid is accepted and the current high bid becomes 500.
2. **Given** an active auction with current high bid of 500, **When** a bidder submits a bid of 500, **Then** the bid is rejected with a low-bid error.
3. **Given** two bidders submit 500 at nearly the same instant, **When** the system processes both requests, **Then** exactly one bid is accepted and the other is rejected as too low.

---

### User Story 2 - See Auction Updates in Real Time (Priority: P2)

As a watcher, I see bid changes and timer extensions in near real time so I can react without refreshing.

**Why this priority**: Real-time visibility drives engagement and fairness in competitive auctions.

**Independent Test**: Can be tested by placing bids from one client and confirming all subscribed clients receive bid and timer updates.

**Acceptance Scenarios**:

1. **Given** multiple connected watchers, **When** a valid bid is accepted, **Then** all watchers receive the updated amount and bidder context quickly.
2. **Given** an auction with less than 30 seconds remaining, **When** a valid bid is accepted, **Then** all watchers receive the extended end time.

---

### User Story 3 - Preserve Financial Audit Integrity (Priority: P3)

As an operations/admin user, I can trust that every bid attempt relevant to financial or dispute review is preserved in immutable history.

**Why this priority**: Auditability is essential for compliance, settlement, and fraud investigation.

**Independent Test**: Can be tested by running bid traffic and verifying immutable bid logs exist for accepted and outbid events.

**Acceptance Scenarios**:

1. **Given** accepted and rejected bid attempts, **When** persistence completes, **Then** audit records exist with auction, user, amount, timestamp, and source metadata.
2. **Given** auction close settlement, **When** payment and invoicing execute, **Then** settlement changes are completed atomically and consistently.

### Edge Cases

- Two or more equal bid amounts arrive in the same processing window for the same auction.
- Bids arrive within the final 30 seconds and repeatedly trigger anti-sniping extensions.
- An auction transitions to closed while a bid is in flight.
- A client submits bids using a stale local clock or delayed network path.
- Temporary asynchronous persistence lag occurs after a valid in-memory state update.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST accept bid submissions only for auctions in an active state.
- **FR-002**: The system MUST evaluate each incoming bid against the latest authoritative live amount for that auction.
- **FR-003**: The system MUST ensure per-auction bid processing is serialized so concurrent bids cannot produce conflicting winners.
- **FR-004**: If two bids with the same amount compete for the same auction at nearly the same time, the system MUST accept exactly one and reject all others as too low.
- **FR-005**: The system MUST return a clear reason when a bid is rejected, including low bid and closed auction outcomes.
- **FR-006**: The system MUST publish bid acceptance updates to live subscribers immediately after the live auction state changes.
- **FR-007**: The system MUST extend auction end time by 60 seconds when a valid bid is accepted within the final 30 seconds before scheduled close.
- **FR-008**: The system MUST expose a server-time reference endpoint/query so clients can align countdown displays with server time.
- **FR-009**: The system MUST persist immutable bid log entries for accepted bids and outbid events with auction id, bidder id, amount, event time, and source metadata.
- **FR-010**: The system MUST support reversing a bid through an authorized administrative action that restores auction live state to the prior valid bid.
- **FR-011**: The system MUST retain live auction cache keys for closed auctions no longer than 24 hours after close.
- **FR-012**: The system MUST support at least 50 incoming bid attempts per second across active auctions without violating bidding correctness.

### Key Entities *(include if feature involves data)*

- **User**: Participant account with wallet balance and verification status; may act as bidder, seller, watcher, or administrator.
- **Auction**: Sale listing with seller reference, current price, end time, and lifecycle status.
- **Bid Event**: Immutable record of a bid attempt or result for audit and analytics.
- **Live Auction State**: Authoritative, rapidly updated state for bidding decisions and countdown management.
- **Settlement Record**: Finalized payment and invoice outcomes after auction closure.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 99% of valid bid submissions receive a final accept/reject response in under 50 milliseconds under normal operating load.
- **SC-002**: 99% of live bid update broadcasts reach subscribed clients within 200 milliseconds of bid acceptance.
- **SC-003**: In concurrency tests with simultaneous equal-amount bids, 100% of test runs produce exactly one accepted winner per bid amount and auction state remains consistent.
- **SC-004**: The platform sustains at least 50 bid attempts per second during peak load tests without correctness regressions.
- **SC-005**: 100% of accepted bids and outbid events are present in immutable audit history within the agreed asynchronous persistence window.

## Assumptions

- Authentication and authorization are already available in the platform and can enforce bidder/admin permissions.
- The project remains headless and live-auction first, with real-time client consumption as a primary user journey.
- Auction currency and minimum increment policy are defined elsewhere and provided consistently to bid validation.
- Operational monitoring is available to measure latency and broadcast timing against success criteria.
