# Testing Environments

This repository supports two local verification lanes:

- JavaScript/editor workflows through the `npm run env:*`, `npm run build`, and `npm run test:js*` scripts.
- PHP integration workflows through `composer test:ut`, `composer test`, and `composer test:coverage`.

## Prerequisites

- Composer v2
- Node v20 (`.nvmrc`)
- Docker, if you want to use a container-backed local database
- A MySQL client on `PATH` for `wp db reset`, `wp db check`, and related Composer scripts

On macOS with Homebrew, the client install is:

```bash
brew install mysql-client
export PATH="/opt/homebrew/opt/mysql-client/bin:$PATH"
```

## PHPUnit database bootstrap

The PHP test suite reads database credentials from the ignored file `tests/.env`.
Do not commit that file.

Generate it with:

```bash
./tools/bootstrap-test-env.sh
```

The bootstrap script uses this precedence:

1. Explicit `WP_TESTS_DB_*` environment variables
2. Auto-detection of a single running Docker `mysql` or `mariadb` container with a published `3306/tcp` port
3. Fallback defaults from `tests/.env.dist`

Use `--force` to overwrite an existing local file:

```bash
./tools/bootstrap-test-env.sh --force
```

If you want to point the suite at a non-Docker database, export the variables first:

```bash
WP_TESTS_DB_NAME=wordpress_test \
WP_TESTS_DB_USER=root \
WP_TESTS_DB_PASS=secret \
WP_TESTS_DB_HOST=127.0.0.1:3306 \
./tools/bootstrap-test-env.sh --force
```

## Suggested local flow

Install dependencies:

```bash
composer install
npm install --legacy-peer-deps
```

If you use the repo's `wp-env` scripts, start them first:

```bash
npm run env:start
```

Then bootstrap the PHP test database config:

```bash
./tools/bootstrap-test-env.sh
```

Run the main gates:

```bash
composer test
composer analyse:psalm
npm run lint
npm run test:js -- --ci
npm run build
```

Run coverage gates when you change thresholds or need an updated metrics snapshot:

```bash
composer test:coverage
npm run test:js:coverage
```

## Notes

- The bootstrap script writes only `tests/.env`, which is already ignored by Git.
- Local PHP 8.5 may emit deprecation noise from upstream WordPress and WP-CLI dependencies even when the repo gates pass.
- The committed test scripts assume `wp` can call local MySQL client tools successfully. If `wp db reset` fails with `env: mysql: No such file or directory`, fix your `PATH` first.
