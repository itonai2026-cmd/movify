# Movify – cPanel Deployment Guide

## Overview

This guide explains how to deploy Movify on a cPanel-based hosting server with your specific database and Fal.ai credentials.

## Your Server Information

```
Database Host: r133813iton.cd.cpanel.net (or localhost if on same server)
Database Name: r133813iton_ai_video
Database User: r133813iton_dacos
Database Password: [stored in environment]

Fal.ai API Key: d6f7fb96-a1e5-4094-87e7-fdef9acfd8f9:0b66c7406b921e669155f6506cd02e46
```

## Step 1: Database Setup

### Via cPanel

1. Open **cPanel → MySQL Databases**
2. Database `r133813iton_ai_video` should already exist
3. User `r133813iton_dacos` should already be assigned
4. Verify access:
   ```bash
   mysql -h r133813iton.cd.cpanel.net -u r133813iton_dacos -p
   ```
   Enter password: `Azor&?2026?!`

### Import Database Schema

1. SSH into your server or use **cPanel → File Manager**
2. Navigate to your Movify directory
3. Run:
   ```bash
   mysql -h r133813iton.cd.cpanel.net -u r133813iton_dacos -p r133813iton_ai_video < database/schema.sql
   ```
4. Enter password when prompted

Or via phpMyAdmin in cPanel:
1. Go to **cPanel → phpMyAdmin**
2. Select database `r133813iton_ai_video`
3. Import `database/schema.sql` using the Import tab

## Step 2: Create .env File

### Option A: Via File Manager

1. In cPanel File Manager, navigate to Movify root directory
2. Right-click → Create New File → name it `.env`
3. Edit the file and add:

```env
APP_ENV=production
APP_URL=https://yourdomain.com
BASE_PATH=/movify

DB_HOST=r133813iton.cd.cpanel.net
DB_NAME=r133813iton_ai_video
DB_USER=r133813iton_dacos
DB_PASS=Azor&?2026?!

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your.email@gmail.com
SMTP_PASS=your_16_char_app_password

SMTP_FROM=noreply@yourdomain.com

FAL_AI_API_KEY=d6f7fb96-a1e5-4094-87e7-fdef9acfd8f9:0b66c7406b921e669155f6506cd02e46
```

### Option B: Via SSH

```bash
ssh user@yourdomain.com
cd /home/user/public_html/movify
cat > .env << 'EOF'
APP_ENV=production
APP_URL=https://yourdomain.com
BASE_PATH=/movify

DB_HOST=r133813iton.cd.cpanel.net
DB_NAME=r133813iton_ai_video
DB_USER=r133813iton_dacos
DB_PASS=Azor&?2026?!

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your.email@gmail.com
SMTP_PASS=your_16_char_app_password

SMTP_FROM=noreply@yourdomain.com

FAL_AI_API_KEY=d6f7fb96-a1e5-4094-87e7-fdef9acfd8f9:0b66c7406b921e669155f6506cd02e46
EOF
chmod 600 .env
```

## Step 3: Configure SMTP Email

### Gmail Setup (Recommended)

1. Go to https://myaccount.google.com
2. Security → 2-Step Verification (if not enabled)
3. Security → App passwords
4. Select "Mail" and "Windows Computer"
5. Copy the 16-character password
6. Update `.env`:
   ```env
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_USER=your.email@gmail.com
   SMTP_PASS=xxxx xxxx xxxx xxxx
   ```

### Corporate Email (Outlook, Custom)

Contact your email provider for SMTP settings:
- SMTP_HOST: mail.yourdomain.com
- SMTP_PORT: 587 or 465
- SMTP_USER: your@yourdomain.com
- SMTP_PASS: your password

## Step 4: Test the Setup

### Test Database Connection

Create a file `test_db.php`:

```php
<?php
require_once __DIR__ . '/config.php';

try {
    $stmt = $pdo->query('SELECT 1');
    echo "✓ Database connection successful!<br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "Database: " . DB_NAME . "<br>";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage();
}
?>
```

Visit: `https://yourdomain.com/movify/test_db.php`

Delete after testing.

### Test Email

1. Go to `https://yourdomain.com/movify/register.php`
2. Register a test account
3. Check if you receive verification email
4. If not, check:
   - Spam folder
   - PHP error logs (cPanel → Logs → Error Log)
   - Fal.ai credentials are correct

## Step 5: Configure Fal.ai

The API key is already in your credentials:
```
d6f7fb96-a1e5-4094-87e7-fdef9acfd8f9:0b66c7406b921e669155f6506cd02e46
```

This is configured in `.env` as:
```env
FAL_AI_API_KEY=d6f7fb96-a1e5-4094-87e7-fdef9acfd8f9:0b66c7406b921e669155f6506cd02e46
```

Video generation will work once configured.

## Step 6: Verify Permissions

SSH into server:

```bash
cd /home/user/public_html/movify

# .env should not be world-readable
chmod 600 .env

# uploads directory should be writable
chmod 755 uploads/

# Verify .gitignore protects .env
cat .gitignore | grep .env
# Should show: .env or *.env.local
```

## Step 7: cPanel File Structure

Your directory structure should look like:

```
/public_html/movify/
├── .env                  ← Created (DO NOT commit)
├── .env.example         ← Template (OK to commit)
├── .gitignore           ← Protects .env
├── config.php           ← Reads from .env
├── register.php
├── verify.php
├── login.php
├── resend_verify.php    ← NEW
├── includes/
│   ├── auth.php         ← SMTP implementation
│   └── ...
├── database/
│   └── schema.sql
└── uploads/             ← User-uploaded videos
```

## Troubleshooting

### "Could not connect to database"

Check in `.env`:
```bash
DB_HOST=r133813iton.cd.cpanel.net  ← Correct hostname
DB_USER=r133813iton_dacos          ← Correct user
DB_PASS=Azor&?2026?!               ← Correct password
```

Also check cPanel → MySQL → Remote MySQL Hosts (if accessing remotely).

### Emails Not Sending

1. Check SMTP_USER and SMTP_PASS are filled
2. Check spam folder
3. View error log: cPanel → Metrics → Error Log
4. Search for "Email sent" or "SMTP" in logs

### Videos Not Generating

1. Verify FAL_AI_API_KEY in `.env`
2. Check Fal.ai dashboard for remaining quota
3. Verify API key format hasn't changed

### Permission Denied on uploads/

SSH:
```bash
chmod 755 /home/user/public_html/movify/uploads/
chmod 755 /home/user/public_html/movify/uploads/.htaccess
```

## SSL/HTTPS

cPanel Auto SSL should handle this. If needed:
1. cPanel → AutoSSL
2. Or manually: cPanel → SSL/TLS Status

Ensure `APP_URL` in `.env` uses `https://`:
```env
APP_URL=https://yourdomain.com
```

## Backup

### Backup Database

cPanel → Backup → Download Home Directory Backup

Or via SSH:
```bash
mysqldump -h r133813iton.cd.cpanel.net -u r133813iton_dacos -p r133813iton_ai_video > backup.sql
```

### Backup Videos

The `uploads/` directory contains user-generated videos:
```bash
tar -czf uploads_backup.tar.gz uploads/
```

## Monitoring

### Check Error Log

cPanel → Metrics → Error Log

Look for:
- `SMTP connection failed`
- `Database connection error`
- `Fal.ai error`

### Monitor Disk Space

cPanel → File Manager → Disk Space Usage

User videos accumulate in `uploads/`. Consider:
- Setting up automatic cleanup (cron job)
- Moving old videos to archive storage
- Monitoring quota

## Security Checklist

- [ ] `.env` is NOT in version control (`.gitignore` includes it)
- [ ] `.env` file permissions: `chmod 600 .env`
- [ ] Database password is strong (✓ already is)
- [ ] SMTP password uses App Password (Gmail) not main password
- [ ] `.env` only readable by owner (chmod 600)
- [ ] HTTPS enabled (AutoSSL)
- [ ] Regular backups scheduled

## Production Settings in .env

```env
APP_ENV=production          ← Hide error messages
APP_URL=https://yourdomain.com
BASE_PATH=/movify          ← If in subdirectory, else leave /

DB_HOST=r133813iton.cd.cpanel.net
DB_NAME=r133813iton_ai_video
DB_USER=r133813iton_dacos
DB_PASS=Azor&?2026?!

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your@gmail.com
SMTP_PASS=xxxx xxxx xxxx xxxx

SMTP_FROM=support@yourdomain.com

FAL_AI_API_KEY=d6f7fb96-a1e5-4094-87e7-fdef9acfd8f9:0b66c7406b921e669155f6506cd02e46
```

## Support

If deployment fails:
1. Check cPanel Error Log for PHP errors
2. Review `EMAIL_SETUP.md` for email-specific issues
3. Ensure database schema is imported
4. Verify all `.env` values are correct

---

**Deployment completed!** Your Movify instance should now be live with full email verification.
