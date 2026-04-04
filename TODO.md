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

- [x] Create core/helpers.php (parseDuration, parseExpiration, parseRate, valuePlaceholder)
- [x] Create pages/edit_profile.php (logic only)
- [x] Create views/edit_profile.view.php (HTML only)
- [x] Update root edit_profile.php → thin entry point

## Phase 3: remaining pages

- [x] users.php ✅ DONE
- [ ] index.php
- [ ] login.php
- [ ] adduser.php
- [ ] edit_user.php
- [ ] log.php
- [ ] voucher.php
- [ ] logout.php
- [ ] actions/ (update paths)
- [ ] cron_disable.php
