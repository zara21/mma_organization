# mma_organization

Use PHP 8.2, Apache with mod_rewrite, and MariaDB or MySQL. Import the project SQL into the `mma_organization` database on a fresh installation.

Apply `database/migrations/2026_11_19_fight_results.sql` once to the imported or existing database before opening the updated fight cards. The new detail fields are optional for old results.

For an optional local sample roster, run `php scripts/seed_demo.php` from the project root.
