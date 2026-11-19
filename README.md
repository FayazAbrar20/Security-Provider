# Security Provider — Guard Booking Platform

A web platform where clients book verified security guards for homes, businesses or personal protection. Guards apply, an admin verifies them, and clients book approved guards. Built with **PHP and MySQL**.

<!-- Add screenshots here, e.g.:
![Client dashboard](docs/client-dashboard.png)
![Admin approvals](docs/admin.png)
-->

## Features

| Role | Can do |
|---|---|
| **Client** | Register and log in; search approved guards by name, location or specialty; view guard profiles; book a guard; see booking status (pending, confirmed, rejected, cancelled); cancel a pending booking; edit profile; change password |
| **Guard** | Apply with profile details and a photo; log in; see bookings; confirm or reject pending bookings; change password |
| **Admin** | See platform stats; review pending guard applications and approve or reject them; add admins; create a recovery key |

Guards are only visible to clients after an admin approves them.

**Forgot password** on every login page: clients and guards confirm their email, phone number and full name; admins confirm their email and a one-time recovery key (shown at signup or created from the dashboard). After 5 failed attempts for an email in 15 minutes, resets are blocked, and failures never reveal which emails exist. Note: phone and name are not secret, so a production version should email a one-time reset link instead.

**Design:** one shared dark theme (`assets/theme.css`) matching the landing page: frosted-glass cards, gold accents, animated stat cards and pop-ups without JavaScript.

## Security

- **Prepared statements for every query.** User input is never placed into SQL text, so it cannot change a query (no SQL injection).
- **Output escaping.** Everything a user typed is HTML-escaped before it is shown, so a name like `<script>…</script>` is displayed as text, never run (no XSS).
- **CSRF protection.** Every form carries a per-session token, and any POST without it is rejected. Bookings and cancellations can only happen through these forms, never by opening a link.
- **Password hashing** with PHP `password_hash` / `password_verify`, and a password policy: at least 8 characters, with letters and numbers.
- **Unforgeable "remember me".** The cookie holds a random selector and validator, and the database stores only a SHA-256 hash of the validator. Tokens expire after 7 days, are replaced on every use, and are deleted on logout and on password change.
- **Sessions:** the session ID is regenerated at login, and session cookies are HttpOnly and SameSite.
- **Admin accounts:** only the very first admin can sign up publicly (first-time setup). After that, only a logged-in admin can create admins.
- **Safe photo uploads:**
  - the file's content is checked to be a real JPG, PNG or GIF;
  - maximum 2 MB;
  - stored under a random file name;
  - scripts can never run from the uploads folder.
- **No database errors shown to visitors;** errors go to the server log.
- **Account changes are atomic:** creating a user and their profile happens in one transaction.

## Tech stack

PHP 8, MySQL / MariaDB (mysqli with prepared statements), HTML, CSS, Apache (XAMPP for local development), Git.

## Getting started (local, with XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Copy this repository into `C:\xampp\htdocs\Security-Provider`.
3. Open **phpMyAdmin** (http://localhost/phpmyadmin), go to **Import**, and import `database/schema.sql`. Upgrading an existing database instead? Import `database/migrate.sql`.
4. Open http://localhost/Security-Provider/Project01/.
5. Create the first admin at http://localhost/Security-Provider/Project01/Admin/admin_registration.php.
6. Register a guard, approve it as admin, then register a client and book the guard.

The database connection defaults to XAMPP's `root` user with no password. To use other credentials, set the `DB_HOST`, `DB_USER`, `DB_PASS` and `DB_NAME` environment variables; there is no need to edit the code.

## Project structure

```
Project01/
  index.php              Landing page
  db.php                 Database connection (loads includes/security.php)
  includes/security.php  Sessions, CSRF, escaping, prepared-statement helper, remember-me tokens
  Admin/                 Admin registration, login, dashboard, logout
  Client/                Client registration, login, dashboard, booking, profile, password, logout
  Guard/                 Guard registration, login, dashboard, logout
  uploads/               Site images; guard photos are uploaded to uploads/guards/
database/
  schema.sql             Full database for a new install
  migrate.sql            Upgrade for an existing database
```

## Team

Fayaz Abrar, Hasibul Islam, MD. Imrul Kayes Rifat
