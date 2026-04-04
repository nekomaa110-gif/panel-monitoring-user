# Refactor TODO - PHP RADIUS Panel

## Phase 1: profile.php ✅ DONE

- [x] Create core/auth.php
- [x] Create views/layout/header.php
- [x] Create views/layout/footer.php
- [x] Create views/layout/sidebar.php
- [x] Create views/layout/navbar.php
- [x] Create pages/profile.php (logic only)
- [x] Create views/profile.view.php (HTML only)
- [x] Update root profile.php → thin entry point

## Phase 2: edit_profile.php ✅ DONE

- [x] Create core/helpers.php (parseDuration, parseExpiration, parseRate, valuePlaceholder, indoDate)
- [x] Create pages/edit_profile.php (logic only)
- [x] Create views/edit_profile.view.php (HTML only)
- [x] Update root edit_profile.php → thin entry point

## Phase 3: remaining pages ✅ ALL DONE

- [x] users.php — pages/users.php + views/users.view.php
- [x] index.php — pages/index.php + views/index.view.php
- [x] adduser.php — pages/adduser.php + views/adduser.view.php
- [x] edit_user.php — pages/edit_user.php + views/edit_user.view.php
- [x] log.php — pages/log.php + views/log.view.php
- [x] voucher.php — pages/voucher.php + views/voucher.view.php
- [x] login.php — pages/login.php + views/login.view.php
- [x] logout.php — no change needed (pure redirect, no HTML)
- [x] actions/delete.php — updated require "../core/auth.php"
- [x] actions/disable.php — updated require "../core/auth.php"
- [x] actions/enable.php — updated require "../core/auth.php"
- [x] cron_disable.php — updated to use **DIR** path, no view needed (CLI script)

## Final Structure

```
/var/www/zeronet/
├── config/db.php               ← DB connection (unchanged)
├── core/
│   ├── auth.php                ← Session guard
│   └── helpers.php             ← parseDuration, parseExpiration, parseRate, valuePlaceholder, indoDate
├── pages/
│   ├── index.php               ← Dashboard logic
│   ├── login.php               ← Login logic
│   ├── profile.php             ← Profile CRUD logic
│   ├── edit_profile.php        ← Edit profile attributes logic
│   ├── users.php               ← Users list logic
│   ├── adduser.php             ← Add user logic
│   ├── edit_user.php           ← Edit user logic
│   ├── log.php                 ← Log reader logic
│   └── voucher.php             ← Voucher generate/import/delete logic
├── views/
│   ├── layout/
│   │   ├── header.php          ← <head> + Bootstrap CDN
│   │   ├── footer.php          ← $extraJs + </body></html>
│   │   ├── sidebar.php         ← Nav links with active state
│   │   └── navbar.php          ← Top bar with $navTitle + logout
│   ├── index.view.php
│   ├── login.view.php
│   ├── profile.view.php
│   ├── edit_profile.view.php
│   ├── users.view.php
│   ├── adduser.view.php
│   ├── edit_user.view.php
│   ├── log.view.php
│   └── voucher.view.php
├── actions/
│   ├── delete.php              ← require "../core/auth.php"
│   ├── disable.php             ← require "../core/auth.php"
│   └── enable.php              ← require "../core/auth.php"
├── assets/style.css            ← unchanged
├── cron_disable.php            ← CLI cron, no view, uses __DIR__
├── logout.php                  ← session_destroy + redirect (unchanged)
│
│   (Root entry points — each is 1 line: require __DIR__ . "/pages/X.php")
├── index.php
├── login.php
├── profile.php
├── edit_profile.php
├── users.php
├── adduser.php
├── edit_user.php
├── log.php
└── voucher.php
```
