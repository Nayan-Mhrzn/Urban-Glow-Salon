-- =============================================
-- Migration: Add Verification Code to Password Reset Tokens
-- =============================================

ALTER TABLE password_reset_tokens
ADD COLUMN verification_code VARCHAR(10) NULL AFTER token;
