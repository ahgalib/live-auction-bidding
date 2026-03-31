# Tasks: Remove Server Time Sync Endpoint Usage

## Phase 1: Frontend
- [X] T001 Remove server-time sync usage from auction store bootstrap and resync flow.
- [X] T002 Remove server-time composable usage from countdown rendering.

## Phase 2: Backend
- [X] T003 Remove GraphQL `ServerTime` operation handling.
- [X] T004 Remove REST `/api/server-time` endpoint.
- [X] T005 Update GraphQL schema and backend tests to reflect removal.

## Phase 3: Validation
- [X] T006 Run backend feature tests.
- [X] T007 Run frontend build.
