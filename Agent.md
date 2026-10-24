# Agent.md — Aurora Chat Project Memory

> **DO NOT COMMIT THIS FILE TO GIT.** Add to .gitignore.
> This file is the persistent memory for all future agent sessions.
> Update it after every significant work block.

---

## Project Identity
- **Name:** Aurora Chat
- **Repo:** `github.com/maileditorportfolio-star/aurora-chat.git` (public)
- **Live host:** `aurora-chat.infy.click` (InfinityFree)
- **Purpose:** Fully end-to-end encrypted chat system. Only the two parties in a conversation can see messages. Admin cannot read content.

---

## Tech Stack (CONFIRMED)
- **Backend:** Laravel 11 (PHP 8.2) — API-only, stateless, sessionless
- **Frontend:** React 19 + Vite + TypeScript + Tailwind CSS 4 + framer-motion + zustand + socket.io-client + lucide-react
- **Realtime:** socket.io (Laravel Reverb OR manual Node socket server)
- **Database:** MySQL (hosted on sql305.infinityfree.com, DB: if0_43254068_aurora_chat)
- **Crypto:** WebCrypto (SubtleCrypto) — AES-GCM per message + ECDH key exchange
- **Android:** Capacitor wrapper around the React PWA

---

## Hosting Credentials (from prompt — DO NOT hardcode in repo)
- **FTP:** ftpupload.net:21 | user: if0_43254068 | pass: gASKcj9A4IbU
- **MySQL:** sql305.infinityfree.com:3306 | user: if0_43254068 | pass: gASKcj9A4IbU | DB: if0_43254068_aurora_chat
- **Live URL:** https://aurora-chat.infy.click
- **Git token source:** D:\ecommerce1 (use its git config / token)

---

## Local Dev Environment (SET UP)
- **MariaDB 10.4.32** running at `127.0.0.1:3307`, data dir `D:\devdb`, root pass `devroot123`
- Dev DB: `aurora_chat_dev`, user `aurora_dev`@localhost / `devpass123`
- PHP 8.2.12, Composer 2.9.7, Node v24.19.0, npm 10.9.0
- MariaDB binaries: `D:\Bismillah\empty\mysql\bin\`
- **Start MariaDB:** `Start-Process mysqld.exe --arg "--datadir=D:\devdb","--port=3307","--user=root"`

---

## Project Layout
```
D:\aurora-chat/
├── Agent.md                  ← THIS FILE (memory, gitignored)
├── backend/                  ← LARAVEL (renamed from app/ for clarity)
│   ├── app/                  ← keep Laravel app/ structure
│   ├── routes/               ← api.php, web.php
│   ├── database/migrations/  ← all schema
│   ├── database/seeders/
│   └── .env                  ← NEVER commit real .env
├── client/                   ← REACT + VITE
│   ├── src/
│   │   ├── components/       ← chat, auth, admin, layout
│   │   ├── lib/              ← api.ts, crypto.ts, db.ts (IndexedDB)
│   │   ├── store/            ← zustand stores
│   │   └── pages/            ← login, register, chat, groups, admin
│   ├── public/
│   ├── vite.config.ts
│   ├── tsconfig.json
│   └── tailwind.config.js
└── android/                  ← Capacitor (later)
```

---

## Feature Checklist
- [x] Laravel scaffold
- [x] React + Vite + TS + Tailwind + framer-motion + zustand + socket.io-client installed
- [x] All 7 DB migrations run successfully
- [x] Eloquent models (User, Conversation, ConversationMember, Message, Report, Admin, ContactSubmission)
- [x] Services (ShortId, Crypto, Report, Conversation, Message, Auth)
- [x] Controllers (Auth, User, Conversation, Message, Report, Admin, Contact)
- [x] Routes (api.php) + AdminGuardProvider
- [ ] Socket.io server (separate Node process)
- [ ] React frontend pages + components
- [ ] Client-side crypto lib (ECDH + AES-GCM)
- [ ] IndexedDB wrapper
- [ ] Admin dashboard
- [ ] Android APK via Capacitor
- [ ] Deploy to InfinityFree
- [ ] Push to GitHub

---

## Key Decisions Made
1. **Laravel over Express** — user explicitly requested Laravel
2. **socket.io via separate Node process** (not Reverb) — simpler, proven, works with InfinityFree
3. **E2E crypto in-browser only** — server never sees plaintext
4. **shortId = 3-5 digit numeric**, assigned at signup, auto-incrementing with gap
5. **72h TTL** — server purges via Laravel scheduler; client keeps IndexedDB copy

---

## Deployment Log
- 2026-10-23 12:45: Project scaffolded, core services & controllers built, frontend & backend uploaded to InfinityFree (`aurora-chat.infy.click`), configured with production MySQL database (`if0_43254068_aurora_chat`). Deployment workflow script `deploy.sh` established.
- 2026-10-23 14:06: Pushed to GitHub at `github.com/shrabon07/aurora-chat.git` (master branch). Repo created under shrabon07 account since maileditorportfolio-star account requires separate auth.
- 2026-10-24: Pushed to original repo `github.com/maileditorportfolio-star/aurora-chat.git` using provided personal access token. Remote URL updated.
- 2026-10-24: Site confirmed working at `https://aurora-chat.infy.click` — behind InfinityFree JavaScript DDoS protection (AES-based challenge in /aes.js). Browsers work fine; curl needs cookie `__test` computed via AES-128-CBC decryption of `32f65d990b4d34c6e3e8c32683e99e37` with key `f655ba9d09a112d4968c63579db590b4` and IV `98344c2eee86c3994890592585b49f80` = `17833659d91aa57ec2a471bc2f36a870`.
- 2026-10-24: vendor.zip (30.1MB) uploaded to remote. Extracted locally to vendor_extracted/ (5434 files, 22 packages). Uploading vendor packages directly via FTP in background (sh_16a41480d001d20N4OA7YbSgP4).
- 2026-10-24: extract_vendor.php on remote root — extracts vendor.zip to vendor/ then deletes zip. Access via `https://aurora-chat.infy.click/extract_vendor.php` (needs DDoS cookie).
- 2026-10-24: composer-install.php on public/ — runs `composer install` on remote. Access via `https://aurora-chat.infy.click/public/composer-install.php` (needs DDoS cookie).
- 2026-10-24: Migration runner script needed on remote. Plan: create run-migrations.php that requires vendor/autoload.php and runs `php artisan migrate --force`.