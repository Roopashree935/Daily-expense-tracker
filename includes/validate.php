<?php
/**
 * Input validation.
 *
 * Prepared statements stop a value from being read as SQL, but they happily
 * store nonsense: tbluser.MobileNumber is a bigint, tbluser.Password a
 * varchar(200) and tblexpense.ExpenseCost a varchar(200), so "abc" as a cost
 * is stored as "abc" and then makes every SUM() in the reports wrong. These
 * helpers reject that before the database sees it.
 */

/** A real calendar date written as Y-m-d. 2026-02-30 is rejected. */
function is_valid_date(string $value): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return false;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);

    // createFromFormat() accepts 2026-02-30 and rolls it into March, so the
    // formatted result has to match the input exactly.
    return $date !== false && $date->format('Y-m-d') === $value;
}

/** A money amount: digits, optionally with one or two decimals. No sign, no letters. */
function is_valid_amount(string $value): bool
{
    return (bool) preg_match('/^\d{1,9}(\.\d{1,2})?$/', $value);
}

/** A 10-digit mobile number. */
function is_valid_mobile(string $value): bool
{
    return (bool) preg_match('/^[0-9]{10}$/', $value);
}

/** A syntactically valid email address. */
function is_valid_email(string $value): bool
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

/** A name: not empty, and not longer than the column (varchar(150)). */
function is_valid_name(string $value): bool
{
    $value = trim($value);

    return $value !== '' && strlen($value) <= 150;
}

/** An expense item: not empty, and not longer than the column (varchar(200)). */
function is_valid_item(string $value): bool
{
    $value = trim($value);

    return $value !== '' && strlen($value) <= 200;
}
