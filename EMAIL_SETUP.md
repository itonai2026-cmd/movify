# Email Verification Setup Guide

## Overview

The email verification system in Movify has been fully implemented with SMTP support. This guide explains how to configure and test it.

## Problem Solved

The original implementation had a non-functional email circuit:
- `send_mail()` used PHP's native `mail()` function
- This doesn't support SMTP authentication
- The SMTP constants (SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS) were defined but unused

## Solution Implemented

### 1. Dual-Mode Email Sending

The new `send_mail()` in `includes/auth.php` now:

1. **First attempts:** Native PHP `mail()` (works if sendmail/postfix is configured)
2. **Fallback:** Custom SMTP socket connection with:
   - Support for TLS (port 587)
   - Support for SSL (port 465)
   - AUTH LOGIN authentication
   - Proper MIME headers

### 2. Resend Verification Code

New endpoint: `resend_verify.php`
- Generates a new verification code
- Sends it via email
- Rate-limited to 1 resend per 60 seconds per session
- Shows countdown timer in UI

### 3. Updated Verification Flow

`verify.php` now includes:
- "Resend code" button
- Real-time countdown (60 seconds between resends)
- Better error/success messaging
- "Register again" fallback link

## Configuration

### Step 1: Copy .env.example to .env

```bash
cp .env.example .env
```

### Step 2: Configure SMTP

Edit `.env` with your email provider credentials:

#### Option A: Gmail (Recommended for Testing)

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your.email@gmail.com
SMTP_PASS=xxxx xxxx xxxx xxxx
```

**To get Gmail App Password:**
1. Enable 2-Factor Authentication on your Google account
2. Go to https://myaccount.google.com/apppasswords
3. Select "Mail" and "Windows Computer"
4. Copy the 16-character password
5. Paste into SMTP_PASS (spaces are OK, they'll be sent as-is)

#### Option B: Custom SMTP Server

```env
SMTP_HOST=mail.yourserver.com
SMTP_PORT=587
SMTP_USER=admin@yourserver.com
SMTP_PASS=your_password
```

#### Option C: Development Mode (No Email)

Leave SMTP_USER and SMTP_PASS empty:

```env
SMTP_USER=
SMTP_PASS=
```

This will:
- Log verification codes to error_log
- Display codes on the verification page (in dev mode)
- Not actually send emails (for testing)

### Step 3: Test the Configuration

1. Register a new account
2. You should receive an email with verification code
3. If no email, check:
   - PHP error logs
   - Browser console for AJAX errors
   - SMTP credentials are correct

## How It Works

### Registration Flow

```
User fills register.php
    ↓
register_user() generates 6-digit code
    ↓
send_verification_email() called
    ↓
send_mail() attempts:
  1. PHP mail() [may fail]
  2. SMTP socket [usually works]
    ↓
Email sent to user
    ↓
Redirect to verify.php
    ↓
User enters code
    ↓
verify_email() checks code in DB
    ↓
Account marked as verified
```

### Resend Code Flow

```
User clicks "Resend code"
    ↓
JavaScript calls resend_verify.php (AJAX)
    ↓
Rate limiting checked (60 second cooldown)
    ↓
New code generated in DB
    ↓
send_verification_email() called again
    ↓
Countdown timer starts in UI
```

## SMTP Socket Implementation Details

The custom `send_mail_smtp()` function:

1. **Connects** to SMTP server with TLS/SSL
2. **Authenticates** using AUTH LOGIN with base64-encoded credentials
3. **Sends** email with proper MIME headers
4. **Disconnects** gracefully with QUIT command

Supports:
- Port 587 (STARTTLS)
- Port 465 (Implicit TLS)
- Standard AUTH LOGIN authentication
- HTML email bodies with proper charset

## Files Modified/Created

| File | Change |
|------|--------|
| `includes/auth.php` | Rewrote `send_mail()` with SMTP socket support |
| `resend_verify.php` | NEW: Resend code endpoint with rate limiting |
| `verify.php` | Added "Resend code" button and AJAX handler |
| `.env.example` | NEW: Configuration template with documentation |
| `composer.json` | NEW: Composer config (for optional PHPMailer later) |

## Rate Limiting

Resend verification code:
- **Max 1 resend per 60 seconds** per session
- Enforced server-side and displayed in UI
- Prevents spam while allowing legitimate retries

## Logging

All email operations log to PHP error log:
- Successful sends: `"Email sent successfully to {email}"`
- SMTP errors: `"SMTP connection failed to {host}:{port}"`
- Dev mode: `"SMTP not configured – would send..."`

Check logs at:
- Linux: `/var/log/php-errors.log` or `php_error_log`
- Windows: Check your PHP configuration

## Troubleshooting

### "SMTP not configured" message
- Check that SMTP_USER and SMTP_PASS are set in .env
- Verify database was created: `mysql -u root ai_video_generator < database/schema.sql`

### "Connection failed" error
- Verify SMTP_HOST and SMTP_PORT are correct
- Check if firewall blocks outgoing port 587/465
- Test with: `telnet smtp.gmail.com 587`

### Emails not arriving
- Check spam folder
- Verify sender address in SMTP_FROM
- Enable "Less secure apps" for Gmail (if not using App Password)

### Timeout errors
- Increase timeout in `send_mail_smtp()` (default 10 seconds)
- Check network connectivity to SMTP server
- Use port 587 instead of 465 (StartTLS more reliable)

## Security Notes

1. **Never commit .env** – Add to `.gitignore`
2. **Use App Passwords** – For Gmail, not your main password
3. **Enable SSL/TLS** – Always use port 587 or 465, never 25
4. **CSRF Protection** – Verify.php includes CSRF token validation
5. **Rate Limiting** – Prevents code brute-force attempts

## Production Deployment

### Environment Variables

Set these via your hosting control panel or server configuration:

```bash
# .env or server variables
DB_HOST=prod-db.example.com
DB_USER=movify_user
DB_PASS=strong_password_here
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USER=apikey
SMTP_PASS=SG.xxxxxxxxxxxxx
FAL_AI_API_KEY=your_production_key
APP_URL=https://movify.example.com
APP_ENV=production
```

### Check Ports

Ensure your server allows outgoing connections:
- Port 587 (TLS) – Recommended
- Port 465 (SSL) – Alternative

Contact your hosting provider if blocked.

## Testing Checklist

- [ ] Created `.env` from `.env.example`
- [ ] Set SMTP_USER and SMTP_PASS
- [ ] Database created with schema
- [ ] Registered new account
- [ ] Received verification email
- [ ] Entered code and verified
- [ ] Attempted "Resend code" 
- [ ] Saw countdown timer
- [ ] Logged in successfully

---

For questions or issues, check `/var/log/php-errors.log` or your PHP error log.
