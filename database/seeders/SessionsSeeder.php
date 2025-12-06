<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SessionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates a demo session for the first available user.
     * This is useful for development/testing environments.
     */
    public function run(): void
    {
        // Find the first available user to create a session for
        $user = DB::table('cmis.users')
            ->whereNull('deleted_at')
            ->first();

        if (!$user) {
            $this->command->warn('⚠️  SessionsSeeder: No users found, skipping session creation.');
            return;
        }

        // Generate a unique session ID
        $sessionId = Str::random(40);

        // Check if session already exists for this user
        $existingSession = DB::table('cmis.sessions')
            ->where('user_id', $user->id)
            ->exists();

        if ($existingSession) {
            $this->command->info('ℹ️  SessionsSeeder: User already has a session, skipping.');
            return;
        }

        // Build the session payload with proper Laravel session format
        $payload = base64_encode(serialize([
            '_token' => Str::random(40),
            '_previous' => [
                'url' => config('app.url') . '/dashboard',
                'route' => 'dashboard',
            ],
            '_flash' => [
                'old' => [],
                'new' => [],
            ],
            'url' => [],
            'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d' => $user->id,
        ]));

        DB::table('cmis.sessions')->insert([
            'id' => $sessionId,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Development Session)',
            'payload' => $payload,
            'last_activity' => time(),
        ]);

        $this->command->info("✅ SessionsSeeder: Created demo session for user: {$user->email}");
    }
}
