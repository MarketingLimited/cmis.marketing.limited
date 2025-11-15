# Database Seeders

This directory contains Laravel database seeders generated from the PostgreSQL backup file.

## Overview

The seeders were automatically generated from `database/backup-db-for-seeds.sql` using the script located at `scripts/generate-seeders-from-backup.php`.

## Generated Seeders

### Active Seeders (Called by DatabaseSeeder)

1. **RolesSeeder** - Seeds the `cmis.roles` table with system roles (1 row)
   - Contains the default "Owner" role with full permissions

2. **UsersSeeder** - Seeds the `cmis.users` table with the admin user (1 row)
   - Creates the default admin user (admin@cmis.test)

3. **SessionsSeeder** - Seeds the `cmis.sessions` table (1 row)
   - Contains a sample session (mostly for development/testing)

### Inactive Seeders (Not Called)

4. **MigrationsSeeder** - Seeds the `cmis.migrations` table (19 rows)
   - **Not called** by DatabaseSeeder as migrations are managed by Laravel's migration system
   - Kept for reference but should not be used

## Usage

To run all seeders:

```bash
php artisan db:seed
```

To run a specific seeder:

```bash
php artisan db:seed --class=RolesSeeder
```

To refresh the database and seed:

```bash
php artisan migrate:fresh --seed
```

## Regenerating Seeders

If you need to regenerate the seeders from the backup file:

```bash
php scripts/generate-seeders-from-backup.php
```

This will parse `database/backup-db-for-seeds.sql` and regenerate all seeder classes.

## Notes

- All seeders use `SET CONSTRAINTS ALL DEFERRED` to handle foreign key constraints during seeding
- Data is inserted in chunks of 500 rows to avoid memory issues with large datasets
- The backup file contains 116 tables, but only 4 tables had actual data at the time of generation
- Timestamps from PostgreSQL (with timezone info) are preserved as-is

## Data Seeding Order

The seeders are called in this order by `DatabaseSeeder`:

1. RolesSeeder (creates system roles)
2. UsersSeeder (creates admin user, depends on roles)
3. SessionsSeeder (creates session for user)

This order ensures foreign key relationships are satisfied.
