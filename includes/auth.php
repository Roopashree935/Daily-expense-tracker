<?php
/**
 * Session helpers.
 *
 * Every protected page starts with require_once of this file and then calls
 * require_login() before it prints anything, so a logged-out visitor is always
 * sent to logout.php instead of seeing a half-rendered page.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * True when the visitor has a logged-in session.
 *
 * The old check was `if (strlen($_SESSION['detsuid']==0))`, which only worked
 * by accident: PHP compares `$_SESSION['detsuid'] == 0` first, and strlen() of
 * the resulting boolean is 1 for a logged-in user and 0 for a guest. Reading
 * it as a session test was luck, not intent. This function says what it means.
 */
function is_logged_in(): bool
{
    return isset($_SESSION['detsuid']) && (int) $_SESSION['detsuid'] > 0;
}

/** Sends guests to logout.php and stops the request. */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: logout.php');
        exit;
    }
}

/**
 * Marks the visitor as logged in.
 *
 * session_regenerate_id(true) gives the user a brand-new session id and deletes
 * the old session file. Without it, an attacker who fixed a session id on the
 * victim before login would keep using that same id afterwards (session
 * fixation). Doing it at the moment privilege changes is the standard fix.
 */
function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['detsuid'] = $userId;
}
