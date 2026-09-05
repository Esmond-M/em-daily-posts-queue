# Testing

## Install and run

From the plugin directory with PHP and Composer on PATH:

```sh
composer install
composer test
composer test:unit
composer test:hooks
```

Use `composer install` to reproduce `composer.lock`; an update is not needed.
PHP must satisfy the locked dependencies (the current lock requires PHP 8.1 or
newer) and have PHPUnit's DOM, JSON, libxml, mbstring, tokenizer, XML, and
XMLWriter extensions. Composer also needs ZIP or an external archive extractor.
The plugin's minimum PHP version is separate from this development baseline.

You do not need Local's site shell or a running site. The baseline loads no
site configuration, connects to no database, sends no emails, and runs no cron
jobs. Composer installs a separate WordPress source copy in the ignored
`wordpress/` directory; it does not install a database or modify the Local site.

On Windows, if Composer is unavailable but PHP is on PATH after dependencies
are installed, run:

```powershell
php vendor/bin/phpunit --configuration phpunit.xml
```

If PHP ZIP is available but disabled, it can be enabled for just the install
command, using the path to your Composer PHAR:

```powershell
php -d extension=zip C:/ProgramData/ComposerSetup/bin/composer.phar install
```

## Suites and boundaries

| Suite | What it verifies | What it loads |
| --- | --- | --- |
| `unit` | JSON/legacy queue decoding, malformed entry filtering, selected snapshot comparison cases | Queue utility class and PHPUnit |
| `wordpress-hooks` | Submission row/bulk actions, preservation of other post types, guest/authenticated AJAX registrations | Real WordPress hook API plus queue controller |

The hook suite loads only `wordpress/wp-includes/plugin.php`, not `wp-load.php`
or the WordPress PHPUnit installer. Hook globals are restored after every test.
Hook registration tests do not prove the request handlers enforce capabilities
or nonces. Snapshot tests do not establish complete concurrency correctness.

PHPUnit discovers `*Test.php` in the two suite directories. Empty runs,
warnings, and risky tests fail. To check discovery or run in a different order:

```sh
composer test -- --list-tests
composer test -- --order-by=random --random-order-seed=20260905
```

## Adding tests incrementally

- Add expected inputs/outputs for queue logic; prefer public behavior over method-existence checks.
- Existing decoder characterization uses reflection because the decoder is private. Do not expose production methods solely for tests.
- Use the hook suite for WordPress filter/action behavior that needs no site bootstrap.
- Add a separately invoked full WordPress integration suite when implementing permissions, persistence, post lifecycle, or scheduling changes. Use an explicitly configured disposable database; the WordPress test installer recreates tables.
- The legacy `tests/wp-config.php` is not loaded by either current suite and is not a ready-to-run integration environment.
- Add browser checks for UI changes. Do not treat a green PHP baseline as UI validation.

No production behavior was changed to establish this baseline. Dependencies and
the installed WordPress copy stay ignored; test code and these instructions are
version-controlled.

## Verified baseline

2026-09-05: PHP 8.2.12, PHPUnit 9.6.23, WordPress hook API 6.8.1.
Both suites: **23 tests, 27 assertions**. Unit suite: 16 tests; hook suite: 7 tests.
Randomized order also passes. This is not a PHP-version compatibility matrix or
a full WordPress integration run.

References: [PHPUnit configuration](https://docs.phpunit.de/en/9.6/configuration.html),
[WordPress integration test lifecycle](https://make.wordpress.org/core/handbook/testing/automated-testing/writing-phpunit-tests/).
