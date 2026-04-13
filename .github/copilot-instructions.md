# GitHub Copilot Instructions – Wunderbyte Moodle Development

## Project Context
This repository contains Moodle plugin(s) developed by Wunderbyte GmbH.
Component mapping for this repository: `plugintype_pluginname` -> `mod_mooduell`.
All code must comply with Moodle's official coding standards and plugin guidelines as documented at https://moodledev.io.

---

## Copyright Header
Every PHP file must include this exact file-level PHPDoc block at the top (after `<?php`):

```php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * [Short description of this file].
 *
 * @package    plugintype_pluginname
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
```

---

## PHP Coding Style (Moodle Standards)
- Follow the Moodle PHP Coding Style: https://moodledev.io/general/development/policies/codingstyle
- Use **4 spaces** for indentation — never tabs.
- Opening braces `{` go on the **same line** as the control structure or function declaration.
- Always use `<?php` — never short tags (`<?`).
- All PHP files must end with a newline and must **not** have a closing `?>` tag.
- Line length should not exceed **180 characters**.
- Use `snake_case` for function names, variable names, and database column names.
- Use `UpperCamelCase` (PascalCase) for class names.
- Constants must be `UPPER_CASE` and defined with `define()` or `const`.
- All functions and classes must have full PHPDoc blocks (`@param`, `@return`, `@throws` where applicable).

---

## Moodle API Usage
- Always use Moodle's database API (`$DB->get_record()`, `$DB->get_records()`, `$DB->insert_record()`, etc.) — never raw SQL unless absolutely necessary and documented.
- Use `required_param()` and `optional_param()` for all user input — never access `$_GET` or `$_POST` directly.
- Use `$OUTPUT->header()` / `$OUTPUT->footer()` for page rendering.
- Always call `require_login()` at the top of pages that require authentication.
- Use `require_capability()` or `has_capability()` to enforce access control.
- Use `get_string()` for all user-facing strings — never hardcode English text in PHP (use `lang/en/plugintype_pluginname.php`).
- Add and maintain German strings in `lang/de/plugintype_pluginname.php` for every new or changed user-facing string.
- Keep language string keys synchronized between `lang/en/` and `lang/de/`.
- Use Moodle's form API (`moodleform`) for all user input forms.
- Use `$PAGE->set_*` methods to configure page properties.

---

## Security
- Never hardcode credentials, secrets, or environment-specific values.
- Always sanitize output with `s()`, `format_string()`, or `format_text()` as appropriate.
- Always use `sesskey()` / `confirm_sesskey()` for any state-changing action triggered by a URL.
- Use `clean_param()` to validate and sanitize data before use.
- Avoid `eval()` and dynamic `include`/`require` with user-supplied paths.

---

## Plugin Structure
- Follow Moodle's standard plugin directory layout: https://moodledev.io/docs/apis/plugintypes
- Every plugin must have: `version.php`, `lang/en/plugintype_pluginname.php`, `lang/de/plugintype_pluginname.php`, `db/install.xml` (if it uses DB tables).
- `version.php` must define `$plugin->version`, `$plugin->requires`, `$plugin->component`, and `$plugin->maturity`.
- Use `db/upgrade.php` with `xmldb` for all database schema changes — never modify `install.xml` directly after initial install.

---

## Locale & Timezone
- Company default timezone is Vienna, Austria (`Europe/Vienna`) for business-facing date/time behavior.
- Keep canonical storage in UTC where required by Moodle/core APIs, and convert for display/output as needed.
- Validate date/time behavior (deadlines, schedules, display) against `Europe/Vienna`.

---

## JavaScript
- Write JavaScript as ES modules using Moodle's AMD module system (RequireJS / ES modules in `amd/src/`).
- Always compile AMD modules using Grunt: `grunt amd`.
- Use `core/str` for localized strings in JS.
- Follow the Moodle JavaScript coding style: https://moodledev.io/general/development/policies/codingstyle/javascript

---

## CSS / LESS / SCSS
- Place styles in `styles.css` or use SCSS if the plugin uses a build process.
- Follow Moodle's CSS coding style guidelines.
- Avoid inline styles; use CSS classes instead.

---

## Testing
- Write PHPUnit tests for all new PHP logic in `tests/` following Moodle's test conventions: https://moodledev.io/general/development/tools/phpunit
- Write Behat tests for user-facing features in `tests/behat/`.
- Test class names must end in `_test` and extend `advanced_testcase` or `basic_testcase`.
- Always call `$this->resetAfterTest(true)` in PHPUnit tests that modify data.

---

## General Best Practices
- Never suppress errors with `@` — handle them properly.
- Prefer early returns over deeply nested conditionals.
- Remove all debug code (`var_dump`, `print_r`, `debugging()` with `DEBUG_DEVELOPER`) before committing.
- All strings visible to the user must go through the Moodle string API.
- Keep functions short and focused; extract helpers where logic becomes complex.