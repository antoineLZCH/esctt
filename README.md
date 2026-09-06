# ES Colombienne Tennis de table

WordPress public site in a small pnpm monorepo:

- `apps/wordpress` — Bedrock project and document root (`web/`)
- `packages/theme` — Sage theme
- `packages/esctt-content` — Composer-managed WordPress plugin backed by Secure Custom Fields

The repository uses pnpm workspaces only; it does not use Nx or Turborepo.

## Local setup with Herd

Herd supplies the PHP runtime. The commands below use Composer, pnpm and PHP's
built-in server, so they do not require a Herd-specific command.

Requirements: Herd with PHP 8.3 or newer selected on `PATH`, Composer 2, Node
22.12 or newer, pnpm 10, and a local MySQL/MariaDB database.

```sh
cp apps/wordpress/.env.example apps/wordpress/.env
# Edit apps/wordpress/.env with the local database credentials.
pnpm install
pnpm setup:site
pnpm dev
```

Open <http://127.0.0.1:8080/wp/wp-admin/install.php> to finish the WordPress
installation. `pnpm setup:site` installs the locked Composer dependencies,
including Secure Custom Fields, and builds Sage's production assets. The
must-use loader loads SCF before `esctt-content`; ACF and ACF Pro are not
dependencies.

Useful commands:

```sh
pnpm theme:dev   # Vite watch mode for Sage
pnpm check       # scaffold checks, PHP syntax checks and a Sage build
```

The WordPress document root is `apps/wordpress/web`. Keep `.env`, `vendor/`,
`node_modules/`, WordPress core and generated assets out of version control.
