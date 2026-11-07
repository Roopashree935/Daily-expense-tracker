<?php
/**
 * Copy this file to includes/config.php and edit the values.
 *
 * includes/config.php is listed in .gitignore, so your real database
 * credentials never reach the repository. This example file is committed and
 * also acts as the fallback when config.php does not exist yet: its defaults
 * are the stock XAMPP/WAMP values, so a fresh clone runs without any setup.
 */

return [
    // Database
    'db_host' => 'localhost',   // InfinityFree: sqlXXX.infinityfree.com (not localhost)
    'db_name' => 'detsdb',
    'db_user' => 'root',        // XAMPP default
    'db_pass' => '',            // XAMPP default: empty
    'db_port' => 3306,

    // 'dev'  -> PHP errors are printed on the page (local development)
    // 'prod' -> PHP errors are logged, never printed (public deployment)
    'app_env' => 'dev',
];
