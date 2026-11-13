# Repository Guidelines

## Project Structure & Module Organization

WordPress uses free Polylang, MySQL, and optional AnythingLLM.

- `wp-content/themes/xdj-site/`: PHP templates, styles, and original brand assets.
- `wp-content/plugins/xdj-business/`: content types, multilingual helpers, inquiry API, and administration.
- `docker/`: WordPress and scheduler images, startup scripts, and vendored Polylang archive.
- `ops/`: local Compose wrapper, initialization, and legacy migration scripts.
- `tests/`: executable Python smoke checks.
- `migration/`: import manifests and reports; downloaded HTML, images, and videos are ignored.
- `docs/`: construction plan, technical design, platform research, and archived architecture.

## Build, Test, and Development Commands

Run from the repository root with Docker Desktop running:

```sh
./ops/local.sh config --quiet        # Validate local Compose configuration
./ops/local.sh up -d --build         # Build and start at http://localhost:8080
./ops/local.sh logs --tail=100       # Inspect service logs
./ops/local.sh stop                 # Stop without deleting persistent data
python3 tests/inquiry-smoke.py
python3 tests/multilingual-smoke.py
python3 tests/media-migration.py
```

The media check requires local migration files and `ffmpeg`. Start AI separately with `./ops/local.sh --profile ai up -d anythingllm`.

Startup copies project code from images into WordPress storage. Rebuild after source changes; container-only edits are temporary. The main filename is intentionally `docker-compos.yaml`.

## Coding Style & Naming Conventions

Use four spaces for new PHP, Python, and JavaScript blocks, and two spaces for YAML. Follow WordPress conventions: prefix project functions and options with `xdj_`, sanitize inputs, escape output, and check capabilities and nonces for administrative mutations. Preserve nearby style; avoid unrelated reformatting. No formatter is configured. Lint changed PHP inside the WordPress container with `php -l`.

Language slugs: `zh-hans`, `zh-hant`, `en`, `fr`, `de`, `it`, `es`. Avoid content-type rewrite slugs that collide with translated pages.

## Testing Guidelines

Tests use Python assertions and HTTP requests; no coverage threshold exists. Keep checks in `tests/*-smoke.py` or focused verification scripts. Inquiry tests write test records; run against local data. For navigation changes, verify all seven languages and inspect affected pages in a browser. Report incomplete translations and integrations accurately.

## Commit & Pull Request Guidelines

History uses descriptive English and Chinese messages without strict prefixes. Write focused commits. PRs should describe behavior changes, validation, migration effects, and remaining limitations; link relevant issues and include screenshots for visual changes.

## Security & Configuration

Never commit `.env.local`, secrets, customer inquiries, or raw browser archives. Keep production behind the existing Traefik instance. Preserve volumes during updates. Use free plugins unless the user explicitly changes that requirement; historical paid-plugin references are not implementation authorization.

## Shared Server Deployment

Beacon is a read-only reference. Modify only this repository. Production uses `docker-compos.production.yaml`, project `onestep`, and the existing `matrix-network`/Traefik. See `docs/云部署与GitHubActions.md`; never invoke Beacon's deployment gateway or modify its stack.

## Content Migration

Run the versioned plugin `migrate.php` explicitly with WP-CLI after backups. Never seed CI fixtures into a real site (`ONESTEP_CI=1` is required). `tests/complete-site-smoke.py` writes an invalid-domain test enquiry; `tests/migration-state.php` checks gallery and translation relationships. Preserve archive attribution; do not publish default device passwords or turn anonymous partner images into verified case studies. Model/SMTP credentials are absent; retain functional local retrieval and database enquiries.
