# FitSense Web App — Technical Design

## Overview

FitSense is a gym management web application built with PHP, HTML, Tailwind CSS, and MySQL. It serves three roles — Member, Trainer, and Admin — and integrates the Gemini API for AI-powered fitness and nutrition recommendations. The design is mobile-first (375px minimum), optimised for one-handed gym-floor use, and follows a Black and Yellow high-contrast theme.

All accounts are Admin-created. There is no public registration. The system is a traditional server-rendered PHP application with JSON API endpoints for dynamic interactions (AJAX calls from the browser).

### Key Design Decisions

- Server-rendered PHP pages with AJAX-driven dynamic sections (no SPA framework) keeps the stack simple and deployable on standard shared hosting.
- Tailwind CSS via CDN or build step provides utility-first styling without a heavy CSS framework.
- Gemini API is called server-side only; the API key never reaches the browser.
- Sessions are PHP native sessions stored server-side; session IDs are transmitted via HttpOnly cookies.
- Soft-delete pattern for all user records preserves audit integrity.

---

## Architecture

The system follows a three-layer architecture:

```
┌─────────────────────────────────────────────────────────────┐
│                     PRESENTATION LAYER                       │
│  PHP/HTML views + Tailwind CSS + vanilla JS (AJAX)          │
│  login.php, staff-login.php, chat.php, dashboards, etc.     │
└────────────────────────┬────────────────────────────────────┘
                         │ HTTP requests / JSON responses
┌────────────────────────▼────────────────────────────────────┐
│                      LOGIC LAYER                             │
│  includes/auth.php   — session & role enforcement           │
│  includes/functions.php — utilities, sanitisation           │
│  api/*.php           — JSON endpoints (AJAX)                │
│  includes/gemini.php — Gemini API client                    │
└────────────────────────┬────────────────────────────────────┘
                         │ PDO prepared statements
┌────────────────────────▼────────────────────────────────────┐
│                       DATA LAYER                             │
│  MySQL (fitsense_db) — all persistent state                 │
│  config/database.php — connection + constants               │
└─────────────────────────────────────────────────────────────┘
```

### System Architecture Diagram

```
Browser (375px+)
     │
     │  HTTPS
     ▼
┌──────────────────────────────────────────────────────┐
│  PHP Web Server (Apache / Nginx + PHP-FPM)           │
│                                                      │
│  ┌─────────────┐  ┌──────────────┐  ┌────────────┐  │
│  │  Page Views │  │  API Routes  │  │  Auth Gate │  │
│  │  *.php      │  │  api/*.php   │  │  auth.php  │  │
│  └──────┬──────┘  └──────┬───────┘  └─────┬──────┘  │
│         └────────────────┴────────────────┘         │
│                          │                           │
│              ┌───────────▼──────────┐                │
│              │   Business Logic     │                │
│              │  functions.php       │                │
│              │  gemini.php          │                │
│              └───────────┬──────────┘                │
│                          │ PDO                       │
│              ┌───────────▼──────────┐                │
│              │   MySQL Database     │                │
│              │   fitsense_db        │                │
│              └──────────────────────┘                │
└──────────────────────────────────────────────────────┘
                          │
                          │ HTTPS (server-side only)
                          ▼
              ┌───────────────────────┐
              │   Google Gemini API   │
              │   generativelanguage  │
              │   .googleapis.com     │
              └───────────────────────┘
```

### Request / Session Flow

```
User visits page
      │
      ▼
session_start() → check $_SESSION['user_id'] + login_time
      │
      ├─ No session / expired ──→ redirect login.php (or staff-login.php)
      │
      ├─ needs_password_change = TRUE ──→ redirect change-password.php
      │
      ├─ maintenance_mode = TRUE + role ≠ admin ──→ maintenance.php
      │
      └─ Valid session + correct role ──→ serve requested page
```

---

## File / Folder Structure

```
fitsense/
├── config/
│   └── database.php          # DB connection class + all constants (API keys, timeouts)
├── includes/
│   ├── auth.php              # Auth class: login, logout, requireRole, session validation
│   ├── functions.php         # Utilities: sanitize, formatDate, BMI, sendJson, CSRF helpers
│   └── gemini.php            # GeminiClient class: buildPrompt, sendRequest, parseResponse
├── api/
│   ├── auth.php              # POST login / logout endpoints
│   ├── chat.php              # GET history, POST send_message (calls Gemini)
│   ├── members.php           # CRUD for member profiles, goals, logs
│   ├── trainer.php           # Trainer roster, recommendation review, messaging
│   └── admin.php             # User management, settings, analytics, audit log
├── js/
│   ├── chat.js               # Chat UI: send message, render bubbles, loading state
│   ├── admin-dashboard.js    # Admin SPA-like section switching, user table
│   └── trainer-dashboard.js  # Trainer section switching, member cards
├── index.php                 # Public landing page
├── login.php                 # Member login form
├── staff-login.php           # Trainer / Admin login form
├── logout.php                # Session destroy + redirect
├── change-password.php       # Mandatory first-login password change
├── onboarding.php            # Multi-step member health profile setup
├── chat.php                  # Member AI chat interface (requires member role)
├── member-dashboard.php      # Member progress dashboard (logs, charts, recommendations)
├── trainer-dashboard.php     # Trainer overview, roster, recommendation review, messages
├── admin-dashboard.php       # Admin overview, user management, analytics, settings
├── maintenance.php           # Shown to non-admins when maintenance_mode = true
├── unauthorized.php          # Shown on role mismatch
└── database/
    └── schema.sql            # Full DDL for fitsense_db
```

---

## Components and Interfaces

### Auth Component (`includes/auth.php`)

```php
class Auth {
    public function login(string $username, string $password): array
        // Returns: ['success' => bool, 'user' => array|null, 'needs_password_change' => bool]

    public function logout(): void
        // Destroys session, logs audit event

    public function isSessionValid(): bool
        // Checks login_time against SESSION_TIMEOUT constant

    public function requireRole(string $role, string $redirect = 'unauthorized.php'): void
        // Calls requireAuth() then checks role; redirects on mismatch

    public function requireAnyRole(array $roles, string $redirect = 'unauthorized.php'): void

    public function getCurrentUser(): array|null
        // Fetches full user + member_profiles row for current session user

    public function changePassword(int $userId, string $newPassword): array
        // Hashes with PASSWORD_BCRYPT, sets needs_password_change=FALSE, logs audit

    public function generateCredentials(): array
        // Returns ['username' => string, 'password' => string] — used by admin account creation
}
```

### Gemini Client (`includes/gemini.php`)

```php
class GeminiClient {
    public function buildPrompt(string $userMessage, array $memberProfile, array $goal): string
        // Appends health profile + goal context to user message
        // Injects structured-format instruction when message requests workout/meal plan

    public function sendRequest(string $prompt): array
        // POST to Gemini API over HTTPS with API key from config
        // Returns: ['success' => bool, 'text' => string, 'error' => string|null]

    public function isStructuredRequest(string $message): bool
        // Returns true if message matches workout/meal plan intent patterns
}
```

### API Endpoints

All API files return `Content-Type: application/json`. All state-changing endpoints validate the CSRF token from the `X-CSRF-Token` request header or `_csrf` POST field.

| File | Method | Action | Auth Required |
|------|--------|--------|---------------|
| `api/chat.php` | GET | `history` — last 20 messages | member |
| `api/chat.php` | POST | `send_message` — calls Gemini, persists, returns response | member |
| `api/chat.php` | POST | `clear_chat` | member |
| `api/members.php` | GET | `profile` — member profile + goals + logs | member/trainer/admin |
| `api/members.php` | POST | `update_profile` | member |
| `api/members.php` | POST | `log_workout` | member |
| `api/members.php` | POST | `log_weight` | member |
| `api/trainer.php` | GET | `roster` — assigned members | trainer |
| `api/trainer.php` | GET | `pending_recommendations` | trainer |
| `api/trainer.php` | POST | `review_recommendation` — approve/modify/reject | trainer |
| `api/trainer.php` | POST | `send_message` | trainer |
| `api/admin.php` | GET | `users` | admin |
| `api/admin.php` | GET | `analytics` | admin |
| `api/admin.php` | GET | `audit_log` | admin |
| `api/admin.php` | GET | `settings` | admin |
| `api/admin.php` | POST | `create_user` | admin |
| `api/admin.php` | POST | `update_user` | admin |
| `api/admin.php` | POST | `suspend_user` | admin |
| `api/admin.php` | POST | `delete_user` (soft) | admin |
| `api/admin.php` | POST | `save_settings` | admin |
| `api/admin.php` | POST | `create_exercise` | admin |
| `api/admin.php` | POST | `update_exercise` | admin |
| `api/admin.php` | POST | `create_announcement` | admin |
| `api/admin.php` | POST | `update_announcement` | admin |

### UI Component Patterns

**Toast Notification** — rendered by JS, auto-dismisses after 3 seconds:
```html
<div class="toast toast-success" role="alert" aria-live="polite">Workout logged</div>
```

**AI Disclaimer** — non-dismissible, appears on every AI content screen:
```html
<div class="ai-disclaimer border border-yellow-400 bg-black text-yellow-300 p-3 rounded text-sm">
  ⚠️ This advice is AI-generated and is not a substitute for professional medical or fitness guidance.
  Consult a qualified professional before making changes to your exercise or diet.
</div>
```

**Loading State** — typing indicator shown within 200ms of message send:
```html
<div class="typing-indicator" aria-label="AI is responding">
  <span></span><span></span><span></span>
</div>
```

**Status Badge** — colour-coded recommendation status:
```html
<span class="badge badge-approved">Approved by Trainer</span>
<span class="badge badge-pending">Pending Review</span>
<span class="badge badge-modified">Modified</span>
<span class="badge badge-rejected">Rejected</span>
```

**Touch Target Rule** — all interactive elements use Tailwind `min-h-[44px] min-w-[44px]` and `p-3` minimum padding. Navigation items use `py-3 px-4`.

---

## Data Models

### Entity Relationship Diagram

```
users (id PK)
  ├──< member_profiles (user_id FK) — 1:1 for members
  ├──< fitness_goals (user_id FK) — 1:many
  ├──< ai_recommendations (user_id FK) — 1:many
  │       └── reviewed_by FK → users.id
  ├──< workout_sessions (user_id FK) — 1:many
  │       └── recommendation_id FK → ai_recommendations.id (nullable)
  ├──< weight_logs (user_id FK) — 1:many, UNIQUE(user_id, log_date)
  ├──< chat_messages (user_id FK) — 1:many
  │       └── sender_id FK → users.id (nullable, null = AI)
  └──< audit_logs (user_id FK, nullable)

member_profiles
  └── assigned_trainer_id FK → users.id (nullable)

exercises (created_by FK → users.id, nullable)

announcements (created_by FK → users.id)

system_settings (updated_by FK → users.id, nullable)
```

### Table Definitions (key fields)

**users**
```sql
id INT PK AUTO_INCREMENT
username VARCHAR(50) UNIQUE NOT NULL
email VARCHAR(100) UNIQUE
password_hash VARCHAR(255) NOT NULL          -- bcrypt only
role ENUM('member','trainer','admin') NOT NULL
first_name VARCHAR(50) NOT NULL
last_name VARCHAR(50) NOT NULL
phone VARCHAR(20)
status ENUM('active','inactive','suspended') DEFAULT 'active'
needs_password_change BOOLEAN DEFAULT TRUE
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
last_login TIMESTAMP NULL
```

**member_profiles**
```sql
id INT PK AUTO_INCREMENT
user_id INT NOT NULL FK → users(id) ON DELETE CASCADE
age INT
height_cm DECIMAL(5,2)                       -- validated: 50–300
current_weight_kg DECIMAL(5,2)               -- validated: 20–500
target_weight_kg DECIMAL(5,2)
fitness_level ENUM('beginner','intermediate','advanced') DEFAULT 'beginner'
medical_conditions TEXT
emergency_contact_name VARCHAR(100)
emergency_contact_phone VARCHAR(20)
membership_start DATE
membership_end DATE
assigned_trainer_id INT FK → users(id) ON DELETE SET NULL
```

**fitness_goals**
```sql
id INT PK AUTO_INCREMENT
user_id INT NOT NULL FK → users(id) ON DELETE CASCADE
goal_type ENUM('lose_weight','build_muscle','improve_stamina','maintain_fitness','other')
description TEXT
target_value DECIMAL(8,2)
target_unit VARCHAR(20)
target_date DATE
status ENUM('active','completed','paused') DEFAULT 'active'
```

**ai_recommendations**
```sql
id INT PK AUTO_INCREMENT
user_id INT NOT NULL FK → users(id) ON DELETE CASCADE
type ENUM('workout','meal_plan','general_advice') NOT NULL
title VARCHAR(255) NOT NULL
content JSON NOT NULL
ai_prompt TEXT
ai_response TEXT
status ENUM('pending','approved','rejected','modified') DEFAULT 'pending'
reviewed_by INT FK → users(id) ON DELETE SET NULL
trainer_notes TEXT
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

**workout_sessions**
```sql
id INT PK AUTO_INCREMENT
user_id INT NOT NULL FK → users(id) ON DELETE CASCADE
recommendation_id INT FK → ai_recommendations(id) ON DELETE SET NULL
session_date DATE NOT NULL
duration_minutes INT
exercises_completed JSON
notes TEXT
rating INT CHECK (rating BETWEEN 1 AND 5)
calories_burned INT
```

**weight_logs**
```sql
id INT PK AUTO_INCREMENT
user_id INT NOT NULL FK → users(id) ON DELETE CASCADE
weight_kg DECIMAL(5,2) NOT NULL
log_date DATE NOT NULL
notes TEXT
UNIQUE KEY unique_user_date (user_id, log_date)
```

**chat_messages**
```sql
id INT PK AUTO_INCREMENT
user_id INT NOT NULL FK → users(id) ON DELETE CASCADE   -- the member this thread belongs to
sender_type ENUM('member','trainer','ai') NOT NULL
sender_id INT FK → users(id) ON DELETE SET NULL         -- NULL when sender_type = 'ai'
message TEXT NOT NULL
message_type ENUM('text','recommendation','system') DEFAULT 'text'
is_read BOOLEAN DEFAULT FALSE
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

**system_settings** (key-value store)
```sql
setting_key VARCHAR(100) UNIQUE NOT NULL
setting_value TEXT
-- Default rows: maintenance_mode, max_ai_requests_per_day, session_timeout, password_min_length
```

---

## Gemini API Integration Design

### Prompt Construction

Every chat message sent to Gemini is enriched server-side before dispatch:

```
[SYSTEM CONTEXT]
You are an AI fitness coach for FitSense gym. Respond in a friendly, motivating tone.
Always include the AI disclaimer at the end of workout or nutrition advice.
Only provide fitness, nutrition, and wellness guidance.

[MEMBER PROFILE]
Name: {first_name}
Age: {age} | Height: {height_cm}cm | Weight: {current_weight_kg}kg
Fitness Level: {fitness_level}
Active Goal: {goal_type} — {goal_description}

[ACTIVE EXERCISES IN LIBRARY]
{comma-separated list of active exercise names}

[STRUCTURED FORMAT INSTRUCTION — only included when isStructuredRequest() = true]
Return workout plans in this exact JSON structure:
{
  "title": "...",
  "exercises": [{"name":"...","sets":N,"reps":N,"rest_seconds":N,"notes":"..."}],
  "duration_minutes": N
}
Return meal plans in this exact JSON structure:
{
  "title": "...",
  "meals": [{"name":"...","ingredients":["..."],"protein_g":N,"carbs_g":N,"fat_g":N,"calories":N}]
}

[USER MESSAGE]
{user_message}
```

### API Call Flow

```
Member sends message
      │
      ▼
api/chat.php — POST send_message
      │
      ├─ Check daily request count (ai_recommendations + chat_messages for today)
      │   └─ If ≥ max_ai_requests_per_day → return 429 with friendly message
      │
      ├─ Fetch member profile + active goal from DB
      │
      ├─ GeminiClient::buildPrompt($message, $profile, $goal)
      │
      ├─ GeminiClient::sendRequest($prompt)
      │   └─ POST https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent
      │       Authorization: Bearer {GEMINI_API_KEY}  (server-side only)
      │
      ├─ On success:
      │   ├─ Persist member message to chat_messages (sender_type='member')
      │   ├─ Persist AI response to chat_messages (sender_type='ai', sender_id=NULL)
      │   ├─ If isStructuredRequest: parse JSON, store in ai_recommendations (status='pending')
      │   └─ Return response JSON to browser
      │
      └─ On error / timeout:
          ├─ Log error server-side (error_log)
          └─ Return user-friendly error message (no API details exposed)
```

### Configuration Constants (`config/database.php`)

```php
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY'));   // loaded from environment / .env
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent');
define('GEMINI_TIMEOUT_SECONDS', 30);
define('SESSION_TIMEOUT', 3600);
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('BASE_URL', 'http://localhost/fitsense/');
```

The API key is loaded from a server environment variable or a `.env` file that is excluded from version control. It is never embedded in PHP source files committed to the repository.

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Valid login produces role-correct session

*For any* active user record with a known plaintext password, calling `Auth::login()` with those credentials should return `success = true` and set `$_SESSION['role']` to the user's actual role.

**Validates: Requirements 3.1**

---

### Property 2: Invalid credentials are always rejected

*For any* username/password pair where the password does not match the stored bcrypt hash (or the user does not exist), `Auth::login()` should return `success = false` and increment the failed-attempt counter without revealing which field was wrong.

**Validates: Requirements 3.2**

---

### Property 3: Expired sessions are invalid

*For any* session where `time() - $_SESSION['login_time'] >= SESSION_TIMEOUT`, `Auth::isSessionValid()` should return `false`.

**Validates: Requirements 3.4**

---

### Property 4: Passwords are always stored as bcrypt hashes

*For any* password string passed to `Auth::changePassword()` or the account-creation flow, the value stored in `users.password_hash` should start with `$2y$` (bcrypt prefix) and `password_verify($plaintext, $hash)` should return `true`.

**Validates: Requirements 3.5**

---

### Property 5: Logout destroys session and writes audit log

*For any* authenticated session, calling `Auth::logout()` should result in `$_SESSION` being empty and a new row in `audit_logs` with `action = 'logout'` and the correct `user_id`.

**Validates: Requirements 3.6**

---

### Property 6: Members are blocked from staff-only pages

*For any* page that calls `$auth->requireRole('trainer')` or `$auth->requireRole('admin')`, a request carrying a session with `role = 'member'` should be redirected to `unauthorized.php` and never reach the page body.

**Validates: Requirements 3.8**

---

### Property 7: needs_password_change flag gates all pages

*For any* user with `needs_password_change = TRUE`, any page that calls `Auth::requireAuth()` should redirect to `change-password.php` before rendering any content.

**Validates: Requirements 4.1, 4.2**

---

### Property 8: Password complexity validation rejects non-compliant passwords

*For any* candidate password string, the server-side validation function should return `false` (invalid) if and only if the string is shorter than 8 characters, or lacks at least one uppercase letter, one lowercase letter, one digit, or one special character.

**Validates: Requirements 4.4**

---

### Property 9: Successful password change clears the flag and writes audit log

*For any* user, after `Auth::changePassword()` succeeds, `users.needs_password_change` should be `FALSE` and `audit_logs` should contain a row with `action = 'password_changed'` and the correct `user_id`.

**Validates: Requirements 4.7**

---

### Property 10: Health profile validation enforces range constraints

*For any* combination of weight, height, and age values, the server-side validation function should accept the input if and only if weight is in [20, 500] kg, height is in [50, 300] cm, and age is in [10, 120].

**Validates: Requirements 5.3**

---

### Property 11: Health profile data persists correctly (round trip)

*For any* valid member profile data submitted through the onboarding flow, querying `member_profiles` by `user_id` should return the same values that were submitted.

**Validates: Requirements 5.6**

---

### Property 12: Fitness goal persists with status = 'active' (round trip)

*For any* goal type selected during onboarding or profile update, querying `fitness_goals` for that `user_id` with `status = 'active'` should return a row with the submitted `goal_type`.

**Validates: Requirements 5.7**

---

### Property 13: Gemini prompt always includes member profile and structured format instruction

*For any* member message, `GeminiClient::buildPrompt()` should produce a string that contains the member's `fitness_level`, `goal_type`, and — when `isStructuredRequest()` returns `true` — the JSON structure instruction for workouts or meal plans.

**Validates: Requirements 6.2, 7.1**

---

### Property 14: API errors never expose internal details to the member

*For any* Gemini API call that throws an exception or returns a non-200 status, the JSON response returned to the browser should not contain the raw API error message, HTTP status code from Gemini, or the API key.

**Validates: Requirements 6.5**

---

### Property 15: Every chat message is persisted (round trip)

*For any* message sent by a member or trainer, after the API call completes, querying `chat_messages` by `user_id` should include a row with the correct `message` text and `sender_type`.

**Validates: Requirements 6.6, 12.2**

---

### Property 16: Chat history returns at most 20 messages

*For any* member with N messages in `chat_messages` (N ≥ 20), the `api/chat.php?action=history` endpoint should return exactly 20 messages ordered by `created_at` descending.

**Validates: Requirements 6.7**

---

### Property 17: Daily AI request limit is enforced

*For any* member who has already made `max_ai_requests_per_day` requests today, the next `send_message` call should return an HTTP 429 response with a user-friendly message and no Gemini API call should be made.

**Validates: Requirements 6.9**

---

### Property 18: Structured recommendations are stored with status = 'pending' (round trip)

*For any* structured workout or meal plan response from Gemini, after processing, `ai_recommendations` should contain a row with `user_id` matching the requesting member, `status = 'pending'`, and `type` matching the request type.

**Validates: Requirements 7.2**

---

### Property 19: Duplicate weight log for same date is detected

*For any* member who already has a `weight_logs` entry for a given date, attempting to insert another entry for the same date without the overwrite confirmation should be rejected (the database UNIQUE constraint on `(user_id, log_date)` should prevent silent overwrites).

**Validates: Requirements 8.4, 2.7**

---

### Property 20: Workout log persists correctly (round trip)

*For any* valid workout log submission, querying `workout_sessions` by `user_id` should include a row with the submitted `session_date`, `duration_minutes`, and `rating`.

**Validates: Requirements 8.5**

---

### Property 21: Weight log persists correctly (round trip)

*For any* valid weight log submission, querying `weight_logs` by `user_id` and `log_date` should return the submitted `weight_kg`.

**Validates: Requirements 8.6**

---

### Property 22: Workout history is returned in chronological order

*For any* member with multiple workout sessions, the history endpoint should return sessions ordered by `session_date` descending (most recent first).

**Validates: Requirements 9.1**

---

### Property 23: BMI calculation is correct

*For any* weight in kg and height in cm where both are positive, `calculateBMI(weight, height)` should return `round(weight / (height/100)^2, 1)`.

**Validates: Requirements 9.4**

---

### Property 24: Trainer roster contains only assigned members

*For any* trainer, the `api/trainer.php?action=roster` endpoint should return only members whose `member_profiles.assigned_trainer_id` equals the authenticated trainer's `user_id`.

**Validates: Requirements 10.1**

---

### Property 25: Pending recommendation badge count matches database

*For any* trainer, the count of pending recommendations shown in the dashboard should equal the count of rows in `ai_recommendations` with `status = 'pending'` for members assigned to that trainer.

**Validates: Requirements 10.4**

---

### Property 26: Pending recommendations are sorted oldest-first

*For any* trainer's list of pending recommendations, the items should be ordered by `created_at` ascending (oldest submission first).

**Validates: Requirements 11.1**

---

### Property 27: Recommendation status transitions are persisted correctly

*For any* recommendation, after a trainer calls approve, modify, or reject:
- approve → `status = 'approved'`, `reviewed_by = trainer_id`
- modify → `status = 'modified'`, `trainer_notes` updated, `reviewed_by = trainer_id`
- reject → `status = 'rejected'`, `trainer_notes` non-empty, `reviewed_by = trainer_id`

**Validates: Requirements 11.2, 11.3, 11.4**

---

### Property 28: Rejection requires non-empty trainer notes

*For any* rejection attempt where `trainer_notes` is empty or whitespace-only, the API should return an error and the recommendation's status should remain unchanged.

**Validates: Requirements 11.4**

---

### Property 29: Unread message badge count matches database

*For any* trainer, the unread message badge count should equal the count of rows in `chat_messages` with `sender_type = 'member'`, `is_read = FALSE`, and `user_id` belonging to an assigned member.

**Validates: Requirements 12.4**

---

### Property 30: Reading a message marks it as read

*For any* unread message, after the trainer views it via the messages endpoint, `chat_messages.is_read` should be `TRUE` for that message.

**Validates: Requirements 12.5**

---

### Property 31: Timestamp formatting produces relative strings

*For any* timestamp, `formatRelativeTime($timestamp)` should return a non-empty string that does not contain a raw ISO date (e.g., "2 hours ago", "Yesterday", "Just now") and never returns the raw `Y-m-d H:i:s` format.

**Validates: Requirements 12.6**

---

### Property 32: Account creation is restricted to admins

*For any* request to `api/admin.php?action=create_user` made by a session with `role ≠ 'admin'`, the response should be HTTP 403 and no user row should be created.

**Validates: Requirements 13.1**

---

### Property 33: New accounts have needs_password_change = TRUE and a valid bcrypt hash

*For any* account created by an admin, the resulting `users` row should have `needs_password_change = TRUE` and `password_hash` should be a valid bcrypt hash (verifiable with `password_verify`).

**Validates: Requirements 13.2**

---

### Property 34: User field edits are persisted correctly (round trip)

*For any* valid admin edit of `first_name`, `last_name`, `email`, `phone`, `role`, or `assigned_trainer_id`, querying the `users` row after the update should reflect the new values.

**Validates: Requirements 13.5**

---

### Property 35: Account suspension and soft-delete update status and write audit log

*For any* account, after an admin suspends it (`status = 'suspended'`) or soft-deletes it (`status = 'inactive'`), the `users` row should have the new status and `audit_logs` should contain a row recording the action with the admin's `user_id`.

**Validates: Requirements 13.6, 13.7**

---

### Property 36: Deactivated exercises are excluded from active queries

*For any* exercise with `is_active = FALSE`, it should not appear in queries that filter `WHERE is_active = TRUE` (used for AI prompts and member-facing exercise lists).

**Validates: Requirements 14.3**

---

### Property 37: Announcements are shown only to matching role audiences

*For any* active announcement with `target_audience = 'members'`, it should not be returned for users with `role = 'trainer'` or `role = 'admin'`, and vice versa. Announcements with `target_audience = 'all'` should be returned for all roles.

**Validates: Requirements 15.2**

---

### Property 38: Deactivated announcements are not displayed

*For any* announcement with `is_active = FALSE`, it should not appear in the dashboard banner query results.

**Validates: Requirements 15.3**

---

### Property 39: Analytics counts match actual database records

*For any* time period, the analytics endpoint's `total_members`, `chat_sessions`, and `workouts_generated` values should equal the actual COUNT queries against `users`, `chat_messages`, and `ai_recommendations` for that period.

**Validates: Requirements 16.1**

---

### Property 40: Audit log pagination returns at most page_size records

*For any* page request to the audit log endpoint with a given `page_size`, the returned array should contain at most `page_size` items.

**Validates: Requirements 16.3**

---

### Property 41: Audit log filters return only matching records

*For any* combination of date range, user_id, and action type filters applied to the audit log endpoint, every returned record should satisfy all applied filter conditions.

**Validates: Requirements 16.4**

---

### Property 42: System settings changes are persisted and applied (round trip)

*For any* valid setting key updated by an admin, querying `system_settings` after the save should return the new value, and subsequent requests should use the new value (e.g., updated `session_timeout` should be used in `isSessionValid()`).

**Validates: Requirements 17.1**

---

### Property 43: Maintenance mode blocks non-admin access

*For any* user with `role ≠ 'admin'` when `maintenance_mode = 'true'` in `system_settings`, any page request should redirect to `maintenance.php`. Admin users should be able to access all pages normally.

**Validates: Requirements 17.2, 17.3**

---

### Property 44: HTML output escaping prevents XSS

*For any* string containing HTML special characters (`<`, `>`, `"`, `'`, `&`) that is stored in the database and then rendered in a PHP view, the rendered HTML should contain the escaped entity equivalents (`&lt;`, `&gt;`, etc.) and not the raw characters.

**Validates: Requirements 19.2**

---

### Property 45: CSRF token validation rejects requests without valid tokens

*For any* POST request to a state-changing endpoint that does not include a valid CSRF token matching the session's token, the response should be HTTP 403 and no state change should occur.

**Validates: Requirements 19.3, 19.5**

---

### Property 46: Authenticated users see role-appropriate dashboard link on landing page

*For any* authenticated user visiting `index.php`, the rendered HTML should contain a link to their role-appropriate dashboard (`chat.php` for members, `trainer-dashboard.php` for trainers, `admin-dashboard.php` for admins) and should not contain the unauthenticated login buttons.

**Validates: Requirements 20.3**

---

### Property 47: Landing page live statistics match database counts

*For any* state of the database, the statistics displayed on `index.php` (total active members, workouts generated, success stories) should equal the results of the corresponding COUNT queries at the time of page render.

**Validates: Requirements 20.4**

---

## Error Handling

### Authentication Errors

| Scenario | Behaviour |
|----------|-----------|
| Invalid credentials | Return generic "Incorrect username or password" — no field-level hint |
| Account locked (5 failed attempts) | Display lock message, instruct user to contact Admin |
| Session expired | Redirect to login with "Your session has expired" message |
| Role mismatch | Redirect to `unauthorized.php` with plain-language explanation |
| needs_password_change = TRUE | Redirect to `change-password.php` before any other page |

### Gemini API Errors

| Scenario | Behaviour |
|----------|-----------|
| API timeout (> 30s) | Return `{"error": "Something went wrong — please try again"}`, log full error server-side |
| Non-200 HTTP response | Same as timeout — user-friendly message, no API details exposed |
| Malformed JSON response | Log parse error, return user-friendly message |
| Daily limit exceeded | Return HTTP 429 with friendly message and reset time |

### Form Validation Errors

- All validation is performed server-side first; client-side JS validation is a UX enhancement only.
- Inline validation messages are displayed adjacent to the relevant field on blur.
- Forms are never cleared on validation failure — all valid field values are retained.
- Each unmet password criterion is listed individually (not as a single generic message).

### Database Errors

- All DB operations are wrapped in try/catch blocks.
- Exceptions are logged via `error_log()` and never exposed to the user.
- Transactions are used for multi-step operations (account creation, recommendation review) with `rollBack()` on failure.
- A generic "Something went wrong" message is shown to the user on unexpected DB errors.

### CSRF / Security Errors

- Missing or invalid CSRF token → HTTP 403, log event to `audit_logs`.
- Requests to admin/trainer API endpoints from wrong role → HTTP 403.
- Maintenance mode active + non-admin → redirect to `maintenance.php`.

---

## Testing Strategy

### Dual Testing Approach

Both unit tests and property-based tests are required. They are complementary:

- **Unit tests** verify specific examples, integration points, and edge cases.
- **Property-based tests** verify universal correctness across many generated inputs.

### Unit Tests (PHPUnit)

Focus areas:

- `Auth::login()` with known valid and invalid credentials (specific examples)
- `Auth::changePassword()` with a known user — verify hash and flag
- `GeminiClient::buildPrompt()` with a known profile — verify output contains expected substrings
- `GeminiClient::isStructuredRequest()` with specific workout/meal plan phrases
- `calculateBMI()` with known weight/height pairs
- `formatRelativeTime()` with specific timestamps (e.g., 1 hour ago, yesterday)
- Password complexity validator with specific passing and failing strings
- Health profile range validator with boundary values (20, 500, 50, 300, 10, 120)
- CSRF token generation and validation
- Announcement audience filtering with specific role/audience combinations
- Audit log filter query with specific date ranges and action types

### Property-Based Tests (PHPUnit + eris or php-quickcheck)

Use **eris** (`giorgiosironi/eris`) or **php-quickcheck** for PHP property-based testing. Configure each test to run a minimum of **100 iterations**.

Each property test must be tagged with a comment in the format:
`// Feature: fitsense-web-app, Property {N}: {property_text}`

**Properties to implement as property-based tests:**

| Property | Test Description |
|----------|-----------------|
| P4 | For any password string, stored hash is bcrypt and verifies correctly |
| P8 | For any string, complexity validator accepts iff all 4 criteria met and length ≥ 8 |
| P10 | For any weight/height/age triple, range validator accepts iff all in bounds |
| P13 | For any member profile + message, buildPrompt output contains profile fields |
| P16 | For any N ≥ 20 messages, history endpoint returns exactly 20 |
| P22 | For any set of workout sessions, history is ordered by session_date DESC |
| P23 | For any positive weight/height, BMI = round(w / (h/100)^2, 1) |
| P26 | For any set of pending recommendations, list is ordered by created_at ASC |
| P31 | For any timestamp, formatRelativeTime returns a relative string (not ISO format) |
| P37 | For any announcement + user role, audience filter returns correct subset |
| P40 | For any page request, audit log returns at most page_size records |
| P41 | For any filter combination, all returned audit log records satisfy the filter |
| P44 | For any string with HTML special chars, htmlspecialchars output contains no raw chars |
| P45 | For any POST without valid CSRF token, response is 403 and state is unchanged |

### Integration Tests

- Full login → dashboard redirect flow for each role
- First-login → change-password → onboarding → chat flow for a new member
- Admin creates user → user logs in with generated credentials → forced password change
- Trainer approves recommendation → member sees updated status badge
- Maintenance mode enabled → non-admin redirected → admin still has access

### Manual / Exploratory Testing

- Mobile layout at 375px, 390px (iPhone 14), 768px (tablet), 1280px (desktop)
- One-handed operation: all primary actions reachable in bottom 60% of screen
- Gym-floor lighting simulation: verify 4.5:1 contrast ratio on Black/Yellow theme
- AI disclaimer visibility on all AI content screens
- Toast notification auto-dismiss timing (3 seconds)
- Loading state appearance within 200ms of message send
