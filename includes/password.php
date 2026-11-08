<?php
/**
 * Password hashing.
 *
 * md5() is not a password hash. It is fast (a GPU tries billions of guesses per
 * second), it is unsalted (two users with the same password get the same hash,
 * so one leaked table cracks every account at once) and it always returns 32
 * hex characters, whatever the password.
 *
 * password_hash() with PASSWORD_DEFAULT uses bcrypt: salted per user, slow on
 * purpose, and the algorithm and cost are stored inside the hash itself so the
 * cost can be raised later without another migration.
 *
 * The existing rows in database/detsdb.sql still hold md5 values. Locking those
 * users out would be unacceptable, so verify_password() still accepts an md5
 * value — and password_needs_upgrade() tells the login code to replace it with
 * a bcrypt hash on that same successful login. After one login every account
 * has moved to bcrypt without anyone resetting a password.
 */

/** True when the stored value is a 32-character hex md5 digest. */
function is_legacy_md5(string $stored): bool
{
    return (bool) preg_match('/^[a-f0-9]{32}$/i', $stored);
}

/** Produces the value that goes into tbluser.Password. */
function hash_password(string $plain): string
{
    return password_hash($plain, PASSWORD_DEFAULT);
}

/**
 * Checks a password against a stored value, accepting the legacy md5 format.
 *
 * hash_equals() compares in constant time, so the comparison cannot be timed
 * to discover the hash byte by byte.
 */
function verify_password(string $plain, string $stored): bool
{
    if ($stored === '') {
        return false;
    }

    if (is_legacy_md5($stored)) {
        return hash_equals(strtolower($stored), md5($plain));
    }

    return password_verify($plain, $stored);
}

/**
 * True when the stored value should be replaced after a successful login:
 * either it is a legacy md5 digest, or bcrypt's default cost has moved on.
 */
function password_needs_upgrade(string $stored): bool
{
    return is_legacy_md5($stored) || password_needs_rehash($stored, PASSWORD_DEFAULT);
}
