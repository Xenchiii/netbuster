# Net Busters — WS1 Individual Final Project

Internet service shop (plans, cart, setup, payment, order tracking) built with
HTML5, CSS3, vanilla JavaScript and PHP + MySQL on XAMPP. Follows the 12-page Net Busters flowchart.

## Setup (XAMPP)
1. Copy the `netbusters` folder to `C:\xampp\htdocs\`  (keep the folder name `netbusters`).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open http://localhost/phpmyadmin → **Import** → choose `netbusters/database/netbusters.sql` → Go.
   (This creates the `netbusters_db` database, tables and sample data.)
4. Open http://localhost/netbusters/

If your folder name or MySQL password differs, edit `config/db.php` (`BASE_URL`, `DB_PASS`).

## Demo accounts (password for both: `password`)
| Role  | Username | Use |
|-------|----------|-----|
| Admin | admin    | Dashboard, plans CRUD, orders, users, reports, logs |
| User  | juan     | Browse, cart, checkout, track orders |

Change these passwords after your demo. New customers can register at `register.php`.

## Flowchart → pages
| Flowchart page | File |
|---|---|
| 1 Start & homepage | `index.php` |
| 2 Guest or account | `login.php`, `register.php`, `logout.php` |
| 3 Search products | `services.php` (live JS search / filter / sort) |
| 4 Product & stock check, "Notify me" | `plan.php`, `notify.php`, `notifications.php` |
| 5 Add to cart | `cart.php` |
| 6 Standard / priority setup | `checkout.php` (section 1) |
| 7 Network details & estimate | `checkout.php` (section 2) |
| 8 Payment entry, review order | `checkout.php` (section 3 + summary) |
| 9 Payment processing / error loop | `checkout.php` (server validation, DB transaction) |
| 10 Order number & confirmation | `order_success.php` |
| 11 Tracking alerts | `order_success.php`, `notifications.php` |
| 12 Technician & activation | `orders.php` (customer), `admin/orders.php` (admin) |

## Database (6 tables, all related with PK/FK)
`tbl_users` → `tbl_plans` (main records) · `tbl_orders` → `tbl_order_items` · `tbl_notify` · `tbl_transactions` (log)

## Rubric checklist
- **HTML/CSS:** semantic pages, shared header/footer, responsive layout (`assets/css/style.css`).
- **JavaScript:** form validation with inline errors, password strength, live search/filter/sort, Luhn card check, dynamic checkout totals, table sorting, confirm dialogs (`assets/js/app.js`).
- **PHP/MySQL:** full CRUD on plans, order create/update/cancel, prepared statements everywhere, `password_hash`, sessions, CSRF tokens, role checks, DB transactions with row locking for slots.
- **Security:** card numbers are never stored (only last 4 digits). Payment is a demo simulation.
- **Documentation:** use the supplied flowchart, DFD, ERD above, and screenshots of each page.

## Testing ideas for your Test/Debug Log
- Register with a weak password / mismatched confirm / duplicate username.
- Open "Ultra Fiber 1000" (0 slots) → Notify me → admin sets slots > 0 → bell badge appears for the user.
- Checkout with invalid card (e.g. 1234 5678 9012 3456) → payment error, cart kept.
- Place order → cancel while Pending → slots restored. Admin assigns technician → Activated.
- Try opening `/admin/` as a normal user → "Access denied".
