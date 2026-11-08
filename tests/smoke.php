<?php
/**
 * Smoke test — the five-second check that a fresh install is wired up.
 *
 *     php tests/smoke.php
 *
 * It connects with the same includes/config.php the app uses, then asserts that
 * the database and both tables are there and hold the columns the pages query.
 * Exit code 0 means everything answered; 1 means at least one check failed, so
 * it can gate a deploy script.
 *
 * This is not a replacement for the end-to-end walkthrough in the README: it
 * proves the wiring, not the pages.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this from the command line: php tests/smoke.php\n");
}

require_once __DIR__ . '/../includes/dbconnection.php';

$checks = 0;
$failures = 0;

function check(string $label, bool $passed, string $detail = ''): void
{
    global $checks, $failures;
    $checks++;

    if ($passed) {
        echo "  ok    {$label}\n";
        return;
    }

    $failures++;
    echo "  FAIL  {$label}" . ($detail !== '' ? " ({$detail})" : '') . "\n";
}

echo "Daily Expense Tracker — smoke test\n";
echo 'PHP ' . PHP_VERSION . ", database {$detsConfig['db_name']} on {$detsConfig['db_host']}\n\n";

// 1. The connection. dbconnection.php has already exited with status 1 if it
//    could not connect, so reaching this line means the credentials work.
check('database connection', $con instanceof mysqli);

// 2. The two tables the pages use.
$expected = [
    'tbluser' => ['ID', 'FullName', 'Email', 'MobileNumber', 'Password', 'RegDate'],
    'tblexpense' => ['ID', 'UserId', 'ExpenseDate', 'ExpenseItem', 'ExpenseCost', 'NoteDate'],
];

foreach ($expected as $table => $columns) {
    $row = db_row(
        $con,
        'select count(*) as found from information_schema.tables where table_schema = database() and table_name = ?',
        's',
        [$table]
    );
    $exists = ((int) ($row['found'] ?? 0)) === 1;
    check("table {$table} exists", $exists);

    if (!$exists) {
        echo "        (import database/detsdb.sql first — see README.md)\n";
        continue;
    }

    $present = db_rows(
        $con,
        'select column_name from information_schema.columns where table_schema = database() and table_name = ?',
        's',
        [$table]
    );
    $names = array_map(static function (array $r): string {
        return strtolower($r['column_name']);
    }, $present);

    foreach ($columns as $column) {
        check("  {$table}.{$column}", in_array(strtolower($column), $names, true));
    }
}

// 3. A real read through the prepared-statement helper, the way the pages do it.
if ($failures === 0) {
    $row = db_row($con, 'select count(*) as users from tbluser', '', []);
    check('select count(*) from tbluser', is_array($row) && isset($row['users']),
        'returned ' . var_export($row, true));
}

echo "\n";
if ($failures === 0) {
    echo "All {$checks} checks passed.\n";
    exit(0);
}

echo "{$failures} of {$checks} checks failed.\n";
exit(1);
