# Daily Expense Tracker

A web-based expense tracking system built with **PHP and MySQL**. Users register, log in, record daily
expenses, and review them through date-wise, month-wise and year-wise reports on a dashboard.

> **Status:** the folder skeleton is in place. The application files are being uploaded folder by folder —
> see [What still needs uploading](#what-still-needs-uploading) for the exact map.

---

## Tech stack

| Layer | What's used |
|---|---|
| Server | PHP (procedural, `mysqli`) |
| Database | MySQL / MariaDB — schema in `database/detsdb.sql` |
| Front end | Bootstrap 3, jQuery, Font Awesome, Flot (charting) |
| Bundled libraries | `assets/plugins/`, `css/`, `js/`, `fonts/` (vendored, unmodified) |

---

## Repository layout

```
.
├── index.php                  login page            ← upload (root)
├── register.php  logout.php   user session
├── dashboard.php              landing page after login
├── add-expense.php            record an expense
├── manage-expense.php         list / edit / delete expenses
├── expense-reports.php        report index
├── expense-datewise-reports.php          (+ -detailed)
├── expense-monthwise-reports.php         (+ -detailed)
├── expense-yearwise-reports.php          (+ -detailed)
├── user-profile.php  change-password.php  forgot-password.php  reset-password.php
│
├── includes/                  dbconnection.php, header.php, sidebar.php, footer.php
├── database/detsdb.sql        schema + seed data              ✔ committed
├── docs/                      synopsis.pdf
├── assets/images/             logos, backgrounds, avatars
├── assets/plugins/            vendored JS libraries
├── css/  js/  fonts/  sass/   theme assets
└── tables/                    data1.json, data2.json
```

---

## What still needs uploading

Every folder below contains a `.gitkeep` placeholder. Drop the listed files in, then delete the
`.gitkeep`. Totals: **219 files**.

| Folder | What goes here | Files | Notes |
|---|---|---|---|
| `./` (repo root) | All 18 `*.php` pages | 18 | `index.php`, `dashboard.php`, `add-expense.php`, `manage-expense.php`, the 8 `expense-*.php` report pages, `register.php`, `login`/`logout`, `user-profile.php`, `change-password.php`, `forgot-password.php`, `reset-password.php` |
| `includes/` | `dbconnection.php`, `header.php`, `sidebar.php`, `footer.php` | 4 | **Required** — every page includes these |
| `database/` | `detsdb.sql` | 1 | ✔ already committed |
| `docs/` | `synopsis.pdf` | 1 | Project synopsis |
| `assets/images/` | Logos, background images, user avatars | 13 | Binary — upload as files |
| `assets/plugins/` | Bootstrap, jQuery, Flot, gmaps, sticky-kit, etc. | 142 | **Bulk of the repo (~5 MB).** Unmodified third-party libraries |
| `css/` | Bootstrap + theme stylesheets and source maps | 13 | |
| `js/` | Page scripts | 13 | |
| `fonts/` | Font Awesome and Glyphicons webfonts | 11 | Binary |
| `sass/` | `styles.scss` | 1 | Theme source |
| `tables/` | `data1.json`, `data2.json` | 2 | Table demo data |

**Uploading tip:** GitHub's web uploader accepts at most **100 files per drag-and-drop**, and this
project has 219. Either upload folder by folder, or clone the repo and copy everything in at once:

```bash
git clone https://github.com/Roopashree935/Daily-expense-tracker.git
cd Daily-expense-tracker
# copy the project files into this directory
git add .
git commit -m "feat: add Daily Expense Tracker source"
git push origin master
```

---

## Running it locally (XAMPP)

1. Install **XAMPP** (or WAMP/LAMP) and start **Apache** and **MySQL**.
2. Copy the project folder into the web root — `htdocs` for XAMPP, `www` for WAMP,
   `/var/www/html` for LAMP. The folder name becomes part of the URL.
3. Open <http://localhost/phpmyadmin> and create a database named **`detsdb`**.
4. Select `detsdb` → **Import** → choose `database/detsdb.sql` → **Go**.
5. Check the connection settings in `includes/dbconnection.php` and adjust host, user and password.
6. Visit <http://localhost/Daily-expense-tracker/> (use whatever folder name you used in step 2).

### Demo login

```
Email:    testuser@gmail.com
Password: Test@123
```

You can also register a new account instead.

---

## Notes and limitations

This is a learning project and is **not production-ready**. Recorded honestly so nobody mistakes it
for a hardened application:

- Passwords are stored with `md5()`, which is not a suitable password hash.
- Queries are built by string interpolation rather than prepared statements, so the login,
  registration and report pages are **not safe against SQL injection**.
- `includes/dbconnection.php` contains local development credentials (XAMPP defaults: user `root`,
  empty password). Replace these before running it anywhere public, and never commit real credentials.
- No CSRF protection and no rate limiting on authentication.

Improving those would be the natural next step for this project.
