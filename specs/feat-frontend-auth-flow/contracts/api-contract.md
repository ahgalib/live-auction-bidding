# API Contract: Frontend Auth & Bidding

## Base URL
```
http://localhost:8000/api
http://backend:8000/api (Docker)
```

## Authentication
All endpoints requiring authentication use Bearer token in `Authorization` header:
```
Authorization: Bearer {token}
```

---

## Endpoints

### 1. User Registration
**Endpoint:** `POST /api/register`  
**Auth Required:** ❌ No  
**Rate Limit:** 5 requests per 5 minutes per IP  

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123"
}
```

**Response (201 Created):**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "wallet_balance": 0.00,
    "is_admin": false
  }
}
```

**Response (422 Unprocessable Entity):**
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["This email is already registered"],
    "password": ["Password must be at least 8 characters", "Password must contain at least one uppercase letter"]
  }
}
```

**Validation Rules (Backend):**
- Name: required, 2-100 chars, string
- Email: required, valid email, unique
- Password: required, min 8 chars, must include uppercase, lowercase, number

---

### 2. User Login
**Endpoint:** `POST /api/login`  
**Auth Required:** ❌ No  
**Rate Limit:** 10 requests per 5 minutes per IP  

**Request:**
```json
{
  "email": "john@example.com",
  "password": "SecurePass123"
}
```

**Response (200 OK):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
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

**Response (401 Unauthorized):**
```json
{
  "message": "Invalid credentials"
}
```

**Notes:**
- Returns JWT token valid for 1 hour (3600 seconds)
- Optional: refresh_token for extending session
- DO NOT reveal whether email exists or password is wrong (security)

---

### 3. Get Current User
**Endpoint:** `GET /api/me`  
**Auth Required:** ✅ Yes (Bearer token)  
**Rate Limit:** 100 requests per minute  

**Response (200 OK):**
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

**Response (401 Unauthorized):**
```json
{
  "message": "Unauthenticated"
}
```

**Use Cases:**
- Verify token is valid on app load
- Get current user wallet balance
- Check user profile info

---

### 4. User Logout
**Endpoint:** `POST /api/logout`  
**Auth Required:** ✅ Yes (Bearer token)  
**Rate Limit:** 50 requests per minute  

**Request:**
```json
{}
```

**Response (200 OK):**
```json
{
  "message": "Logout successful"
}
```

**Response (401 Unauthorized):**
```json
{
  "message": "Unauthenticated"
}
```

**Notes:**
- Invalidates the token on server
- Frontend must clear localStorage

---

### 5. Get Auction State
**Endpoint:** `GET /api/auctions/{auctionId}`  
**Auth Required:** ❌ No (but can be authenticated)  
**Rate Limit:** 200 requests per minute  
**Purpose:** Get current auction info and bid history

**Response (200 OK):**
```json
{
  "id": 1,
  "name": "iPhone 15 Pro",
  "description": "Latest iPhone with advanced features",
  "starting_price": 100.00,
  "current_price": 1500.00,
  "highest_bidder_id": 5,
  "starts_at": "2026-03-31T10:00:00Z",
  "ends_at": "2026-03-31T14:00:00Z",
  "current_highest_bid": {
    "id": 150,
    "user_id": 5,
    "amount": 1500.00,
    "bid_at": "2026-03-31T12:45:30Z"
  },
  "bids": [
    {
      "id": 150,
      "user": {
        "id": 5,
        "name": "Alice"
      },
      "amount": 1500.00,
      "bid_at": "2026-03-31T12:45:30Z"
    },
    {
      "id": 149,
      "user": {
        "id": 3,
        "name": "Bob"
      },
      "amount": 1400.00,
      "bid_at": "2026-03-31T12:40:15Z"
    }
  ]
}
```

**Response (404 Not Found):**
```json
{
  "message": "Auction not found"
}
```

---

### 6. Place Bid
**Endpoint:** `POST /api/auctions/{auctionId}/bid`  
**Auth Required:** ✅ Yes (Bearer token)  
**Rate Limit:** 30 requests per minute per user  
**Purpose:** Submit a new bid on an auction

**Request:**
```json
{
  "amount": 1600.00
}
```

**Response (201 Created):**
```json
{
  "message": "Bid placed successfully",
  "bid": {
    "id": 151,
    "user_id": 3,
    "amount": 1600.00,
    "bid_at": "2026-03-31T12:50:00Z"
  },
  "user": {
    "id": 3,
    "wallet_balance": 400.00
  }
}
```

**Response (400 Bad Request):**
```json
{
  "message": "Bid validation error",
  "errors": {
    "amount": ["Bid must be higher than current highest bid"]
  }
}
```

**Response (402 Payment Required):**
```json
{
  "message": "Insufficient wallet balance"
}
```

**Response (404 Not Found):**
```json
{
  "message": "Auction not found"
}
```

**Response (410 Gone):**
```json
{
  "message": "Auction has already ended"
}
```

**Validation Rules (Backend):**
- Amount must be > current highest bid
- Amount must be ≤ user's wallet balance
- Amount must be > 0
- Auction must be ongoing (ends_at > now)
- User cannot bid on their own auction
- Minimum bid increment (optional): e.g., $0.01

---

### 7. Withdraw Bid (Optional, Advanced)
**Endpoint:** `POST /api/auctions/{auctionId}/withdraw-bid`  
**Auth Required:** ✅ Yes (Bearer token)  
**Rate Limit:** 10 requests per minute per user  
**Purpose:** Withdraw the user's latest bid (if allowed)

**Request:**
```json
{}
```

**Response (200 OK):**
```json
{
  "message": "Bid withdrawn successfully",
  "refunded_amount": 1600.00,
  "user": {
    "id": 3,
    "wallet_balance": 2000.00
  }
}
```

**Response (400 Bad Request):**
```json
{
  "message": "Cannot withdraw bid - auction rules do not allow it"
}
```

---

## Error Response Format

**General Error Response (4xx, 5xx):**

```json
{
  "message": "Error description",
  "errors": {
    "field_name": ["Specific field error 1", "Specific field error 2"]
  },
  "code": "ERROR_CODE"
}
```

### Common HTTP Status Codes

| Code | Meaning | Retry? |
|------|---------|--------|
| 200 | OK | - |
| 201 | Created | - |
| 400 | Bad Request | ❌ No (validation error) |
| 401 | Unauthorized | ❌ No (redirect to login) |
| 402 | Payment Required | ❌ No (insufficient balance) |
| 404 | Not Found | ❌ No |
| 408 | Request Timeout | ✅ Yes (with backoff) |
| 429 | Too Many Requests | ✅ Yes (after delay) |
| 500 | Server Error | ✅ Yes (with backoff) |
| 503 | Service Unavailable | ✅ Yes (with longer delay) |

---

## Real-Time Events (WebSocket/Pusher)

Events are broadcast through Pusher or a WebSocket connection.

### Subscribe to Channel
Channel: `auction.{auctionId}`

### Events

#### BidUpdated
Fired when a new bid is placed on the auction.

```json
{
  "type": "BidUpdated",
  "data": {
    "auction_id": 1,
    "bid": {
      "id": 151,
      "user_id": 5,
      "user_name": "Alice",
      "amount": 1600.00,
      "bid_at": "2026-03-31T12:50:00Z"
    }
  }
}
```

#### UserOutbid
Fired when the current user is outbid.

```json
{
  "type": "UserOutbid",
  "data": {
    "auction_id": 1,
    "outbid_by_user_name": "Alice",
    "new_highest_bid": 1600.00
  }
}
```

#### AuctionEnded
Fired when the auction ends.

```json
{
  "type": "AuctionEnded",
  "data": {
    "auction_id": 1,
    "winning_user_id": 5,
    "winning_user_name": "Alice",
    "winning_bid": 1600.00,
    "ended_at": "2026-03-31T14:00:00Z"
  }
}
```

---

## Token Refresh (Optional Implementation)

**Endpoint:** `POST /api/refresh`  
**Auth Required:** ✅ Yes (Refresh token)  

**Request:**
```json
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Response (200 OK):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "expires_in": 3600
}
```

---

## CORS Configuration Required

Backend must allow:
- **Origins:** `http://localhost:5173`, `http://localhost:3000`, deployment domain
- **Methods:** GET, POST, PUT, DELETE, OPTIONS
- **Headers:** Authorization, Content-Type
- **Credentials:** true (if using cookies)

---

## Rate Limiting Strategy

Implemented per IP andPer endpoint:

| Endpoint | Limit | Window |
|----------|-------|--------|
| POST /register | 5 req | 5 min |
| POST /login | 10 req | 5 min |
| POST /logout | 50 req | 1 min |
| GET /me | 100 req | 1 min |
| GET /auctions/* | 200 req | 1 min |
| POST /bid | 30 req | 1 min |

If exceeded, return `429 Too Many Requests` with header:
```
Retry-After: {seconds}
```
