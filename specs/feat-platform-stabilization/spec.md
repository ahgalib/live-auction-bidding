# Feature Specification: Platform Stabilization + Admin Auction Management

**Feature Branch**: `feat-platform-stabilization`
**Created**: 2026-03-31
**Status**: Draft
**Input**: User request to fix production issues, stop login->bid auto logout, add admin auction management, and align implementation to spec-driven flow.

## User Scenarios & Testing

### User Story 1 - Stable Bid Session (Priority: P1)

As a bidder, I can login and place bids without being unexpectedly logged out.

**Independent Test**: Login, place valid bid, then call protected profile route and confirm session still valid.

**Acceptance Scenarios**:
1. Given valid user credentials, when user logs in, then a valid bearer token is stored and used for protected calls.
2. Given an authenticated user, when user submits a valid bid, then bid is processed and user remains authenticated.

### User Story 2 - Admin Auction Management (Priority: P1)

As an admin, I can create and manage auctions without direct database edits.

**Independent Test**: Admin can list, create, update, and delete auctions from admin UI using backend authorization.

**Acceptance Scenarios**:
1. Given an admin token, when admin loads management page, then auctions list is returned.
2. Given an admin token, when admin submits valid create form, then a new auction is persisted.
3. Given non-admin user token, when user calls admin operations, then operation returns `FORBIDDEN`.

### User Story 3 - Contract and Frontend Path Consistency (Priority: P2)

As a maintainer, I can rely on one clear frontend auth/bid flow and matching GraphQL contracts.

**Independent Test**: Active frontend routes use one auth store flow and GraphQL schema docs match implemented response shape.

## Requirements

### Functional Requirements

- **FR-001**: Active login flow MUST persist plain bearer token value, not object payloads.
- **FR-002**: Active frontend auction room MUST use backend-compatible data contract.
- **FR-003**: Backend MUST expose admin-only auction management operations.
- **FR-004**: Backend MUST enforce admin authorization for creation/update/deletion of auctions.
- **FR-005**: GraphQL schema documentation MUST match runtime fields used by frontend.

### Non-Functional Requirements

- **NFR-001**: Existing bid atomicity and anti-sniping logic must remain unchanged.
- **NFR-002**: Existing GraphQL and bid endpoint tests must continue to pass.
- **NFR-003**: Admin management changes must be backward compatible with non-admin bidder flow.

## Success Criteria

- **SC-001**: Login -> place bid -> protected profile sequence succeeds without forced logout in manual validation.
- **SC-002**: Admin can manage auction lifecycle from UI without direct DB changes.
- **SC-003**: Non-admin users receive explicit `FORBIDDEN` for admin operations.
- **SC-004**: Updated contracts are documented in `project-doc/graphql-schema.graphql`.
