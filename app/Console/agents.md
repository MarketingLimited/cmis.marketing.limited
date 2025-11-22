# دليل الوكلاء - Console Layer (app/Console/)

## 1. Purpose

طبقة Console توفر **Artisan Commands**:
- **30+ Custom Commands**
- **Scheduled Tasks**: Cron jobs
- **Database Operations**: Sync, embeddings
- **Maintenance**: Cleanup, optimization

## 2. Owned Scope

```
app/Console/
├── Commands/
│   ├── Embeddings/      # AI embedding generation
│   ├── Sync/            # Platform data sync
│   ├── VectorEmbeddings/ # Vector search
│   ├── Database/        # DB maintenance
│   └── Maintenance/     # System cleanup
│
├── Kernel.php           # Command registration & scheduling
└── Traits/              # Reusable command logic
```

## 3. Key Commands

```bash
# AI Operations
php artisan embeddings:generate
php artisan embeddings:sync

# Platform Sync
php artisan sync:meta
php artisan sync:google

# Database
php artisan db:optimize
php artisan db:cleanup

# Maintenance
php artisan cache:warm
php artisan queue:monitor
```

## 4. Command Pattern

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncMetaDataCommand extends Command
{
    protected $signature = 'sync:meta {org_id?} {--days=7}';
    protected $description = 'Sync Meta platform data';

    public function handle(MetaSyncService $service)
    {
        $this->info('Starting Meta sync...');

        $result = $service->sync([
            'org_id' => $this->argument('org_id'),
            'days' => $this->option('days'),
        ]);

        $this->info("Synced {$result['count']} records");
        return Command::SUCCESS;
    }
}
```

## 5. Scheduling (Kernel.php)

```php
protected function schedule(Schedule $schedule)
{
    // Sync platforms daily
    $schedule->command('sync:meta')->daily();

    // Generate embeddings hourly
    $schedule->command('embeddings:generate')->hourly();

    // Cleanup old logs weekly
    $schedule->command('logs:cleanup')->weekly();
}
```
