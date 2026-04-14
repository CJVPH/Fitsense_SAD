# Requirements Document

## Introduction

FitSense is a Human-Centered Design (HCD) gym management web application built with HTML, CSS, PHP, and Tailwind CSS using a high-contrast Black and Yellow theme. The system is designed around the real-world context of gym floor use — users are often sweaty, distracted, and operating one-handed on a mobile device. Every interface decision prioritises clarity, speed, and reduced cognitive load. The system supports three user roles — Members, Trainers, and Admins — and integrates with the Gemini API to deliver AI-powered workout and nutrition recommendations. All accounts are created exclusively by Admins; there is no public self-registration.

## Glossary

- **System**: The FitSense web application as a whole.
- **Member**: A gym member with an account created by an Admin, who uses the AI fitness features.
- **Trainer**: A gym coach or trainer assigned to supervise a roster of Members.
- **Admin**: A system administrator responsible for account management, content, and system configuration.
- **AI_Partner**: The Gemini-powered AI component that generates workout and nutrition recommendations.
- **Health_Profile**: A Member's stored data including weight, height, age, and fitness level.
- **Fitness_Goal**: A Member's declared objective (lose weight, build muscle, improve stamina).
- **Recommendation**: An AI-generated workout routine or meal plan stored and associated with a Member.
- **Workout_Log**: A record of a completed workout session submitted by a Member.
- **Meal_Log**: A record of meals consumed submitted by a Member.
- **Weight_Log**: A dated record of a Member's body weight.
- **Audit_Log**: A system record of security-relevant actions (account creation, password changes, logins).
- **Exercise_Library**: The admin-managed collection of exercises available in the system.
- **Announcement**: A system-wide or role-targeted message posted by an Admin.
- **Default_Credentials**: Auto-generated username and password assigned to a new account by the Admin.
- **Chat_Session**: A conversation thread between a Member and the AI_Partner.
- **HCD**: Human-Centered Design — a design philosophy that places the real-world needs, limitations, and context of users at the centre of every interface decision.
- **Inline_Validation**: Real-time field-level feedback displayed adjacent to a form input as the user types or moves focus away.
- **Loading_State**: A visual indicator (spinner, skeleton screen, or progress bar) shown while the System is processing a request.
- **AI_Disclaimer**: A prominently displayed notice that AI-generated content is not a substitute for professional medical or fitness advice.

---

## Requirements

### Requirement 1: Application Architecture

**User Story:** As a developer, I want a modular, layered architecture, so that presentation, business logic, and data concerns are cleanly separated and maintainable.

#### Acceptance Criteria

1. THE System SHALL separate code into Presentation (PHP/HTML/Tailwind views), Logic (PHP back-end and API integration), and Data (MySQL database) layers.
2. THE System SHALL use PDO with prepared statements for all database interactions.
3. THE System SHALL store all configuration values (database credentials, API keys, session timeout, password policy) in a dedicated configuration file and not inline in application code.
4. WHEN the Gemini API is called, THE System SHALL transmit the request over HTTPS and store the API key only in server-side configuration.
5. THE System SHALL apply a Black and Yellow color theme consistently across all pages using Tailwind CSS utility classes, with a minimum contrast ratio of 4.5:1 between text and background colors to meet WCAG AA readability standards on bright gym-floor lighting.
6. THE System SHALL render all pages responsively so that layout and navigation remain fully usable on screens as narrow as 375px without horizontal scrolling.
7. THE System SHALL use a mobile-first CSS approach, defining base styles for 375px viewports and progressively enhancing for larger screens.

---

### Requirement 2: Database Schema

**User Story:** As a developer, I want a well-structured database, so that all application data is stored consistently and relationships are enforced.

#### Acceptance Criteria

1. THE System SHALL maintain a `users` table with fields for id, username, email, password_hash, role (member/trainer/admin), first_name, last_name, phone, status (active/inactive/suspended), needs_password_change flag, created_at, updated_at, and last_login.
2. THE System SHALL maintain a `member_profiles` table linked to `users` with fields for age, height_cm, current_weight_kg, target_weight_kg, fitness_level, medical_conditions, emergency contact, membership dates, and assigned_trainer_id.
3. THE System SHALL maintain a `fitness_goals` table linked to `users` with fields for goal_type, description, target_value, target_unit, target_date, and status.
4. THE System SHALL maintain an `exercises` table with fields for name, category, muscle_groups, equipment_needed, difficulty_level, instructions, safety_notes, created_by, and is_active flag.
5. THE System SHALL maintain an `ai_recommendations` table linked to `users` with fields for type (workout/meal_plan/general_advice), title, content, ai_prompt, ai_response, status (pending/approved/rejected/modified), reviewed_by, and trainer_notes.
6. THE System SHALL maintain a `workout_sessions` table linked to `users` and optionally to `ai_recommendations` with fields for session_date, duration_minutes, exercises_completed, notes, rating, and calories_burned.
7. THE System SHALL maintain a `weight_logs` table linked to `users` with fields for weight_kg, log_date, and notes, enforcing one entry per user per date.
8. THE System SHALL maintain a `chat_messages` table linked to `users` with fields for sender_type (member/trainer/ai), sender_id, message, message_type, and is_read flag.
9. THE System SHALL maintain an `announcements` table with fields for title, content, type, target_audience, is_active flag, and created_by.
10. THE System SHALL maintain an `audit_logs` table with fields for user_id, action, table_name, record_id, old_values, new_values, ip_address, and user_agent.
11. THE System SHALL maintain a `system_settings` table with key-value pairs for configurable parameters such as maintenance_mode, max_ai_requests_per_day, session_timeout, and password_min_length.

---

### Requirement 3: Authentication & Session Management

**User Story:** As any user, I want secure login and session handling, so that my account and data are protected without unnecessary friction.

#### Acceptance Criteria

1. WHEN a user submits valid credentials, THE System SHALL authenticate the user, create a server-side session, and redirect the user to the appropriate dashboard based on role.
2. WHEN a user submits invalid credentials, THE System SHALL display a single, clear error message ("Incorrect username or password") without revealing which field was incorrect, and increment a failed-attempt counter.
3. WHEN a user's failed login attempts reach 5 within a session, THE System SHALL lock the account and display a clear message instructing the user to contact an Admin to unlock it.
4. WHILE a session is active, THE System SHALL invalidate the session after 3600 seconds of inactivity and redirect the user to the login page with a message explaining that the session expired.
5. THE System SHALL store passwords exclusively as bcrypt hashes and never store or log plaintext passwords.
6. WHEN a user logs out, THE System SHALL destroy the session, clear all session cookies, and record a logout event in the audit_logs table.
7. THE System SHALL provide separate login entry points for Members (`login.php`) and Staff (Trainers/Admins) (`staff-login.php`), each enforcing the correct role.
8. WHEN a Member attempts to access a Staff-only page, THE System SHALL redirect the Member to an unauthorized page with a plain-language explanation and a link back to their dashboard.
9. THE System SHALL display login forms with large tap targets (minimum 44×44px touch area) and clearly labelled fields to support one-handed mobile use on the gym floor.

---

### Requirement 4: First-Login Password Change

**User Story:** As a Member or Trainer, I want to be guided through changing my default password on first login, so that my account is secured immediately and I understand why the step is required.

#### Acceptance Criteria

1. WHEN a user with `needs_password_change = TRUE` completes authentication, THE System SHALL redirect the user to the change-password page before granting access to any other page.
2. WHILE a user has not completed the mandatory password change, THE System SHALL block navigation to all other application pages and redirect back to the change-password page.
3. THE System SHALL display a brief, friendly explanation on the change-password page informing the user why a password change is required before they can continue.
4. WHEN a user submits a new password on the change-password page, THE System SHALL validate that the password is at least 8 characters long and contains at least one uppercase letter, one lowercase letter, one digit, and one special character.
5. IF the new password does not meet the complexity requirements, THEN THE System SHALL display Inline_Validation adjacent to the password field listing each unmet criterion individually, without clearing the form.
6. THE System SHALL display a real-time password strength indicator (weak / fair / strong) that updates as the user types, so the user can self-correct before submitting.
7. WHEN a valid new password is submitted, THE System SHALL update the password_hash, set `needs_password_change = FALSE`, record the event in audit_logs, and redirect the user to their role-appropriate dashboard with a success confirmation message.

---

### Requirement 5: Member Health Profile & Onboarding

**User Story:** As a Member, I want a guided, low-friction onboarding experience after my first login, so that I can set up my profile quickly and start using FitSense without feeling overwhelmed.

#### Acceptance Criteria

1. WHEN a Member logs in for the first time after changing their password, THE System SHALL present a multi-step onboarding flow with a visible progress indicator (e.g., "Step 1 of 3") to collect weight (kg), height (cm), age, and fitness level (beginner/intermediate/advanced).
2. THE System SHALL present one logical group of fields per onboarding step to reduce cognitive load, rather than displaying all fields on a single long form.
3. THE System SHALL validate that weight is between 20 kg and 500 kg, height is between 50 cm and 300 cm, and age is between 10 and 120.
4. IF any onboarding field fails validation, THEN THE System SHALL display Inline_Validation adjacent to the invalid field immediately on blur, retain all other entered values, and prevent progression to the next step until the error is resolved.
5. THE System SHALL display contextual helper text beneath each onboarding field explaining what the value is used for (e.g., "Used to personalise your AI workout recommendations").
6. WHEN the onboarding form is submitted with valid data, THE System SHALL persist the data to the `member_profiles` table and advance the Member to the goal-setting step with a brief confirmation ("Profile saved — now let's set your goal").
7. WHEN a Member selects a Fitness_Goal (lose weight / build muscle / improve stamina), THE System SHALL persist the goal to the `fitness_goals` table with status = 'active'.
8. THE Member SHALL be able to update Health_Profile fields and Fitness_Goal at any time from the profile settings page.

---

### Requirement 6: AI Fitness Partner — Chat Interface

**User Story:** As a Member, I want to chat with an AI fitness coach in a clear, conversational interface, so that I can get personalised fitness and nutrition guidance without confusion about what the AI can and cannot do.

#### Acceptance Criteria

1. THE System SHALL provide a chat interface where a Member can type free-text questions about fitness, nutrition, or wellness.
2. WHEN a Member sends a message, THE System SHALL append the Member's Health_Profile (weight, height, age, fitness level) and active Fitness_Goal to the prompt before sending it to the Gemini API.
3. WHEN the Gemini API returns a response, THE System SHALL display the response in the chat interface within 30 seconds.
4. WHEN a Member sends a message, THE System SHALL immediately display a Loading_State (typing indicator) in the chat interface so the Member knows the request is being processed.
5. IF the Gemini API returns an error or times out, THEN THE System SHALL display a user-friendly error message ("Something went wrong — please try again") and log the failure without exposing API error details to the Member.
6. THE System SHALL persist every sent message and AI response to the `chat_messages` table with the correct sender_type.
7. WHEN a Member opens the chat interface, THE System SHALL load and display the 20 most recent messages from the current Member's chat history.
8. THE System SHALL display a prominent, persistent AI_Disclaimer alongside every AI-generated workout or nutrition suggestion, stating: "This advice is AI-generated and is not a substitute for professional medical or fitness guidance. Consult a qualified professional before making changes to your exercise or diet."
9. THE System SHALL enforce a configurable daily limit on AI requests per Member (default: 50), and IF a Member exceeds this limit, THEN THE System SHALL display a clear, friendly message explaining the limit and when it resets, and prevent further requests until the next calendar day.
10. THE System SHALL visually distinguish AI messages from Trainer messages in the chat thread using distinct avatar icons and label text ("AI Partner" vs the Trainer's name) so Members always know who they are reading.

---

### Requirement 7: AI Recommendation Generator

**User Story:** As a Member, I want the AI to generate clearly formatted workout routines and meal plans, so that I have structured, easy-to-follow guidance on the gym floor.

#### Acceptance Criteria

1. WHEN a Member requests a workout plan or meal plan via the chat interface, THE System SHALL instruct the Gemini API to return the response in a structured format (exercise name, sets, reps, rest period for workouts; meal name, ingredients, macros for meal plans).
2. WHEN a structured Recommendation is received, THE System SHALL store it in the `ai_recommendations` table with status = 'pending' and type = 'workout' or 'meal_plan' as appropriate.
3. THE System SHALL display stored Recommendations to the Member in a formatted, card-based layout that is easy to scan on a mobile screen, visually distinct from regular chat messages.
4. THE System SHALL display the AI_Disclaimer prominently at the top of every rendered Recommendation card.
5. WHEN a Trainer reviews a Recommendation and sets status to 'approved' or 'modified', THE System SHALL update the record and make the Trainer's notes visible to the Member with a clear status badge (e.g., a green "Approved by Trainer" label).

---

### Requirement 8: Progress Tracking — Daily Logging

**User Story:** As a Member, I want to log my completed workouts, meals, and weight quickly and without friction, so that tracking my progress feels effortless rather than a chore.

#### Acceptance Criteria

1. THE System SHALL provide a daily workout logging form where a Member can record session_date, duration_minutes, exercises_completed, notes, rating (1–5), and optional calories_burned.
2. THE System SHALL provide a daily weight logging form where a Member can record weight_kg and an optional note for a given date.
3. THE System SHALL pre-populate the date field in all logging forms with today's date to reduce the number of taps required for a typical log entry.
4. IF a Member attempts to submit a weight log for a date that already has an entry, THEN THE System SHALL display a clear confirmation dialog ("You already logged your weight for this date. Replace it?") before overwriting.
5. WHEN a workout log is saved, THE System SHALL persist the record to the `workout_sessions` table linked to the Member's user_id and display a brief success confirmation ("Workout logged").
6. WHEN a weight log is saved, THE System SHALL persist the record to the `weight_logs` table linked to the Member's user_id and display a brief success confirmation ("Weight logged").
7. THE System SHALL display all logging form inputs with a minimum touch target height of 44px and sufficient label contrast to remain readable under gym-floor lighting conditions.

---

### Requirement 9: Progress Tracking — History Dashboard

**User Story:** As a Member, I want to view my historical logs and progress charts in a clear, motivating layout, so that I can see how I am improving over time.

#### Acceptance Criteria

1. THE System SHALL display a history dashboard showing a chronological list of the Member's past workout sessions.
2. THE System SHALL display a weight-progress chart plotting the Member's weight_logs over time.
3. THE System SHALL display a list of the Member's past AI Recommendations with their current status (pending/approved/modified/rejected) shown as colour-coded badges.
4. WHEN a Member views the history dashboard, THE System SHALL calculate and display the Member's current BMI based on the most recent weight_log and the stored height_cm.
5. THE System SHALL display progress charts using high-contrast Yellow-on-Black colours consistent with the application theme, with axis labels large enough to read at arm's length on a mobile device.

---

### Requirement 10: Trainer — Member Roster & Progress Monitoring

**User Story:** As a Trainer, I want to view my assigned members and their progress at a glance, so that I can effectively supervise their fitness journeys without navigating through multiple screens.

#### Acceptance Criteria

1. WHEN a Trainer logs in, THE System SHALL display a roster of all Members with `assigned_trainer_id` matching the Trainer's user_id.
2. THE System SHALL display each Member's most recent weight_log, active Fitness_Goal, and last login date on the roster view as a scannable summary card.
3. WHEN a Trainer selects a Member, THE System SHALL display that Member's full workout log history, weight progress chart, and AI Recommendations on a single consolidated view.
4. THE System SHALL show the count of pending AI Recommendations awaiting Trainer review as a prominent badge on the Trainer's dashboard overview.

---

### Requirement 11: Trainer — AI Oversight & Manual Overrides

**User Story:** As a Trainer, I want to review and override AI-generated plans for my members, so that I can ensure the plans are safe and appropriate for each individual.

#### Acceptance Criteria

1. THE System SHALL present Trainers with a list of AI Recommendations with status = 'pending' for their assigned Members, sorted by submission date with the oldest first.
2. WHEN a Trainer approves a Recommendation, THE System SHALL update the status to 'approved' and record the Trainer's user_id in the `reviewed_by` field.
3. WHEN a Trainer modifies a Recommendation, THE System SHALL allow the Trainer to edit the trainer_notes field, update the status to 'modified', and persist the changes.
4. WHEN a Trainer rejects a Recommendation, THE System SHALL update the status to 'rejected' and require the Trainer to provide a reason in the trainer_notes field before the rejection can be saved.
5. WHEN a Recommendation's status changes, THE System SHALL make the updated status and trainer_notes visible to the Member in their Recommendations list with a clear status badge and the Trainer's name.
6. THE System SHALL display approve, modify, and reject actions as clearly labelled buttons with sufficient size and spacing to prevent accidental taps on mobile devices.

---

### Requirement 12: Trainer — Direct Messaging

**User Story:** As a Trainer, I want to send direct messages to my assigned members in a clear, familiar messaging interface, so that I can communicate guidance and feedback without ambiguity.

#### Acceptance Criteria

1. THE System SHALL provide a messaging interface where a Trainer can compose and send text messages to any Member in their assigned roster.
2. WHEN a Trainer sends a message, THE System SHALL persist the message to the `chat_messages` table with sender_type = 'trainer' and the correct sender_id and user_id.
3. WHEN a Member opens their chat interface, THE System SHALL display Trainer messages inline in the conversation thread, visually distinguished from AI messages using a distinct avatar, label ("From [Trainer Name]"), and background colour.
4. THE System SHALL display the count of unread Member messages as a badge on the Trainer's dashboard navigation item.
5. WHEN a Trainer reads a Member's message, THE System SHALL update the `is_read` flag to TRUE for that message.
6. THE System SHALL display message timestamps in a human-readable relative format (e.g., "2 hours ago", "Yesterday") to give Trainers immediate context without requiring date parsing.

---

### Requirement 13: Admin — Account Management

**User Story:** As an Admin, I want to create and manage all user accounts efficiently, so that I control who has access to the system and can onboard new members quickly.

#### Acceptance Criteria

1. THE System SHALL restrict account creation exclusively to Admins; no public self-registration endpoint SHALL exist.
2. WHEN an Admin creates a Member account, THE System SHALL auto-generate a unique username and a random password meeting the minimum complexity requirements, set `needs_password_change = TRUE`, and display the Default_Credentials to the Admin in a clearly formatted, copyable panel for distribution.
3. WHEN an Admin creates a Trainer account, THE System SHALL follow the same credential generation process as for Members.
4. WHEN an Admin creates a Member account, THE System SHALL allow the Admin to optionally assign the Member to a Trainer at creation time.
5. THE System SHALL allow an Admin to edit any user's first_name, last_name, email, phone, role, and assigned_trainer_id.
6. WHEN an Admin suspends an account, THE System SHALL set the user's status to 'suspended', immediately invalidate any active session for that user, and record the action in audit_logs.
7. WHEN an Admin deletes an account, THE System SHALL soft-delete the record by setting status = 'inactive' rather than removing the database row, and record the action in audit_logs.
8. THE System SHALL record every account creation, modification, suspension, and deletion event in the `audit_logs` table with the Admin's user_id, action, and timestamp.
9. WHEN an Admin performs a destructive action (suspend or delete), THE System SHALL display a confirmation dialog with a plain-language description of the consequences before executing the action.

---

### Requirement 14: Admin — Exercise Library Management

**User Story:** As an Admin, I want to manage the exercise library, so that the AI and Trainers have an accurate and up-to-date set of exercises to reference.

#### Acceptance Criteria

1. THE System SHALL allow an Admin to create a new exercise with name, category, muscle_groups, equipment_needed, difficulty_level, instructions, and safety_notes.
2. THE System SHALL allow an Admin to edit any field of an existing exercise.
3. WHEN an Admin deactivates an exercise, THE System SHALL set `is_active = FALSE` and exclude the exercise from AI prompts and Member-facing displays without deleting the record.
4. THE System SHALL display the full exercise library to Admins in a searchable, filterable table with clear column headers and sufficient row height for touch interaction.

---

### Requirement 15: Admin — Announcements

**User Story:** As an Admin, I want to post announcements, so that I can communicate important information to users in a way they will notice.

#### Acceptance Criteria

1. THE System SHALL allow an Admin to create an Announcement with a title, content, type (general/maintenance/event/policy), and target_audience (all/members/trainers/admins).
2. WHEN an Announcement is active, THE System SHALL display it to users whose role matches the target_audience as a prominent banner at the top of their dashboard, above the main content area.
3. THE System SHALL allow an Admin to deactivate an Announcement by setting `is_active = FALSE`, after which it will no longer be displayed to users.

---

### Requirement 16: Admin — Analytics & Audit Trail

**User Story:** As an Admin, I want to view system analytics and audit logs, so that I can monitor system health and ensure accountability.

#### Acceptance Criteria

1. THE System SHALL display an analytics dashboard showing total active users by role, chat sessions per day, AI recommendations generated per day, and average workout session rating.
2. THE System SHALL display a real-time system status panel showing database connectivity and AI API availability.
3. THE System SHALL display a paginated audit log table showing user_id, action, table_name, record_id, ip_address, and timestamp for all recorded events.
4. THE System SHALL allow an Admin to filter the audit log by date range, user, and action type.

---

### Requirement 17: Admin — System Settings & Maintenance Mode

**User Story:** As an Admin, I want to configure system settings and enable maintenance mode, so that I can control application behaviour without code changes.

#### Acceptance Criteria

1. THE System SHALL allow an Admin to update system_settings values for session_timeout, max_ai_requests_per_day, and password_min_length through the admin UI.
2. WHEN an Admin enables maintenance mode, THE System SHALL display a maintenance page to all non-Admin users attempting to access any page and prevent login for non-Admin roles.
3. WHILE maintenance mode is active, THE System SHALL allow Admin users to log in and access the admin dashboard normally.
4. WHEN an Admin saves a system setting change, THE System SHALL persist the new value to the `system_settings` table and apply it to subsequent requests without requiring a server restart.

---

### Requirement 18: Admin — Optional CMS Static Pages

**User Story:** As an Admin, I want to manage static informational pages, so that members can access gym information without leaving the application.

#### Acceptance Criteria

1. WHERE the CMS feature is enabled, THE System SHALL provide editable static pages for About Us, Contact Us, FAQs, and Privacy Policy.
2. WHERE the CMS feature is enabled, THE System SHALL allow an Admin to update the content of each static page through the admin UI.
3. WHERE the CMS feature is enabled, THE System SHALL display the static pages to unauthenticated visitors via the landing page navigation.

---

### Requirement 19: Security & Input Validation

**User Story:** As a developer, I want all inputs validated and outputs escaped, so that the application is protected against common web vulnerabilities.

#### Acceptance Criteria

1. THE System SHALL sanitize and validate all user-supplied input on the server side before processing or persisting it.
2. THE System SHALL escape all dynamic content rendered in HTML using `htmlspecialchars()` or equivalent to prevent XSS.
3. THE System SHALL use CSRF tokens on all state-changing HTML forms.
4. THE System SHALL use PDO prepared statements for all database queries to prevent SQL injection.
5. IF a request is received without a valid CSRF token on a state-changing endpoint, THEN THE System SHALL reject the request with an HTTP 403 response and log the event.

---

### Requirement 20: Landing Page

**User Story:** As a visitor or returning user, I want a landing page that clearly describes FitSense and provides easy login access, so that I can understand the product and sign in without confusion.

#### Acceptance Criteria

1. THE System SHALL display a public landing page accessible without authentication that describes FitSense features in plain, jargon-free language.
2. WHEN a visitor is not authenticated, THE System SHALL display clearly labelled login buttons for Member Login and Staff Login on the landing page with sufficient size and contrast for easy identification.
3. WHEN an authenticated user visits the landing page, THE System SHALL display a link to their role-appropriate dashboard instead of the login buttons.
4. THE System SHALL display live statistics (total active members, workouts generated, success stories) on the landing page, sourced from the database.
5. THE System SHALL render the landing page fully on screens as narrow as 375px with all calls-to-action reachable without horizontal scrolling.

---

### Requirement 21: Human-Centered Design & User Experience

**User Story:** As a gym member or trainer using FitSense on the gym floor, I want every interface to be immediately understandable and operable with one hand on a mobile device, so that I can focus on my workout rather than on navigating the app.

#### Acceptance Criteria

1. THE System SHALL apply a Black and Yellow high-contrast color scheme across all pages, with a minimum contrast ratio of 4.5:1 for all body text and 3:1 for large headings, to ensure readability under bright gym-floor lighting.
2. THE System SHALL size all interactive elements (buttons, links, form inputs, navigation items) to a minimum touch target of 44×44px and provide at least 8px of spacing between adjacent targets to prevent accidental activation.
3. THE System SHALL position primary navigation controls within the thumb-reachable zone (bottom 60% of the screen on mobile) so Members can operate the app one-handed between sets.
4. WHEN any asynchronous operation is initiated (form submission, API call, data load), THE System SHALL display a Loading_State within 200ms so the user receives immediate feedback that the action was registered.
5. WHEN any operation completes successfully, THE System SHALL display a brief, non-blocking success notification (e.g., a toast message) that auto-dismisses after 3 seconds.
6. WHEN any operation fails, THE System SHALL display a clear, plain-language error message that describes what went wrong and, where possible, what the user can do to resolve it.
7. THE System SHALL display all form fields with visible labels positioned above the input (not as placeholder-only labels) so that labels remain visible while the user is typing.
8. THE System SHALL provide Inline_Validation on all form fields, displaying field-level error messages adjacent to the relevant input on blur, without requiring a full form submission to surface errors.
9. THE System SHALL display helpful hint text beneath complex form fields (e.g., password requirements, acceptable value ranges) before the user encounters an error, reducing trial-and-error interaction.
10. THE System SHALL display the AI_Disclaimer prominently on every screen where AI-generated content appears, using a visually distinct style (e.g., a bordered notice box) that is not dismissible, so users always understand the nature of the content.
11. THE System SHALL use consistent iconography and labelling across all navigation elements so that users can build a reliable mental model of the application after their first session.
12. WHEN a Member completes a significant action (logging a workout, completing onboarding, receiving an approved Recommendation), THE System SHALL display a positive reinforcement message to acknowledge the achievement and encourage continued engagement.
13. THE System SHALL ensure that all images and icons include descriptive `alt` attributes, all form inputs are associated with `<label>` elements via `for`/`id` pairing, and all interactive elements are keyboard-navigable, to support users with assistive technologies.
14. THE System SHALL limit the number of primary actions visible on any single screen to a maximum of five, grouping secondary actions into contextual menus or secondary panels, to prevent decision paralysis on small screens.
