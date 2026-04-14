# Implementation Plan: FitSense Web App

## Overview

Implement FitSense as a server-rendered PHP application with AJAX-driven dynamic sections, MySQL (PDO), Tailwind CSS, and Gemini API integration. The build order follows a strict dependency chain: config → database → auth → core pages → role dashboards → AI features → admin tools → testing.

All PHP code uses PDO prepared statements. All views use Tailwind CSS utility classes with the Black and Yellow HCD theme. All interactive elements meet the 44×44px minimum touch target. Property-based tests use **eris** (`giorgiosironi/eris`), minimum 100 iterations each.

---

## Tasks

- [x] 1. Project scaffold and configuration
  - Create the full folder structure: `config/`, `includes/`, `api/`, `js/`, `database/`
  - Write `config/database.php`: `Database` class (PDO, `ATTR_ERRMODE_EXCEPTION`, `FETCH_ASSOC`), and all constants — `SESSION_TIMEOUT`, `PASSWORD_MIN_LENGTH`, `MAX_LOGIN_ATTEMPTS`, `GEMINI_API_KEY` (loaded from env), `GEMINI_API_URL`, `GEMINI_TIMEOUT_SECONDS`, `BASE_URL`
  - Add `.env.example` with placeholder keys; add `.env` to `.gitignore`
  - Create `maintenance.php` and `unauthorized.php` stub pages (Black/Yellow theme, plain-language message, dashboard link)
  - _Requirements: 1.1, 1.3, 1.4_

- [x] 2. Database schema
  - Write `database/schema.sql` with all 11 tables: `users`, `member_profiles`, `fitness_goals`, `exercises`, `ai_recommendations`, `workout_sessions`, `weight_logs`, `chat_messages`, `announcements`, `audit_logs`, `system_settings`
  - Include `UNIQUE KEY unique_user_date (user_id, log_date)` on `weight_logs`
  - Seed default `system_settings` rows: `maintenance_mode`, `max_ai_requests_per_day`, `session_timeout`, `password_min_length`
  - Seed one default admin account (`needs_password_change = FALSE`)
  - _Requirements: 2.1–2.11_

- [x] 3. Core utility library (`includes/functions.php`)
  - [x] 3.1 Implement utility functions
    - `sanitizeInput()` — trim, stripslashes, htmlspecialchars
    - `generatePassword($length)` — cryptographically random, meets complexity
    - `generateUsername($firstName, $lastName)` — unique slug + numeric suffix
    - `formatDate()`, `formatRelativeTime()` — returns relative strings ("2 hours ago", "Yesterday", "Just now"), never raw ISO format
    - `calculateBMI($weightKg, $heightCm)` — `round(w / (h/100)^2, 1)`
    - `getBMICategory($bmi)`
    - `sendJsonResponse($data, $statusCode)`
    - `redirectWithMessage($url, $message, $type)`
    - `displayFlashMessage()`
    - `generateCsrfToken()` / `validateCsrfToken($token)` — store in `$_SESSION['csrf_token']`
    - `validatePasswordComplexity($password)` — returns array of unmet criteria; accepts iff length ≥ `PASSWORD_MIN_LENGTH`, has uppercase, lowercase, digit, special char
    - `validateHealthProfile($weight, $height, $age)` — accepts iff weight ∈ [20,500], height ∈ [50,300], age ∈ [10,120]
    - _Requirements: 1.2, 4.4, 5.3, 19.1, 19.2_

  - [ ]* 3.2 Write property test for `calculateBMI`
    - **Property 23: BMI calculation is correct**
    - **Validates: Requirements 9.4**

  - [ ]* 3.3 Write property test for `validatePasswordComplexity`
    - **Property 8: Password complexity validation rejects non-compliant passwords**
    - **Validates: Requirements 4.4**

  - [ ]* 3.4 Write property test for `validateHealthProfile`
    - **Property 10: Health profile validation enforces range constraints**
    - **Validates: Requirements 5.3**

  - [ ]* 3.5 Write property test for `formatRelativeTime`
    - **Property 31: Timestamp formatting produces relative strings**
    - **Validates: Requirements 12.6**

  - [ ]* 3.6 Write property test for HTML output escaping
    - **Property 44: HTML output escaping prevents XSS**
    - **Validates: Requirements 19.2**

- [x] 4. Authentication system (`includes/auth.php`)
  - [x] 4.1 Implement `Auth` class
    - `login($username, $password)` — PDO query on `users WHERE status='active'`, `password_verify`, set session vars (`user_id`, `role`, `first_name`, `last_name`, `needs_password_change`, `login_time`), update `last_login`, write `audit_logs`
    - Failed-attempt counter in `$_SESSION['login_attempts']`; lock account at `MAX_LOGIN_ATTEMPTS`
    - `logout()` — write audit log, `session_destroy()`, clear cookies
    - `isSessionValid()` — check `time() - $_SESSION['login_time'] < SESSION_TIMEOUT`
    - `requireAuth($redirect)`, `requireRole($role)`, `requireAnyRole($roles)`
    - `changePassword($userId, $newPassword)` — bcrypt hash, set `needs_password_change=FALSE`, write audit log
    - `generateCredentials()` — returns `['username', 'password']` using `generateUsername` + `generatePassword`
    - `getCurrentUser()` — JOIN `member_profiles`
    - `logAuditEvent($userId, $action, $table, $recordId, $oldValues, $newValues)`
    - _Requirements: 3.1–3.9, 4.1, 4.2, 4.7_

  - [ ]* 4.2 Write property test for valid login produces role-correct session
    - **Property 1: Valid login produces role-correct session**
    - **Validates: Requirements 3.1**

  - [ ]* 4.3 Write property test for invalid credentials are always rejected
    - **Property 2: Invalid credentials are always rejected**
    - **Validates: Requirements 3.2**

  - [ ]* 4.4 Write property test for expired sessions are invalid
    - **Property 3: Expired sessions are invalid**
    - **Validates: Requirements 3.4**

  - [ ]* 4.5 Write property test for passwords stored as bcrypt hashes
    - **Property 4: Passwords are always stored as bcrypt hashes**
    - **Validates: Requirements 3.5**

  - [ ]* 4.6 Write property test for logout destroys session and writes audit log
    - **Property 5: Logout destroys session and writes audit log**
    - **Validates: Requirements 3.6**

  - [ ]* 4.7 Write property test for members blocked from staff-only pages
    - **Property 6: Members are blocked from staff-only pages**
    - **Validates: Requirements 3.8**

  - [ ]* 4.8 Write property test for needs_password_change flag gates all pages
    - **Property 7: needs_password_change flag gates all pages**
    - **Validates: Requirements 4.1, 4.2**

  - [ ]* 4.9 Write property test for successful password change clears flag and writes audit log
    - **Property 9: Successful password change clears the flag and writes audit log**
    - **Validates: Requirements 4.7**

- [ ] 5. Checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Login pages and session flow
  - [x] 6.1 Implement `login.php` (Member login)
    - Black/Yellow Tailwind layout, mobile-first 375px
    - Large tap targets (min 44×44px), labelled fields
    - POST to `api/auth.php?action=login`, redirect to `change-password.php` if `needs_password_change`, else `chat.php` (member dashboard)
    - Display flash messages (session expired, account locked)
    - _Requirements: 3.1–3.3, 3.7, 3.9_

  - [x] 6.2 Implement `staff-login.php` (Trainer/Admin login)
    - Same layout as `login.php`; enforces `role IN ('trainer','admin')` on server
    - Redirect to `trainer-dashboard.php` or `admin-dashboard.php` based on role
    - _Requirements: 3.7_

  - [x] 6.3 Implement `api/auth.php` login/logout endpoints
    - POST `action=login` — calls `Auth::login()`, returns JSON with redirect URL
    - POST `action=logout` — calls `Auth::logout()`, returns JSON
    - Validate CSRF token on all POST requests
    - _Requirements: 3.1, 3.2, 3.3, 3.6, 19.3, 19.5_

  - [ ]* 6.4 Write property test for CSRF token validation rejects requests without valid tokens
    - **Property 45: CSRF token validation rejects requests without valid tokens**
    - **Validates: Requirements 19.3, 19.5**

- [x] 7. First-login password change (`change-password.php`)
  - Require auth; redirect to self if `needs_password_change = TRUE` (block all other navigation)
  - Display friendly explanation of why change is required
  - Real-time password strength indicator (weak/fair/strong) via inline JS
  - Inline validation per criterion (length, uppercase, lowercase, digit, special char) on blur
  - POST to `api/auth.php?action=change_password`; on success redirect to onboarding (new member) or dashboard
  - _Requirements: 4.1–4.7_

- [x] 8. Member onboarding (`onboarding.php`)
  - Multi-step form (3 steps) with visible progress indicator ("Step 1 of 3")
  - Step 1: weight (kg), height (cm), age — one field group per step
  - Step 2: fitness level (beginner/intermediate/advanced) with contextual helper text
  - Step 3: fitness goal selection (lose weight / build muscle / improve stamina)
  - Inline validation on blur; retain all values on error; prevent step progression until resolved
  - POST to `api/members.php?action=save_onboarding`; persist to `member_profiles` and `fitness_goals`
  - Show confirmation message on completion; redirect to `chat.php`
  - _Requirements: 5.1–5.8_

  - [ ]* 8.1 Write property test for health profile data persists correctly (round trip)
    - **Property 11: Health profile data persists correctly (round trip)**
    - **Validates: Requirements 5.6**

  - [ ]* 8.2 Write property test for fitness goal persists with status = 'active' (round trip)
    - **Property 12: Fitness goal persists with status = 'active' (round trip)**
    - **Validates: Requirements 5.7**

- [x] 9. Gemini API client (`includes/gemini.php`)
  - [x] 9.1 Implement `GeminiClient` class
    - `buildPrompt($userMessage, $memberProfile, $goal)` — inject system context, member profile block, active exercises list, structured format instruction (when `isStructuredRequest()` = true), user message
    - `isStructuredRequest($message)` — regex/keyword match for workout/meal plan intent
    - `sendRequest($prompt)` — POST to `GEMINI_API_URL` over HTTPS with `GEMINI_API_KEY` (server-side only), `GEMINI_TIMEOUT_SECONDS` timeout; returns `['success', 'text', 'error']`
    - On error: log via `error_log()`, return user-friendly message, never expose API key or raw error
    - _Requirements: 1.4, 6.2, 6.3, 6.5, 7.1_

  - [ ]* 9.2 Write property test for Gemini prompt always includes member profile and structured format instruction
    - **Property 13: Gemini prompt always includes member profile and structured format instruction**
    - **Validates: Requirements 6.2, 7.1**

  - [ ]* 9.3 Write property test for API errors never expose internal details to the member
    - **Property 14: API errors never expose internal details to the member**
    - **Validates: Requirements 6.5**

- [x] 10. AI chat API and interface
  - [x] 10.1 Implement `api/chat.php`
    - GET `action=history` — return last 20 `chat_messages` for current member, ordered by `created_at` DESC
    - POST `action=send_message` — check daily limit (`max_ai_requests_per_day`), fetch profile + goal, call `GeminiClient`, persist member message + AI response to `chat_messages`, if structured request parse JSON and insert into `ai_recommendations (status='pending')`, return response JSON
    - POST `action=clear_chat`
    - Return HTTP 429 with friendly message + reset time when daily limit exceeded
    - Validate CSRF token on all POST requests
    - _Requirements: 6.1–6.9, 7.1, 7.2, 19.3_

  - [x] 10.2 Implement `chat.php` (Member AI chat view)
    - Require `role = 'member'`; check `needs_password_change`; check maintenance mode
    - Chat bubble layout: AI messages (left, "AI Partner" label + icon) vs Trainer messages (right, trainer name + distinct background)
    - Persistent, non-dismissible AI disclaimer banner
    - Loading state (typing indicator) shown within 200ms of send
    - Load history on page load via `api/chat.php?action=history`
    - Recommendation cards: card-based layout, status badge, AI disclaimer at top of card
    - _Requirements: 6.1, 6.4, 6.7, 6.8, 6.10, 7.3, 7.4_

  - [x] 10.3 Implement `js/chat.js`
    - `sendMessage()` — POST to `api/chat.php`, show typing indicator immediately, render response bubble
    - `renderMessage(msg)` — distinguish AI vs Trainer vs Member bubbles
    - `renderRecommendationCard(rec)` — card layout with status badge and disclaimer
    - Toast notification on success/error (auto-dismiss 3s)
    - _Requirements: 6.4, 6.5, 7.3, 21.4, 21.5_

  - [ ]* 10.4 Write property test for every chat message is persisted (round trip)
    - **Property 15: Every chat message is persisted (round trip)**
    - **Validates: Requirements 6.6, 12.2**

  - [ ]* 10.5 Write property test for chat history returns at most 20 messages
    - **Property 16: Chat history returns at most 20 messages**
    - **Validates: Requirements 6.7**

  - [ ]* 10.6 Write property test for daily AI request limit is enforced
    - **Property 17: Daily AI request limit is enforced**
    - **Validates: Requirements 6.9**

  - [ ]* 10.7 Write property test for structured recommendations stored with status = 'pending' (round trip)
    - **Property 18: Structured recommendations are stored with status = 'pending' (round trip)**
    - **Validates: Requirements 7.2**

- [ ] 11. Checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 12. Member progress logging API (`api/members.php`)
  - [x] 12.1 Implement member API endpoints
    - GET `action=profile` — return member profile + goals + logs (accessible by member/trainer/admin)
    - POST `action=update_profile` — update `member_profiles`, validate fields
    - POST `action=save_onboarding` — upsert `member_profiles`, insert `fitness_goals`
    - POST `action=log_workout` — validate fields, insert into `workout_sessions`, return success
    - POST `action=log_weight` — check for existing entry on same date (return conflict flag if exists), upsert `weight_logs`
    - Validate CSRF token on all POST requests
    - _Requirements: 5.6, 5.7, 5.8, 8.1–8.6, 19.3_

  - [ ]* 12.2 Write property test for duplicate weight log for same date is detected
    - **Property 19: Duplicate weight log for same date is detected**
    - **Validates: Requirements 8.4, 2.7**

  - [ ]* 12.3 Write property test for workout log persists correctly (round trip)
    - **Property 20: Workout log persists correctly (round trip)**
    - **Validates: Requirements 8.5**

  - [ ]* 12.4 Write property test for weight log persists correctly (round trip)
    - **Property 21: Weight log persists correctly (round trip)**
    - **Validates: Requirements 8.6**

- [x] 13. Member dashboard (`member-dashboard.php`)
  - Require `role = 'member'`; check maintenance mode
  - Workout history: chronological list of past sessions (most recent first)
  - Weight progress chart: plot `weight_logs` over time using high-contrast Yellow-on-Black colours, axis labels readable at arm's length
  - Recommendations list: card layout with colour-coded status badges (pending/approved/modified/rejected), trainer notes visible when status ≠ pending
  - BMI display: calculate from most recent `weight_log` + stored `height_cm`; show category
  - Logging forms: workout log form + weight log form, date pre-populated with today, 44px min touch targets, confirmation dialog on duplicate weight date
  - Announcements banner: show active announcements matching `role = 'member'` or `target_audience = 'all'`
  - _Requirements: 8.1–8.7, 9.1–9.5, 15.2, 21.2, 21.3_

  - [ ]* 13.1 Write property test for workout history is returned in chronological order
    - **Property 22: Workout history is returned in chronological order**
    - **Validates: Requirements 9.1**

- [x] 14. Trainer dashboard and API
  - [x] 14.1 Implement `api/trainer.php`
    - GET `action=roster` — return members WHERE `assigned_trainer_id = $trainerId`
    - GET `action=pending_recommendations` — return `ai_recommendations WHERE status='pending'` for assigned members, ordered by `created_at` ASC
    - GET `action=member_detail` — full workout log, weight chart data, recommendations for one member
    - GET `action=messages` — unread messages from assigned members
    - POST `action=review_recommendation` — approve/modify/reject; reject requires non-empty `trainer_notes`; update `status`, `reviewed_by`, `trainer_notes`
    - POST `action=send_message` — insert into `chat_messages (sender_type='trainer')`
    - POST `action=mark_read` — set `is_read = TRUE`
    - Validate CSRF token on all POST requests
    - _Requirements: 10.1–10.4, 11.1–11.6, 12.1–12.6, 19.3_

  - [x] 14.2 Implement `trainer-dashboard.php` and `js/trainer-dashboard.js`
    - Require `role = 'trainer'`; check maintenance mode
    - Member roster: scannable summary cards (most recent weight, active goal, last login)
    - Pending recommendations badge count on nav
    - Member detail view: workout log history, weight chart, recommendations
    - Recommendation review: approve/modify/reject buttons (44px min, 8px spacing), status badges
    - Messaging interface: compose + send to assigned members; unread badge on nav; relative timestamps
    - Announcements banner for trainer audience
    - _Requirements: 10.1–10.4, 11.1–11.6, 12.1–12.6, 15.2_

  - [ ]* 14.3 Write property test for trainer roster contains only assigned members
    - **Property 24: Trainer roster contains only assigned members**
    - **Validates: Requirements 10.1**

  - [ ]* 14.4 Write property test for pending recommendation badge count matches database
    - **Property 25: Pending recommendation badge count matches database**
    - **Validates: Requirements 10.4**

  - [ ]* 14.5 Write property test for pending recommendations are sorted oldest-first
    - **Property 26: Pending recommendations are sorted oldest-first**
    - **Validates: Requirements 11.1**

  - [ ]* 14.6 Write property test for recommendation status transitions are persisted correctly
    - **Property 27: Recommendation status transitions are persisted correctly**
    - **Validates: Requirements 11.2, 11.3, 11.4**

  - [ ]* 14.7 Write property test for rejection requires non-empty trainer notes
    - **Property 28: Rejection requires non-empty trainer notes**
    - **Validates: Requirements 11.4**

  - [ ]* 14.8 Write property test for unread message badge count matches database
    - **Property 29: Unread message badge count matches database**
    - **Validates: Requirements 12.4**

  - [ ]* 14.9 Write property test for reading a message marks it as read
    - **Property 30: Reading a message marks it as read**
    - **Validates: Requirements 12.5**

- [ ] 15. Checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 16. Admin API (`api/admin.php`)
  - [x] 16.1 Implement user management endpoints
    - GET `action=users` — paginated user list
    - POST `action=create_user` — generate credentials, set `needs_password_change=TRUE`, optionally assign trainer, display credentials in response; write audit log
    - POST `action=update_user` — edit `first_name`, `last_name`, `email`, `phone`, `role`, `assigned_trainer_id`; write audit log
    - POST `action=suspend_user` — set `status='suspended'`, invalidate active session, write audit log
    - POST `action=delete_user` — soft-delete (`status='inactive'`), write audit log
    - All endpoints require `role = 'admin'`; return HTTP 403 otherwise
    - Validate CSRF token on all POST requests
    - _Requirements: 13.1–13.9, 19.3, 19.5_

  - [x] 16.2 Implement exercise library endpoints
    - POST `action=create_exercise` — insert into `exercises`
    - POST `action=update_exercise` — update any field
    - POST `action=deactivate_exercise` — set `is_active=FALSE`
    - GET `action=exercises` — return all exercises (admin view, including inactive)
    - _Requirements: 14.1–14.4_

  - [x] 16.3 Implement announcements endpoints
    - POST `action=create_announcement` — insert into `announcements`
    - POST `action=update_announcement` — update fields or set `is_active=FALSE`
    - _Requirements: 15.1–15.3_

  - [x] 16.4 Implement analytics and audit log endpoints
    - GET `action=analytics` — total active users by role, chat sessions per day, AI recommendations per day, avg workout rating; counts must match actual DB COUNT queries
    - GET `action=audit_log` — paginated, filterable by date range / user / action type; return at most `page_size` records per page
    - GET `action=settings` — return all `system_settings` rows
    - POST `action=save_settings` — update `system_settings`, apply immediately
    - GET `action=system_status` — DB connectivity + Gemini API availability
    - _Requirements: 16.1–16.4, 17.1, 17.4_

  - [ ]* 16.5 Write property test for account creation is restricted to admins
    - **Property 32: Account creation is restricted to admins**
    - **Validates: Requirements 13.1**

  - [ ]* 16.6 Write property test for new accounts have needs_password_change = TRUE and valid bcrypt hash
    - **Property 33: New accounts have needs_password_change = TRUE and a valid bcrypt hash**
    - **Validates: Requirements 13.2**

  - [ ]* 16.7 Write property test for user field edits are persisted correctly (round trip)
    - **Property 34: User field edits are persisted correctly (round trip)**
    - **Validates: Requirements 13.5**

  - [ ]* 16.8 Write property test for account suspension and soft-delete update status and write audit log
    - **Property 35: Account suspension and soft-delete update status and write audit log**
    - **Validates: Requirements 13.6, 13.7**

  - [ ]* 16.9 Write property test for deactivated exercises are excluded from active queries
    - **Property 36: Deactivated exercises are excluded from active queries**
    - **Validates: Requirements 14.3**

  - [ ]* 16.10 Write property test for announcements shown only to matching role audiences
    - **Property 37: Announcements are shown only to matching role audiences**
    - **Validates: Requirements 15.2**

  - [ ]* 16.11 Write property test for deactivated announcements are not displayed
    - **Property 38: Deactivated announcements are not displayed**
    - **Validates: Requirements 15.3**

  - [ ]* 16.12 Write property test for analytics counts match actual database records
    - **Property 39: Analytics counts match actual database records**
    - **Validates: Requirements 16.1**

  - [ ]* 16.13 Write property test for audit log pagination returns at most page_size records
    - **Property 40: Audit log pagination returns at most page_size records**
    - **Validates: Requirements 16.3**

  - [ ]* 16.14 Write property test for audit log filters return only matching records
    - **Property 41: Audit log filters return only matching records**
    - **Validates: Requirements 16.4**

  - [ ]* 16.15 Write property test for system settings changes are persisted and applied (round trip)
    - **Property 42: System settings changes are persisted and applied (round trip)**
    - **Validates: Requirements 17.1**

  - [ ]* 16.16 Write property test for maintenance mode blocks non-admin access
    - **Property 43: Maintenance mode blocks non-admin access**
    - **Validates: Requirements 17.2, 17.3**

- [x] 17. Admin dashboard (`admin-dashboard.php` and `js/admin-dashboard.js`)
  - Require `role = 'admin'`; maintenance mode does NOT block admin
  - User management panel: searchable/filterable table, create/edit/suspend/delete actions with confirmation dialogs, generated credentials display panel (copyable)
  - Exercise library panel: searchable/filterable table with sufficient row height for touch, create/edit/deactivate actions
  - Announcements panel: create/edit/deactivate announcements with target audience selector
  - Analytics panel: stats cards (active users by role, chat sessions/day, AI recs/day, avg rating), system status panel (DB + Gemini API)
  - Audit log panel: paginated table, date range / user / action type filters
  - System settings panel: editable fields for `session_timeout`, `max_ai_requests_per_day`, `password_min_length`, maintenance mode toggle
  - All destructive actions (suspend, delete) show confirmation dialog with plain-language consequences
  - _Requirements: 13.1–13.9, 14.1–14.4, 15.1–15.3, 16.1–16.4, 17.1–17.4_

- [x] 18. Landing page (`index.php`)
  - Public page, no auth required
  - Plain-language feature description, Member Login and Staff Login buttons (44px min, high contrast)
  - If authenticated: show role-appropriate dashboard link instead of login buttons
  - Live statistics: total active members, workouts generated — sourced from DB COUNT queries at render time
  - Fully responsive at 375px, no horizontal scrolling
  - _Requirements: 20.1–20.5_

  - [ ]* 18.1 Write property test for authenticated users see role-appropriate dashboard link on landing page
    - **Property 46: Authenticated users see role-appropriate dashboard link on landing page**
    - **Validates: Requirements 20.3**

  - [ ]* 18.2 Write property test for landing page live statistics match database counts
    - **Property 47: Landing page live statistics match database counts**
    - **Validates: Requirements 20.4**

- [x] 19. Maintenance mode middleware
  - Add maintenance mode check to the session flow in `includes/auth.php` (or a shared `includes/middleware.php`)
  - If `system_settings.maintenance_mode = 'true'` and `role ≠ 'admin'`, redirect to `maintenance.php`
  - Admin users bypass maintenance mode and access all pages normally
  - _Requirements: 17.2, 17.3_

- [x] 20. Global HCD and accessibility polish
  - Audit all pages: verify all interactive elements have `min-h-[44px] min-w-[44px]` and `p-3` minimum padding, 8px spacing between adjacent targets
  - Verify primary navigation is in the bottom 60% of screen on mobile (375px)
  - Add `alt` attributes to all images/icons, `for`/`id` pairing on all form labels, keyboard navigation for all interactive elements
  - Verify Black/Yellow contrast ratio ≥ 4.5:1 for body text, ≥ 3:1 for large headings across all pages
  - Ensure loading state (typing indicator, spinner) appears within 200ms on all async operations
  - Ensure toast notifications auto-dismiss after 3 seconds
  - Limit primary actions per screen to maximum 5; group secondary actions into contextual menus
  - Add positive reinforcement messages for: workout logged, onboarding completed, approved recommendation received
  - _Requirements: 1.5, 1.6, 1.7, 21.1–21.14_

- [ ] 21. Final checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

---

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Property-based tests use **eris** (`giorgiosironi/eris`), minimum 100 iterations, tagged with `// Feature: fitsense-web-app, Property {N}: {property_text}`
- Unit tests use **PHPUnit**
- Draft reference code in `draft/` folder can be used as a starting point for each component
- The Gemini API key must be loaded from environment variables, never hardcoded
- All DB queries use PDO prepared statements — no raw string interpolation in SQL
