# Aurora Chat

A production-ready, end-to-end encrypted chat platform: a **Laravel 12** API with a **React 19** frontend, a dedicated **Node.js Socket.IO** real-time server, **MySQL** persistence, and an **Android app** built with Capacitor. Every message is encrypted on the device with **WebCrypto (ECDH + AES-GCM 256-bit)** before it leaves it — the server only ever stores ciphertext, and even that is **auto-purged after 72 hours**.

This repository contains **source code only**. No credentials, no database dumps, and no runtime data are committed — you create your own environment from the provided `.env.example` files.

---

## Table of contents

1. [Features](#features)
2. [Tech stack](#tech-stack)
3. [Requirements](#requirements)
4. [Download the project](#download-the-project)
5. [Local setup](#local-setup)
6. [Project structure](#project-structure)
7. [Available scripts](#available-scripts)
8. [How the encryption works](#how-the-encryption-works)
9. [API reference](#api-reference)
10. [Realtime events](#realtime-events)
11. [Deployment](#deployment)
12. [Android app](#android-app)
13. [Cron jobs](#cron-jobs)
14. [Troubleshooting](#troubleshooting)
15. [Contributing](#contributing)
16. [License](#license)

---

## Features

### Web app (React 19 + Vite)

- **Auth** — email/password signup and login, plus Google OAuth sign-in
- **Public IDs** — every user gets a short, memorable **3–5 digit numeric ID** at signup; find anyone by ID
- **One-to-one & group conversations** — create chats, add members, live presence
- **End-to-end encryption** — ECDH key exchange + AES-GCM per message, all in-browser via WebCrypto
- **72-hour server TTL** — the server auto-deletes expired messages; your own history stays in **IndexedDB**
- **Reports** — report any user; a user who accumulates **50 reports** is permanently blocked and their messages deleted
- **Framer Motion** — animated transitions, message bubbles, and micro-interactions throughout
- **Admin dashboard** (`/admin`) — view submitted contact emails, blocked users, and platform stats

### API (Laravel 12)

- **JWT auth** (`tymon/jwt-auth`) — stateless API with separate `api` and `admin` guards
- **Eloquent + MySQL** — 8 tables: users, conversations, conversation_members, messages, reports, admins, contact_submissions, cache/sessions
- **Consistent responses** — `{ success, data | message }` envelope across every endpoint
- **Report & moderation pipeline** — transactional report counting, auto-block threshold, email notifications

### Realtime (Socket.IO)

- Separate **Node.js** socket server (no Reverb dependency) — simpler to host, proven on cPanel
- Per-conversation rooms with **presence** (online/offline), **typing** indicators, and **encrypted message relay**
- Socket connections are **JWT-authenticated** — the socket server validates every handshake token against the API

---

## Tech stack

| Layer     | Technology                                             |
| --------- | ------------------------------------------------------ |
| Frontend  | React 19, Vite, TypeScript                              |
| Styling   | Tailwind CSS, Framer Motion, Lucide icons               |
| State     | Zustand                                                 |
| Realtime  | socket.io-client (web) / Socket.IO (Node server)        |
| Backend   | Laravel 12 (PHP 8.2+), tymon/jwt-auth                   |
| Database  | MySQL 8 / MariaDB via Eloquent migrations               |
| Crypto    | WebCrypto (SubtleCrypto) — ECDH P-256 + AES-GCM-256     |
| Android   | Capacitor wrapper around the built React PWA            |

---

## Requirements

- **PHP 8.2+** with the MySQL, PDO, and mbstring extensions
- **Composer 2+**
- **Node.js 20+** (developed on Node 24)
- **MySQL 8+** or **MariaDB 10.6+**
- (Only for Android builds) **Android Studio** with **JDK 17+** and the Android SDK

Check your versions:

```bash
php -v
composer -V
node -v
npm -v
mysql --version
```

---

## Download the project

### Option A — clone with Git (recommended)

```bash
git clone https://github.com/maileditorportfolio-star/aurora-chat.git
cd aurora-chat
```

Already have it locally but want the latest? Inside the project folder:

```bash
git pull origin main
```

### Option B — download as a ZIP

1. Open the repository on GitHub: <https://github.com/maileditorportfolio-star/aurora-chat>
2. Click **Code → Download ZIP**
3. Unzip it and rename the folder to `aurora-chat`
4. Open a terminal inside that folder

---

## Local setup

### 1. Install dependencies

```bash
composer install          # Laravel API

cd client
npm install               # React frontend
```

The socket server has its own manifest in `socket-server/`:

```bash
cd socket-server
npm install
```

### 2. Create the database

```sql
CREATE DATABASE aurora_chat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Configure environment variables

**API** — copy `.env.example` to `.env`:

```bash
# Windows PowerShell
Copy-Item .env.example .env

# macOS / Linux
cp .env.example .env
```

Edit `.env`:

```ini
APP_NAME="Aurora Chat"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (MySQL / MariaDB)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aurora_chat
DB_USERNAME=root
DB_PASSWORD="your-password"

# Auth — CHANGE THIS to a long random string
JWT_SECRET="change-me-to-a-long-random-string"
JWT_EXPIRES_IN="60"

QUEUE_CONNECTION=database
SESSION_DRIVER=database

# Mail
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@yourdomain.com"

# Chat behaviour
MESSAGE_TTL_SECONDS=259200        # 72 hours
REPORT_BLOCK_THRESHOLD=50
```

Generate the application key:

```bash
php artisan key:generate
```

**Client** — create `client/.env.local`:

```ini
VITE_API_URL=http://localhost:8000/api
VITE_SOCKET_URL=http://localhost:6003
```

> `VITE_*` values are inlined at build time. Point them at your production API/socket URLs before running a production build.

**Socket server** — copy `socket-server/.env.example` to `socket-server/.env`:

```ini
PORT=6003
API_URL=http://localhost:8000/api
SOCKET_IO_CORS=http://localhost:5173
```

### 4. Migrate the database

```bash
php artisan migrate
```

This creates all 8 tables: users, conversations, conversation_members, messages, reports, admins, contact_submissions, cache, sessions.

### 5. Run everything

```bash
# Terminal 1 — Laravel API on :8000
php artisan serve

# Terminal 2 — React dev server on :5173
cd client
npm run dev

# Terminal 3 — Socket.IO server on :6003
cd socket-server
node index.js
```

- Web app → <http://localhost:5173>
- API health → <http://localhost:8000/api/health>
- Socket health → <http://localhost:6003/health>

Create an admin account by inserting a row into the `admins` table (email + bcrypt-hashed password), then open <http://localhost:5173/admin>.

> **Important:** the React dev server serves the app, but Laravel also serves the built frontend from `public/chat.html`. For local development use the Vite port; for production use Laravel's port.

---

## Project structure

```
aurora-chat/
├── app/
│   ├── Console/Commands/     # PurgeExpiredMessages (72h TTL cleanup)
│   ├── Http/
│   │   ├── Controllers/      # Auth, User, Conversation, Message, Report, Admin, Contact
│   │   └── Middleware/       # FixCorruptedJson (LiteSpeed/WAF body repair)
│   ├── Models/               # User, Conversation, ConversationMember, Message, Report, Admin, ContactSubmission
│   ├── Providers/            # AdminGuardProvider (admin JWT guard)
│   └── Services/             # Auth, ShortId, Crypto, Conversation, Message, Report
├── bootstrap/app.php         # Middleware registration (CORS + JSON repair)
├── config/                   # auth.php (api + admin guards), jwt.php, app.php
├── database/
│   └── migrations/           # 8 tables
├── client/                   # React 19 + Vite frontend
│   ├── src/
│   │   ├── components/       # auth, chat, admin, layout
│   │   ├── lib/              # api.ts (axios), socket.ts, crypto.ts (WebCrypto), db.ts (IndexedDB)
│   │   ├── store/            # zustand stores (auth, chat)
│   │   └── styles.css
│   ├── index.html
│   └── vite.config.ts        # VITE_API_URL / VITE_SOCKET_URL inlined at build time
├── public/                   # Laravel docroot — built frontend assets + chat.html/admin.html
├── routes/
│   ├── api.php               # All REST endpoints
│   └── web.php               # /chat and /admin page routes
├── socket-server/            # Standalone Node.js Socket.IO server
│   ├── index.js              # Local/dev entry (listens on a port)
│   ├── passenger.js          # cPanel/Passenger entry (served under a base URI)
│   └── package.json
├── android/                  # Capacitor Android app (generated)
├── storage/                  # Logs, cache, sessions
├── .env.example
├── composer.json
└── package.json
```

---

## Available scripts

### Composer (API)

Run from the repository root.

| Command        | What it does                                                    |
| -------------- | --------------------------------------------------------------- |
| `composer run setup` | Install deps, copy `.env`, generate key, migrate, build client |
| `composer run dev`   | Run `php artisan serve` + queue listener + log tail + Vite together |
| `composer test`      | Clear config cache and run the PHPUnit suite                     |

### Client (React)

Run from `client/`.

| Command         | What it does                    |
| --------------- | ------------------------------- |
| `npm run dev`   | Vite dev server on `:5173`      |
| `npm run build` | TypeScript check + production build to `client/dist` |
| `npm run preview` | Preview the production build locally  |

### Socket server

Run from `socket-server/`.

| Command             | What it does                                       |
| ------------------- | -------------------------------------------------- |
| `node index.js`     | Run locally, listening on `PORT` (default `6003`)  |
| `node passenger.js` | Run under CloudLinux Passenger (cPanel)            |

---

## How the encryption works

Aurora Chat is **zero-knowledge by design**: the server stores ciphertext and cannot read message content.

1. **Per-user long-term key** — each user generates an ECDH key pair (P-256) in-browser and uploads the **public key** via `POST /auth/public-key`.
2. **Key exchange** — before sending, a sender fetches the recipient's public key, generates a fresh **ephemeral** key pair, and derives a shared secret with ECDH.
3. **Per-message encryption** — the message is encrypted with **AES-GCM-256** using the derived key plus a random IV; the sender's ephemeral public key, IV, and auth tag travel with the ciphertext.
4. **Decryption** — the recipient derives the same shared secret from their own long-term private key + the sender's ephemeral key, then decrypts. No third party (including the server) can decrypt.
5. **Retention** — the server deletes messages after **72 hours** (`messages:purge-expired` cron). Your own history is kept in **IndexedDB** on your device so old conversations survive server-side expiry.

---

## API reference

Base URL: `http://localhost:8000/api` (or your deployed API URL)

Every response uses the envelope `{ success, data | message }`.

### Auth

| Method | Endpoint           | Auth  | Description                                    |
| ------ | ------------------ | ----- | ---------------------------------------------- |
| GET    | `/health`          | —     | Service health check                           |
| POST   | `/auth/register`   | —     | Create account (name, email, password)         |
| POST   | `/auth/login`      | —     | Sign in, returns a JWT                         |
| POST   | `/auth/google`     | —     | Google OAuth sign-in (google_id, email, name)  |
| POST   | `/auth/logout`     | token | Invalidate the current token                   |
| GET    | `/auth/me`         | token | Current profile                                |
| POST   | `/auth/public-key` | token | Upload the user's ECDH public key              |

### Users

| Method | Endpoint               | Auth  | Description                          |
| ------ | ---------------------- | ----- | ------------------------------------ |
| GET    | `/users/by-id/{shortId}` | —   | Look up a user by their public ID    |
| GET    | `/users/search?q=`     | —     | Search users by name or ID           |

### Conversations

| Method | Endpoint                            | Auth  | Description                          |
| ------ | ----------------------------------- | ----- | ------------------------------------ |
| GET    | `/conversations`                    | token | List the current user's conversations |
| POST   | `/conversations`                    | token | Start a conversation with a user ID  |
| POST   | `/conversations/{id}/members`       | token | Add a member to a group conversation |

### Messages

| Method | Endpoint                        | Auth  | Description                          |
| ------ | ------------------------------- | ----- | ------------------------------------ |
| POST   | `/messages`                     | token | Store an encrypted message           |
| GET    | `/messages/{conversationId}`    | token | Message history (paginated)          |
| DELETE | `/messages/{messageId}`         | token | Soft-delete a message                |

### Reports & contact

| Method | Endpoint     | Auth  | Description                                       |
| ------ | ------------ | ----- | ------------------------------------------------- |
| POST   | `/reports`   | token | Report a user (auto-block at 50 reports)          |
| POST   | `/contact`   | —     | Submit a contact form (viewed in the admin panel) |

### Admin

| Method | Endpoint                         | Auth (admin) | Description                    |
| ------ | -------------------------------- | ------------ | ------------------------------ |
| POST   | `/admin/login`                   | —            | Admin sign in                  |
| GET    | `/admin/me`                      | admin        | Current admin                  |
| GET    | `/admin/emails`                  | admin        | Registered users (paginated)   |
| GET    | `/admin/blocked`                 | admin        | Blocked users                  |
| GET    | `/admin/submissions`             | admin        | Contact form submissions       |
| POST   | `/admin/submissions/{id}/read`   | admin        | Mark a submission read         |
| GET    | `/admin/stats`                   | admin        | Platform statistics            |

---

## Realtime events

Socket URL: `http://localhost:6003` (or your deployed socket URL) — connect with `auth: { token }`.

### Emitted by the client

| Event                 | Payload                                          |
| --------------------- | ------------------------------------------------ |
| `conversation:join`   | `{ conversationId }`                             |
| `conversation:leave`  | `{ conversationId }`                             |
| `message:send`        | `{ conversationId, messageId, ciphertext, senderEphemeralPubkey, iv, mac }` + ack |
| `typing:start`        | `{ conversationId }`                             |
| `typing:stop`         | `{ conversationId }`                             |
| `key:announce`        | `{ conversationId, publicKey }`                  |

### Emitted by the server

| Event               | Payload                                  |
| ------------------- | ---------------------------------------- |
| `connected`         | `{ user }` after JWT validation          |
| `message:received`  | The stored encrypted message record      |
| `presence:online`   | `{ userId }`                             |
| `presence:offline`  | `{ userId }`                             |
| `typing:start`      | `{ userId }`                             |
| `typing:stop`       | `{ userId }`                             |
| `key:announced`     | `{ userId, publicKey }`                  |

---

## Deployment

Aurora Chat is deployed in three parts: the **Laravel API**, the **React frontend**, and the **Socket.IO server**. They can run on one machine (recommended) or split across hosts — in which case set `VITE_API_URL` and `VITE_SOCKET_URL` to the public socket/API URLs **before building the client**.

### Option 1 — cPanel shared hosting (LiteSpeed + Passenger)

This is the configuration this project has been verified on (Aurora Cyber, `chat.auroracyber.online`).

#### 1. Create the subdomain

```bash
# Via UAPI (or cPanel → Subdomains)
curl -k -u "user:pass" \
  "https://yourhost.com:2083/execute/SubDomain/addsubdomain?subdomain=chat.yourdomain.com&documentroot=chat"
```

The document root (`~/chat`) becomes a **symlink** to the Laravel `public/` folder later.

#### 2. Create the MySQL database

Via cPanel or UAPI — a **dedicated** database for chat, e.g. `yourprefix_chat` with a matching user and all privileges. Never share it with another application's database.

#### 3. Upload the source

Build the frontend locally first, then ship one tarball (the shared-hosting host may lack PHP extensions needed for `composer install` on the server — upload a pre-built `vendor/`):

```bash
cd client && npm run build && cd ..

# Copy the built frontend into Laravel's docroot
cp client/dist/assets/* public/
cp client/dist/index.html public/chat.html

tar czf deploy-chat.tgz app bootstrap config database public routes storage artisan composer.json composer.lock .env
scp deploy-chat.tgz user@server:~
ssh user@server "cd aurora-chat && tar xzf deploy-chat.tgz"
```

#### 4. Configure production

Edit the remote `.env` (`APP_ENV=production`, `APP_DEBUG=false`, real DB credentials, strong `JWT_SECRET`, `MAIL_MAILER=sendmail` on shared hosts), then:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Wire the symlink: `~/chat` → `~/aurora-chat/public`.

#### 5. Run the Socket.IO server with Passenger

Shared hosts typically expose Node via **CloudLinux alt-nodejs**:

```bash
# cPanel → Passenger App Management → Create Application
# App name: aurora-chat-socket
# Language: Node.js (alt-nodejs22)
# Application URL: https://chat.yourdomain.com/socketio
# Application root: ~/aurora-chat/socket-server
# Startup file: passenger.js
```

`passenger.js` serves socket.io under the `/socketio` base URI and verifies every connection's JWT against the API. Verify with:

```bash
curl -s https://chat.yourdomain.com/socketio/health
# → {"status":"ok","service":"aurora-chat-socket"}
```

> **Known issue — LiteSpeed mod_security corrupts JSON bodies.** The WAF escapes colons and commas in POST bodies (`{"a":1}` arrives as `{"a\:1}`). The `App\Http\Middleware\FixCorruptedJson` middleware repairs the body before routing. If you deploy behind this host, keep that middleware registered in `bootstrap/app.php`.

#### 6. Cron jobs (cPanel → Cron Jobs)

```bash
* * * * *  php /home/USER/aurora-chat/artisan queue:work --stop-when-empty --tries=3 --max-time=50
*/5 * * * *  php /home/USER/aurora-chat/artisan messages:purge-expired
```

### Option 2 — VPS (Nginx + PHP-FPM + Node)

1. Install PHP 8.2+, MySQL, Node 20+, and Nginx.
2. Clone the repo, `composer install`, `npm install && npm run build` in `client/`, copy `client/dist` into `public/` as above.
3. Serve `public/` with Nginx (standard Laravel `try_files` block), terminate TLS with Let's Encrypt.
4. Run the socket server with **PM2**:

```bash
cd socket-server
npm install --omit=dev
pm2 start index.js --name aurora-socket
pm2 save
```

5. Point Nginx at the socket:

```nginx
location /socketio/ {
    proxy_pass http://127.0.0.1:6003/;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
}
```

6. Build the client **with the public URLs** before copying it into `public/`:

```bash
cd client
VITE_API_URL=https://chat.yourdomain.com/api \
VITE_SOCKET_URL=https://chat.yourdomain.com/socketio \
npm run build
```

7. Add the system cron entries from [Option 1, step 6](#6-cron-jobs-cpanel--cron-jobs).

### Post-deploy checklist

- [ ] `GET https://chat.yourdomain.com/api/health` returns `{"success":true,...}`
- [ ] `GET https://chat.yourdomain.com/socketio/health` (or `:6003/health`) returns `{"status":"ok",...}`
- [ ] Register + login round-trip works in a **clean browser/incognito window**
- [ ] A message sent between two accounts decrypts on the other side
- [ ] Admin dashboard (`/admin`) logs in and lists users
- [ ] Cron logs show `queue:work` and `messages:purge-expired` running

---

## Android app

The Android app is a **Capacitor** wrapper around the built React PWA — one codebase, native install.

### Build an APK

1. Install **Android Studio** (JDK 17+ and the Android SDK are included).
2. Build the frontend for production:

   ```bash
   cd client
   VITE_API_URL=https://chat.yourdomain.com/api \
   VITE_SOCKET_URL=https://chat.yourdomain.com/socketio \
   npm run build
   ```

3. Sync Capacitor (the `android/` platform is already scaffolded in this repo):

   ```bash
   npx cap sync android
   ```

4. Open the project:

   ```bash
   npx cap open android
   ```

5. In Android Studio, **Build → Build Bundle(s)/APK(s) → Build APK(s)**, or from the CLI:

   ```bash
   cd android
   ./gradlew assembleDebug        # android/app/build/outputs/apk/debug/app-debug.apk
   ```

### Release notes

- The bundle ID is `com.auroracyber.chat`.
- Release builds need a signing keystore — configure it in `android/app/build.gradle` before `./gradlew assembleRelease`.
- WebCrypto (SubtleCrypto) is only available in **secure contexts**: the app works out of the box against `https://` endpoints.
- If you add native capabilities later (push notifications, background sync), add them as Capacitor plugins in `client/` and re-run `npx cap sync android`.

---

## Cron jobs

Two scheduled jobs keep the service healthy (see [Deployment → cPanel step 6](#6-cron-jobs-cpanel--cron-jobs)):

| Schedule   | Command                          | Purpose                                   |
| ---------- | -------------------------------- | ----------------------------------------- |
| `* * * * *`    | `artisan queue:work --stop-when-empty --tries=3 --max-time=50` | Drain the database queue (report emails, etc.) |
| `*/5 * * * *`  | `artisan messages:purge-expired`  | Delete messages past their 72-hour TTL   |

---

## Troubleshooting

**`Array to string conversion` on admin login** — the `admin` guard receives a config array instead of a provider name. Ensure `AdminGuardProvider` passes `$config['provider']` (not `$config`) to `createUserProvider()`, and that `config/auth.php` defines the `admins` eloquent provider.

**`Method [login] does not exist` from JWT** — the custom admin guard must call `JWTAuth::fromUser($admin)`; `JWTAuth::login()` does not exist in tymon/jwt-auth.

**Admin login fails with a blank body** — LiteSpeed/WAF may be mangling the JSON body. Confirm the `FixCorruptedJson` middleware is registered before your routes in `bootstrap/app.php`.

**`Access denied for user`** — verify the DB credentials in `.env` and that the user has privileges on the *chat* database specifically (cPanel users are prefixed, e.g. `yourprefix_chat`).

**Socket connects but messages never arrive** — the socket server validates the JWT against `API_URL/auth/me`. Check `API_URL` in `socket-server/.env` (or the Passenger app's environment) points at the **API**, not the web root.

**Socket URL 404 in production** — on cPanel the socket is served under the Passenger base URI (`/socketio`), and the client connects with `path: '/socket.io'` against that base. On a VPS behind Nginx, make sure the websocket `Upgrade` headers are proxied (see the config block above).

**CORS errors** — the web middleware adds `HandleCors`; make sure your browser origin is reachable without an intermediary stripping the headers.

**Stale frontend after a code change** — rebuild the client, re-copy `dist` into `public/`, and clear the browser cache. Vite hashes asset names, so old assets can linger.

**`JWT_SECRET` warnings in production** — set a long random value and re-run `php artisan config:cache`.

---

## Contributing

We welcome contributions! Please read [CONTRIBUTING.md](./CONTRIBUTING.md) for the full process — it covers the workflow (branching, PRs, code style), how to add a new API endpoint or realtime event, and how to extend the Android app.

**Quick rules of thumb:**

- Keep the API stateless and the response envelope (`{ success, data | message }`) consistent.
- Never store or log plaintext messages — all payloads must stay ciphertext end to end.
- Run `composer test` and the client TypeScript build before opening a PR.
- For deployment changes, update the [Deployment](#deployment) section so the guide stays accurate.

---

## License

Released for use and modification under the [MIT license](https://opensource.org/licenses/MIT). Replace the branding with your own before going live.
