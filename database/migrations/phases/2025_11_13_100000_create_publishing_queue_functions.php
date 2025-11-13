<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates PostgreSQL functions for publishing queue management
     * Implements Sprint 2.1: Queue per-Channel
     */
    public function up(): void
    {
        // Function: Get publishing queue configuration
        DB::unprepared("
            CREATE OR REPLACE FUNCTION cmis.get_publishing_queue(p_social_account_id UUID)
            RETURNS TABLE (
                queue_id UUID,
                org_id UUID,
                social_account_id UUID,
                weekdays_enabled VARCHAR(7),
                time_slots JSONB,
                timezone VARCHAR(50),
                is_active BOOLEAN,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
            LANGUAGE plpgsql
            SECURITY DEFINER
            AS $$
            BEGIN
                RETURN QUERY
                SELECT
                    pq.queue_id,
                    pq.org_id,
                    pq.social_account_id,
                    pq.weekdays_enabled,
                    pq.time_slots,
                    pq.timezone,
                    pq.is_active,
                    pq.created_at,
                    pq.updated_at
                FROM cmis.publishing_queues pq
                WHERE pq.social_account_id = p_social_account_id
                  AND pq.is_active = true;
            END;
            $$;
        ");

        // Function: Upsert publishing queue
        DB::unprepared("
            CREATE OR REPLACE FUNCTION cmis.upsert_publishing_queue(
                p_org_id UUID,
                p_social_account_id UUID,
                p_weekdays_enabled VARCHAR(7),
                p_time_slots JSONB,
                p_timezone VARCHAR(50)
            )
            RETURNS TABLE (
                queue_id UUID,
                org_id UUID,
                social_account_id UUID,
                weekdays_enabled VARCHAR(7),
                time_slots JSONB,
                timezone VARCHAR(50),
                is_active BOOLEAN,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
            LANGUAGE plpgsql
            SECURITY DEFINER
            AS $$
            BEGIN
                -- Insert or update publishing queue
                INSERT INTO cmis.publishing_queues (
                    queue_id,
                    org_id,
                    social_account_id,
                    weekdays_enabled,
                    time_slots,
                    timezone,
                    is_active,
                    created_at,
                    updated_at
                )
                VALUES (
                    gen_random_uuid(),
                    p_org_id,
                    p_social_account_id,
                    COALESCE(p_weekdays_enabled, '1111100'),
                    COALESCE(p_time_slots, '[]'::jsonb),
                    COALESCE(p_timezone, 'UTC'),
                    true,
                    NOW(),
                    NOW()
                )
                ON CONFLICT (social_account_id)
                DO UPDATE SET
                    weekdays_enabled = COALESCE(EXCLUDED.weekdays_enabled, cmis.publishing_queues.weekdays_enabled),
                    time_slots = COALESCE(EXCLUDED.time_slots, cmis.publishing_queues.time_slots),
                    timezone = COALESCE(EXCLUDED.timezone, cmis.publishing_queues.timezone),
                    updated_at = NOW();

                -- Return the upserted queue
                RETURN QUERY
                SELECT
                    pq.queue_id,
                    pq.org_id,
                    pq.social_account_id,
                    pq.weekdays_enabled,
                    pq.time_slots,
                    pq.timezone,
                    pq.is_active,
                    pq.created_at,
                    pq.updated_at
                FROM cmis.publishing_queues pq
                WHERE pq.social_account_id = p_social_account_id;
            END;
            $$;
        ");

        // Function: Get next available time slot
        DB::unprepared("
            CREATE OR REPLACE FUNCTION cmis.get_next_available_slot(
                p_social_account_id UUID,
                p_after_time TIMESTAMP DEFAULT NOW()
            )
            RETURNS TABLE (next_slot TIMESTAMP)
            LANGUAGE plpgsql
            SECURITY DEFINER
            AS $$
            DECLARE
                v_queue RECORD;
                v_current_time TIMESTAMP;
                v_current_date DATE;
                v_day_index INT;
                v_time_slot TEXT;
                v_candidate_slot TIMESTAMP;
                v_max_days INT := 30; -- Look ahead max 30 days
                v_days_checked INT := 0;
            BEGIN
                -- Get queue configuration
                SELECT * INTO v_queue
                FROM cmis.publishing_queues
                WHERE social_account_id = p_social_account_id
                  AND is_active = true;

                -- If no queue configured, return null
                IF NOT FOUND THEN
                    RETURN;
                END IF;

                -- Start from after_time (converted to queue timezone)
                v_current_time := p_after_time AT TIME ZONE v_queue.timezone;
                v_current_date := v_current_time::DATE;

                -- Loop through days to find next available slot
                WHILE v_days_checked < v_max_days LOOP
                    -- Get day of week (0=Sunday, 1=Monday, ..., 6=Saturday)
                    -- Convert to our format (0=Monday, 1=Tuesday, ..., 6=Sunday)
                    v_day_index := CASE EXTRACT(DOW FROM v_current_date)
                        WHEN 0 THEN 6  -- Sunday -> 6
                        WHEN 1 THEN 0  -- Monday -> 0
                        WHEN 2 THEN 1  -- Tuesday -> 1
                        WHEN 3 THEN 2  -- Wednesday -> 2
                        WHEN 4 THEN 3  -- Thursday -> 3
                        WHEN 5 THEN 4  -- Friday -> 4
                        WHEN 6 THEN 5  -- Saturday -> 5
                    END;

                    -- Check if this day is enabled in queue
                    IF SUBSTRING(v_queue.weekdays_enabled FROM (v_day_index + 1) FOR 1) = '1' THEN
                        -- Loop through time slots for this day
                        FOR v_time_slot IN
                            SELECT value::TEXT
                            FROM jsonb_array_elements_text(v_queue.time_slots)
                        LOOP
                            -- Build candidate timestamp
                            v_candidate_slot := (v_current_date::TEXT || ' ' || v_time_slot)::TIMESTAMP;
                            v_candidate_slot := v_candidate_slot AT TIME ZONE v_queue.timezone;

                            -- If this slot is after our after_time, check if it's available
                            IF v_candidate_slot > p_after_time THEN
                                -- Check if slot is not already taken
                                -- (Assuming posts table has scheduled_for column)
                                IF NOT EXISTS (
                                    SELECT 1
                                    FROM cmis.social_posts sp
                                    WHERE sp.social_account_id = p_social_account_id
                                      AND sp.scheduled_for = v_candidate_slot
                                      AND sp.status IN ('scheduled', 'queued')
                                ) THEN
                                    -- Found an available slot!
                                    RETURN QUERY SELECT v_candidate_slot;
                                    RETURN;
                                END IF;
                            END IF;
                        END LOOP;
                    END IF;

                    -- Move to next day
                    v_current_date := v_current_date + INTERVAL '1 day';
                    v_days_checked := v_days_checked + 1;
                END LOOP;

                -- No available slot found within max_days
                RETURN;
            END;
            $$;
        ");

        // Function: Get queued posts
        DB::unprepared("
            CREATE OR REPLACE FUNCTION cmis.get_queued_posts(p_social_account_id UUID)
            RETURNS TABLE (
                post_id UUID,
                social_account_id UUID,
                content TEXT,
                scheduled_for TIMESTAMP,
                status VARCHAR(50),
                platform VARCHAR(50),
                post_type VARCHAR(50),
                media_urls JSONB,
                created_at TIMESTAMP
            )
            LANGUAGE plpgsql
            SECURITY DEFINER
            AS $$
            BEGIN
                RETURN QUERY
                SELECT
                    sp.post_id,
                    sp.social_account_id,
                    sp.content,
                    sp.scheduled_for,
                    sp.status,
                    sp.platform,
                    sp.post_type,
                    sp.media_urls,
                    sp.created_at
                FROM cmis.social_posts sp
                WHERE sp.social_account_id = p_social_account_id
                  AND sp.status IN ('queued', 'scheduled')
                  AND sp.scheduled_for > NOW()
                ORDER BY sp.scheduled_for ASC;
            END;
            $$;
        ");

        // Function: Schedule post to queue
        DB::unprepared("
            CREATE OR REPLACE FUNCTION cmis.schedule_post_to_queue(
                p_post_id UUID,
                p_social_account_id UUID,
                p_scheduled_for TIMESTAMP
            )
            RETURNS BOOLEAN
            LANGUAGE plpgsql
            SECURITY DEFINER
            AS $$
            BEGIN
                -- Update post with scheduled time
                UPDATE cmis.social_posts
                SET
                    social_account_id = p_social_account_id,
                    scheduled_for = p_scheduled_for,
                    status = 'queued',
                    updated_at = NOW()
                WHERE post_id = p_post_id;

                -- Check if update was successful
                IF FOUND THEN
                    RETURN true;
                ELSE
                    RETURN false;
                END IF;
            END;
            $$;
        ");

        // Function: Remove post from queue
        DB::unprepared("
            CREATE OR REPLACE FUNCTION cmis.remove_post_from_queue(p_post_id UUID)
            RETURNS BOOLEAN
            LANGUAGE plpgsql
            SECURITY DEFINER
            AS $$
            BEGIN
                -- Update post status to draft and clear scheduled_for
                UPDATE cmis.social_posts
                SET
                    scheduled_for = NULL,
                    status = 'draft',
                    updated_at = NOW()
                WHERE post_id = p_post_id
                  AND status IN ('queued', 'scheduled');

                -- Check if update was successful
                IF FOUND THEN
                    RETURN true;
                ELSE
                    RETURN false;
                END IF;
            END;
            $$;
        ");

        // Grant execute permissions
        DB::unprepared("
            GRANT EXECUTE ON FUNCTION cmis.get_publishing_queue(UUID) TO authenticated;
            GRANT EXECUTE ON FUNCTION cmis.upsert_publishing_queue(UUID, UUID, VARCHAR, JSONB, VARCHAR) TO authenticated;
            GRANT EXECUTE ON FUNCTION cmis.get_next_available_slot(UUID, TIMESTAMP) TO authenticated;
            GRANT EXECUTE ON FUNCTION cmis.get_queued_posts(UUID) TO authenticated;
            GRANT EXECUTE ON FUNCTION cmis.schedule_post_to_queue(UUID, UUID, TIMESTAMP) TO authenticated;
            GRANT EXECUTE ON FUNCTION cmis.remove_post_from_queue(UUID) TO authenticated;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP FUNCTION IF EXISTS cmis.get_publishing_queue(UUID);");
        DB::unprepared("DROP FUNCTION IF EXISTS cmis.upsert_publishing_queue(UUID, UUID, VARCHAR, JSONB, VARCHAR);");
        DB::unprepared("DROP FUNCTION IF EXISTS cmis.get_next_available_slot(UUID, TIMESTAMP);");
        DB::unprepared("DROP FUNCTION IF EXISTS cmis.get_queued_posts(UUID);");
        DB::unprepared("DROP FUNCTION IF EXISTS cmis.schedule_post_to_queue(UUID, UUID, TIMESTAMP);");
        DB::unprepared("DROP FUNCTION IF EXISTS cmis.remove_post_from_queue(UUID);");
    }
};
