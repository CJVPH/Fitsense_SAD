-- =============================================================================
-- FitSense Database Schema
-- File: database/schema.sql
-- Description: Complete DDL for fitsense_db — creates all 11 tables from scratch
-- =============================================================================

CREATE DATABASE IF NOT EXISTS fitsense_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE fitsense_db;

-- =============================================================================
-- DROP TABLES (reverse dependency order)
-- =============================================================================

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS weight_logs;
DROP TABLE IF EXISTS workout_sessions;
DROP TABLE IF EXISTS ai_recommendations;
DROP TABLE IF EXISTS fitness_goals;
DROP TABLE IF EXISTS exercises;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS member_profiles;
DROP TABLE IF EXISTS users;

-- =============================================================================
-- TABLE: users
-- All accounts (members, trainers, admins) — Admin-created only, no public reg
-- =============================================================================

CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50)  UNIQUE NOT NULL,
    email           VARCHAR(100) UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,                          -- bcrypt only
    role            ENUM('member','trainer','admin') NOT NULL,
    first_name      VARCHAR(50)  NOT NULL,
    last_name       VARCHAR(50)  NOT NULL,
    phone           VARCHAR(20),
    profile_photo   VARCHAR(255),
    status          ENUM('active','inactive','suspended') DEFAULT 'active',
    needs_password_change BOOLEAN DEFAULT TRUE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    last_login      TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: member_profiles
-- Extended health/fitness profile for members (1:1 with users for members)
-- =============================================================================

CREATE TABLE member_profiles (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    user_id                 INT NOT NULL,
    age                     INT,
    height_cm               DECIMAL(5,2),                          -- validated: 50–300
    current_weight_kg       DECIMAL(5,2),                          -- validated: 20–500
    target_weight_kg        DECIMAL(5,2),
    fitness_level           ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
    medical_conditions      TEXT,
    emergency_contact_name  VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    membership_start        DATE,
    membership_end          DATE,
    assigned_trainer_id     INT,
    FOREIGN KEY (user_id)            REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_trainer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: fitness_goals
-- Member fitness goals (1:many with users)
-- =============================================================================

CREATE TABLE fitness_goals (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    goal_type    ENUM('lose_weight','build_muscle','improve_stamina','maintain_fitness','other'),
    description  TEXT,
    target_value DECIMAL(8,2),
    target_unit  VARCHAR(20),
    target_date  DATE,
    status       ENUM('active','completed','paused') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: exercises
-- Exercise library — used in AI prompts and member-facing lists
-- =============================================================================

CREATE TABLE exercises (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(255) NOT NULL,
    category         VARCHAR(100),
    muscle_group     VARCHAR(255),
    equipment        VARCHAR(255),
    difficulty_level ENUM('beginner','intermediate','advanced'),
    description      TEXT,
    instructions     TEXT,
    safety_notes     TEXT,
    created_by       INT,
    is_active        BOOLEAN DEFAULT TRUE,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: ai_recommendations
-- AI-generated workout/meal plans pending trainer review
-- =============================================================================

CREATE TABLE ai_recommendations (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    type          ENUM('workout','meal_plan','general_advice') NOT NULL,
    title         VARCHAR(255) NOT NULL,
    content       JSON NOT NULL,
    ai_prompt     TEXT,
    ai_response   TEXT,
    status        ENUM('pending','approved','rejected','modified') DEFAULT 'pending',
    reviewed_by   INT,
    trainer_notes TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: workout_sessions
-- Member-logged workout sessions (may reference an AI recommendation)
-- =============================================================================

CREATE TABLE workout_sessions (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    recommendation_id   INT,
    session_date        DATE NOT NULL,
    duration_minutes    INT,
    exercises_completed JSON,
    notes               TEXT,
    rating              INT CHECK (rating BETWEEN 1 AND 5),
    calories_burned     INT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)           REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recommendation_id) REFERENCES ai_recommendations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: weight_logs
-- Daily weight tracking — one entry per member per date (enforced by UNIQUE KEY)
-- =============================================================================

CREATE TABLE weight_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    weight_kg  DECIMAL(5,2) NOT NULL,
    log_date   DATE NOT NULL,
    notes      TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, log_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: chat_messages
-- AI and trainer-to-member chat thread (sender_id NULL when sender_type = 'ai')
-- =============================================================================

CREATE TABLE chat_messages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,                                      -- the member this thread belongs to
    sender_type  ENUM('member','trainer','ai') NOT NULL,
    sender_id    INT,                                               -- NULL when sender_type = 'ai'
    message      TEXT NOT NULL,
    message_type ENUM('text','recommendation','system') DEFAULT 'text',
    is_read      BOOLEAN DEFAULT FALSE,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: announcements
-- System-wide announcements targeted by role audience
-- =============================================================================

CREATE TABLE announcements (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255) NOT NULL,
    content         TEXT,
    type            ENUM('general','maintenance','event','policy'),
    target_audience ENUM('all','members','trainers','admins'),
    is_active       BOOLEAN DEFAULT TRUE,
    created_by      INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: audit_logs
-- Immutable record of all significant actions for compliance and debugging
-- =============================================================================

CREATE TABLE audit_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT,
    action     VARCHAR(100) NOT NULL,
    table_name VARCHAR(100),
    record_id  INT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE: system_settings
-- Key-value store for runtime configuration (no surrogate PK — key is identity)
-- =============================================================================

CREATE TABLE system_settings (
    setting_key   VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_by    INT,
    updated_at    TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED DATA: system_settings
-- =============================================================================

INSERT INTO system_settings (setting_key, setting_value) VALUES
    ('maintenance_mode',         'false'),
    ('max_ai_requests_per_day',  '50'),
    ('session_timeout',          '3600'),
    ('password_min_length',      '8');

-- =============================================================================
-- SEED DATA: default admin account
-- Default password: FitSense@2024 (change immediately)
-- The hash below is a bcrypt cost-12 hash — replace with a freshly generated
-- hash before deploying to production.
-- =============================================================================

INSERT INTO users (
    username,
    email,
    password_hash,
    role,
    first_name,
    last_name,
    status,
    needs_password_change
) VALUES (
    'admin',
    'admin@fitsense.local',
    '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', -- Default password: Admin@2024
    'admin',
    'System',
    'Admin',
    'active',
    FALSE
);

-- =============================================================================
-- SEED DATA: starter exercise library
-- =============================================================================

INSERT INTO exercises (name, category, muscle_group, equipment, difficulty_level, instructions, safety_notes, is_active) VALUES
(
    'Push-Up',
    'Strength',
    'Chest, Shoulders, Triceps',
    'None',
    'beginner',
    'Start in a high plank position with hands shoulder-width apart. Lower your chest to the floor by bending your elbows, then push back up to the starting position.',
    'Keep your core tight and body in a straight line throughout. Avoid flaring elbows out past 45 degrees.',
    TRUE
),
(
    'Bodyweight Squat',
    'Strength',
    'Quadriceps, Glutes, Hamstrings',
    'None',
    'beginner',
    'Stand with feet shoulder-width apart, toes slightly turned out. Push hips back and bend knees to lower until thighs are parallel to the floor, then drive through heels to stand.',
    'Keep knees tracking over toes. Do not let heels lift off the floor.',
    TRUE
),
(
    'Plank',
    'Core',
    'Core, Shoulders, Glutes',
    'None',
    'beginner',
    'Hold a forearm plank position with elbows directly under shoulders. Keep hips level and hold for the target duration.',
    'Avoid sagging hips or raising them too high. Breathe steadily throughout.',
    TRUE
),
(
    'Dumbbell Bench Press',
    'Strength',
    'Chest, Shoulders, Triceps',
    'Dumbbells, Bench',
    'intermediate',
    'Lie on a flat bench holding a dumbbell in each hand at chest level. Press the dumbbells up until arms are fully extended, then lower with control.',
    'Keep feet flat on the floor. Do not bounce the weights off your chest.',
    TRUE
),
(
    'Barbell Deadlift',
    'Strength',
    'Hamstrings, Glutes, Lower Back, Traps',
    'Barbell, Weight Plates',
    'intermediate',
    'Stand with feet hip-width apart, bar over mid-foot. Hinge at hips and grip the bar just outside your legs. Drive through the floor to stand tall, then hinge back down with control.',
    'Maintain a neutral spine throughout. Start light to learn the movement pattern before adding load.',
    TRUE
),
(
    'Pull-Up',
    'Strength',
    'Lats, Biceps, Rear Deltoids',
    'Pull-Up Bar',
    'intermediate',
    'Hang from a bar with an overhand grip slightly wider than shoulder-width. Pull your chest toward the bar by driving elbows down, then lower with control.',
    'Avoid swinging or kipping. If unable to complete a full rep, use a resistance band for assistance.',
    TRUE
),
(
    'Treadmill Running',
    'Cardio',
    'Legs, Cardiovascular System',
    'Treadmill',
    'beginner',
    'Set treadmill to a comfortable pace. Run with an upright posture, relaxed shoulders, and a midfoot strike. Begin with a 5-minute warm-up walk.',
    'Stay hydrated. Stop immediately if you feel chest pain or dizziness. Use the safety clip.',
    TRUE
),
(
    'Dumbbell Lunges',
    'Strength',
    'Quadriceps, Glutes, Hamstrings, Calves',
    'Dumbbells',
    'beginner',
    'Stand holding dumbbells at your sides. Step forward with one foot and lower your back knee toward the floor, then push off the front foot to return to standing. Alternate legs.',
    'Keep your front knee behind your toes. Maintain an upright torso throughout.',
    TRUE
),
(
    'Seated Cable Row',
    'Strength',
    'Lats, Rhomboids, Biceps',
    'Cable Machine',
    'intermediate',
    'Sit at a cable row station with feet on the platform and knees slightly bent. Pull the handle to your lower abdomen, squeezing shoulder blades together, then extend arms with control.',
    'Avoid rounding your lower back. Do not use momentum to pull the weight.',
    TRUE
),
(
    'Burpee',
    'Cardio',
    'Full Body, Cardiovascular System',
    'None',
    'advanced',
    'From standing, drop hands to the floor, jump feet back to a plank, perform a push-up, jump feet forward, then explosively jump up with arms overhead.',
    'Land softly to protect your joints. Modify by stepping instead of jumping if needed.',
    TRUE
);

-- =============================================================================
-- TABLE: contact_inquiries
-- =============================================================================

DROP TABLE IF EXISTS contact_inquiries;

CREATE TABLE contact_inquiries (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL,
    subject    VARCHAR(200)  NOT NULL,
    message    TEXT          NOT NULL,
    user_id    INT           DEFAULT NULL,
    status     ENUM('new','read','replied') DEFAULT 'new',
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
