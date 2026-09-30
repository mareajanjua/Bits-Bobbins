# Bits&Bobbins

Bits&Bobbins is a Laravel + MySQL online shopping cart project using plain Blade, HTML, CSS and JavaScript on the frontend.

## Tech stack

- PHP / Laravel
- MySQL
- Blade templates
- HTML, CSS and JavaScript
- Bootstrap Icons and local frontend assets

Vite is not used in this project.

## Database name vs SQL file name

The database name and SQL file name are intentionally different:

- `bitsandbobbins` is the MySQL database name used in `.env`, `.env.example`, phpMyAdmin and setup instructions.
- `database/bits_and_bobbins.sql` is only the import file name. It does not contain a `CREATE DATABASE` statement, so it creates tables inside whichever database you import it into.

## Clean install

1. Run Composer:

   ```powershell
   composer install
   ```

2. Copy the environment example:

   ```powershell
   Copy-Item .env.example .env
   ```

3. Generate the Laravel app key:

   ```powershell
   php artisan key:generate
   ```

4. In phpMyAdmin, create an empty database named:

   ```text
   bitsandbobbins
   ```

   Use `utf8mb4_general_ci`.

5. Import:

   ```text
   database/bits_and_bobbins.sql
   ```

6. After import, check:

   - the database has the project tables
   - `delivery_type` has 3 rows
   - `admin` has 1 row

7. Start the app:

   ```powershell
   php artisan serve
   ```

## Default admin login

```text
Email: admin@email.com
Password: admin123
```

## Important workflow notes

- Card, cheque and DD payments must be cleared before dispatch.
- VPP / Cash on Delivery orders can be dispatched while payment is pending.
- VPP payment is automatically marked cleared when the employee marks the last item in that order as delivered.
- Cancelled orders cannot be manually marked as payment cleared.
- Refund transfer/payment handling for cancelled paid orders is handled outside this system.
- Return approval records the refund amount and restores stock.
- Replacement approval marks the item as replaced; the physical replacement shipment is handled manually by the shop.

## Build the submission zip

Do not zip the folder manually. From the project root, run:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\build-submission.ps1
```

It creates:

```text
bits_and_bobbins-submission.zip
```

The script excludes `.env`, `.git`, `vendor`, logs, sessions, compiled views, generated cache files, test uploads and leftover template files.

If the instructor needs `vendor` included because they will run it without Composer, remove `"vendor",` from `$excludeDirs` in `scripts/build-submission.ps1`, then rebuild the zip.

## Test the zip before submitting

1. Unzip `bits_and_bobbins-submission.zip` into a new folder.
2. Run `composer install`.
3. Copy `.env.example` to `.env`.
4. Run `php artisan key:generate`.
5. Create `bitsandbobbins` in phpMyAdmin.
6. Import `database/bits_and_bobbins.sql`.
7. Run `php artisan serve`.
8. Login as admin and place one test order.

