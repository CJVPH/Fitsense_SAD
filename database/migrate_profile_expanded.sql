-- Migration: expand member_profiles with full personal + lifestyle data
-- Run once against fitsense_db

ALTER TABLE member_profiles
    ADD COLUMN IF NOT EXISTS target_weight_kg       DECIMAL(5,2)  NULL AFTER current_weight_kg,
    ADD COLUMN IF NOT EXISTS address                VARCHAR(255)  NULL AFTER emergency_contact_phone,
    ADD COLUMN IF NOT EXISTS work_schedule          ENUM('day_shift','night_shift','rotating_shift','work_from_home','student','not_working','other') NULL AFTER address,
    ADD COLUMN IF NOT EXISTS occupation             VARCHAR(100)  NULL AFTER work_schedule,
    ADD COLUMN IF NOT EXISTS sleep_hours_per_night  DECIMAL(3,1)  NULL AFTER occupation,
    ADD COLUMN IF NOT EXISTS activity_level         ENUM('sedentary','lightly_active','moderately_active','very_active','extremely_active') NULL AFTER sleep_hours_per_night,
    ADD COLUMN IF NOT EXISTS dietary_preference     ENUM('no_preference','vegetarian','vegan','keto','halal','gluten_free','other') NULL AFTER activity_level,
    ADD COLUMN IF NOT EXISTS allergies              TEXT          NULL AFTER dietary_preference,
    ADD COLUMN IF NOT EXISTS onboarding_completed   BOOLEAN       NOT NULL DEFAULT FALSE AFTER allergies;
