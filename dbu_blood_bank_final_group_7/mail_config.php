<?php
/**
 * DBU Blood Bank — Gmail SMTP Configuration
 * ─────────────────────────────────────────────────────────────────
 * HOW TO ENABLE REAL EMAILS (XAMPP / Production):
 *
 *  1. Go to your Google Account → Security → 2-Step Verification → App passwords
 *  2. Generate a 16-character App Password for "Mail"
 *  3. Fill in MAIL_USERNAME with your Gmail address
 *  4. Fill in MAIL_PASSWORD with the 16-char App Password (no spaces)
 *  5. Set MAIL_ENABLED to true
 *
 * Leave MAIL_ENABLED = false for local demo / Replit preview.
 * All alerts are still logged to notifications_log regardless.
 * ─────────────────────────────────────────────────────────────────
 */

define('MAIL_ENABLED',   true);                        // ← SET true AFTER configuring below
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);
define('MAIL_SECURE',    'tls');                       // 'tls' (port 587) or 'ssl' (port 465)
define('MAIL_USERNAME',  'your.dbu.email@gmail.com');  // ← PLEASE REPLACE with your Gmail address
define('MAIL_PASSWORD',  'xxxx xxxx xxxx xxxx');       // ← PLEASE REPLACE with your 16-char Gmail App Password
define('MAIL_FROM',      'your.dbu.email@gmail.com');  // ← PLEASE REPLACE with same Gmail address
define('MAIL_FROM_NAME', 'DBU Blood Bank — Debre Berhan University');
?>
