#!/usr/bin/env bash
#
# End-to-end checks for the Daily Expense Tracker.
#
# Drives a running copy of the app over HTTP with curl, exactly as a browser
# would: it logs in, posts forms with their CSRF token, adds and deletes an
# expense, opens every report, and tries the attacks that used to work.
#
# Requirements
#   * the app is being served (php -S, Apache, whatever) at $BASE_URL
#   * the schema from database/detsdb.sql is imported
#   * `php` on PATH, because a few checks read the database through the app's
#     own includes/config.php instead of opening a second connection
#
# Usage
#   bash tests/e2e.sh                        # http://localhost:8000
#   BASE_URL=http://localhost/app bash tests/e2e.sh
#
# It writes to the database through the application (registers a throwaway
# user, adds expenses). Point it at a development database, not a live one.
#
set -u

BASE_URL="${BASE_URL:-http://localhost:8000}"
JARS="$(mktemp -d)"
trap 'rm -rf "$JARS"' EXIT

PASS=0
FAIL=0
ok()  { PASS=$((PASS + 1)); printf 'PASS  %s\n' "$1"; }
no()  { FAIL=$((FAIL + 1)); printf 'FAIL  %s\n' "$1"; }
chk() { if [ "$2" = "$3" ]; then ok "$1 ($2)"; else no "$1 (expected [$3] got [$2])"; fi; }

code()    { curl -s -o /dev/null -w '%{http_code}' "$@"; }
loc()     { curl -s -o /dev/null -w '%{redirect_url}' "$@"; }

# sql "<select ...>" -> the first row's columns, tab separated
sql() {
  php -r 'require "includes/dbconnection.php";
          $row = db_row($con, $argv[1]);
          echo $row ? implode("\t", array_map("strval", $row)) : "";' -- "$1"
}

# tk <jar> <page> -> the CSRF token that page printed
tk() { curl -s -b "$1" -c "$1" "$2" | sed -n 's/.*name="csrf_token" value="\([^"]*\)".*/\1/p' | head -1; }

# post <jar> <page> <page-with-the-form> [curl args...]
post() {
  local jar=$1 page=$2 form=$3
  shift 3
  local t
  t=$(tk "$jar" "$form")
  curl -s -b "$jar" -c "$jar" --data-urlencode "csrf_token=$t" "$@" "$page"
}
postcode() { post "$@" -o /dev/null -w '%{http_code}'; }
postloc()  { post "$@" -o /dev/null -w '%{redirect_url}'; }

J="$JARS/a"; J2="$JARS/b"; J3="$JARS/c"; J4="$JARS/d"; J5="$JARS/e"; J6="$JARS/f"
STAMP="$(date +%s)"
NEWUSER="e2e.$STAMP@example.com"

echo "Daily Expense Tracker — end-to-end checks against $BASE_URL"

echo "--- guests are sent to logout.php"
chk "dashboard.php without a session" "$(loc -b /dev/null "$BASE_URL/dashboard.php")" "$BASE_URL/logout.php"
chk "manage-expense.php without a session" "$(loc -b /dev/null "$BASE_URL/manage-expense.php")" "$BASE_URL/logout.php"
chk "expense-reports-detailed.php without a session" "$(loc -b /dev/null "$BASE_URL/expense-reports-detailed.php")" "$BASE_URL/logout.php"

echo "--- login"
chk "GET index.php" "$(code -c "$J" "$BASE_URL/index.php")" "200"
SID_BEFORE=$(awk '/PHPSESSID/{v=$7} END{print v}' "$J")
chk "valid login redirects to the dashboard" \
  "$(postloc "$J" "$BASE_URL/index.php" "$BASE_URL/index.php" \
      --data-urlencode 'email=testuser@gmail.com' --data-urlencode 'password=Test@123' --data-urlencode 'login=login')" \
  "$BASE_URL/dashboard.php"
chk "the session id changes on login (no fixation)" \
  "$([ -n "$SID_BEFORE" ] && [ "$SID_BEFORE" != "$(awk '/PHPSESSID/{v=$7} END{print v}' "$J")" ] && echo yes)" "yes"
chk "the dashboard renders the user's name" "$(curl -s -b "$J" "$BASE_URL/dashboard.php" | grep -q 'Test User' && echo yes)" "yes"

echo "--- SQL injection must not authenticate (with a valid token)"
chk "email = ' OR '1'='1 is rejected" \
  "$(postloc "$J2" "$BASE_URL/index.php" "$BASE_URL/index.php" \
      --data-urlencode "email=' OR '1'='1" --data-urlencode 'password=x' --data-urlencode 'login=login')" ""
chk "email = ' OR 1=1 -- is rejected" \
  "$(postloc "$J2" "$BASE_URL/index.php" "$BASE_URL/index.php" \
      --data-urlencode "email=' OR 1=1 -- " --data-urlencode 'password=x' --data-urlencode 'login=login')" ""
chk "a wrong password is rejected" \
  "$(postloc "$J2" "$BASE_URL/index.php" "$BASE_URL/index.php" \
      --data-urlencode 'email=testuser@gmail.com' --data-urlencode 'password=wrong' --data-urlencode 'login=login')" ""
chk "forgot-password injection does not redirect" \
  "$(postloc "$J2" "$BASE_URL/forgot-password.php" "$BASE_URL/forgot-password.php" \
      --data-urlencode "email=x' OR '1'='1" --data-urlencode 'contactno=1' --data-urlencode 'submit=')" ""

echo "--- CSRF"
chk "a login POST with no token is refused" \
  "$(code -c "$J2" --data-urlencode 'email=testuser@gmail.com' --data-urlencode 'password=Test@123' --data-urlencode 'login=login' "$BASE_URL/index.php")" "403"
chk "a login POST with a wrong token is refused" \
  "$(code -c "$J2" --data-urlencode 'csrf_token=deadbeef' --data-urlencode 'email=testuser@gmail.com' --data-urlencode 'password=Test@123' --data-urlencode 'login=login' "$BASE_URL/index.php")" "403"
chk "an add-expense POST with no token is refused" \
  "$(code -b "$J" --data-urlencode 'dateexpense=2026-11-07' --data-urlencode 'item=NoToken' --data-urlencode 'costitem=1' --data-urlencode 'submit=1' "$BASE_URL/add-expense.php")" "403"
chk "a report POST with no token is refused" \
  "$(code -b "$J" --data-urlencode 'fromdate=2026-01-01' --data-urlencode 'todate=2026-12-31' --data-urlencode 'requesttype=datewise' "$BASE_URL/expense-datewise-reports-detailed.php")" "403"
chk "every form on every page carries a token" \
  "$(for p in index.php register.php forgot-password.php add-expense.php manage-expense.php user-profile.php \
              change-password.php expense-reports.php expense-datewise-reports.php expense-monthwise-reports.php \
              expense-yearwise-reports.php; do curl -s -b "$J" "$BASE_URL/$p" | grep -c 'name="csrf_token"'; done | grep -c '^0$')" "0"

echo "--- add, list and delete an expense"
chk "add-expense.php renders" "$(code -b "$J" "$BASE_URL/add-expense.php")" "200"
post "$J" "$BASE_URL/add-expense.php" "$BASE_URL/add-expense.php" \
  --data-urlencode 'dateexpense=2026-11-07' --data-urlencode 'item=E2E Test Item' \
  --data-urlencode 'costitem=123.45' --data-urlencode 'submit=1' > /dev/null
chk "the new expense is listed" "$(curl -s -b "$J" "$BASE_URL/manage-expense.php" | grep -q 'E2E Test Item' && echo yes)" "yes"
chk "the cost is stored exactly" "$(curl -s -b "$J" "$BASE_URL/manage-expense.php" | grep -q '123.45' && echo yes)" "yes"
NEWID=$(sql "select ID from tblexpense where ExpenseItem='E2E Test Item' order by ID desc limit 1")
chk "a delete through a plain GET link does nothing" \
  "$(curl -s -o /dev/null -b "$J" "$BASE_URL/manage-expense.php?delid=$NEWID"; sql "select count(*) from tblexpense where ID=$NEWID")" "1"
chk "a delete through the CSRF-protected form works" \
  "$(post "$J" "$BASE_URL/manage-expense.php" "$BASE_URL/manage-expense.php" --data-urlencode "delid=$NEWID" > /dev/null; sql "select count(*) from tblexpense where ID=$NEWID")" "0"

echo "--- one user cannot delete another user's expense"
chk "expense 1 belongs to user 1, not to the logged-in user" "$(sql 'select UserId from tblexpense where ID=1')" "1"
post "$J" "$BASE_URL/manage-expense.php" "$BASE_URL/manage-expense.php" --data-urlencode 'delid=1' > /dev/null
chk "deleting someone else's expense is refused" "$(sql 'select count(*) from tblexpense where ID=1')" "1"

echo "--- reports"
for p in expense-reports.php expense-datewise-reports.php expense-monthwise-reports.php expense-yearwise-reports.php; do
  chk "GET $p" "$(code -b "$J" "$BASE_URL/$p")" "200"
done
post "$J" "$BASE_URL/add-expense.php" "$BASE_URL/add-expense.php" \
  --data-urlencode 'dateexpense=2026-11-07' --data-urlencode 'item=E2E Report Item' \
  --data-urlencode 'costitem=99.50' --data-urlencode 'submit=1' > /dev/null
chk "the datewise report shows the new total" \
  "$(post "$J" "$BASE_URL/expense-datewise-reports-detailed.php" "$BASE_URL/expense-datewise-reports.php" \
      --data-urlencode 'fromdate=2026-11-01' --data-urlencode 'todate=2026-11-30' --data-urlencode 'requesttype=datewise' | grep -q '99.5' && echo yes)" "yes"
chk "the monthwise report renders" \
  "$(post "$J" "$BASE_URL/expense-monthwise-reports-detailed.php" "$BASE_URL/expense-monthwise-reports.php" \
      --data-urlencode 'fromdate=2026-01-01' --data-urlencode 'todate=2026-12-31' --data-urlencode 'requesttype=monthwise' | grep -q '11-2026' && echo yes)" "yes"
chk "the yearwise report renders" \
  "$(post "$J" "$BASE_URL/expense-yearwise-reports-detailed.php" "$BASE_URL/expense-yearwise-reports.php" \
      --data-urlencode 'fromdate=2026-01-01' --data-urlencode 'todate=2026-12-31' --data-urlencode 'requesttype=yearwise' | grep -q '>2026<' && echo yes)" "yes"
chk "the plain expense report renders" \
  "$(post "$J" "$BASE_URL/expense-reports-detailed.php" "$BASE_URL/expense-reports.php" \
      --data-urlencode 'fromdate=2026-01-01' --data-urlencode 'todate=2026-12-31' --data-urlencode 'requesttype=expense' | grep -q '99.5' && echo yes)" "yes"
chk "a report with the from date after the to date is refused" \
  "$(post "$J" "$BASE_URL/expense-datewise-reports-detailed.php" "$BASE_URL/expense-datewise-reports.php" \
      --data-urlencode 'fromdate=2026-12-31' --data-urlencode 'todate=2026-01-01' --data-urlencode 'requesttype=datewise' | grep -q 'must be on or before' && echo yes)" "yes"

echo "--- profile"
chk "user-profile.php renders" "$(code -b "$J" "$BASE_URL/user-profile.php")" "200"
chk "the profile shows the seeded name" "$(curl -s -b "$J" "$BASE_URL/user-profile.php" | grep -q 'value="Test User"' && echo yes)" "yes"
chk "a profile update with a bad mobile number is refused" \
  "$(post "$J" "$BASE_URL/user-profile.php" "$BASE_URL/user-profile.php" \
      --data-urlencode 'fullname=Test User' --data-urlencode 'contactnumber=abc' --data-urlencode 'submit=1' | grep -q '10-digit mobile' && echo yes)" "yes"

echo "--- output escaping (stored XSS)"
post "$J" "$BASE_URL/add-expense.php" "$BASE_URL/add-expense.php" \
  --data-urlencode 'dateexpense=2026-11-07' --data-urlencode 'item=<script>alert(1)</script>' \
  --data-urlencode 'costitem=5' --data-urlencode 'submit=1' > /dev/null
chk "the script tag is stored as typed" "$(sql "select count(*) from tblexpense where ExpenseItem like '<script>%'")" "1"
chk "the script tag is escaped in the expense list" \
  "$(curl -s -b "$J" "$BASE_URL/manage-expense.php" | grep -q '&lt;script&gt;alert(1)&lt;/script&gt;' && echo yes)" "yes"
chk "no raw script tag reaches the expense list" \
  "$(curl -s -b "$J" "$BASE_URL/manage-expense.php" | grep -c '<script>alert(1)</script>')" "0"

echo "--- input validation"
post "$J" "$BASE_URL/add-expense.php" "$BASE_URL/add-expense.php" \
  --data-urlencode 'dateexpense=2026-11-07' --data-urlencode 'item=Bad Cost' \
  --data-urlencode 'costitem=cheap' --data-urlencode 'submit=1' > /dev/null
chk "a non-numeric cost is rejected" "$(sql "select count(*) from tblexpense where ExpenseItem='Bad Cost'")" "0"
post "$J" "$BASE_URL/add-expense.php" "$BASE_URL/add-expense.php" \
  --data-urlencode 'dateexpense=2026-02-30' --data-urlencode 'item=Bad Date' \
  --data-urlencode 'costitem=10' --data-urlencode 'submit=1' > /dev/null
chk "an impossible date (2026-02-30) is rejected" "$(sql "select count(*) from tblexpense where ExpenseItem='Bad Date'")" "0"
post "$J6" "$BASE_URL/register.php" "$BASE_URL/register.php" \
  --data-urlencode 'name=Short Mobile' --data-urlencode "email=short.$STAMP@example.com" \
  --data-urlencode 'mobilenumber=12345' --data-urlencode 'password=Secret@123' \
  --data-urlencode 'repeatpassword=Secret@123' --data-urlencode 'submit=1' > /dev/null
chk "a 5-digit mobile number is rejected" "$(sql "select count(*) from tbluser where Email='short.$STAMP@example.com'")" "0"
chk "a mismatched repeat password is rejected" \
  "$(post "$J6" "$BASE_URL/register.php" "$BASE_URL/register.php" \
      --data-urlencode 'name=Mismatch' --data-urlencode "email=mismatch.$STAMP@example.com" \
      --data-urlencode 'mobilenumber=9876543212' --data-urlencode 'password=Secret@123' \
      --data-urlencode 'repeatpassword=Different@123' --data-urlencode 'submit=1' | grep -q 'does not match' && echo yes)" "yes"

echo "--- password hashing"
case "$(sql "select Password from tbluser where Email='testuser@gmail.com'")" in
  \$2y\$*) ok "the legacy md5 row was upgraded to bcrypt on login" ;;
  *) no "the legacy md5 row was upgraded to bcrypt on login" ;;
esac
chk "login still works against the bcrypt hash" \
  "$(postloc "$J3" "$BASE_URL/index.php" "$BASE_URL/index.php" \
      --data-urlencode 'email=testuser@gmail.com' --data-urlencode 'password=Test@123' --data-urlencode 'login=login')" \
  "$BASE_URL/dashboard.php"

echo "--- registration and change password"
post "$J4" "$BASE_URL/register.php" "$BASE_URL/register.php" \
  --data-urlencode 'name=New Person' --data-urlencode "email=$NEWUSER" \
  --data-urlencode 'mobilenumber=9876543210' --data-urlencode 'password=Secret@123' \
  --data-urlencode 'repeatpassword=Secret@123' --data-urlencode 'submit=1' > /dev/null
chk "the new user row is created" "$(sql "select count(*) from tbluser where Email='$NEWUSER'")" "1"
case "$(sql "select Password from tbluser where Email='$NEWUSER'")" in
  \$2y\$*) ok "the registered password is stored as bcrypt" ;;
  *) no "the registered password is stored as bcrypt" ;;
esac
chk "the new user can log in" \
  "$(postloc "$J4" "$BASE_URL/index.php" "$BASE_URL/index.php" \
      --data-urlencode "email=$NEWUSER" --data-urlencode 'password=Secret@123' --data-urlencode 'login=login')" \
  "$BASE_URL/dashboard.php"
BEFORE=$(sql "select Password from tbluser where Email='$NEWUSER'")
chk "change-password reports success" \
  "$(post "$J4" "$BASE_URL/change-password.php" "$BASE_URL/change-password.php" \
      --data-urlencode 'currentpassword=Secret@123' --data-urlencode 'newpassword=Changed@456' \
      --data-urlencode 'confirmpassword=Changed@456' --data-urlencode 'submit=1' | grep -q 'successully changed' && echo yes)" "yes"
chk "the stored hash changed" "$([ "$BEFORE" != "$(sql "select Password from tbluser where Email='$NEWUSER'")" ] && echo yes)" "yes"
chk "the new password logs in" \
  "$(postloc "$J5" "$BASE_URL/index.php" "$BASE_URL/index.php" \
      --data-urlencode "email=$NEWUSER" --data-urlencode 'password=Changed@456' --data-urlencode 'login=login')" \
  "$BASE_URL/dashboard.php"
chk "the old password no longer logs in" \
  "$(postloc "$J5" "$BASE_URL/index.php" "$BASE_URL/index.php" \
      --data-urlencode "email=$NEWUSER" --data-urlencode 'password=Secret@123' --data-urlencode 'login=login')" ""

echo "--- pagination"
TOTAL=$(sql 'select count(*) from tblexpense where UserId=3')
chk "page 1 shows ten rows" "$(curl -s -b "$J" "$BASE_URL/manage-expense.php" | grep -c 'name="delid"')" "$([ "$TOTAL" -ge 10 ] && echo 10 || echo "$TOTAL")"
chk "a nonsense page number falls back to page 1" \
  "$(curl -s -b "$J" "$BASE_URL/manage-expense.php?page=abc" | grep -c 'name="delid"')" "$([ "$TOTAL" -ge 10 ] && echo 10 || echo "$TOTAL")"
# 'Cloths' and 'Mobile' belong to user 2; the logged-in user is 3.
chk "another user's rows never appear" \
  "$(for p in 1 2 3; do curl -s -b "$J" "$BASE_URL/manage-expense.php?page=$p"; done | grep -c 'Cloths')" "0"

echo
echo "RESULT: $PASS passed, $FAIL failed"
[ "$FAIL" -eq 0 ]
