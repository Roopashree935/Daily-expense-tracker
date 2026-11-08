<?php
/**
 * Cross-site request forgery protection.
 *
 * Without a token, another site can make the visitor's browser submit this
 * app's forms — a hidden form posting to add-expense.php while the user is
 * logged in here is enough, because the browser sends the session cookie
 * automatically. The attacker never sees the response; the damage is the write.
 *
 * The defence is a random value that is stored in the session, printed into
 * every form, and required back on submit. A page on another origin cannot read
 * the value, so it cannot build a valid request.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** The current session's token, created on first use. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // 32 random bytes: not guessable, and different for every session.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/** The hidden input to place inside a <form>. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Rejects the request unless it carries this session's token.
 *
 * hash_equals() is used so the comparison takes the same time whether the first
 * character matches or the last, which stops a timing attack on the token.
 */
function csrf_check(): void
{
    $sent = $_POST['csrf_token'] ?? '';

    if (!is_string($sent) || $sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        exit('Invalid or expired form submission. Reload the page and try again.');
    }
}
