<?php
/**
 * Loads the configuration, sets the error-reporting policy and opens $con.
 *
 * Every page includes this file first, so this is the one place where those
 * three things happen.
 */

$detsConfigFile = __DIR__ . '/config.php';
if (!is_file($detsConfigFile)) {
    // No local config yet: fall back to the committed example so a fresh
    // clone (and XAMPP out of the box) still connects.
    $detsConfigFile = __DIR__ . '/config.example.php';
}
$detsConfig = require $detsConfigFile;

/**
 * Errors used to be hidden with error_reporting(0) on every page, which meant
 * a broken query produced a blank page and no clue. Now the level is always
 * E_ALL and only the *display* depends on the environment: developers see the
 * message, production logs it and shows nothing.
 */
$detsIsDev = ($detsConfig['app_env'] ?? 'prod') === 'dev';
error_reporting(E_ALL);
ini_set('display_errors', $detsIsDev ? '1' : '0');
ini_set('log_errors', '1');

/**
 * PHP 8.1+ makes mysqli throw mysqli_sql_exception on any error. This codebase
 * was written for the older contract — "false means the query failed" — so it
 * asks for that back instead of letting every failed query turn into an
 * uncaught exception with a stack trace. Failures are handled explicitly below
 * and in includes/db.php.
 */
mysqli_report(MYSQLI_REPORT_OFF);

$con = @mysqli_connect(
    $detsConfig['db_host'],
    $detsConfig['db_user'],
    $detsConfig['db_pass'],
    $detsConfig['db_name'],
    (int) $detsConfig['db_port']
);

if (!$con) {
    // mysqli_connect_error() names the host, the user and the database, so it
    // goes to the log and never into the response.
    error_log('Daily Expense Tracker: database connection failed: ' . mysqli_connect_error());
    http_response_code(500);
    exit($detsIsDev
        ? 'Database connection failed: ' . mysqli_connect_error()
        : 'Database connection failed.');
}
