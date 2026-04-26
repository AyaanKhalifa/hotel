# Royale Vista v2 — Hotel Booking Website

A full-stack hotel booking website built in PHP + MySQL.

---

## ⚡ Quick Setup on WAMP

### Step 1 — Place files
Copy the `royale-vista-v2` folder into:
```
C:\wamp64\www\royale-vista-v2\
```

### Step 2 — Create database
1. Open **phpMyAdmin**: http://localhost/phpmyadmin
2. Click **New** → name it `royalevista` → click **Create**
3. Click **Import** → choose `setup.sql` → click **Go**

### Step 3 — Open the website
```
http://localhost/royale-vista-v2/
```

### Step 4 — Admin panel
```
http://localhost/royale-vista-v2/admin/
```

---

## 🔑 Login Credentials

| Role  | Email                      | Password  |
|-------|---------------------------|-----------|
| Admin | admin@royalevista.com     | password  |
| Guest | john@example.com          | password  |
| Guest | priya@example.com         | password  |
| Guest | james@example.com         | password  |

---

## ✅ Working Features

| Feature | Status |
|---------|--------|
| User registration & login | ✅ Working |
| Browse rooms with live availability | ✅ Working |
| Book a room (4-step flow) | ✅ Working |
| Payment selection (Card / UPI / Hotel) | ✅ Working |
| Booking confirmation + loyalty points | ✅ Working |
| Cancel booking | ✅ Working |
| Invoice / receipt download | ✅ Working |
| My Bookings dashboard | ✅ Working |
| Offer/coupon codes | ✅ Working |
| Membership discounts | ✅ Working |
| Loyalty points earn & redeem | ✅ Working |
| Concierge requests | ✅ Working |
| Admin: bookings management | ✅ Working |
| Admin: cancel + refund | ✅ Working |
| Admin: rooms & pricing | ✅ Working |
| Admin: users / guests | ✅ Working |
| Admin: gallery manager | ✅ Working |
| Admin: analytics & reports | ✅ Working |
| Multi-currency display | ✅ Working |
| Multi-language support | ✅ Working |
| Dark/light theme toggle | ✅ Working |
| Reviews & ratings | ✅ Working |
| Room wishlist | ✅ Working |
| Worldwide properties page | ✅ Working |

---

## 📁 Key File Map

```
index.php                 — Homepage
rooms.php                 — Browse & search rooms
booking-details.php       — Step 2: guest details
booking-payment.php       — Step 3: payment method
booking-confirm.php       — Step 4: confirmation
bookings.php              — My bookings + cancel
invoice.php               — Invoice/receipt
login.php                 — Login + register
profile.php               — User profile
loyalty.php               — Loyalty points
concierge.php             — Concierge requests
membership.php            — Membership plans

api/book.php              — Booking + cancel API
api/availability.php      — Room availability check
api/reviews.php           — Submit reviews
api/loyalty.php           — Points API
api/wishlist.php          — Wishlist toggle

admin/index.php           — Admin dashboard
admin/bookings.php        — Manage bookings
admin/rooms.php           — Room management
admin/users.php           — Guest management
admin/analytics.php       — Charts & reports
admin/gallery.php         — Photo gallery
admin/offers.php          — Promo codes
admin/loyalty.php         — Loyalty management

includes/config.php       — App config, currencies, helpers
includes/db.php           — Database connection (edit DB credentials here)
setup.sql                 — Full database schema + seed data
```

---

## ⚙️ Database Credentials

Edit `includes/db.php` if your MySQL has a password:
```php
$DB_HOST = 'localhost';
$DB_NAME = 'royalevista';
$DB_USER = 'root';
$DB_PASS = '';  ← set your password here
```

---

## 🐛 Bugs Fixed in This Version

1. **`api/availability.php`** — Broken subquery using `COALESCE(r.room_id,0)` meant rooms were never shown as available. Fixed to proper join.
2. **`api/book.php` cancel** — Only allowed cancelling `confirmed` bookings. Fixed to also allow `pending`.
3. **`setup.sql`** — Duplicate/conflicting table definitions (`properties`, `concierge_requests` created twice with different schemas). Completely rebuilt as single clean schema.
4. **`admin/rooms.php`** — Referenced non-existent `base_price_per_night` column. Fixed.
5. **`admin/loyalty.php`** — PHP syntax error (stray `]` in inline style). Fixed.
6. **`admin/css/admin.css`** — Missing CSS variable aliases (`--muted`, `--border`, `--text`, `--gold-dk`, etc.) and missing class aliases (`adm-card`, `adm-sidebar`, `adm-nav-link`, etc.). All added.
7. **`admin/index.php`** — Used non-existent CSS classes from `style.css` instead of `admin.css`. Rewritten to use `adminPage()` helper.
8. **`admin/partials/topbar.php` + `sidebar.php`** — Wrong CSS class names (`adm-sidebar` vs `adm-sb`, etc.). Fixed.
9. **`admin/gallery.php` + `admin/concierge.php`** — Undefined CSS variables and `adm-card` classes. Fixed.
10. **`includes/config.php`** — Duplicate `CHF` currency key that overwrote `DKK`. Fixed.
11. **`locations.php`** — Queried `is_active` column that doesn't exist on `hotel_properties` table. Fixed.
