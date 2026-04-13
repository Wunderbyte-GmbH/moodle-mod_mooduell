# Copilot Agent Instructions – Wunderbyte Moodle Plugin Development

## Repository Overview
This repository contains a Moodle plugin developed by Wunderbyte GmbH.
Component mapping for this repository: `plugintype_pluginname` -> `quizaccess_courseseb`.
Moodle plugins are PHP-based extensions that follow a strict directory structure and API convention.
Always refer to https://moodledev.io for official Moodle developer documentation.

---

## Repository Layout
Key directories and files to be aware of:

```
/
├── .github/
│   └── copilot-instructions.md   # Coding style guidelines – read this too
├── amd/
│   └── src/                      # JavaScript ES module source files (edit these)
│   └── build/                    # Compiled AMD JS (do NOT edit manually)
├── classes/                      # PHP classes (PSR-4 style, autoloaded by Moodle)
├── db/
│   ├── install.xml               # Initial DB schema (do NOT modify after first install)
│   ├── upgrade.php               # DB upgrade steps for version bumps
│   ├── access.php                # Capability definitions
│   └── services.php              # External/web service definitions (if applicable)
├── lang/
│   └── en/                       # English language strings
│   └── de/                       # German language strings
├── templates/                    # Mustache templates
├── tests/
│   ├── *.php                     # PHPUnit test classes
│   └── behat/                    # Behat feature files
├── version.php                   # Plugin version and metadata – update on every release
└── lib.php                       # Core plugin hook callbacks
```

---

## Environment Assumptions
- PHP 8.1+ is available
- Moodle core is installed and accessible (typically at the parent directory or a sibling directory)
- Composer dependencies are installed via `composer install`
- Node.js and npm are available for JS compilation
- Grunt CLI is available: `npx grunt` or `grunt`

---

## Build Commands

### PHP / Composer
```bash
# Install PHP dependencies (if composer.json exists)
composer install --no-interaction

# Check coding style (Moodle PHPCS ruleset)
vendor/bin/phpcs --standard=moodle .

# Auto-fix coding style issues where possible
vendor/bin/phpcbf --standard=moodle .
```

### JavaScript (AMD modules)
```bash
# Install Node dependencies
npm install

# Compile a single AMD module (recommended during development)
npx grunt amd --root=amd/src/mymodule.js

# Compile ALL AMD modules in the plugin
npx grunt amd

# Watch for changes and recompile automatically
npx grunt watch
```

> ⚠️ Never manually edit files in `amd/build/` — always edit `amd/src/` and recompile.

### Mustache templates
```bash
# Lint Mustache templates
npx grunt mustache
```

---

## Testing

### PHPUnit
```bash
# Run all tests in this plugin (from Moodle root)
vendor/bin/phpunit --testsuite plugintype_pluginname_testsuite

# Run a specific test file
vendor/bin/phpunit path/to/tests/mytest_test.php

# Run a specific test method
vendor/bin/phpunit --filter test_my_method path/to/tests/mytest_test.php
```

> PHPUnit must be initialised in the Moodle root first:
> ```bash
> php admin/tool/phpunit/cli/init.php
> ```

### Behat
```bash
# Initialise Behat (run from Moodle root)
php admin/tool/behat/cli/init.php

# Run all Behat tests for this plugin
vendor/bin/behat --config /path/to/moodledata/behat/behat.yml \
  --tags @plugintype_pluginname
```

---

## Database Changes
- **Never** modify `db/install.xml` after the plugin has been installed — this will break upgrades.
- All schema changes after initial release go in `db/upgrade.php` using the XMLDB API.
- Always bump `$plugin->version` in `version.php` when making DB changes.
- After any DB change, run:
```bash
php admin/cli/upgrade.php --non-interactive
```

---

## Version Bumps
When releasing a new version, always update `version.php`:
- Increment `$plugin->version` (format: `YYYYMMDDXX`, e.g., `2026040701`)
- Update `$plugin->requires` if a newer Moodle version is now required
- Update `$plugin->release` to a human-readable string (e.g., `'1.2.3'`)

---

## Language Strings
- All user-facing strings live in `lang/en/plugintype_pluginname.php`
- Include German strings at minimum in `lang/de/plugintype_pluginname.php` for every new or changed string.
- Keep string identifiers identical between `en` and `de` language packs.
- Never hardcode English text in PHP, JS, or Mustache — always use:
  - PHP: `get_string('mystring', 'plugintype_pluginname')`
  - JS: `core/str` AMD module
  - Mustache: `{{#str}} mystring, plugintype_pluginname {{/str}}`

---

## Locale & Timezone
- Company default timezone is Vienna, Austria (`Europe/Vienna`) for business-facing date/time behavior.
- Store canonical timestamps in UTC where Moodle APIs expect UTC and only convert for display/output.
- Ensure date/time formatting and scheduling logic are verified against `Europe/Vienna` behavior.

---

## Common Pitfalls & Known Workarounds
- After changing any PHP class in `classes/`, no recompile is needed — Moodle autoloads them.
- After changing `db/access.php` (capabilities), run `php admin/cli/upgrade.php` or use "Purge all caches" in Moodle admin.
- After changing Mustache templates, purge the Moodle template cache: **Admin → Development → Purge all caches**.
- After changing language strings, purge the string cache or set `$CFG->langstringcache = false;` in `config.php` during development.
- Moodle's `$DB` methods return `stdClass` objects, not arrays — access fields with `->` not `[]`.

---

## What NOT to Do
- Do **not** edit `amd/build/` files directly.
- Do **not** modify `db/install.xml` after first install.
- Do **not** use `$_GET`, `$_POST`, or `$_REQUEST` — use `required_param()` / `optional_param()`.
- Do **not** write raw SQL — use `$DB` API methods.
- Do **not** echo HTML directly in PHP — use `$OUTPUT` methods or Mustache templates.
- Do **not** commit with `debugging()` calls or `var_dump()` left in code.

---

## Pull Request Checklist
Before opening a PR, verify:
- [ ] `version.php` has been updated with a new version number
- [ ] PHPCS passes: `vendor/bin/phpcs --standard=moodle .`
- [ ] AMD JS recompiled if any `amd/src/` files were changed
- [ ] PHPUnit tests pass for affected functionality
- [ ] New features have corresponding PHPUnit and/or Behat tests
- [ ] All user-facing strings use `get_string()`
- [ ] Copyright header present on all new PHP files (`2026 Wunderbyte GmbH`)
- [ ] No hardcoded credentials or debug output left in code