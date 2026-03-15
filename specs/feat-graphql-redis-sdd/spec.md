# Feature Specification: GraphQL + Redis Backend Alignment

**Feature Branch**: `feat-graphql-redis-sdd`  
**Created**: 2026-03-15  
**Status**: Draft  
**Input**: User description: "run project with spec-driven development, use Redis instead of normal cache, and GraphQL instead of REST API"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Query Auction State via GraphQL (Priority: P1)

As a frontend client, I can fetch live auction state from GraphQL so UI data contracts are single-shape and REST-free.

**Independent Test**: Send `AuctionState` GraphQL query and verify expected fields are returned.

**Acceptance Scenarios**:
1. **Given** an existing auction, **When** `AuctionState` query runs, **Then** `id`, `currentPrice`, `currentWinnerId`, `endTime`, and `status` are returned.
2. **Given** a missing auction id, **When** `AuctionState` query runs, **Then** GraphQL returns a `NOT_FOUND` error.

---

### User Story 2 - Place Bid via GraphQL Mutation (Priority: P1)

As an authenticated bidder, I can submit bids using GraphQL mutation and receive deterministic accept/reject responses.

**Independent Test**: Send `PlaceBid` mutation with bearer token and verify accepted/rejected behaviors.

**Acceptance Scenarios**:
1. **Given** an active auction and higher amount, **When** `PlaceBid` executes, **Then** mutation returns `accepted=true` and updated `currentPrice`.
2. **Given** equal or lower amount, **When** `PlaceBid` executes, **Then** mutation returns `accepted=false` with `errorCode=LOW_BID`.

---

### User Story 3 - Redis as Default Runtime Cache (Priority: P2)

As an operator, I want Redis to be the default cache/session runtime store so live state remains memory-first.

**Independent Test**: Check environment and Laravel cache config default to `redis`.

**Acceptance Scenarios**:
1. **Given** default env/config, **When** app boots, **Then** `CACHE_STORE=redis` is used.
2. **Given** session and queue defaults, **When** app runs in local/dev, **Then** Redis-backed drivers are configured by default.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The backend MUST expose a `POST /graphql` endpoint for query and mutation operations.
- **FR-002**: The backend MUST support `AuctionState`, `ServerTime`, `PlaceBid`, and `WithdrawBid` operations through GraphQL.
- **FR-003**: Authenticated GraphQL operations MUST validate bearer token and return `UNAUTHENTICATED` error when missing/invalid.
- **FR-004**: Redis MUST be the default cache store in Laravel config and environment template.
- **FR-005**: Project docs MUST define GraphQL-first contracts and mark REST endpoints as compatibility-only.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: GraphQL query for server time returns 200 with valid `data.serverTime.serverTimeUtc`.
- **SC-002**: Authenticated `PlaceBid` GraphQL mutation can accept a valid higher bid in automated tests.
- **SC-003**: Environment template defaults show Redis for cache/session/queue settings.
