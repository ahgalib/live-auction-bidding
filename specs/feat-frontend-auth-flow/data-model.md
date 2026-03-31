# Data Model: Frontend Auth Flow & Pages

## State Management (Pinia Auth Store)

### User Entity
```typescript
{
  id: number,
  name: string,
  email: string,
  wallet_balance: number (decimal),
  is_admin: boolean
}
```

### Auth State
```typescript
{
  user: User | null,
  token: string | null,
  refreshToken: string | null,
  isAuthenticated: boolean,
  isLoading: boolean,
  error: string | null
}
```

### Auth Actions
```typescript
{
  login(email: string, password: string, rememberMe: boolean): Promise<void>,
  register(name: string, email: string, password: string): Promise<void>,
  logout(): void,
  checkAuth(): Promise<void>,
  refreshTokenIfNeeded(): Promise<void>,
  setUser(user: User): void
}
```

---

## API Payloads

### Register Request
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123"
}
```

### Register Response (Success)
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "wallet_balance": 0,
    "is_admin": false
  }
}
```

### Register Response (Error)
```json
{
  "message": "Validation error",
  "errors": {
    "email": ["This email is already registered"],
    "password": ["Password must be at least 8 characters"]
  }
}
```

### Login Request
```json
{
  "email": "john@example.com",
  "password": "SecurePass123"
}
```

### Login Response (Success)
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...", // optional
  "expires_in": 3600,
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "wallet_balance": 1000.00,
    "is_admin": false
  }
}
```

### Login Response (Error)
```json
{
  "message": "Invalid credentials"
}
```

### Me Endpoint Response
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "wallet_balance": 1000.00,
  "is_admin": false,
  "created_at": "2026-03-30T10:30:00Z",
  "updated_at": "2026-03-31T11:45:00Z"
}
```

### Logout Request
```json
{}
```

### Logout Response
```json
{
  "message": "Logout successful"
}
```

---

## Form Data Models

### Register Form
```typescript
{
  name: string,
  email: string,
  password: string,
  confirmPassword: string,
  agreeToTerms: boolean
}
```

### Register Form Validation Errors
```typescript
{
  name?: string,
  email?: string,
  password?: string,
  confirmPassword?: string,
  agreeToTerms?: string
}
```

### Login Form
```typescript
{
  email: string,
  password: string,
  rememberMe: boolean
}
```

### Login Form Validation Errors
```typescript
{
  email?: string,
  password?: string
}
```

### Bid Form
```typescript
{
  amount: number,
  auctionId: number
}
```

---

## Local Storage Structure

### Auth Token Storage
```json
{
  "auction_access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "auction_refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "auction_token_expires": 1704067200000,
  "auction_user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "wallet_balance": 1000.00,
    "is_admin": false
  }
}
```

### Remember Me Session
If "Remember Me" enabled:
- Token stored with 7-day expiration
- Session persists across browser close
- Auto-logout after 7 days

If unchecked:
- Token stored in sessionStorage only
- Clears on browser close

---

## Auction Entity (Read-Only)

```typescript
{
  id: number,
  name: string,
  description: string,
  starting_price: number,
  current_price: number,
  highest_bidder_id: number | null,
  starts_at: string (ISO8601),
  ends_at: string (ISO8601),
  current_highest_bid: {
    id: number,
    user_id: number,
    amount: number,
    bid_at: string (ISO8601)
  } | null,
  bids: Array<{
    id: number,
    user: {
      id: number,
      name: string
    },
    amount: number,
    bid_at: string (ISO8601)
  }>
}
```

---

## Real-Time Event Structure (WebSocket/Pusher)

### BidUpdated Event
```json
{
  "event": "BidUpdated",
  "data": {
    "auction_id": 1,
    "bid": {
      "id": 150,
      "user_id": 5,
      "user_name": "Alice Johnson",
      "amount": 1500.00,
      "bid_at": "2026-03-31T12:45:30Z"
    },
    "highest_bid_now": 1500.00
  }
}
```

### User Outbid Event
```json
{
  "event": "UserOutbid",
  "data": {
    "auction_id": 1,
    "user_id": 3,
    "new_highest_bid": 1500.00,
    "outbid_by_user": "Alice Johnson"
  }
}
```

### Auction Ended Event
```json
{
  "event": "AuctionEnded",
  "data": {
    "auction_id": 1,
    "winning_bid": 2000.00,
    "winning_user_id": 5,
    "winning_user_name": "Bob Wilson",
    "ended_at": "2026-03-31T13:00:00Z"
  }
}
```

---

## Component Props & Emits

### LoginForm.vue
**Props:**
- `loading: boolean` - Show loading state
- `error: string | null` - Error message

**Emits:**
- `submit(formData: { email, password, rememberMe })`

### RegisterForm.vue
**Props:**
- `loading: boolean`
- `error: string | null`

**Emits:**
- `submit(formData: { name, email, password })`
- `navigate-to-login()`

### BidForm.vue
**Props:**
- `auctionId: number`
- `loading: boolean`
- `error: string | null`
- `userBalance: number`
- `minimumBid: number`

**Emits:**
- `submit(amount: number)`

### BidHistory.vue
**Props:**
- `bids: Array<Bid>`
- `currentUserId: number`

**Emits:**
- None (display only)

---

## Validation Rules

### Email
- Format: valid email regex
- Length: 5-255 chars
- Unique: check server on blur/submit

### Password
- Minimum 8 characters
- At least 1 uppercase letter
- At least 1 lowercase letter
- At least 1 number
- (Optional) at least 1 special character

### Name
- Minimum 2 characters
- Maximum 100 characters
- Alphanumeric + spaces allowed

### Bid Amount
- Positive number
- Must be > current highest bid
- Must be ≤ user's wallet balance
- Minimum increment: $0.01

---

## Error Handling

### Form Validation Errors
```typescript
{
  field: string,
  message: string,
  code: string // e.g. "EMAIL_INVALID", "PASSWORD_TOO_SHORT"
}
```

### API Errors (Standard Format)
```json
{
  "message": "Error message",
  "errors": {
    "field_name": ["error 1", "error 2"]
  }
}
```

### Network Errors
```typescript
{
  type: "NETWORK_ERROR" | "TIMEOUT" | "UNAUTHORIZED",
  message: string,
  retryable: boolean
}
```

---

## Routes & Redirects

| Route | Component | Auth Required | Description |
|-------|-----------|---------------|-------------|
| `/` | LandingPage | ❌ | Landing/home page |
| `/login` | LoginPage | ❌ | Login form |
| `/register` | RegisterPage | ❌ | Registration form |
| `/bid/:auctionId` | BiddingRoom | ✅ | Auction bidding interface |
| `/profile` | ProfilePage | ✅ | User profile (future) |
| `*` | NotFound | ❌ | 404 page |

**Redirect Logic:**
- Unauthenticated user visits `/bid/*` → redirect to `/login`
- Authenticated user visits `/login` → redirect to `/bid/1`
- User logs out → redirect to `/`
- Token expires → redirect to `/login` with "session expired" message
