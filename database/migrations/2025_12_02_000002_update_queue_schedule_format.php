<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates the schedule column format from simple time arrays to slot objects.
     *
     * OLD format: {"monday": ["09:00", "13:00"]}
     * NEW format: {"monday": [{"time": "09:00", "label_id": null, "is_evergreen": false}, ...]}
     */
    public function up(): void
    {
        // Get all existing queue settings with schedule data
        $settings = DB::table('cmis.integration_queue_settings')
            ->whereNotNull('schedule')
            ->get();

        foreach ($settings as $setting) {
            $oldSchedule = json_decode($setting->schedule, true);

            if (!$oldSchedule) {
                continue;
            }

            $newSchedule = [];

            foreach ($oldSchedule as $day => $times) {
                $newSchedule[$day] = [];

                if (is_array($times)) {
                    foreach ($times as $time) {
                        // If already in new format (object), preserve it
                        if (is_array($time) && isset($time['time'])) {
                            $newSchedule[$day][] = $time;
                        } else {
                            // Convert simple time string to slot object
                            $newSchedule[$day][] = [
                                'time' => $time,
                                'label_id' => null,
                                'is_evergreen' => false,
                            ];
                        }
                    }
                }
            }

            // Update the record with new format
            DB::table('cmis.integration_queue_settings')
                ->where('id', $setting->id)
                ->update(['schedule' => json_encode($newSchedule)]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Converts back to simple time arrays.
     */
    public function down(): void
    {
        // Get all existing queue settings
        $settings = DB::table('cmis.integration_queue_settings')
            ->whereNotNull('schedule')
            ->get();

        foreach ($settings as $setting) {
            $schedule = json_decode($setting->schedule, true);

            if (!$schedule) {
                continue;
            }

            $oldSchedule = [];

            foreach ($schedule as $day => $slots) {
                $oldSchedule[$day] = [];

                if (is_array($slots)) {
                    foreach ($slots as $slot) {
                        // Extract just the time from slot object
                        if (is_array($slot) && isset($slot['time'])) {
                            $oldSchedule[$day][] = $slot['time'];
                        } else {
                            // Already simple format
                            $oldSchedule[$day][] = $slot;
                        }
                    }
                }
            }

            DB::table('cmis.integration_queue_settings')
                ->where('id', $setting->id)
                ->update(['schedule' => json_encode($oldSchedule)]);
        }
    }
};
