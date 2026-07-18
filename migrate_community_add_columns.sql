-- Add missing columns to community_actions table
-- Run this in your MySQL database

ALTER TABLE community_actions 
ADD COLUMN IF NOT EXISTS cover_photo VARCHAR(255) NULL AFTER description,
ADD COLUMN IF NOT EXISTS activity_time TIME NULL AFTER target_date,
ADD COLUMN IF NOT EXISTS registration_deadline DATETIME NULL AFTER activity_time,
ADD COLUMN IF NOT EXISTS location VARCHAR(255) NULL AFTER registration_deadline,
ADD COLUMN IF NOT EXISTS category VARCHAR(100) NULL AFTER location;
