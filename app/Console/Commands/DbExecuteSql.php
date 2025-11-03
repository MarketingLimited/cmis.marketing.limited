<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class DbExecuteSql extends Command
{
    protected $signature = "db:execute-sql {file}";
    protected $description = "Execute a raw SQL file against the default database connection";
    public function handle()
    {
        $file = base_path($this->argument("file"));
        if (!file_exists($file)) {
            $this->error("SQL file not found: {$file}");
            return Command::FAILURE;
        }
        try {
            $sql = file_get_contents($file);
            DB::unprepared($sql);
            $this->info("✅ Successfully executed SQL file: {$file}");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error executing SQL: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
