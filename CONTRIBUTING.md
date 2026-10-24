# Contributing

Thanks for taking the time to improve Aurora Chat. This guide covers the
day-to-day workflow so changes land consistently.

## Getting set up

```bash
composer install
cd client && npm install && cd ..
cd socket-server && npm install && cd ..

cp .env.example .env
# edit .env with local MySQL credentials + a strong JWT_SECRET
php artisan key:generate
php artisan migrate

# Build the frontend once so the Laravel docroot has assets
cd client && npm run build && cd ..

# Terminal 1
php artisan serve
# Terminal 2
cd client && npm run dev
# Terminal 3
cd socket-server && node index.js
```

See the [README](README.md#local-setup) for database details.

## Before opening a pull request

Run the same checks as CI:

```bash
composer test
cd client && npm run build
cd socket-server && node -e "require('child_process').execSync('node -c index.js')"
```

- **Encryption invariant:** messages must always leave the client as ciphertext.
  Never log or echo plaintext content.
- **Response envelope:** keep `{ success, data | message }` consistent across every
  endpoint.
- **Middleware ordering:** `FixCorruptedJson` must run before route handlers so
  controllers receive clean JSON (see `bootstrap/app.php`).

## Code style

- PHP: `declare(strict_types=1)` on every file; PSR-12-ish.
- Client: TypeScript, ESM, `import` ordering by layer (`lib` → `store` →
  `components`).
- Keep controllers thin: validate input, delegate to services, return a JSON
  envelope.
- Never store or log plaintext messages — all payloads stay ciphertext end to
  end.

## Commit messages

Use a short conventional prefix and keep each commit focused:

```
feat(api): add conversation member removal endpoint
fix(client): re-establish socket on reconnect after network loss
fix(api): pass provider name instead of config to createUserProvider
test(api): add happy-path spec for message history
docs: document the cPanel deployment workflow
chore: update dependencies
```

Prefixes in use: `feat`, `fix`, `test`, `docs`, `chore`, `ci`, `build`,
`style`, `refactor`.

## Database changes

1. Edit the migration in `database/migrations/` (add a new file if the change
   is additive).
2. Run `php artisan migrate --force` against your local database.
3. Commit the migration. The schema is the source of truth — never hand-edit
   a migration after it is deployed.

## Adding a new API endpoint

1. Add a controller method in `app/Http/Controllers/`.
2. Register the route in `routes/api.php` and wrap it with the correct guard
   (`auth:api` or `auth:admin`).
3. Add the typed client helper in `client/src/lib/api.ts`.
4. Document the endpoint in the [API reference](README.md#api-reference).

## Adding a new realtime event

1. Emit from `socket-server/index.js` (and `passenger.js` for production).
2. Add the matching handler in `client/src/lib/socket.ts`.
3. Document the event in the [Realtime events](README.md#realtime-events) table.

## Extending the Android app

1. Build the client with the production URLs.
2. Run `npx cap sync android` (the `android/` platform is already scaffolded).
3. Open the project with `npx cap open android` and build in Android Studio.
4. Update the [Android app](README.md#android-app) section if you change the
   bundle ID or add a native plugin.

## Security

- Never commit `.env` files, database dumps or uploaded files.
- Do not log credentials or plaintext messages.
- Report vulnerabilities privately instead of opening a public issue.