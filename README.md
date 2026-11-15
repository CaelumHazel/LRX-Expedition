# LRX Shipment Tracking

LRX is a shipment and parcel operations application built with Laravel. It manages shipment creation, barcode and tracking history, stock reservations for catalog items, courier assignments, warehouses, and shipping rates.

## Technology

- PHP 8.3+ and Laravel 13
- PostgreSQL for Supabase/production; SQLite is used by the automated tests
- Blade, Tailwind CSS v4, Alpine.js, and Vite
- JetBrains Mono and a dark Catppuccin Mocha-based interface

## Setup

1. Install PHP/Composer and Node.js/npm.
2. Copy `.env.example` to `.env`, set `APP_KEY`, and configure the database connection. For Supabase, use `DB_CONNECTION=pgsql` and the connection details from the Supabase project.
3. Install dependencies:

   ```sh
   composer install
   npm install
   ```

4. Configure the initial administrator in `.env`, then migrate and seed:

   ```dotenv
   ADMIN_USERNAME=
   ADMIN_EMAIL=
   ADMIN_PASSWORD=
   ```

   ```sh
   php artisan migrate
   php artisan db:seed
   ```

5. Build the frontend and start the local server:

   ```sh
   npm run build
   php artisan serve
   ```

   Open `/login` and sign in with the configured **username** and password. `ADMIN_EMAIL` is account metadata; login uses `ADMIN_USERNAME` and `ADMIN_PASSWORD`. The seeder creates or updates the administrator by username. Never commit `.env` or share its credentials.

Migrations change the configured database. Confirm `.env` points to the intended database before running `php artisan migrate` or `php artisan db:seed`; running the tests does not require Supabase.

## Implemented Features

### Authentication and roles

- Login uses username and password; logout invalidates the session.
- Application pages are behind the `auth` middleware.
- Admin users can update tracking manually. Other authenticated users can view operational pages but cannot submit manual tracking updates.
- Create the initial admin using `ADMIN_USERNAME`, `ADMIN_EMAIL`, and `ADMIN_PASSWORD`; no default credential is stored in this documentation.

### Dashboard and shipments

- Dashboard shows shipment summaries and recent shipments.
- Shipment forms accept sender and receiver names/phone numbers, package names/descriptions/quantities, manually measured total weight, and a detailed receiver address.
- Cargo type, origin/destination, courier, and warehouse are selectable fields. City search supports typing and keyboard navigation (arrow keys and Enter).
- Active couriers and warehouses are available for assignment. Changing a shipment's warehouse updates its current position and records a tracking log.
- Shipment details include contacts, route, package/legacy catalog item details, price information, tracking history, and a printable barcode.
- Resi numbers use `LRXyyyymmddxxxx` (for example `LRX202611150001`). A per-day sequence row is locked inside the shipment transaction and the tracking number has a unique constraint. The sequence starts at `0001` each day and supports up to `9999` shipments per day.
- Shipment search includes tracking number and sender/receiver names. Filters include status, cargo type, origin, destination, courier, and creation-date range.

### Tracking

- Statuses used by the application are `pending`, `picked_up`, `in_transit`, `at_warehouse`, `out_for_delivery`, `delivered`, and `cancelled`.
- Admins can add a manual status/location update; changes to shipment status and current position are recorded in `tracking_logs` with timestamp, user, and source.
- Tracking updates may also be scheduled for a future date/time. The scheduled command skips updates if a manual update was made after scheduling, or if the shipment is already delivered/cancelled.
- The tracking detail URL is `/tracking/{trackingNumber}`. It currently requires login; public tracking access is not enabled.
- Pages show current data when loaded/refreshed; a WebSocket/push-based live update channel is not configured.

### Inventory and shipment stock

- The Shipment Stock page separates active and completed shipments and supports search by resi or package/item name.
- Legacy catalog items linked through `shipment_items` reserve inventory when a shipment is created. Cancellation restores the reserved quantity; stock changes are recorded in `stock_movements`.
- Free-text package descriptions are stored in `shipment_packages` and do not create catalog items or change inventory counts.

### Couriers and warehouses

- The Couriers page lists couriers and their active shipment/package assignments. **Add Courier** creates a courier record; active couriers can then be assigned to shipments.
- Courier detail pages show shipment, route, package, recipient, warehouse, and status information.
- Warehouses can be created, viewed, edited, and deleted when not blocked by related data. Warehouse detail shows assigned shipments and their package/item details.
- Inventory and Customers navigation targets currently return an HTTP 404 page styled within the authenticated application layout and labeled as under development.

### Shipping fees and city catalog

- Active configured rates take precedence. A matching rate is selected by route and cargo type, with an optional courier-specific rate. The fee is `ceil(weight in kg) × price_per_kg`; the configured rate provides the estimated delivery days.
- If no configured rate matches, a clearly labeled dummy estimate is used:
  - Same city: 25 km
  - Same province: 120 km
  - Same island group: 650 km
  - Different island groups: 1700 km
  - Dummy fee: `ceil(weight in kg) × (5000 + distance_km × 5)`
  - Dummy estimated days: `max(1, ceil(distance_km / 500))`
- These values are approximations, not road distances or production carrier tariffs. The server recalculates the fee; browser values are not authoritative.
- The local catalog in `database/data/indonesia-cities.json` contains 514 kabupaten/kota. Its base data comes from [api-wilayah-indonesia](https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json); Papua province divisions were added locally. Verify the catalog against an official current source before using it as a regulatory/address authority.
- Declared value is entered manually and is separate from the shipping fee.

### UI

- The authenticated layout provides a persistent collapsible sidebar, responsive navigation, top bar, and shared notification messages.
- Styling uses the Catppuccin Mocha and LRX theme tokens in `resources/css/app.css`; the body font is JetBrains Mono.
- The city combobox and sidebar interactions use Alpine.js.

## Data Model and Important Code

| Area | Location | Responsibility |
|---|---|---|
| Routes | `routes/web.php` | Login/logout, authenticated pages, shipment, tracking, warehouse, courier, and rate routes |
| Controllers | `app/Http/Controllers/` | Request orchestration for shipments, tracking, couriers, warehouses, dashboard, and rates |
| Form requests | `app/Http/Requests/` | Validation and authorization for shipment, tracking, courier, and warehouse input |
| Models | `app/Models/` | Eloquent records and relationships |
| Services | `app/Services/ShipmentInventoryService.php` | Catalog stock reservation, restoration, and movements |
| Services | `app/Services/ShippingFeeEstimator.php` | Configured-rate lookup and dummy fee calculation |
| Support | `app/Support/IndonesianCityCatalog.php` | City catalog lookup, aliases, and approximate distance grouping |
| Support | `app/Support/TrackingBarcode.php` | SVG barcode generation for tracking numbers |
| Scheduled command | `app/Console/Commands/ApplyScheduledTrackingUpdates.php` | Applies due updates without overriding later manual changes |
| Database | `database/migrations/` | PostgreSQL-compatible schema, shipment snapshots/packages, sequences, stock, and tracking tables |
| Views | `resources/views/` | Blade pages for shipments, tracking, warehouse, couriers, authentication, and shared layout |
| Frontend | `resources/js/app.js`, `resources/css/app.css` | Alpine.js setup and Tailwind theme/styles |

Core records include `users`, `shipments`, `customers` (sender/receiver references and snapshots), `shipment_items`, `shipment_packages`, `tracking_logs`, `scheduled_tracking_updates`, `tracking_number_sequences`, `items`, `stock_movements`, `couriers`, `warehouses`, and `shipping_rates`. Tracking history is stored in `tracking_logs` (the application table name), rather than a table named `tracking_histories`.

## Scheduler

Run the scheduler worker during local development:

```sh
php artisan schedule:work
```

In production, configure Laravel's scheduler to execute `php artisan schedule:run` every minute. Due tracking updates are processed by the `tracking:apply-scheduled-updates` command.

## Tests and Build

```sh
php artisan test
npm run build
```

The test environment uses SQLite; tests do not verify connectivity to or migrate the Supabase project.
