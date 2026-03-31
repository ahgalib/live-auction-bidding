# Task List: Atomic Core Alignment (dev branch)

- [x] Add `CONSTITUTION.md` at repository root.
- [x] Create branch-level `spec.md` and `task_list.md` before implementation edits.
- [x] Add DB migration for `users.wallet_balance`.
- [x] Add DB migration for auction metadata (`title`, `min_increment`, `category`).
- [x] Update `User` and `Auction` model fillable/casts for new fields.
- [x] Replace placeholder seeder with blueprint demo users and auctions in UTC.
- [x] Refactor auction store to GraphQL (`AuctionState`, `PlaceBid`) instead of REST.
- [x] Refactor auth store to GraphQL (`Register`, `Login`, `OAuthToken`, `Me`, `Logout`) instead of REST.
- [x] Refactor server time composable to GraphQL `ServerTime`.
- [x] Run tests and report results + residual risks.
