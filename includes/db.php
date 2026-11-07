<?php
/**
 * Prepared-statement helpers.
 *
 * Why this exists: every query in this project used to be built by gluing
 * $_POST values straight into the SQL string, e.g.
 *
 *     "select ID from tbluser where Email='$email' && Password='$password'"
 *
 * With email = `' OR 1=1 -- ` the password check disappears and the query
 * returns the first user in the table: anyone could log in as anyone, and the
 * same trick worked in the register, reset-password, profile and report pages.
 *
 * A prepared statement sends the SQL and the values separately, so a value can
 * never be read as SQL. Every query now uses `?` placeholders and passes the
 * values through one of these three helpers.
 *
 * The type string ('i' integer, 's' string, 'd' double) is the first argument
 * of mysqli_stmt_bind_param and tells MySQL how to treat each value.
 */

/**
 * Prepares, binds and executes a statement.
 *
 * Returns the executed mysqli_stmt, or false when the query failed (the error
 * is logged, never printed, so a broken query cannot leak the schema).
 */
function db_run($con, string $sql, string $types = '', array $params = [])
{
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        error_log('Daily Expense Tracker: prepare failed: ' . mysqli_error($con) . ' | ' . $sql);
        return false;
    }

    if ($types !== '') {
        // The values are passed as separate arguments, never concatenated.
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log('Daily Expense Tracker: execute failed: ' . mysqli_stmt_error($stmt) . ' | ' . $sql);
        return false;
    }

    return $stmt;
}

/** Runs a SELECT and returns every row as an associative array. */
function db_rows($con, string $sql, string $types = '', array $params = []): array
{
    $stmt = db_run($con, $sql, $types, $params);
    if (!$stmt) {
        return [];
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);

    return $rows;
}

/** Runs a SELECT and returns the first row, or null when there is none. */
function db_row($con, string $sql, string $types = '', array $params = [])
{
    $rows = db_rows($con, $sql, $types, $params);

    return $rows ? $rows[0] : null;
}

/**
 * Runs an INSERT, UPDATE or DELETE.
 *
 * Returns the number of affected rows, or false when the statement failed.
 * Callers test `!== false` rather than truthiness, because an UPDATE that
 * changes nothing affects 0 rows and that is still a success.
 */
function db_exec($con, string $sql, string $types = '', array $params = [])
{
    $stmt = db_run($con, $sql, $types, $params);
    if (!$stmt) {
        return false;
    }

    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    return $affected;
}
