-- Migration: add theme_preference to users
-- Run this once against fitsense_db

ALTER TABLE users
    ADD COLUMN theme_preference ENUM('dark','light') NOT NULL DEFAULT 'dark'
    AFTER needs_password_change;
