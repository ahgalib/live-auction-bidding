# Frontend Authentication Flow & Pages Specification

## Feature Name
**Frontend Authentication Flow with Landing, Login, Registration, and Bidding Pages**

## Overview
Build a complete user journey: unauthenticated users land on a landing page, then authenticate via login or registration, and finally access the main bidding room where they can place bids on auctions.

---

## User Stories

### US1: Landing Page (First-Time Visitor)
**As an** unauthenticated user  
**I want to** see a professional landing page  
**So that** I understand what the application does and can navigate to login/register

**Acceptance Criteria:**
- [ ] Page displays "Velocity Auction" branding
- [ ] Hero section explains real-time auction bidding concept
- [ ] Two prominent CTAs: "Login" and "Register"
- [ ] Page is fully responsive (mobile, tablet, desktop)
- [ ] Contains footer with basic information

**UI Elements:**
- Hero banner with call-to-action buttons
- Feature highlights (real-time bidding, live auctions, secure payments)
- Navigation bar with Login/Register links
- Responsive design with Tailwind CSS

---

### US2: User Registration
**As a** new user  
**I want to** create an account with my email and password  
**So that** I can access the bidding platform

**Acceptance Criteria:**
- [ ] Form has fields: Name, Email, Password, Confirm Password
- [ ] Email validation (valid format, not already registered)
- [ ] Password strength validation (min 8 chars, mix of upper/lower/numbers)
- [ ] Submit validation: all fields required
- [ ] Success: redirect to login page with success message
- [ ] Error handling: display field-specific error messages
- [ ] Link to login page for existing users

**Form Validation Rules:**
- Name: 2-100 chars, alphanumeric + spaces
- Email: valid email format, unique
- Password: min 8 chars, at least 1 uppercase, 1 lowercase, 1 number
- Confirm Password: must match password field

---

### US3: User Login
**As a** registered user  
**I want to** log in with email and password  
**So that** I can access my auction bidding dashboard

**Acceptance Criteria:**
- [ ] Form has fields: Email, Password, Remember Me checkbox
- [ ] Remember Me checkbox persists login token for 7 days
- [ ] Failed login shows error: "Invalid email or password"
- [ ] Success: redirect to bidding page with user dashboard
- [ ] No error messages reveal whether email exists (security)
- [ ] Loading state during API call
- [ ] Link to registration page for new users

**Security Requirements:**
- Do NOT reveal if email exists or not
- Implement rate limiting (frontend + backend)
- Password always hashed in transit (HTTPS)
- Token stored in localStorage with expiration

---

### US4: Bidding Page (Authenticated)
**As a** logged-in user  
**I want to** see the auction room with live bids  
**So that** I can place bids and monitor my wallet

**Acceptance Criteria:**
- [ ] Page title shows auction name and current time
- [ ] Display current highest bid amount
- [ ] Display remaining time in auction (h:mm:ss format)
- [ ] Show user's current wallet balance
- [ ] Show user's current highest bid (if any)
- [ ] Bid input field with increment buttons (accept any positive number)
- [ ] Place Bid button with loading state
- [ ] Real-time bid history view (recent 10 bids)
- [ ] User logout button in header
- [ ] User profile info (name, email) in header

**Real-Time Features:**
- Bids update in real-time via WebSocket/Pusher
- Auction countdown timer updates every second
- Outbid notifications appear as toast/alert
- Balance updates immediately after bid

---

### US5: Session Management
**As a** logged-in user  
**I want to** my session to persist across page reloads  
**So that** I don't get logged out unexpectedly

**Acceptance Criteria:**
- [ ] Token stored in localStorage with auto-renew logic
- [ ] On app load, check localStorage for valid token
- [ ] If token expired, redirect to login
- [ ] Logout clears token and redirects to landing page
- [ ] Unauthorized API responses (401) redirect to login

---

## Page Flow Diagram

```
┌──────────────────────┐
│   Landing Page       │  (Unauthenticated)
├──────────────────────┤
│ Login >>>  Register  │
└────┬─────────┬───────┘
     │         │
     v         v
┌─────────┐  ┌──────────────┐
│  Login  │  │ Registration │
│  Page   │  │    Page      │
└────┬────┘  └────┬─────────┘
     │            │
     └──────┬─────┘
            v
     ┌─────────────────┐
     │  Bidding Room   │  (Authenticated)
     │                 │
     │  [Logout]       │
     └─────────────────┘
```

---

## Technical Requirements

### Frontend Stack
- Vue 3 with Composition API + `<script setup>`
- Pinia for state management
- Vue Router for page navigation
- Axios for API calls
- Tailwind CSS for styling
- Modern responsive design patterns

### Backend Requirements (verify/implement if missing)
- POST `/api/register` - Create new user
- POST `/api/login` - Authenticate and return token
- GET `/api/me` - Get current user info (authenticated)
- POST `/api/logout` - Invalidate session
- GET `/api/auctions/{id}` - Get auction state
- POST `/api/auctions/{id}/bid` - Place bid (authenticated)

### Security & Performance
- HTTPS only in production
- CORS properly configured
- Rate limiting on auth endpoints
- Refresh token logic (optional but recommended)
- Input validation on client + server
- No sensitive data in localStorage (use httpOnly cookies if possible)

---

## Out of Scope (for this iteration)
- Email verification
- Password reset flow
- User profile editing
- Auction creation
- Admin dashboard
- OAuth2 integrations
