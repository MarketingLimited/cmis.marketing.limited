<?php

namespace App\Services\Social;

use App\Models\Social\QueueSlotLabel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Queue Slot Label Service
 *
 * Manages CRUD operations for queue slot labels.
 * Labels are organization-wide and can be used across all profiles.
 */
class QueueSlotLabelService
{
    /**
     * Get all labels for an organization.
     *
     * @param string $orgId Organization UUID
     * @param string|null $search Optional search term
     * @return Collection
     */
    public function getLabels(string $orgId, ?string $search = null): Collection
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            return QueueSlotLabel::where('org_id', $orgId)
                ->search($search)
                ->ordered()
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to fetch queue slot labels', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get a single label by ID.
     *
     * @param string $orgId Organization UUID
     * @param string $labelId Label UUID
     * @return QueueSlotLabel|null
     */
    public function getLabel(string $orgId, string $labelId): ?QueueSlotLabel
    {
        DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

        return QueueSlotLabel::where('org_id', $orgId)
            ->where('id', $labelId)
            ->first();
    }

    /**
     * Create a new label.
     *
     * @param string $orgId Organization UUID
     * @param array $data Label data
     * @return QueueSlotLabel
     */
    public function createLabel(string $orgId, array $data): QueueSlotLabel
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            // Generate unique slug
            $slug = $this->generateUniqueSlug($orgId, $data['name']);

            // Get next sort order
            $maxSortOrder = QueueSlotLabel::where('org_id', $orgId)->max('sort_order') ?? 0;

            $label = QueueSlotLabel::create([
                'org_id' => $orgId,
                'name' => $data['name'],
                'slug' => $slug,
                'background_color' => $data['background_color'] ?? '#3B82F6',
                'text_color' => $data['text_color'] ?? '#FFFFFF',
                'color_type' => $data['color_type'] ?? 'solid',
                'gradient_start' => $data['gradient_start'] ?? null,
                'gradient_end' => $data['gradient_end'] ?? null,
                'sort_order' => $maxSortOrder + 1,
            ]);

            Log::info('Queue slot label created', [
                'org_id' => $orgId,
                'label_id' => $label->id,
                'name' => $label->name,
            ]);

            return $label;
        } catch (\Exception $e) {
            Log::error('Failed to create queue slot label', [
                'org_id' => $orgId,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update an existing label.
     *
     * @param string $orgId Organization UUID
     * @param string $labelId Label UUID
     * @param array $data Updated label data
     * @return QueueSlotLabel|null
     */
    public function updateLabel(string $orgId, string $labelId, array $data): ?QueueSlotLabel
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            $label = QueueSlotLabel::where('org_id', $orgId)
                ->where('id', $labelId)
                ->first();

            if (!$label) {
                return null;
            }

            // Update fields
            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
                // Regenerate slug if name changed
                $updateData['slug'] = $this->generateUniqueSlug($orgId, $data['name'], $labelId);
            }

            if (isset($data['background_color'])) {
                $updateData['background_color'] = $data['background_color'];
            }

            if (isset($data['text_color'])) {
                $updateData['text_color'] = $data['text_color'];
            }

            if (isset($data['color_type'])) {
                $updateData['color_type'] = $data['color_type'];
            }

            if (array_key_exists('gradient_start', $data)) {
                $updateData['gradient_start'] = $data['gradient_start'];
            }

            if (array_key_exists('gradient_end', $data)) {
                $updateData['gradient_end'] = $data['gradient_end'];
            }

            if (isset($data['sort_order'])) {
                $updateData['sort_order'] = $data['sort_order'];
            }

            $label->update($updateData);

            Log::info('Queue slot label updated', [
                'org_id' => $orgId,
                'label_id' => $labelId,
                'changes' => array_keys($updateData),
            ]);

            return $label->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to update queue slot label', [
                'org_id' => $orgId,
                'label_id' => $labelId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete a label (soft delete).
     *
     * @param string $orgId Organization UUID
     * @param string $labelId Label UUID
     * @return bool
     */
    public function deleteLabel(string $orgId, string $labelId): bool
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            $label = QueueSlotLabel::where('org_id', $orgId)
                ->where('id', $labelId)
                ->first();

            if (!$label) {
                return false;
            }

            // Remove label from all queue slots that use it
            $this->removeLabelFromQueueSlots($orgId, $labelId);

            $label->delete();

            Log::info('Queue slot label deleted', [
                'org_id' => $orgId,
                'label_id' => $labelId,
                'name' => $label->name,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete queue slot label', [
                'org_id' => $orgId,
                'label_id' => $labelId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Remove a label from all queue slots that reference it.
     *
     * @param string $orgId Organization UUID
     * @param string $labelId Label UUID
     * @return int Number of slots updated
     */
    protected function removeLabelFromQueueSlots(string $orgId, string $labelId): int
    {
        $updatedCount = 0;

        // Get all queue settings for this org
        $queueSettings = DB::table('cmis.integration_queue_settings')
            ->where('org_id', $orgId)
            ->whereNotNull('schedule')
            ->get();

        foreach ($queueSettings as $settings) {
            $schedule = json_decode($settings->schedule, true);
            $modified = false;

            if (!$schedule) {
                continue;
            }

            foreach ($schedule as $day => $slots) {
                if (!is_array($slots)) {
                    continue;
                }

                foreach ($slots as $index => $slot) {
                    if (is_array($slot) && isset($slot['label_id']) && $slot['label_id'] === $labelId) {
                        $schedule[$day][$index]['label_id'] = null;
                        $modified = true;
                        $updatedCount++;
                    }
                }
            }

            if ($modified) {
                DB::table('cmis.integration_queue_settings')
                    ->where('id', $settings->id)
                    ->update(['schedule' => json_encode($schedule)]);
            }
        }

        return $updatedCount;
    }

    /**
     * Generate a unique slug for a label within an organization.
     *
     * @param string $orgId Organization UUID
     * @param string $name Label name
     * @param string|null $excludeId Exclude this label ID from uniqueness check
     * @return string
     */
    protected function generateUniqueSlug(string $orgId, string $name, ?string $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $query = QueueSlotLabel::where('org_id', $orgId)
                ->where('slug', $slug);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Get color presets for the UI.
     *
     * @return array
     */
    public function getColorPresets(): array
    {
        return [
            'solid' => QueueSlotLabel::getSolidColorPresets(),
            'gradient' => QueueSlotLabel::getGradientPresets(),
            'text' => QueueSlotLabel::getTextColorOptions(),
        ];
    }

    /**
     * Reorder labels.
     *
     * @param string $orgId Organization UUID
     * @param array $labelIds Ordered array of label IDs
     * @return bool
     */
    public function reorderLabels(string $orgId, array $labelIds): bool
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            foreach ($labelIds as $index => $labelId) {
                QueueSlotLabel::where('org_id', $orgId)
                    ->where('id', $labelId)
                    ->update(['sort_order' => $index]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to reorder queue slot labels', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
