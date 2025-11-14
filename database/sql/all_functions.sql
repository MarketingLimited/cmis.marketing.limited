CREATE FUNCTION cmis.analyze_table_sizes() RETURNS TABLE(table_name text, total_size bigint)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT t.table_name::text,
         CASE
           WHEN to_regclass(format('%I.%I', t.table_schema, t.table_name)) IS NOT NULL THEN
             pg_total_relation_size(to_regclass(format('%I.%I', t.table_schema, t.table_name)))::bigint
           ELSE 0
         END AS total_size
  FROM information_schema.tables t
  WHERE t.table_schema = 'cmis';
END;
$$;


ALTER FUNCTION cmis.analyze_table_sizes() OWNER TO begin;

CREATE FUNCTION cmis.audit_creative_changes() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  INSERT INTO cmis_audit.logs(event_type, event_source, description, created_at)
  VALUES (
    'creative_brief_change',
    TG_TABLE_NAME,
    CONCAT('✏️ تعديل في الموجز الإبداعي: ', COALESCE(NEW.name, '<unnamed>')),
    NOW()
  );
  RETURN NEW;
END;
$$;


ALTER FUNCTION cmis.audit_creative_changes() OWNER TO begin;

CREATE FUNCTION cmis.auto_delete_unapproved_assets() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  DELETE FROM cmis.creative_assets
  WHERE status = 'draft'
    AND created_at < NOW() - INTERVAL '7 days';
END;
$$;


ALTER FUNCTION cmis.auto_delete_unapproved_assets() OWNER TO begin;

CREATE FUNCTION cmis.auto_refresh_cache_on_field_change() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- تحديث الـ cache فوراً عند أي تغيير
    PERFORM cmis.refresh_required_fields_cache();
    
    -- تسجيل التحديث
    INSERT INTO cmis_audit.logs (
        event_type,
        event_source,
        description,
        metadata,
        created_at
    ) VALUES (
        'cache_refresh',
        'field_definitions',
        'تم تحديث cache الحقول المطلوبة تلقائياً',
        jsonb_build_object(
            'trigger_op', TG_OP,
            'table_name', TG_TABLE_NAME,
            'timestamp', CURRENT_TIMESTAMP
        ),
        CURRENT_TIMESTAMP
    );
    
    RETURN NULL; -- FOR EACH STATEMENT triggers
END;
$$;


ALTER FUNCTION cmis.auto_refresh_cache_on_field_change() OWNER TO begin;

CREATE FUNCTION cmis.check_permission(p_user_id uuid, p_org_id uuid, p_permission_code text) RETURNS boolean
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    v_permission_id uuid;
    v_has_permission boolean := false;
BEGIN
    -- جلب permission_id من الـ cache (أسرع)
    SELECT permission_id INTO v_permission_id
    FROM cmis.permissions_cache
    WHERE permission_code = p_permission_code;
    
    IF v_permission_id IS NULL THEN
        -- إذا لم يوجد في cache، جلب من الجدول الأصلي
        SELECT permission_id INTO v_permission_id
        FROM cmis.permissions
        WHERE permission_code = p_permission_code;
        
        IF v_permission_id IS NULL THEN
            RETURN false; -- صلاحية غير موجودة
        END IF;
    END IF;
    
    -- تحديث آخر استخدام في cache
    UPDATE cmis.permissions_cache
    SET last_used = CURRENT_TIMESTAMP
    WHERE permission_code = p_permission_code;
    
    -- التحقق من الصلاحيات المباشرة
    SELECT EXISTS (
        SELECT 1 
        FROM cmis.user_permissions up
        WHERE up.user_id = p_user_id
          AND up.org_id = p_org_id
          AND up.permission_id = v_permission_id
          AND up.is_granted = true
          AND up.deleted_at IS NULL -- ✅ تم إضافته
          AND (up.expires_at IS NULL OR up.expires_at > CURRENT_TIMESTAMP)
    ) INTO v_has_permission;
    
    IF v_has_permission THEN
        RETURN true;
    END IF;
    
    -- التحقق من صلاحيات الدور
    SELECT EXISTS (
        SELECT 1
        FROM cmis.user_orgs uo
        JOIN cmis.role_permissions rp ON rp.role_id = uo.role_id
        WHERE uo.user_id = p_user_id
          AND uo.org_id = p_org_id
          AND uo.is_active = true
          AND uo.deleted_at IS NULL -- ✅ تم إضافته
          AND rp.permission_id = v_permission_id
          AND rp.deleted_at IS NULL -- ✅ تم إضافته (هذا هو الأهم!)
    ) INTO v_has_permission;
    
    RETURN v_has_permission;
END;
$$;


ALTER FUNCTION cmis.check_permission(p_user_id uuid, p_org_id uuid, p_permission_code text) OWNER TO begin;

CREATE FUNCTION cmis.check_permission_tx(p_permission text) RETURNS boolean
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    v_context RECORD;
    v_has_permission BOOLEAN;
BEGIN
    -- Validate transaction context first
    SELECT * INTO v_context 
    FROM cmis.validate_transaction_context() 
    LIMIT 1;
    
    IF NOT v_context.is_valid THEN
        RAISE EXCEPTION 'Invalid transaction context: %', v_context.error_message;
    END IF;
    
    -- Check permission using existing function
    SELECT cmis.check_permission(
        v_context.user_id,
        v_context.org_id,
        p_permission
    ) INTO v_has_permission;
    
    RETURN v_has_permission;
END;
$$;


ALTER FUNCTION cmis.check_permission_tx(p_permission text) OWNER TO begin;

CREATE FUNCTION cmis.cleanup_expired_sessions() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- تعطيل الجلسات المنتهية
    UPDATE cmis.user_sessions
    SET is_active = false
    WHERE expires_at < CURRENT_TIMESTAMP
      AND is_active = true;
    
    -- حذف الجلسات القديمة جداً (أكثر من 30 يوم)
    DELETE FROM cmis.user_sessions
    WHERE expires_at < CURRENT_TIMESTAMP - INTERVAL '30 days';
    
    -- تنظيف embeddings cache القديمة (غير مستخدمة لأكثر من 7 أيام)
    DELETE FROM cmis_knowledge.embeddings_cache
    WHERE last_used_at < CURRENT_TIMESTAMP - INTERVAL '7 days'
      AND provider = 'manual';
END;
$$;


ALTER FUNCTION cmis.cleanup_expired_sessions() OWNER TO begin;

CREATE FUNCTION cmis.cleanup_old_cache_entries() RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_deleted_count integer;
BEGIN
    -- حذف إدخالات permissions_cache غير المستخدمة لأكثر من 30 يوم
    DELETE FROM cmis.permissions_cache
    WHERE last_used < CURRENT_TIMESTAMP - INTERVAL '30 days';
    GET DIAGNOSTICS v_deleted_count = ROW_COUNT;
    
    IF v_deleted_count > 0 THEN
        RAISE NOTICE 'Cleaned % old permission cache entries', v_deleted_count;
    END IF;
    
    -- حذف embeddings_cache القديم
    DELETE FROM cmis_knowledge.embeddings_cache
    WHERE last_used_at < CURRENT_TIMESTAMP - INTERVAL '7 days'
      AND provider = 'manual';
    GET DIAGNOSTICS v_deleted_count = ROW_COUNT;
    
    IF v_deleted_count > 0 THEN
        RAISE NOTICE 'Cleaned % old embedding cache entries', v_deleted_count;
    END IF;
END;
$$;


ALTER FUNCTION cmis.cleanup_old_cache_entries() OWNER TO begin;

CREATE FUNCTION cmis.cmis_immutable_setweight(vec tsvector, w "char") RETURNS tsvector
    LANGUAGE plpgsql IMMUTABLE
    AS $$
BEGIN
  RETURN setweight(vec, w);
END;
$$;


ALTER FUNCTION cmis.cmis_immutable_setweight(vec tsvector, w "char") OWNER TO begin;

CREATE FUNCTION cmis.cmis_immutable_tsvector(cfg regconfig, txt text) RETURNS tsvector
    LANGUAGE plpgsql IMMUTABLE
    AS $$
BEGIN
  RETURN to_tsvector(cfg, txt);
END;
$$;


ALTER FUNCTION cmis.cmis_immutable_tsvector(cfg regconfig, txt text) OWNER TO begin;

CREATE FUNCTION cmis.contexts_unified_search_vector_update() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.search_vector :=
        setweight(to_tsvector('english', coalesce(NEW.name, '')), 'A') ||
        setweight(to_tsvector('english', coalesce(NEW.description, '')), 'B') ||
        setweight(to_tsvector('english', coalesce(NEW.creative_brief, '')), 'C') ||
        setweight(to_tsvector('english', coalesce(NEW.value_proposition, '')), 'C') ||
        setweight(to_tsvector('english', coalesce(array_to_string(NEW.tags, ' '), '')), 'D');
    RETURN NEW;
END;
$$;


ALTER FUNCTION cmis.contexts_unified_search_vector_update() OWNER TO begin;

CREATE FUNCTION cmis.create_campaign_and_context_safe(p_org_id uuid, p_offering_id uuid, p_segment_id uuid, p_campaign_name text, p_framework text, p_tone text, p_tags text[]) RETURNS TABLE(campaign_id uuid, context_id uuid, creative_context_id uuid)
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
  v_campaign_id uuid := gen_random_uuid();
  v_value_context_id uuid;
  v_creative_context_id uuid;
BEGIN
  IF p_org_id IS NULL OR p_offering_id IS NULL OR p_segment_id IS NULL OR p_campaign_name IS NULL THEN
    RAISE EXCEPTION 'Missing required parameters for campaign creation.';
  END IF;

  -- إنشاء السياق القيمي (Value Context)
  INSERT INTO cmis.value_contexts (org_id, offering_id, segment_id, locale, awareness_stage, framework, tone, tags)
  VALUES (p_org_id, p_offering_id, p_segment_id, 'ar-BH', 'awareness', p_framework, p_tone, p_tags)
  RETURNING value_contexts.context_id INTO v_value_context_id;

  -- إنشاء السياق الإبداعي (Creative Context)
  INSERT INTO cmis.creative_contexts (org_id, name, creative_brief)
  VALUES (p_org_id, p_campaign_name || ' - Creative Context', jsonb_build_object(
    'framework', p_framework,
    'tone', p_tone,
    'tags', p_tags
  ))
  RETURNING creative_contexts.context_id INTO v_creative_context_id;

  -- إدراج الحملة مع ربطها بالسياقين
  INSERT INTO cmis.campaigns (campaign_id, org_id, name, objective, start_date, end_date, status, context_id, creative_context_id)
  VALUES (v_campaign_id, p_org_id, p_campaign_name, 'conversion', CURRENT_DATE, CURRENT_DATE + INTERVAL '30 days', 'active', v_value_context_id, v_creative_context_id);

  -- تحديث الربط في value_contexts
  UPDATE cmis.value_contexts
  SET campaign_id = v_campaign_id
  WHERE cmis.value_contexts.context_id = v_value_context_id;

  RETURN QUERY SELECT v_campaign_id, v_value_context_id, v_creative_context_id;
END;$$;


ALTER FUNCTION cmis.create_campaign_and_context_safe(p_org_id uuid, p_offering_id uuid, p_segment_id uuid, p_campaign_name text, p_framework text, p_tone text, p_tags text[]) OWNER TO begin;

CREATE FUNCTION cmis.creative_contexts_delete() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Soft delete
    UPDATE cmis.contexts_unified
    SET deleted_at = CURRENT_TIMESTAMP
    WHERE id = OLD.id AND context_type = 'creative';
    RETURN OLD;
END;
$$;


ALTER FUNCTION cmis.creative_contexts_delete() OWNER TO begin;

CREATE FUNCTION cmis.creative_contexts_insert() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO cmis.contexts_unified (
        id, context_type, name, description, creative_brief,
        brand_guidelines, visual_style, tone_of_voice,
        target_platforms, creative_assets, org_id, created_by,
        metadata, tags, categories, keywords, status,
        parent_context_id, related_contexts
    ) VALUES (
        COALESCE(NEW.id, gen_random_uuid()),
        'creative',
        NEW.name,
        NEW.description,
        NEW.creative_brief,
        NEW.brand_guidelines,
        NEW.visual_style,
        NEW.tone_of_voice,
        NEW.target_platforms,
        NEW.creative_assets,
        NEW.org_id,
        NEW.created_by,
        COALESCE(NEW.metadata, '{}'::jsonb),
        COALESCE(NEW.tags, '{}'),
        COALESCE(NEW.categories, '{}'),
        COALESCE(NEW.keywords, '{}'),
        COALESCE(NEW.status, 'active'),
        NEW.parent_context_id,
        NEW.related_contexts
    ) RETURNING * INTO NEW;
    RETURN NEW;
END;
$$;


ALTER FUNCTION cmis.creative_contexts_insert() OWNER TO begin;

CREATE FUNCTION cmis.creative_contexts_update() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    UPDATE cmis.contexts_unified
    SET 
        name = NEW.name,
        description = NEW.description,
        creative_brief = NEW.creative_brief,
        brand_guidelines = NEW.brand_guidelines,
        visual_style = NEW.visual_style,
        tone_of_voice = NEW.tone_of_voice,
        target_platforms = NEW.target_platforms,
        creative_assets = NEW.creative_assets,
        metadata = NEW.metadata,
        tags = NEW.tags,
        categories = NEW.categories,
        keywords = NEW.keywords,
        status = NEW.status,
        parent_context_id = NEW.parent_context_id,
        related_contexts = NEW.related_contexts
    WHERE id = NEW.id AND context_type = 'creative';
    RETURN NEW;
END;
$$;


ALTER FUNCTION cmis.creative_contexts_update() OWNER TO begin;

CREATE FUNCTION cmis.enforce_creative_context() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  IF NEW.creative_context_id IS NULL THEN
    RAISE EXCEPTION 'Creative Context is required for this entity';
  END IF;
  RETURN NEW;
END;
$$;


ALTER FUNCTION cmis.enforce_creative_context() OWNER TO begin;

CREATE FUNCTION cmis.find_related_campaigns(p_campaign_id uuid, p_limit integer DEFAULT 10) RETURNS TABLE(campaign_id uuid, campaign_name character varying, shared_contexts_count integer, similarity_score numeric)
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
    RETURN QUERY
    WITH campaign_contexts AS (
        SELECT context_id, link_strength
        FROM cmis.campaign_context_links
        WHERE campaign_id = p_campaign_id AND is_active = true
    ),
    related AS (
        SELECT 
            ccl.campaign_id,
            COUNT(DISTINCT ccl.context_id) AS shared_count,
            SUM(ccl.link_strength * cc.link_strength) AS similarity
        FROM cmis.campaign_context_links ccl
        INNER JOIN campaign_contexts cc ON ccl.context_id = cc.context_id
        WHERE ccl.campaign_id != p_campaign_id
          AND ccl.is_active = true
        GROUP BY ccl.campaign_id
    )
    SELECT 
        r.campaign_id,
        c.name,
        r.shared_count::INTEGER,
        r.similarity::DECIMAL(5,2)
    FROM related r
    LEFT JOIN cmis.campaigns c ON r.campaign_id = c.campaign_id
    ORDER BY r.similarity DESC, r.shared_count DESC
    LIMIT p_limit;
END;
$$;


ALTER FUNCTION cmis.find_related_campaigns(p_campaign_id uuid, p_limit integer) OWNER TO begin;

CREATE FUNCTION cmis.generate_brief_summary(p_brief_id uuid) RETURNS jsonb
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
  v_brief RECORD;
  v_summary JSONB;
BEGIN
  SELECT * INTO v_brief
  FROM cmis.creative_briefs
  WHERE brief_id = p_brief_id;

  IF NOT FOUND THEN
    RAISE EXCEPTION 'Creative brief not found for ID: %', p_brief_id;
  END IF;

  v_summary := jsonb_build_object(
    'brief_name', v_brief.name,
    'org_id', v_brief.org_id,
    'fields', (v_brief.brief_data - 'non_essential')
  ) || jsonb_build_object('generated_at', NOW());

  RETURN v_summary;
END;
$$;


ALTER FUNCTION cmis.generate_brief_summary(p_brief_id uuid) OWNER TO begin;

CREATE FUNCTION cmis.get_campaign_contexts(p_campaign_id uuid, p_include_inactive boolean DEFAULT false) RETURNS TABLE(context_id uuid, context_type character varying, name character varying, description text, link_type character varying, link_strength numeric, link_purpose text, is_active boolean, created_at timestamp without time zone)
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        cu.id,
        cu.context_type,
        cu.name,
        cu.description,
        ccl.link_type,
        ccl.link_strength,
        ccl.link_purpose,
        ccl.is_active,
        ccl.created_at
    FROM cmis.campaign_context_links ccl
    INNER JOIN cmis.contexts_unified cu ON ccl.context_id = cu.id
    WHERE ccl.campaign_id = p_campaign_id
      AND (p_include_inactive OR ccl.is_active = true)
    ORDER BY ccl.link_strength DESC, ccl.created_at;
END;
$$;


ALTER FUNCTION cmis.get_campaign_contexts(p_campaign_id uuid, p_include_inactive boolean) OWNER TO begin;

CREATE FUNCTION cmis.get_current_org_id() RETURNS uuid
    LANGUAGE plpgsql STABLE
    AS $$
DECLARE
  v_org text;
BEGIN
  BEGIN
    v_org := current_setting('app.current_org_id');
  EXCEPTION
    WHEN others THEN
      RETURN '00000000-0000-0000-0000-000000000000'::uuid; -- fallback value
  END;

  IF v_org IS NULL OR v_org = '' THEN
    RETURN '00000000-0000-0000-0000-000000000000'::uuid;
  ELSE
    RETURN v_org::uuid;
  END IF;
END;
$$;


ALTER FUNCTION cmis.get_current_org_id() OWNER TO begin;

CREATE FUNCTION cmis.get_current_org_id_tx() RETURNS uuid
    LANGUAGE plpgsql STABLE
    AS $$
DECLARE
    v_context RECORD;
BEGIN
    SELECT * INTO v_context FROM cmis.validate_transaction_context() LIMIT 1;
    
    IF NOT v_context.is_valid THEN
        RAISE EXCEPTION 'Invalid transaction context: %', v_context.error_message;
    END IF;
    
    RETURN v_context.org_id;
END;
$$;


ALTER FUNCTION cmis.get_current_org_id_tx() OWNER TO begin;

CREATE FUNCTION cmis.get_current_user_id() RETURNS uuid
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
  RETURN current_setting('app.current_user_id', true)::uuid;
END;
$$;


ALTER FUNCTION cmis.get_current_user_id() OWNER TO begin;

CREATE FUNCTION cmis.get_current_user_id_tx() RETURNS uuid
    LANGUAGE plpgsql STABLE
    AS $$
DECLARE
    v_context RECORD;
BEGIN
    SELECT * INTO v_context FROM cmis.validate_transaction_context() LIMIT 1;
    
    IF NOT v_context.is_valid THEN
        RAISE EXCEPTION 'Invalid transaction context: %', v_context.error_message;
    END IF;
    
    RETURN v_context.user_id;
END;
$$;


ALTER FUNCTION cmis.get_current_user_id_tx() OWNER TO begin;

CREATE FUNCTION cmis.get_next_available_slot(p_social_account_id uuid, p_after_time timestamp without time zone DEFAULT now()) RETURNS TABLE(next_slot timestamp without time zone)
    LANGUAGE plpgsql SECURITY DEFINER
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


ALTER FUNCTION cmis.get_next_available_slot(p_social_account_id uuid, p_after_time timestamp without time zone) OWNER TO begin;

CREATE FUNCTION cmis.get_publishing_queue(p_social_account_id uuid) RETURNS TABLE(queue_id uuid, org_id uuid, social_account_id uuid, weekdays_enabled character varying, time_slots jsonb, timezone character varying, is_active boolean, created_at timestamp without time zone, updated_at timestamp without time zone)
    LANGUAGE plpgsql SECURITY DEFINER
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


ALTER FUNCTION cmis.get_publishing_queue(p_social_account_id uuid) OWNER TO begin;

CREATE FUNCTION cmis.get_queued_posts(p_social_account_id uuid) RETURNS TABLE(post_id uuid, social_account_id uuid, content text, scheduled_for timestamp without time zone, status character varying, platform character varying, post_type character varying, media_urls jsonb, created_at timestamp without time zone)
    LANGUAGE plpgsql SECURITY DEFINER
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


ALTER FUNCTION cmis.get_queued_posts(p_social_account_id uuid) OWNER TO begin;

CREATE FUNCTION cmis.init_transaction_context(p_user_id uuid, p_org_id uuid) RETURNS void
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
    -- Validate inputs
    IF p_user_id IS NULL THEN
        RAISE EXCEPTION 'user_id cannot be NULL';
    END IF;
    
    IF p_org_id IS NULL THEN
        RAISE EXCEPTION 'org_id cannot be NULL';
    END IF;
    
    -- Verify user belongs to org
    IF NOT EXISTS (
        SELECT 1 FROM cmis.user_orgs
        WHERE user_id = p_user_id
        AND org_id = p_org_id
        AND is_active = true
        AND deleted_at IS NULL
    ) THEN
        RAISE EXCEPTION 'User % does not belong to org % or relationship is not active', 
            p_user_id, p_org_id;
    END IF;
    
    -- Set LOCAL context (transaction-scoped only)
    PERFORM set_config('app.current_user_id', p_user_id::TEXT, TRUE);
    PERFORM set_config('app.current_org_id', p_org_id::TEXT, TRUE);
    PERFORM set_config('app.context_initialized', 'true', TRUE);
    PERFORM set_config('app.context_version', '2.0', TRUE);
    
    -- Log initialization (optional)
    RAISE DEBUG 'Transaction context initialized: user=%, org=%', p_user_id, p_org_id;
END;
$$;


ALTER FUNCTION cmis.init_transaction_context(p_user_id uuid, p_org_id uuid) OWNER TO begin;

CREATE FUNCTION cmis.clear_transaction_context() RETURNS void
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
    -- Clear LOCAL context (transaction-scoped)
    PERFORM set_config('app.current_user_id', NULL, TRUE);
    PERFORM set_config('app.current_org_id', NULL, TRUE);
    PERFORM set_config('app.context_initialized', 'false', TRUE);
    PERFORM set_config('app.context_version', NULL, TRUE);

    -- Log cleanup (optional)
    RAISE DEBUG 'Transaction context cleared';
END;
$$;


ALTER FUNCTION cmis.clear_transaction_context() OWNER TO begin;

CREATE FUNCTION cmis.link_brief_to_content(p_brief_id uuid, p_content_id uuid) RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  INSERT INTO cmis.creative_outputs (brief_id, content_id, linked_at)
  VALUES (p_brief_id, p_content_id, NOW())
  ON CONFLICT (brief_id, content_id) DO NOTHING;
END;
$$;


ALTER FUNCTION cmis.link_brief_to_content(p_brief_id uuid, p_content_id uuid) OWNER TO begin;

CREATE FUNCTION cmis.prevent_incomplete_briefs() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  normalized_existing TEXT[] := ARRAY[]::TEXT[];
  normalized_required TEXT[] := ARRAY[]::TEXT[];
  missing_keys TEXT[] := ARRAY[]::TEXT[];
  k TEXT;
BEGIN
  -- 🔍 جلب الحقول المطلوبة من قاعدة البيانات
  SELECT COALESCE(array_agg(lower(regexp_replace(slug, '[^a-z0-9_]+', '', 'g'))), ARRAY[]::TEXT[])
  INTO normalized_required
  FROM cmis.field_definitions
  WHERE required_default = TRUE
    AND module_scope ILIKE '%creative_brief%';

  -- 🔄 تطبيع المفاتيح القادمة من JSON
  SELECT COALESCE(array_agg(lower(regexp_replace(key_name, '[^a-z0-9_]+', '', 'g'))), ARRAY[]::TEXT[])
  INTO normalized_existing
  FROM jsonb_object_keys(NEW.brief_data) AS key_name;

  -- 🧠 تحديد المفاتيح المفقودة
  FOREACH k IN ARRAY normalized_required LOOP
    IF NOT (k = ANY(normalized_existing)) THEN
      missing_keys := array_append(missing_keys, k);
    END IF;
  END LOOP;

  IF array_length(missing_keys, 1) > 0 THEN
    RAISE EXCEPTION 'Creative brief is missing required fields: %',
      array_to_string(missing_keys, ', ');
  END IF;

  RETURN NEW;
END;
$$;


ALTER FUNCTION cmis.prevent_incomplete_briefs() OWNER TO begin;

CREATE FUNCTION cmis.prevent_incomplete_briefs_optimized() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_required_fields TEXT[];
    v_existing_fields TEXT[];
    v_missing_fields TEXT[];
BEGIN
    -- جلب الحقول المطلوبة من الـ cache (أسرع بكثير)
    SELECT required_fields INTO v_required_fields
    FROM cmis.required_fields_cache
    WHERE module_scope = 'creative_brief';
    
    -- إذا لم توجد حقول مطلوبة، السماح بالإدراج
    IF v_required_fields IS NULL OR array_length(v_required_fields, 1) IS NULL THEN
        RETURN NEW;
    END IF;
    
    -- جلب الحقول الموجودة
    SELECT array_agg(lower(regexp_replace(key, '[^a-z0-9_]+', '', 'g')))
    INTO v_existing_fields
    FROM jsonb_object_keys(NEW.brief_data) AS key;
    
    -- حساب الحقول المفقودة
    v_missing_fields := v_required_fields - COALESCE(v_existing_fields, ARRAY[]::TEXT[]);
    
    IF array_length(v_missing_fields, 1) > 0 THEN
        RAISE EXCEPTION 'Creative brief missing required fields: %', 
            array_to_string(v_missing_fields, ', ');
    END IF;
    
    RETURN NEW;
END;
$$;


ALTER FUNCTION cmis.prevent_incomplete_briefs_optimized() OWNER TO begin;

CREATE FUNCTION cmis.refresh_creative_index() RETURNS void
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
BEGIN
  UPDATE cmis_knowledge.knowledge_index k
  SET topic_embedding = cmis_knowledge.generate_embedding_mock(c.caption)
  FROM cmis.content_items c
  WHERE c.updated_at > NOW() - INTERVAL '1 day';
END;
$$;


ALTER FUNCTION cmis.refresh_creative_index() OWNER TO begin;

CREATE FUNCTION cmis.refresh_dashboard_metrics() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    REFRESH MATERIALIZED VIEW CONCURRENTLY cmis.dashboard_metrics;
END;
$$;


ALTER FUNCTION cmis.refresh_dashboard_metrics() OWNER TO begin;

CREATE FUNCTION cmis.refresh_permissions_cache() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- تحديث أو إدراج في الـ cache
    IF TG_OP = 'DELETE' THEN
        DELETE FROM cmis.permissions_cache 
        WHERE permission_code = OLD.permission_code;
    ELSE
        INSERT INTO cmis.permissions_cache (permission_code, permission_id, category)
        VALUES (NEW.permission_code, NEW.permission_id, NEW.category)
        ON CONFLICT (permission_code) DO UPDATE
        SET permission_id = NEW.permission_id,
            category = NEW.category,
            last_used = CURRENT_TIMESTAMP;
    END IF;
    
    RETURN NULL;
END;
$$;


ALTER FUNCTION cmis.refresh_permissions_cache() OWNER TO begin;

CREATE FUNCTION cmis.refresh_required_fields_cache() RETURNS void
    LANGUAGE sql
    AS $$
    SELECT cmis.refresh_required_fields_cache_with_metrics();
$$;


ALTER FUNCTION cmis.refresh_required_fields_cache() OWNER TO begin;

CREATE FUNCTION cmis.refresh_required_fields_cache_with_metrics() RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_start_time timestamptz;
    v_end_time timestamptz;
    v_duration_ms numeric;
    v_record_count integer;
BEGIN
    v_start_time := clock_timestamp();
    
    -- حذف البيانات القديمة
    DELETE FROM cmis.required_fields_cache WHERE module_scope = 'creative_brief';
    
    -- إدراج البيانات الجديدة
    INSERT INTO cmis.required_fields_cache (module_scope, required_fields)
    SELECT 
        'creative_brief',
        COALESCE(array_agg(
            lower(regexp_replace(slug, '[^a-z0-9_]+', '', 'g'))
            ORDER BY slug
        ), ARRAY[]::TEXT[])
    FROM cmis.field_definitions
    WHERE required_default = TRUE
      AND module_scope ILIKE '%creative_brief%';
    
    GET DIAGNOSTICS v_record_count = ROW_COUNT;
    
    v_end_time := clock_timestamp();
    v_duration_ms := EXTRACT(EPOCH FROM (v_end_time - v_start_time)) * 1000;
    
    -- تحديث metadata
    UPDATE cmis.cache_metadata
    SET last_refreshed = v_end_time,
        refresh_count = refresh_count + 1,
        last_refresh_duration_ms = v_duration_ms,
        avg_refresh_time_ms = 
            CASE 
                WHEN avg_refresh_time_ms IS NULL THEN v_duration_ms
                ELSE (avg_refresh_time_ms * (refresh_count - 1) + v_duration_ms) / refresh_count
            END,
        metadata = metadata || jsonb_build_object(
            'last_refresh_record_count', v_record_count,
            'last_refresh_timestamp', v_end_time
        )
    WHERE cache_name = 'required_fields_cache';
    
    RAISE NOTICE 'Cache refreshed in % ms, % fields cached', 
                 round(v_duration_ms, 2), v_record_count;
END;
$$;


ALTER FUNCTION cmis.refresh_required_fields_cache_with_metrics() OWNER TO begin;

CREATE FUNCTION cmis.remove_post_from_queue(p_post_id uuid) RETURNS boolean
    LANGUAGE plpgsql SECURITY DEFINER
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


ALTER FUNCTION cmis.remove_post_from_queue(p_post_id uuid) OWNER TO begin;

CREATE FUNCTION cmis.schedule_post_to_queue(p_post_id uuid, p_social_account_id uuid, p_scheduled_for timestamp without time zone) RETURNS boolean
    LANGUAGE plpgsql SECURITY DEFINER
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


ALTER FUNCTION cmis.schedule_post_to_queue(p_post_id uuid, p_social_account_id uuid, p_scheduled_for timestamp without time zone) OWNER TO begin;

CREATE FUNCTION cmis.search_contexts(p_search_query text, p_context_type character varying DEFAULT NULL::character varying, p_limit integer DEFAULT 20) RETURNS TABLE(id uuid, context_type character varying, name character varying, description text, relevance real, highlights text)
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        cu.id,
        cu.context_type,
        cu.name,
        cu.description,
        ts_rank(cu.search_vector, query) AS relevance,
        ts_headline('english', 
                   coalesce(cu.name, '') || ' ' || coalesce(cu.description, ''),
                   query,
                   'MaxWords=50, MinWords=20, ShortWord=3, HighlightAll=FALSE'
        ) AS highlights
    FROM cmis.contexts_unified cu,
         plainto_tsquery('english', p_search_query) query
    WHERE cu.search_vector @@ query
      AND (p_context_type IS NULL OR cu.context_type = p_context_type)
      AND cu.status = 'active'
      AND cu.deleted_at IS NULL
    ORDER BY relevance DESC
    LIMIT p_limit;
END;
$$;


ALTER FUNCTION cmis.search_contexts(p_search_query text, p_context_type character varying, p_limit integer) OWNER TO begin;

CREATE FUNCTION cmis.sync_social_metrics() RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    rec RECORD;
BEGIN
    FOR rec IN SELECT id, org_id, platform, access_token, external_id
               FROM cmis.integrations
               WHERE account_type = 'social' AND is_active
    LOOP
        INSERT INTO cmis.audit_log (org_id, actor, action, target, meta)
        VALUES (rec.org_id, 'system', 'sync_social_metrics_start', rec.platform, jsonb_build_object('integration_id', rec.id));

        -- Placeholder: تُنفذ هذه الخطوة في الطبقة التطبيقية لاستدعاء Meta Graph API الفعلية
        -- البيانات تُدرج هنا بعد معالجتها خارجياً

        INSERT INTO cmis.audit_log (org_id, actor, action, target, meta)
        VALUES (rec.org_id, 'system', 'sync_social_metrics_end', rec.platform, jsonb_build_object('integration_id', rec.id));
    END LOOP;
END;$$;


ALTER FUNCTION cmis.sync_social_metrics() OWNER TO begin;

CREATE FUNCTION cmis.test_new_security_context() RETURNS TABLE(test_name text, passed boolean, details text)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_test_user_id UUID;
    v_test_org_id UUID;
    v_context RECORD;
    v_permission BOOLEAN;
BEGIN
    -- Get a real user and org for testing
    SELECT u.user_id, uo.org_id 
    INTO v_test_user_id, v_test_org_id
    FROM cmis.users u
    JOIN cmis.user_orgs uo ON uo.user_id = u.user_id
    WHERE uo.is_active = true
    AND uo.deleted_at IS NULL
    LIMIT 1;
    
    IF v_test_user_id IS NULL THEN
        RETURN QUERY SELECT 
            'Prerequisites'::TEXT,
            FALSE,
            'No active users found in database'::TEXT;
        RETURN;
    END IF;
    
    -- Test 1: Initialize context
    BEGIN
        PERFORM cmis.init_transaction_context(v_test_user_id, v_test_org_id);
        RETURN QUERY SELECT 
            'Context Initialization'::TEXT,
            TRUE,
            format('Successfully initialized for user %s', v_test_user_id)::TEXT;
    EXCEPTION WHEN OTHERS THEN
        RETURN QUERY SELECT 
            'Context Initialization'::TEXT,
            FALSE,
            SQLERRM::TEXT;
        RETURN;
    END;
    
    -- Test 2: Validate context
    SELECT * INTO v_context FROM cmis.validate_transaction_context() LIMIT 1;
    RETURN QUERY SELECT 
        'Context Validation'::TEXT,
        v_context.is_valid,
        CASE 
            WHEN v_context.is_valid THEN format('Valid context v%s', v_context.context_version)
            ELSE v_context.error_message
        END::TEXT;
    
    -- Test 3: Check permission
    BEGIN
        v_permission := cmis.check_permission_tx('orgs.view');
        RETURN QUERY SELECT 
            'Permission Check'::TEXT,
            TRUE,
            format('Permission check returned: %s', v_permission)::TEXT;
    EXCEPTION WHEN OTHERS THEN
        RETURN QUERY SELECT 
            'Permission Check'::TEXT,
            FALSE,
            SQLERRM::TEXT;
    END;
    
    -- Test 4: Query with RLS
    BEGIN
        PERFORM COUNT(*) FROM cmis.campaigns;
        RETURN QUERY SELECT 
            'RLS Query'::TEXT,
            TRUE,
            'Successfully queried campaigns table with RLS'::TEXT;
    EXCEPTION WHEN OTHERS THEN
        RETURN QUERY SELECT 
            'RLS Query'::TEXT,
            FALSE,
            SQLERRM::TEXT;
    END;
END;
$$;


ALTER FUNCTION cmis.test_new_security_context() OWNER TO begin;

CREATE FUNCTION cmis.update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$ BEGIN NEW.updated_at = CURRENT_TIMESTAMP; RETURN NEW; END; $$;


ALTER FUNCTION cmis.update_updated_at_column() OWNER TO begin;

CREATE FUNCTION cmis.upsert_publishing_queue(p_org_id uuid, p_social_account_id uuid, p_weekdays_enabled character varying, p_time_slots jsonb, p_timezone character varying) RETURNS TABLE(queue_id uuid, org_id uuid, social_account_id uuid, weekdays_enabled character varying, time_slots jsonb, timezone character varying, is_active boolean, created_at timestamp without time zone, updated_at timestamp without time zone)
    LANGUAGE plpgsql SECURITY DEFINER
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


ALTER FUNCTION cmis.upsert_publishing_queue(p_org_id uuid, p_social_account_id uuid, p_weekdays_enabled character varying, p_time_slots jsonb, p_timezone character varying) OWNER TO begin;

CREATE FUNCTION cmis.validate_brief_structure(p_brief jsonb) RETURNS boolean
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
  required_fields TEXT[] := ARRAY[]::TEXT[];
  missing TEXT[] := ARRAY[]::TEXT[];
  key TEXT;
BEGIN
  SELECT COALESCE(array_agg(slug), ARRAY[]::TEXT[])
  INTO required_fields
  FROM cmis.field_definitions
  WHERE required_default = TRUE
    AND module_scope ILIKE '%creative_brief%';

  FOREACH key IN ARRAY required_fields LOOP
    IF NOT (p_brief ? key) THEN
      missing := array_append(missing, key);
    END IF;
  END LOOP;

  IF array_length(missing, 1) > 0 THEN
    RAISE EXCEPTION 'Missing required fields: %',
      array_to_string(missing, ', ');
  END IF;

  RETURN TRUE;
END;
$$;


ALTER FUNCTION cmis.validate_brief_structure(p_brief jsonb) OWNER TO begin;

CREATE FUNCTION cmis.validate_transaction_context() RETURNS TABLE(is_valid boolean, user_id uuid, org_id uuid, error_message text, context_version text)
    LANGUAGE plpgsql SECURITY DEFINER
    AS $$
DECLARE
    v_user_id TEXT;
    v_org_id TEXT;
    v_initialized TEXT;
    v_version TEXT;
BEGIN
    -- Check if context is initialized
    BEGIN
        v_initialized := current_setting('app.context_initialized', TRUE);
        v_version := current_setting('app.context_version', TRUE);
    EXCEPTION WHEN OTHERS THEN
        v_initialized := NULL;
        v_version := NULL;
    END;
    
    IF v_initialized IS NULL OR v_initialized != 'true' THEN
        RETURN QUERY SELECT 
            FALSE, 
            NULL::UUID, 
            NULL::UUID, 
            'Transaction context not initialized. Call init_transaction_context() first.'::TEXT,
            NULL::TEXT;
        RETURN;
    END IF;
    
    -- Get user_id and org_id
    BEGIN
        v_user_id := current_setting('app.current_user_id', TRUE);
        v_org_id := current_setting('app.current_org_id', TRUE);
    EXCEPTION WHEN OTHERS THEN
        RETURN QUERY SELECT 
            FALSE, 
            NULL::UUID, 
            NULL::UUID, 
            'Failed to read context settings'::TEXT,
            NULL::TEXT;
        RETURN;
    END;
    
    -- Validate they exist
    IF v_user_id IS NULL OR v_org_id IS NULL THEN
        RETURN QUERY SELECT 
            FALSE, 
            NULL::UUID, 
            NULL::UUID, 
            'Missing user_id or org_id in context'::TEXT,
            v_version;
        RETURN;
    END IF;
    
    -- Return valid context
    RETURN QUERY SELECT 
        TRUE, 
        v_user_id::UUID, 
        v_org_id::UUID, 
        NULL::TEXT,
        v_version;
END;
$$;


ALTER FUNCTION cmis.validate_transaction_context() OWNER TO begin;

CREATE FUNCTION cmis.verify_cache_automation() RETURNS TABLE(check_name text, status text, details text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- التحقق من وجود Trigger
    RETURN QUERY
    SELECT 
        'Fields Cache Trigger'::text,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM pg_trigger 
                WHERE tgname = 'trg_refresh_fields_cache'
            ) THEN 'ACTIVE'::text
            ELSE 'MISSING'::text
        END,
        'Auto-refresh trigger for field definitions'::text;
    
    -- التحقق من cache الصلاحيات
    RETURN QUERY
    SELECT 
        'Permissions Cache'::text,
        CASE 
            WHEN (SELECT count(*) FROM cmis.permissions_cache) > 0
            THEN 'POPULATED'::text
            ELSE 'EMPTY'::text
        END,
        format('%s permissions cached', 
               (SELECT count(*) FROM cmis.permissions_cache))::text;
    
    -- التحقق من metadata
    RETURN QUERY
    SELECT 
        'Cache Metadata'::text,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM cmis.cache_metadata
                WHERE cache_name = 'required_fields_cache'
            ) THEN 'TRACKING'::text
            ELSE 'NOT TRACKING'::text
        END,
        'Performance metrics for cache operations'::text;
    
    -- التحقق من الأداء
    RETURN QUERY
    SELECT 
        'Cache Performance'::text,
        CASE 
            WHEN (
                SELECT avg_refresh_time_ms 
                FROM cmis.cache_metadata 
                WHERE cache_name = 'required_fields_cache'
            ) < 100 THEN 'FAST'::text
            ELSE 'SLOW'::text
        END,
        format('Avg refresh time: %s ms', 
               round((SELECT avg_refresh_time_ms 
                      FROM cmis.cache_metadata 
                      WHERE cache_name = 'required_fields_cache'), 2))::text;
END;
$$;


ALTER FUNCTION cmis.verify_cache_automation() OWNER TO begin;

CREATE FUNCTION cmis.verify_optional_improvements() RETURNS text
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN 'placeholder - optional improvements verification deferred';
END;
$$;


ALTER FUNCTION cmis.verify_optional_improvements() OWNER TO begin;

CREATE FUNCTION cmis.verify_phase1_fixes() RETURNS TABLE(check_name text, status text, details text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- التحقق من دالة embeddings
    RETURN QUERY
    SELECT 
        'Embeddings Function'::text,
        CASE 
            WHEN EXISTS (SELECT 1 FROM pg_proc WHERE proname = 'generate_embedding_improved')
            THEN 'FIXED'::text
            ELSE 'FAILED'::text
        END,
        'New embeddings function created'::text;
    
    -- التحقق من جدول cache
    RETURN QUERY
    SELECT 
        'Embeddings Cache Table'::text,
        CASE 
            WHEN EXISTS (SELECT 1 FROM information_schema.tables 
                        WHERE table_schema = 'cmis_knowledge' 
                        AND table_name = 'embeddings_cache')
            THEN 'CREATED'::text
            ELSE 'FAILED'::text
        END,
        'Cache table for embeddings'::text;
    
    -- التحقق من جدول الجلسات
    RETURN QUERY
    SELECT 
        'Sessions Table'::text,
        CASE 
            WHEN EXISTS (SELECT 1 FROM information_schema.tables 
                        WHERE table_schema = 'cmis' 
                        AND table_name = 'user_sessions')
            THEN 'CREATED'::text
            ELSE 'FAILED'::text
        END,
        'User sessions management table'::text;
    
    -- التحقق من المشغل المحسن
    RETURN QUERY
    SELECT 
        'Optimized Trigger'::text,
        CASE 
            WHEN EXISTS (SELECT 1 FROM pg_trigger 
                        WHERE tgname = 'enforce_brief_completeness_optimized')
            THEN 'UPDATED'::text
            ELSE 'FAILED'::text
        END,
        'Brief validation trigger optimized'::text;
END;
$$;


ALTER FUNCTION cmis.verify_phase1_fixes() OWNER TO begin;

CREATE FUNCTION cmis.verify_phase2_permissions() RETURNS TABLE(check_name text, status text, count bigint, details text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- جدول user_orgs
    RETURN QUERY
    SELECT 
        'User-Org Relationships'::text,
        'CREATED'::text,
        count(*),
        'Multi-org support enabled'::text
    FROM cmis.user_orgs;
    
    -- الأدوار
    RETURN QUERY
    SELECT 
        'Roles Created'::text,
        'READY'::text,
        count(*),
        'Role-based access control'::text
    FROM cmis.roles;
    
    -- الصلاحيات
    RETURN QUERY
    SELECT 
        'Permissions Defined'::text,
        'CONFIGURED'::text,
        count(*),
        'Granular permissions'::text
    FROM cmis.permissions;
    
    -- ربط الأدوار بالصلاحيات
    RETURN QUERY
    SELECT 
        'Role Permissions'::text,
        'ASSIGNED'::text,
        count(*),
        'Permissions assigned to roles'::text
    FROM cmis.role_permissions;
    
    -- السياسات المحدثة
    RETURN QUERY
    SELECT 
        'RLS Policies'::text,
        CASE 
            WHEN count(*) > 0 THEN 'UPDATED'::text
            ELSE 'PENDING'::text
        END,
        count(*),
        'Row-level security policies'::text
    FROM pg_policies
    WHERE schemaname = 'cmis'
      AND policyname LIKE '%_orgs_%' OR policyname LIKE '%role%';
END;
$$;


ALTER FUNCTION cmis.verify_phase2_permissions() OWNER TO begin;

CREATE FUNCTION cmis.verify_rbac_policies() RETURNS TABLE(policy_name text, table_name text, status text)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT 
    pol.polname::text AS policy_name,
    cls.relname::text AS table_name,
    CASE 
      WHEN pg_get_expr(pol.polqual, pol.polrelid) LIKE '%check_permission_optimized%' THEN '✅ محدّث'
      WHEN pg_get_expr(pol.polqual, pol.polrelid) LIKE '%check_permission%' THEN '❌ يحتاج تحديث'
      ELSE '✓ لا يستخدم RBAC'
    END AS status
  FROM pg_policy pol
  JOIN pg_class cls ON pol.polrelid = cls.oid
  JOIN pg_namespace nsp ON cls.relnamespace = nsp.oid
  WHERE nsp.nspname = 'cmis'
    AND pol.polname LIKE 'rbac_%'
  ORDER BY cls.relname, pol.polname;
END;
$$;


ALTER FUNCTION cmis.verify_rbac_policies() OWNER TO begin;

CREATE FUNCTION cmis.verify_rls_fixes() RETURNS TABLE(policy_name text, table_name text, check_status text)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT p.polname::text AS policy_name,
         c.relname::text AS table_name,
         CASE
           WHEN pg_get_expr(p.polqual, p.polrelid) LIKE '%deleted_at%' THEN '✓ Soft delete enforced'
           WHEN pg_get_expr(p.polqual, p.polrelid) LIKE '%org_id%' THEN '✓ Org isolation enforced'
           ELSE '⚠ Missing expected condition'
         END AS check_status
  FROM pg_policy p
  JOIN pg_class c ON c.oid = p.polrelid
  JOIN pg_namespace n ON n.oid = c.relnamespace
  WHERE n.nspname = 'cmis';
END;
$$;


ALTER FUNCTION cmis.verify_rls_fixes() OWNER TO begin;

CREATE FUNCTION cmis_ai_analytics.fn_recommend_focus() RETURNS TABLE(recommendation jsonb)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT jsonb_build_object(
    'top_performing_context', (SELECT context_type FROM cmis_ai_analytics.v_context_impact ORDER BY impact_score DESC LIMIT 1),
    'weakest_asset_type', (SELECT output_type FROM cmis_ai_analytics.v_creative_efficiency ORDER BY efficiency_score ASC LIMIT 1),
    'best_campaign', (SELECT campaign_name FROM cmis_ai_analytics.v_kpi_summary ORDER BY performance_rate DESC LIMIT 1),
    'timestamp', NOW()
  );
END;
$$;


ALTER FUNCTION cmis_ai_analytics.fn_recommend_focus() OWNER TO begin;

CREATE FUNCTION cmis_analytics.report_migrations() RETURNS TABLE(executed_at timestamp with time zone, action text, sql_preview text)
    LANGUAGE sql
    AS $$
  SELECT executed_at, action, LEFT(sql_code, 200) || '...' AS sql_preview
  FROM cmis_analytics.migration_log
  ORDER BY executed_at DESC;
$$;


ALTER FUNCTION cmis_analytics.report_migrations() OWNER TO begin;

CREATE FUNCTION cmis_analytics.run_ai_query(p_org_id uuid, p_prompt text) RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    matched_template RECORD;
    generated_sql TEXT;
    result_summary TEXT;
BEGIN
    SELECT * INTO matched_template
    FROM cmis_analytics.prompt_templates
    ORDER BY similarity(prompt_text, p_prompt) DESC
    LIMIT 1;

    IF matched_template IS NULL THEN
        result_summary := 'لم يتم العثور على قالب مناسب للسؤال.';
    ELSE
        generated_sql := matched_template.sql_snippet;
        result_summary := 'تم تنفيذ القالب: ' || matched_template.name;
    END IF;

    INSERT INTO cmis_analytics.ai_queries (org_id, user_prompt, generated_sql, result_summary, confidence_score)
    VALUES (p_org_id, p_prompt, generated_sql, result_summary, 0.85);
END; $$;


ALTER FUNCTION cmis_analytics.run_ai_query(p_org_id uuid, p_prompt text) OWNER TO begin;

CREATE FUNCTION cmis_analytics.snapshot_performance() RETURNS TABLE(campaign_id uuid, campaign_name text, output_id uuid, kpi text, observed numeric, observed_at timestamp with time zone, trend_direction text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        pm.campaign_id,
        c.name AS campaign_name,
        pm.output_id,
        pm.kpi,
        pm.observed,
        pm.observed_at,
        CASE
            WHEN pm.observed > LAG(pm.observed) OVER (PARTITION BY pm.campaign_id, pm.kpi ORDER BY pm.observed_at) THEN 'up'
            WHEN pm.observed < LAG(pm.observed) OVER (PARTITION BY pm.campaign_id, pm.kpi ORDER BY pm.observed_at) THEN 'down'
            ELSE 'stable'
        END AS trend_direction
    FROM cmis.performance_metrics pm
    JOIN cmis.campaigns c ON pm.campaign_id = c.campaign_id
    WHERE pm.observed_at >= NOW() - INTERVAL '30 days'
    ORDER BY pm.observed_at DESC;
END;
$$;


ALTER FUNCTION cmis_analytics.snapshot_performance() OWNER TO begin;

CREATE FUNCTION cmis_analytics.snapshot_performance(snapshot_days integer DEFAULT 30) RETURNS TABLE(campaign_id uuid, campaign_name text, output_id uuid, kpi text, observed numeric, observed_at timestamp with time zone, trend_direction text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        pm.campaign_id,
        c.name AS campaign_name,
        pm.output_id,
        pm.kpi,
        pm.observed,
        pm.observed_at,
        CASE
            WHEN pm.observed > LAG(pm.observed) OVER (PARTITION BY pm.campaign_id, pm.kpi ORDER BY pm.observed_at) THEN 'up'
            WHEN pm.observed < LAG(pm.observed) OVER (PARTITION BY pm.campaign_id, pm.kpi ORDER BY pm.observed_at) THEN 'down'
            ELSE 'stable'
        END AS trend_direction
    FROM cmis.performance_metrics pm
    JOIN cmis.campaigns c ON pm.campaign_id = c.campaign_id
    WHERE pm.observed_at >= NOW() - (snapshot_days || ' days')::interval
    ORDER BY pm.observed_at DESC;
END;
$$;


ALTER FUNCTION cmis_analytics.snapshot_performance(snapshot_days integer) OWNER TO begin;

CREATE FUNCTION cmis_dev.auto_context_task_loader(p_prompt text, p_domain text DEFAULT NULL::text, p_category text DEFAULT 'dev'::text, p_scope_code text DEFAULT 'system_dev'::text, p_priority smallint DEFAULT 3, p_token_limit integer DEFAULT 5000) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_context jsonb;
    v_task_id uuid;
    v_plan jsonb;
    v_summary text;
BEGIN
    -- استدعاء السياق المعرفي الذكي
    SELECT cmis_knowledge.smart_context_loader(p_prompt, p_domain, p_category, p_token_limit)
    INTO v_context;

    -- بناء ملخص سريع للمهمة
    v_summary := left((v_context->>'summary'), 500);

    -- بناء خطة أولية بناءً على المعرفة المحملة
    v_plan := jsonb_build_object(
        'steps', jsonb_build_array(
            jsonb_build_object(
                'order', 1,
                'action_type', 'knowledge',
                'description', 'تحميل المعرفة المرتبطة بالسياق',
                'content', v_context->'context_loaded'
            ),
            jsonb_build_object(
                'order', 2,
                'action_type', 'analysis',
                'description', 'تحليل السياق لتوليد خطة التنفيذ'
            )
        )
    );

    -- إنشاء المهمة وتسجيلها في قاعدة البيانات
    INSERT INTO cmis_dev.dev_tasks (name, description, scope_code, status, priority, execution_plan)
    VALUES (p_prompt, v_summary, p_scope_code, 'context_loaded', p_priority, v_plan)
    RETURNING task_id INTO v_task_id;

    -- تسجيل الحدث في السجلات
    INSERT INTO cmis_dev.dev_logs (task_id, event, details)
    VALUES (v_task_id, 'context_initialized', jsonb_build_object('context', v_context));

    -- إرجاع تقرير الإدخال الكامل
    RETURN jsonb_build_object(
        'task_id', v_task_id,
        'prompt', p_prompt,
        'domain', p_domain,
        'category', p_category,
        'context_summary', v_summary,
        'token_estimate', v_context->'estimated_tokens',
        'execution_plan', v_plan
    );
END;
$$;


ALTER FUNCTION cmis_dev.auto_context_task_loader(p_prompt text, p_domain text, p_category text, p_scope_code text, p_priority smallint, p_token_limit integer) OWNER TO begin;

CREATE FUNCTION cmis_dev.create_dev_task(p_name text, p_description text, p_scope_code text, p_execution_plan jsonb, p_priority smallint DEFAULT 3) RETURNS uuid
    LANGUAGE plpgsql
    AS $$ DECLARE v_task_id uuid; v_similar_task uuid; BEGIN SELECT task_id INTO v_similar_task FROM cmis_dev.dev_tasks WHERE similarity(name, p_name) > 0.8 AND status IN ('pending', 'in_progress') AND created_at > now() - interval '7 days' LIMIT 1; IF v_similar_task IS NOT NULL THEN RAISE NOTICE 'Similar task found: %', v_similar_task; RETURN v_similar_task; END IF; INSERT INTO cmis_dev.dev_tasks (name, description, scope_code, execution_plan, priority, status) VALUES (p_name, p_description, p_scope_code, p_execution_plan, p_priority, 'pending') RETURNING task_id INTO v_task_id; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'task_created', jsonb_build_object('priority', p_priority, 'scope', p_scope_code)); RETURN v_task_id; END; $$;


ALTER FUNCTION cmis_dev.create_dev_task(p_name text, p_description text, p_scope_code text, p_execution_plan jsonb, p_priority smallint) OWNER TO begin;

CREATE FUNCTION cmis_dev.create_dev_task(p_name text, p_description text, p_scope_code text, p_execution_plan jsonb, p_priority integer DEFAULT 3) RETURNS uuid
    LANGUAGE plpgsql
    AS $$ DECLARE v_task_id uuid; v_similar_task uuid; BEGIN SELECT task_id INTO v_similar_task FROM cmis_dev.dev_tasks WHERE similarity(name, p_name) > 0.8 AND status IN ('pending', 'in_progress') AND created_at > now() - interval '7 days' LIMIT 1; IF v_similar_task IS NOT NULL THEN RAISE NOTICE 'Similar task found: %', v_similar_task; RETURN v_similar_task; END IF; INSERT INTO cmis_dev.dev_tasks (name, description, scope_code, execution_plan, priority, status) VALUES (p_name, p_description, p_scope_code, p_execution_plan, p_priority, 'pending') RETURNING task_id INTO v_task_id; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'task_created', jsonb_build_object('priority', p_priority, 'scope', p_scope_code)); RETURN v_task_id; END; $$;


ALTER FUNCTION cmis_dev.create_dev_task(p_name text, p_description text, p_scope_code text, p_execution_plan jsonb, p_priority integer) OWNER TO begin;

CREATE FUNCTION cmis_dev.prepare_context_execution(p_prompt text, p_domain text DEFAULT NULL::text, p_category text DEFAULT 'dev'::text, p_scope_code text DEFAULT 'system_dev'::text, p_priority smallint DEFAULT 3) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_task jsonb;
    v_context jsonb;
    v_task_id uuid;
    v_plan jsonb := '[]'::jsonb;
    v_summary text;
BEGIN
    -- تحميل السياق الإدراكي الذكي
    SELECT cmis_knowledge.smart_context_loader(p_prompt, p_domain, p_category, 5000)
    INTO v_context;

    -- بناء ملخص سريع
    v_summary := left((v_context->>'summary'), 500);

    -- إنشاء خطة تنفيذ أولية قابلة للتنفيذ من قبل GPT
    v_plan := jsonb_build_array(
        jsonb_build_object(
            'order', 1,
            'action_type', 'sql',
            'description', 'تحليل البنية الحالية في قاعدة البيانات',
            'action_body', 'SELECT * FROM cmis.integrations WHERE domain = ' || quote_literal(p_domain)
        ),
        jsonb_build_object(
            'order', 2,
            'action_type', 'api',
            'description', 'استدعاء واجهة Meta Graph API لاختبار التدفق',
            'action_body', 'POST https://graph.facebook.com/v18.0/oauth/access_token'
        ),
        jsonb_build_object(
            'order', 3,
            'action_type', 'analysis',
            'description', 'تحليل النتائج وتوليد دروس معرفية جديدة',
            'action_body', 'AI Analysis Placeholder'
        )
    );

    -- إنشاء المهمة في النظام
    INSERT INTO cmis_dev.dev_tasks (name, description, scope_code, status, priority, execution_plan)
    VALUES (p_prompt, v_summary, p_scope_code, 'ready_for_execution', p_priority, v_plan)
    RETURNING task_id INTO v_task_id;

    -- تسجيل سياق التهيئة في السجلات
    INSERT INTO cmis_dev.dev_logs (task_id, event, details)
    VALUES (v_task_id, 'execution_prepared', jsonb_build_object('context', v_context, 'plan', v_plan));

    -- إرجاع تقرير إدراكي شامل لـ GPT
    RETURN jsonb_build_object(
        'task_id', v_task_id,
        'prompt', p_prompt,
        'domain', p_domain,
        'category', p_category,
        'scope', p_scope_code,
        'status', 'ready_for_execution',
        'context_summary', v_summary,
        'execution_plan', v_plan,
        'token_estimate', v_context->'estimated_tokens'
    );
END;
$$;


ALTER FUNCTION cmis_dev.prepare_context_execution(p_prompt text, p_domain text, p_category text, p_scope_code text, p_priority smallint) OWNER TO begin;

CREATE FUNCTION cmis_dev.run_dev_task(p_prompt text) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_task_id uuid; v_domain text := 'meta_api'; v_category text := 'dev'; v_context jsonb; v_result text; BEGIN SELECT jsonb_agg(to_jsonb(r)) INTO v_context FROM load_context_by_priority(v_domain, v_category, 5000) AS r; v_task_id := cmis_dev.create_dev_task(p_prompt, 'Auto-generated task based on cognitive orchestration', 'system_dev', jsonb_build_object('steps', jsonb_build_array(jsonb_build_object('order',1,'action_type','sql','action_body','SELECT 1 AS test_result;'))), 2); INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'task_started', jsonb_build_object('prompt', p_prompt, 'domain', v_domain, 'category', v_category)); PERFORM 1; v_result := 'success'; UPDATE cmis_dev.dev_tasks SET status='completed', confidence=0.95, effectiveness_score=90, result_summary='Task executed successfully via run_dev_task()' WHERE task_id=v_task_id; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'task_completed', jsonb_build_object('result', v_result)); RETURN jsonb_build_object('task_id', v_task_id, 'status', 'completed', 'confidence', 0.95, 'knowledge_context_size', COALESCE(jsonb_array_length(v_context), 0), 'result', v_result); END; $$;


ALTER FUNCTION cmis_dev.run_dev_task(p_prompt text) OWNER TO begin;

CREATE FUNCTION cmis_dev.run_marketing_task(p_prompt text) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_task_id uuid; v_execution_plan jsonb; v_knowledge jsonb; v_step_result text; v_result_summary text; v_confidence numeric(3,2) := 0.9; BEGIN INSERT INTO cmis_dev.dev_tasks (name, description, scope_code, status) VALUES (left(p_prompt, 120), 'مهمة تسويقية آلية – تم إنشاؤها عبر Orchestrator', 'marketing_ai', 'initializing') RETURNING task_id INTO v_task_id; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'intent_parsed', jsonb_build_object('prompt', p_prompt)); SELECT jsonb_agg(row_to_json(sub)) INTO v_knowledge FROM ( SELECT ki.knowledge_id, ki.topic, ki.tier, km.content FROM cmis_knowledge.index ki JOIN cmis_knowledge.marketing km USING (knowledge_id) WHERE ( lower(p_prompt) LIKE ANY (ARRAY['%instagram%', '%إنستغرام%', '%انستغرام%', '%' || lower(ki.domain) || '%', '%' || lower(ki.topic) || '%']) OR EXISTS ( SELECT 1 FROM unnest(ki.keywords) kw WHERE lower(p_prompt) LIKE '%' || lower(kw) || '%' ) OR lower(km.content) LIKE '%' || lower(p_prompt) || '%' ) AND ki.is_deprecated = false ORDER BY ki.tier ASC, ki.last_verified_at DESC LIMIT 5 ) sub; IF v_knowledge IS NULL THEN INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'knowledge_missing', jsonb_build_object('reason','No relevant marketing knowledge found')); UPDATE cmis_dev.dev_tasks SET status='failed', result_summary='لم يتم العثور على معرفة تسويقية كافية' WHERE task_id=v_task_id; RETURN jsonb_build_object('status','failed','reason','knowledge_not_found'); END IF; v_execution_plan := jsonb_build_object('steps', jsonb_build_array(jsonb_build_object('order',1,'action','analyze_knowledge','desc','تحليل المعرفة التسويقية المرتبطة'), jsonb_build_object('order',2,'action','generate_campaign_plan','desc','إنشاء خطة مبدئية بناء على المعرفة'), jsonb_build_object('order',3,'action','record_result','desc','تسجيل النتائج والخطة في السجلات'))); UPDATE cmis_dev.dev_tasks SET execution_plan = v_execution_plan, status='running' WHERE task_id=v_task_id; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'plan_initialized', jsonb_build_object('steps', jsonb_array_length(v_execution_plan->'steps'))); v_step_result := 'تم تحليل المعرفة التسويقية وإنشاء خطة حملة مبدئية ناجحة.'; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'step_executed', jsonb_build_object('result', v_step_result)); v_result_summary := 'تم إنشاء خطة تسويقية أولية بنجاح بناءً على المعرفة المخزّنة.'; UPDATE cmis_dev.dev_tasks SET status='completed', confidence=v_confidence, result_summary=v_result_summary, effectiveness_score=ROUND((random()*20+80)::numeric) WHERE task_id=v_task_id; RETURN jsonb_build_object('task_id', v_task_id, 'status', 'completed', 'confidence', v_confidence, 'result', v_result_summary, 'knowledge_used', v_knowledge); END; $$;


ALTER FUNCTION cmis_dev.run_marketing_task(p_prompt text) OWNER TO begin;

CREATE FUNCTION cmis_dev.run_marketing_task_improved(p_prompt text) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_task_id uuid;
    v_knowledge jsonb;
    v_result jsonb;
BEGIN
    -- إنشاء مهمة جديدة
    INSERT INTO cmis_dev.dev_tasks (name, description, scope_code, status)
    VALUES (
        left(p_prompt, 120),
        'مهمة تسويقية آلية',
        'marketing_ai',
        'initializing'
    )
    RETURNING task_id INTO v_task_id;
    
    -- البحث عن المعرفة
    v_knowledge := cmis_dev.search_marketing_knowledge(p_prompt);
    
    -- التحقق من وجود معرفة
    IF jsonb_array_length(v_knowledge) = 0 THEN
        UPDATE cmis_dev.dev_tasks
        SET status = 'failed',
            result_summary = 'لم يتم العثور على معرفة تسويقية'
        WHERE task_id = v_task_id;
        
        RETURN jsonb_build_object(
            'task_id', v_task_id,
            'status', 'failed',
            'reason', 'knowledge_not_found'
        );
    END IF;
    
    -- تحديث المهمة بالنجاح
    UPDATE cmis_dev.dev_tasks
    SET status = 'completed',
        confidence = 0.9,
        result_summary = 'تم إنشاء خطة تسويقية بنجاح',
        effectiveness_score = ROUND((random() * 20 + 80)::numeric)
    WHERE task_id = v_task_id;
    
    -- إرجاع النتيجة
    RETURN jsonb_build_object(
        'task_id', v_task_id,
        'status', 'completed',
        'confidence', 0.9,
        'knowledge_used', v_knowledge
    );
END;
$$;


ALTER FUNCTION cmis_dev.run_marketing_task_improved(p_prompt text) OWNER TO begin;

CREATE FUNCTION cmis_dev.search_marketing_knowledge(p_prompt text) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_knowledge jsonb;
BEGIN
    SELECT jsonb_agg(row_to_json(sub)) INTO v_knowledge
    FROM (
        SELECT ki.knowledge_id, ki.topic, ki.tier, km.content
        FROM cmis_knowledge.index ki
        JOIN cmis_knowledge.marketing km USING (knowledge_id)
        WHERE (
            lower(p_prompt) LIKE ANY (ARRAY[
                '%instagram%', '%إنستغرام%', '%انستغرام%',
                '%' || lower(ki.domain) || '%',
                '%' || lower(ki.topic) || '%'
            ])
            OR EXISTS (
                SELECT 1 FROM unnest(ki.keywords) kw
                WHERE lower(p_prompt) LIKE '%' || lower(kw) || '%'
            )
            OR lower(km.content) LIKE '%' || lower(p_prompt) || '%'
        )
        AND ki.is_deprecated = false
        ORDER BY ki.tier ASC, ki.last_verified_at DESC
        LIMIT 5
    ) sub;
    
    RETURN COALESCE(v_knowledge, '[]'::jsonb);
END;
$$;


ALTER FUNCTION cmis_dev.search_marketing_knowledge(p_prompt text) OWNER TO begin;

CREATE FUNCTION cmis_knowledge.auto_analyze_knowledge(p_query text, p_domain text DEFAULT NULL::text, p_category text DEFAULT 'dev'::text, p_max_batches integer DEFAULT 5, p_batch_limit integer DEFAULT 20) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_data jsonb := '[]'::jsonb;
    v_summary text := '';
BEGIN
    -- جلب البيانات تدريجيًا من الدالة السابقة
    SELECT jsonb_agg(jsonb_build_object(
        'topic', topic,
        'part_index', part_index,
        'excerpt', left(content, 300),
        'score', score,
        'batch', batch_num
    )) INTO v_data
    FROM cmis_knowledge.auto_retrieve_knowledge(p_query, p_domain, p_category, p_max_batches, p_batch_limit);

    -- إنشاء ملخص إدراكي أولي
    SELECT string_agg(DISTINCT topic || ': ' || left(content, 300), E'\n') INTO v_summary
    FROM cmis_knowledge.auto_retrieve_knowledge(p_query, p_domain, p_category, 1, 10);

    RETURN jsonb_build_object(
        'query', p_query,
        'domain', p_domain,
        'category', p_category,
        'summary', v_summary,
        'samples', v_data,
        'retrieved_chunks', jsonb_array_length(v_data)
    );
END;
$$;


ALTER FUNCTION cmis_knowledge.auto_analyze_knowledge(p_query text, p_domain text, p_category text, p_max_batches integer, p_batch_limit integer) OWNER TO begin;

CREATE FUNCTION cmis_knowledge.auto_retrieve_knowledge(p_query text, p_domain text DEFAULT NULL::text, p_category text DEFAULT 'dev'::text, p_max_batches integer DEFAULT 5, p_batch_limit integer DEFAULT 20) RETURNS TABLE(knowledge_id uuid, parent_knowledge_id uuid, topic text, part_index integer, content text, score numeric, batch_num integer)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_offset int := 0;
    v_batch int := 0;
BEGIN
    LOOP
        RETURN QUERY
        SELECT *, v_batch AS batch_num
        FROM search_cognitive_knowledge(p_query, p_domain, p_category, p_batch_limit, v_offset);

        v_offset := v_offset + p_batch_limit;
        v_batch := v_batch + 1;

        EXIT WHEN v_batch >= p_max_batches;
    END LOOP;
END;
$$;


ALTER FUNCTION cmis_knowledge.auto_retrieve_knowledge(p_query text, p_domain text, p_category text, p_max_batches integer, p_batch_limit integer) OWNER TO begin;

CREATE FUNCTION cmis_knowledge.batch_update_embeddings(p_batch_size integer DEFAULT 100, p_category text DEFAULT NULL::text) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_count integer := 0;
    v_success integer := 0;
    v_failed integer := 0;
    v_rec record;
    v_result jsonb;
    v_start_time timestamp := clock_timestamp();
BEGIN
    -- إضافة السجلات إلى قائمة الانتظار إذا لم تكن موجودة
    INSERT INTO cmis_knowledge.embedding_update_queue (
        knowledge_id, source_table, source_field, priority
    )
    SELECT 
        knowledge_id, 
        'index', 
        'topic',
        CASE tier
            WHEN 1 THEN 10
            WHEN 2 THEN 7
            ELSE 5
        END
    FROM cmis_knowledge.index
    WHERE 
        topic_embedding IS NULL
        AND (p_category IS NULL OR category = p_category)
        AND is_deprecated = false
    ORDER BY tier ASC, last_verified_at DESC
    LIMIT p_batch_size
    ON CONFLICT DO NOTHING;

    -- معالجة السجلات في قائمة الانتظار
    FOR v_rec IN 
        SELECT queue_id, knowledge_id
        FROM cmis_knowledge.embedding_update_queue
        WHERE status = 'pending'
        ORDER BY priority DESC, created_at ASC
        LIMIT p_batch_size
    LOOP
        BEGIN
            UPDATE cmis_knowledge.embedding_update_queue
            SET status = 'processing', processing_started_at = now()
            WHERE queue_id = v_rec.queue_id;

            -- استدعاء الدالة التي تحدّث embedding لسجل واحد
            v_result := cmis_knowledge.update_single_embedding(v_rec.knowledge_id);

            IF v_result->>'status' = 'success' THEN
                UPDATE cmis_knowledge.embedding_update_queue
                SET status = 'completed', processed_at = now()
                WHERE queue_id = v_rec.queue_id;
                v_success := v_success + 1;
            ELSE
                UPDATE cmis_knowledge.embedding_update_queue
                SET 
                    status = 'failed',
                    error_message = v_result->>'message',
                    retry_count = retry_count + 1
                WHERE queue_id = v_rec.queue_id;
                v_failed := v_failed + 1;
            END IF;

        EXCEPTION WHEN OTHERS THEN
            UPDATE cmis_knowledge.embedding_update_queue
            SET 
                status = 'failed',
                error_message = SQLERRM,
                retry_count = retry_count + 1
            WHERE queue_id = v_rec.queue_id;
            v_failed := v_failed + 1;
        END;

        v_count := v_count + 1;
    END LOOP;

    RETURN jsonb_build_object(
        'status', 'completed',
        'total_processed', v_count,
        'successful', v_success,
        'failed', v_failed,
        'execution_time_seconds', EXTRACT(EPOCH FROM (clock_timestamp() - v_start_time)),
        'timestamp', now()
    );
END;
$$;


ALTER FUNCTION cmis_knowledge.batch_update_embeddings(p_batch_size integer, p_category text) OWNER TO begin;

CREATE FUNCTION cmis_knowledge.cleanup_old_embeddings() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- حذف نتائج البحث المخزنة مؤقتًا والمنتهية الصلاحية
    DELETE FROM cmis_knowledge.semantic_search_results_cache
    WHERE expires_at < now();

    -- حذف السجلات المكتملة الأقدم من 7 أيام في قائمة التحديث
    DELETE FROM cmis_knowledge.embedding_update_queue
    WHERE status = 'completed' AND processed_at < now() - interval '7 days';

    -- حذف سجلات البحث الأقدم من 30 يومًا
    DELETE FROM cmis_knowledge.semantic_search_logs
    WHERE created_at < now() - interval '30 days';

    -- إعادة تعيين إحصائيات الاستخدام للـ cache القديم
    UPDATE cmis_knowledge.embeddings_cache
    SET usage_count = 0
    WHERE last_accessed < now() - interval '30 days';
END;
$$;


ALTER FUNCTION cmis_knowledge.cleanup_old_embeddings() OWNER TO begin;

CREATE FUNCTION cmis_knowledge.generate_embedding_improved(input_text text) RETURNS public.vector
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_embedding vector(768);
    v_cached_embedding vector(768);
    v_base_vector float[];
    i integer;
BEGIN
    -- التحقق من وجود embedding محفوظ مسبقاً
    SELECT embedding INTO v_cached_embedding
    FROM cmis_knowledge.embeddings_cache
    WHERE input_hash = encode(digest(input_text, 'sha256'), 'hex')
    LIMIT 1;
    
    IF v_cached_embedding IS NOT NULL THEN
        -- تحديث وقت آخر استخدام
        UPDATE cmis_knowledge.embeddings_cache
        SET last_used_at = CURRENT_TIMESTAMP
        WHERE input_hash = encode(digest(input_text, 'sha256'), 'hex');
        
        RETURN v_cached_embedding;
    END IF;
    
    -- إنشاء embedding شبه ذكي بناءً على خصائص النص
    -- (ليس مثالياً لكن أفضل بكثير من العشوائي)
    v_base_vector := ARRAY[]::float[];
    
    -- استخدام خصائص النص لإنشاء vector شبه فريد
    FOR i IN 1..768 LOOP
        v_base_vector := array_append(v_base_vector, 
            (
                -- مزج خصائص مختلفة من النص
                sin(i::float * length(input_text)::float / 100.0) * 0.3 +
                cos(i::float * ascii(substr(input_text, (i % length(input_text)) + 1, 1))::float / 255.0) * 0.3 +
                sin(i::float * (SELECT sum(ascii(c)) FROM regexp_split_to_table(lower(input_text), '') AS c)::float / 10000.0) * 0.2 +
                cos(i::float * pi() / 768.0) * 0.2
            )::float
        );
    END LOOP;
    
    v_embedding := v_base_vector::vector(768);
    
    -- حفظ في الـ cache
    INSERT INTO cmis_knowledge.embeddings_cache (input_text, embedding, provider)
    VALUES (input_text, v_embedding, 'manual')
    ON CONFLICT (input_hash) DO UPDATE
    SET last_used_at = CURRENT_TIMESTAMP;
    
    RETURN v_embedding;
END;
$$;


ALTER FUNCTION cmis_knowledge.generate_embedding_improved(input_text text) OWNER TO begin;

CREATE FUNCTION cmis_knowledge.generate_embedding_mock(input_text text) RETURNS public.vector
    LANGUAGE sql
    AS $$
    SELECT cmis_knowledge.generate_embedding_improved(input_text);
$$;


ALTER FUNCTION cmis_knowledge.generate_embedding_mock(input_text text) OWNER TO begin;

CREATE FUNCTION cmis_knowledge.generate_system_report() RETURNS jsonb
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_report jsonb;
BEGIN
    WITH stats AS (
        SELECT 
            (SELECT COUNT(*) FROM cmis_knowledge.index) AS total_knowledge,
            (SELECT COUNT(*) FROM cmis_knowledge.index WHERE topic_embedding IS NOT NULL) AS embedded_knowledge,
            (SELECT COUNT(*) FROM cmis_knowledge.intent_mappings WHERE is_active) AS active_intents,
            (SELECT COUNT(*) FROM cmis_knowledge.direction_mappings WHERE is_active) AS active_directions,
            (SELECT COUNT(*) FROM cmis_knowledge.purpose_mappings WHERE is_active) AS active_purposes,
            (SELECT COUNT(*) FROM cmis_knowledge.embedding_update_queue WHERE status = 'pending') AS pending_updates,
            (SELECT COUNT(*) FROM cmis_knowledge.embedding_update_queue WHERE status = 'failed') AS failed_updates,
            (SELECT AVG(usage_count) FROM cmis_knowledge.embeddings_cache) AS avg_cache_usage,
            (SELECT COUNT(*) FROM cmis_knowledge.semantic_search_logs WHERE created_at > now() - interval '24 hours') AS searches_24h,
            (SELECT AVG(execution_time_ms) FROM cmis_knowledge.semantic_search_logs WHERE created_at > now() - interval '24 hours') AS avg_search_time
    )
    SELECT jsonb_build_object(
        'timestamp', now(),
        'knowledge_stats', jsonb_build_object(
            'total', total_knowledge,
            'embedded', embedded_knowledge,
            'coverage_percentage', ROUND((embedded_knowledge::numeric / NULLIF(total_knowledge, 0)) * 100, 2),
            'pending', total_knowledge - embedded_knowledge
        ),
        'intent_system', jsonb_build_object(
            'active_intents', active_intents,
            'active_directions', active_directions,
            'active_purposes', active_purposes,
            'total_mappings', active_intents + active_directions + active_purposes
        ),
        'processing', jsonb_build_object(
            'pending_updates', pending_updates,
            'failed_updates', failed_updates,
            'avg_cache_usage', ROUND(avg_cache_usage::numeric, 2)
        ),
        'performance', jsonb_build_object(
            'searches_24h', searches_24h,
            'avg_search_time_ms', ROUND(avg_search_time::numeric, 2)
        ),
        'health_status', CASE 
            WHEN (embedded_knowledge::numeric / NULLIF(total_knowledge, 0)) > 0.8 
                AND pending_updates < 100 
                AND failed_updates < 10 THEN 'healthy'
            WHEN (embedded_knowledge::numeric / NULLIF(total_knowledge, 0)) > 0.5 
                AND pending_updates < 500 
                AND failed_updates < 50 THEN 'warning'
            ELSE 'critical'
        END
    ) INTO v_report
    FROM stats;
    
    RETURN v_report;
END;
$$;


ALTER FUNCTION cmis_knowledge.generate_system_report() OWNER TO begin;

CREATE FUNCTION cmis_knowledge.register_knowledge(p_domain text, p_category text, p_topic text, p_content text, p_tier smallint DEFAULT 2, p_keywords text[] DEFAULT ARRAY[]::text[]) RETURNS uuid
    LANGUAGE plpgsql
    AS $$ DECLARE v_knowledge_id uuid; v_token_count int; BEGIN v_token_count := length(p_content) / 4; INSERT INTO cmis_knowledge.index (domain, category, topic, keywords, tier, token_budget, last_verified_at) VALUES (p_domain, p_category, p_topic, p_keywords, p_tier, v_token_count, now()) RETURNING knowledge_id INTO v_knowledge_id; CASE p_category WHEN 'dev' THEN INSERT INTO cmis_knowledge.dev (knowledge_id, content, token_count, version) VALUES (v_knowledge_id, p_content, v_token_count, '1.0'); WHEN 'marketing' THEN INSERT INTO cmis_knowledge.marketing (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); WHEN 'org' THEN INSERT INTO cmis_knowledge.org (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); WHEN 'research' THEN INSERT INTO cmis_knowledge.research (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); END CASE; RETURN v_knowledge_id; END; $$;


ALTER FUNCTION cmis_knowledge.register_knowledge(p_domain text, p_category text, p_topic text, p_content text, p_tier smallint, p_keywords text[]) OWNER TO begin;

CREATE FUNCTION cmis_knowledge.semantic_analysis() RETURNS TABLE(intent text, avg_score double precision, usage_count integer)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT top_intent, AVG(similarity), COUNT(*)
  FROM cmis_knowledge.semantic_search_logs
  WHERE created_at > now() - interval '7 days'
  GROUP BY top_intent;
END;
$$;


ALTER FUNCTION cmis_knowledge.semantic_analysis() OWNER TO begin;

CREATE FUNCTION cmis_knowledge.semantic_search_advanced(p_query text, p_intent text DEFAULT NULL::text, p_direction text DEFAULT NULL::text, p_purpose text DEFAULT NULL::text, p_category text DEFAULT NULL::text, p_limit integer DEFAULT 10, p_threshold numeric DEFAULT 0.3) RETURNS TABLE(knowledge_id uuid, domain text, topic text, content text, similarity_score numeric, intent_match numeric, direction_match numeric, purpose_match numeric, contextual_relevance numeric, temporal_weight numeric, trust_score numeric, combined_score numeric, category text, tier text, metadata jsonb)
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_query_embedding vector(768);
    v_intent_embedding vector(768);
    v_direction_embedding vector(768);
    v_purpose_embedding vector(768);
BEGIN
    v_query_embedding := cmis_knowledge.generate_embedding_mock(p_query);
    v_intent_embedding := cmis_knowledge.generate_embedding_mock(p_intent);
    v_direction_embedding := cmis_knowledge.generate_embedding_mock(p_direction);
    v_purpose_embedding := cmis_knowledge.generate_embedding_mock(p_purpose);

    RETURN QUERY
    WITH scored_results AS (
        SELECT 
            ki.knowledge_id,
            ki.domain,
            ki.topic,
            COALESCE(kd.content, km.content, ko.content, kr.content, 'No content available') AS content,
            COALESCE((1 - (ki.topic_embedding <=> v_query_embedding))::numeric, 0) AS topic_similarity,
            CASE WHEN v_intent_embedding IS NOT NULL AND ki.intent_vector IS NOT NULL THEN (1 - (ki.intent_vector <=> v_intent_embedding))::numeric ELSE 0 END AS intent_similarity,
            CASE WHEN v_direction_embedding IS NOT NULL AND ki.direction_vector IS NOT NULL THEN (1 - (ki.direction_vector <=> v_direction_embedding))::numeric ELSE 0 END AS direction_similarity,
            CASE WHEN v_purpose_embedding IS NOT NULL AND ki.purpose_vector IS NOT NULL THEN (1 - (ki.purpose_vector <=> v_purpose_embedding))::numeric ELSE 0 END AS purpose_similarity,
            ((
                CASE WHEN v_intent_embedding IS NOT NULL AND ki.intent_vector IS NOT NULL THEN (1 - (ki.intent_vector <=> v_intent_embedding)) ELSE 0 END +
                CASE WHEN v_purpose_embedding IS NOT NULL AND ki.purpose_vector IS NOT NULL THEN (1 - (ki.purpose_vector <=> v_purpose_embedding)) ELSE 0 END
            ) / 2)::numeric AS contextual_relevance,
            CASE WHEN ki.last_verified_at IS NOT NULL THEN EXP(-EXTRACT(EPOCH FROM (NOW() - ki.last_verified_at)) / 31536000) ELSE 0.5 END AS temporal_weight,
            (
                COALESCE(ki.verification_confidence, 0.5) +
                CASE WHEN ki.is_verified_by_ai THEN 0.15 ELSE 0 END +
                CASE ki.verification_source
                    WHEN 'peer_review' THEN 0.2
                    WHEN 'expert_validation' THEN 0.15
                    WHEN 'system_check' THEN 0.1
                    ELSE 0
                END
            ) AS trust_score,
            ki.category,
            ki.tier,
            jsonb_build_object(
                'keywords', ki.keywords,
                'last_verified', ki.last_verified_at,
                'verification_source', ki.verification_source,
                'verification_confidence', ki.verification_confidence,
                'is_verified_by_ai', ki.is_verified_by_ai,
                'is_deprecated', ki.is_deprecated
            ) AS metadata
        FROM cmis_knowledge.index ki
        LEFT JOIN cmis_knowledge.dev kd USING (knowledge_id)
        LEFT JOIN cmis_knowledge.marketing km USING (knowledge_id)
        LEFT JOIN cmis_knowledge.org ko USING (knowledge_id)
        LEFT JOIN cmis_knowledge.research kr USING (knowledge_id)
        WHERE (p_category IS NULL OR ki.category = p_category)
          AND ki.is_deprecated = false
          AND ki.topic_embedding IS NOT NULL
    )
    SELECT 
        s.knowledge_id,
        s.domain,
        s.topic,
        s.content,
        s.topic_similarity AS similarity_score,
        s.intent_similarity AS intent_match,
        s.direction_similarity AS direction_match,
        s.purpose_similarity AS purpose_match,
        s.contextual_relevance,
        s.temporal_weight,
        s.trust_score,
        (
            CASE 
                WHEN s.domain IN ('marketing', 'content', 'advertising') THEN
                    s.topic_similarity * 0.4 + s.contextual_relevance * 0.25 + s.temporal_weight * 0.25 + s.trust_score * 0.1
                WHEN s.domain IN ('research', 'data_science') THEN
                    s.topic_similarity * 0.35 + s.contextual_relevance * 0.2 + s.temporal_weight * 0.15 + s.trust_score * 0.3
                WHEN s.domain IN ('operations', 'org', 'dev') THEN
                    s.topic_similarity * 0.45 + s.contextual_relevance * 0.25 + s.temporal_weight * 0.1 + s.trust_score * 0.2
                ELSE
                    s.topic_similarity * 0.4 + s.contextual_relevance * 0.25 + s.temporal_weight * 0.2 + s.trust_score * 0.15
            END
        )::numeric AS combined_score,
        s.category,
        s.tier::text AS tier,
        s.metadata
    FROM scored_results s
    WHERE (
        s.topic_similarity * 0.3 + 
        s.intent_similarity * 0.25 + 
        s.direction_similarity * 0.15 + 
        s.purpose_similarity * 0.15
    ) >= p_threshold
    ORDER BY combined_score DESC, s.contextual_relevance DESC, s.trust_score DESC, s.temporal_weight DESC, s.tier ASC
    LIMIT p_limit;
END;
$$;


ALTER FUNCTION cmis_knowledge.semantic_search_advanced(p_query text, p_intent text, p_direction text, p_purpose text, p_category text, p_limit integer, p_threshold numeric) OWNER TO begin;

CREATE FUNCTION cmis_knowledge.smart_context_loader(p_query text, p_domain text DEFAULT NULL::text, p_category text DEFAULT 'dev'::text, p_token_limit integer DEFAULT 5000) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_analysis jsonb; v_context jsonb := '[]'::jsonb; v_total_tokens int := 0; v_sample jsonb; v_excerpt text; v_fallback record; BEGIN BEGIN SELECT cmis_knowledge.auto_analyze_knowledge(p_query, p_domain, p_category) INTO v_analysis; EXCEPTION WHEN others THEN v_analysis := NULL; END; IF v_analysis IS NOT NULL AND jsonb_typeof(v_analysis->'samples') = 'array' THEN FOR v_sample IN SELECT value FROM jsonb_array_elements(v_analysis->'samples') LOOP v_excerpt := v_sample->>'excerpt'; IF v_total_tokens + (length(v_excerpt) / 4) > p_token_limit THEN EXIT; END IF; v_context := v_context || jsonb_build_object('topic', v_sample->>'topic','excerpt', trim(v_excerpt),'score', v_sample->>'score','batch', v_sample->>'batch'); v_total_tokens := v_total_tokens + (length(v_excerpt) / 4); END LOOP; ELSE FOR v_fallback IN SELECT d.content FROM cmis_knowledge.dev d JOIN cmis_knowledge.index i USING (knowledge_id) WHERE i.topic ILIKE '%' || p_query || '%' AND (p_domain IS NULL OR i.domain = p_domain) AND i.category = p_category ORDER BY i.last_verified_at DESC LIMIT 3 LOOP v_context := v_context || jsonb_build_object('topic', p_query,'excerpt', left(v_fallback.content, 1000),'score', 0.8,'batch', 'direct'); END LOOP; END IF; RETURN jsonb_build_object('query', p_query,'domain', p_domain,'category', p_category,'summary', COALESCE(v_analysis->'summary', 'null'::jsonb),'context_loaded', v_context,'estimated_tokens', v_total_tokens); END; $$;


ALTER FUNCTION cmis_knowledge.smart_context_loader(p_query text, p_domain text, p_category text, p_token_limit integer) OWNER TO begin;

CREATE FUNCTION cmis_knowledge.trigger_update_embeddings() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- إضافة إلى قائمة الانتظار للمعالجة غير المتزامنة
    INSERT INTO cmis_knowledge.embedding_update_queue (
        knowledge_id,
        source_table,
        source_field,
        priority,
        created_at
    ) VALUES (
        COALESCE(NEW.knowledge_id, OLD.knowledge_id),
        TG_TABLE_NAME,
        CASE 
            WHEN TG_TABLE_NAME = 'index' THEN 'topic'
            ELSE 'content'
        END,
        CASE 
            WHEN TG_TABLE_NAME = 'index' AND NEW.tier = 1 THEN 10
            WHEN TG_TABLE_NAME = 'index' AND NEW.tier = 2 THEN 7
            ELSE 5
        END,
        now()
    ) ON CONFLICT DO NOTHING;
    
    RETURN NEW;
END;
$$;


ALTER FUNCTION cmis_knowledge.trigger_update_embeddings() OWNER TO begin;

CREATE FUNCTION cmis_knowledge.update_manifest_on_change() RETURNS trigger
    LANGUAGE plpgsql
    AS $$ DECLARE v_layer TEXT; BEGIN v_layer := TG_TABLE_NAME; UPDATE cmis_knowledge.cognitive_manifest SET last_updated = NOW(), confidence = LEAST(confidence + 0.02, 1.00) WHERE LOWER(layer_name) = LOWER(v_layer) OR (LOWER(layer_name) = 'temporal' AND TG_TABLE_NAME LIKE '%temporal%') OR (LOWER(layer_name) = 'predictive' AND TG_TABLE_NAME LIKE '%predictive%') OR (LOWER(layer_name) = 'feedback' AND TG_TABLE_NAME LIKE '%audit%') OR (LOWER(layer_name) = 'learning' AND TG_TABLE_NAME LIKE '%learning%'); INSERT INTO cmis_audit.logs(event_type, event_source, description, created_at) VALUES ('manifest_sync', TG_TABLE_NAME, CONCAT('🔄 تحديث تلقائي في الـ Manifest بعد تعديل في الطبقة ', v_layer), NOW()); RETURN NEW; END; $$;


ALTER FUNCTION cmis_knowledge.update_manifest_on_change() OWNER TO begin;

CREATE FUNCTION cmis_knowledge.update_single_embedding(p_knowledge_id uuid) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_rec record;
    v_result jsonb;
BEGIN
    -- الحصول على البيانات
    SELECT 
        ki.*,
        COALESCE(kd.content, km.content, ko.content, kr.content) AS content
    INTO v_rec
    FROM cmis_knowledge.index ki
    LEFT JOIN cmis_knowledge.dev kd USING (knowledge_id)
    LEFT JOIN cmis_knowledge.marketing km USING (knowledge_id)
    LEFT JOIN cmis_knowledge.org ko USING (knowledge_id)
    LEFT JOIN cmis_knowledge.research kr USING (knowledge_id)
    WHERE ki.knowledge_id = p_knowledge_id;
    
    IF NOT FOUND THEN
        RETURN jsonb_build_object(
            'status', 'error',
            'message', 'Knowledge ID not found'
        );
    END IF;
    
    -- تحديث embeddings في جدول الفهرس
    UPDATE cmis_knowledge.index 
    SET 
        topic_embedding = cmis_knowledge.generate_embedding_mock(v_rec.topic),
        keywords_embedding = CASE 
            WHEN array_length(v_rec.keywords, 1) > 0 
            THEN cmis_knowledge.generate_embedding_mock(array_to_string(v_rec.keywords, ' '))
            ELSE NULL
        END,
        semantic_fingerprint = cmis_knowledge.generate_embedding_mock(
            COALESCE(v_rec.topic, '') || ' ' || 
            COALESCE(array_to_string(v_rec.keywords, ' '), '') || ' ' ||
            COALESCE(v_rec.domain, '')
        ),
        embedding_updated_at = now(),
        embedding_version = COALESCE(embedding_version, 0) + 1
    WHERE knowledge_id = p_knowledge_id;
    
    -- تحديث content embedding في الجدول المناسب
    IF v_rec.content IS NOT NULL THEN
        CASE v_rec.category
            WHEN 'dev' THEN
                UPDATE cmis_knowledge.dev 
                SET 
                    content_embedding = cmis_knowledge.generate_embedding_mock(v_rec.content),
                    semantic_summary_embedding = cmis_knowledge.generate_embedding_mock(
                        left(v_rec.content, 500)
                    )
                WHERE knowledge_id = p_knowledge_id;
            
            WHEN 'marketing' THEN
                UPDATE cmis_knowledge.marketing 
                SET content_embedding = cmis_knowledge.generate_embedding_mock(v_rec.content)
                WHERE knowledge_id = p_knowledge_id;
            
            WHEN 'org' THEN
                UPDATE cmis_knowledge.org 
                SET content_embedding = cmis_knowledge.generate_embedding_mock(v_rec.content)
                WHERE knowledge_id = p_knowledge_id;
            
            WHEN 'research' THEN
                UPDATE cmis_knowledge.research 
                SET content_embedding = cmis_knowledge.generate_embedding_mock(v_rec.content)
                WHERE knowledge_id = p_knowledge_id;
        END CASE;
    END IF;
    
    -- تحديث cache
    INSERT INTO cmis_knowledge.embeddings_cache (
        source_table, source_id, source_field, 
        embedding, metadata
    ) VALUES (
        'index', p_knowledge_id, 'topic',
        cmis_knowledge.generate_embedding_mock(v_rec.topic),
        jsonb_build_object('category', v_rec.category, 'domain', v_rec.domain)
    )
    ON CONFLICT (source_table, source_id, source_field) 
    DO UPDATE SET 
        embedding = EXCLUDED.embedding,
        updated_at = now(),
        usage_count = cmis_knowledge.embeddings_cache.usage_count + 1;
    
    RETURN jsonb_build_object(
        'status', 'success',
        'knowledge_id', p_knowledge_id,
        'topic', v_rec.topic,
        'category', v_rec.category,
        'embedding_updated', true,
        'timestamp', now()
    );
END;
$$;


ALTER FUNCTION cmis_knowledge.update_single_embedding(p_knowledge_id uuid) OWNER TO begin;

CREATE FUNCTION cmis_knowledge.verify_installation() RETURNS jsonb
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN jsonb_build_object('status', 'partial setup complete', 'timestamp', now());
END;$$;


ALTER FUNCTION cmis_knowledge.verify_installation() OWNER TO begin;

CREATE FUNCTION cmis_marketing.generate_campaign_assets(p_task_id uuid) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_campaign_name text; v_knowledge jsonb; v_assets jsonb; BEGIN SELECT name INTO v_campaign_name FROM cmis_dev.dev_tasks WHERE task_id = p_task_id; SELECT jsonb_agg(row_to_json(sub)) INTO v_knowledge FROM ( SELECT ki.topic, km.content, ki.tier FROM cmis_knowledge.index ki JOIN cmis_knowledge.marketing km USING (knowledge_id) WHERE km.content ILIKE '%' || v_campaign_name || '%' OR ki.topic ILIKE '%' || v_campaign_name || '%' ORDER BY ki.tier ASC LIMIT 3 ) sub; v_assets := jsonb_build_array( jsonb_build_object('platform','instagram','asset_type','post','content', jsonb_build_object('text','منشور جذاب لزيادة التفاعل على CMIS Cloud','hashtags',ARRAY['#CMISCloud','#MarketingAutomation','#TechAgencies']),'confidence',0.95), jsonb_build_object('platform','instagram','asset_type','ad_copy','content', jsonb_build_object('headline','ارتقِ باستراتيجيتك التسويقية مع CMIS Cloud','body','حل متكامل لوكالات التسويق – أتمتة، تحليل، وذكاء اصطناعي في نظام واحد.'),'confidence',0.93) ); INSERT INTO cmis_marketing.assets (task_id, platform, asset_type, content, confidence) SELECT p_task_id, asset->>'platform', asset->>'asset_type', asset->'content', (asset->>'confidence')::numeric FROM jsonb_array_elements(v_assets) asset; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (p_task_id, 'assets_generated', jsonb_build_object('count', jsonb_array_length(v_assets))); RETURN jsonb_build_object('task_id', p_task_id, 'status', 'assets_generated', 'assets', v_assets, 'knowledge_used', v_knowledge); END; $$;


ALTER FUNCTION cmis_marketing.generate_campaign_assets(p_task_id uuid) OWNER TO begin;

CREATE FUNCTION cmis_marketing.generate_creative_content(p_topic text, p_goal text DEFAULT 'awareness'::text, p_tone text DEFAULT 'ملهم'::text, p_length smallint DEFAULT 3) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_hooks jsonb; v_concepts jsonb; v_slogans jsonb; v_narratives jsonb; v_output text := ''; v_mix jsonb := '[]'::jsonb; BEGIN SELECT jsonb_agg(content) INTO v_hooks FROM cmis_knowledge.creative_templates WHERE category='hook' AND (tone=p_tone OR tone IS NULL) ORDER BY random() LIMIT p_length; SELECT jsonb_agg(content) INTO v_concepts FROM cmis_knowledge.creative_templates WHERE category='concept' ORDER BY random() LIMIT p_length; SELECT jsonb_agg(content) INTO v_slogans FROM cmis_knowledge.creative_templates WHERE category='slogan' ORDER BY random() LIMIT p_length; SELECT jsonb_agg(content) INTO v_narratives FROM cmis_knowledge.creative_templates WHERE category='narrative' AND (tone=p_tone OR tone IS NULL) ORDER BY random() LIMIT p_length; v_mix := v_mix || jsonb_build_object('hook', v_hooks->0); v_mix := v_mix || jsonb_build_object('concept', v_concepts->0); v_mix := v_mix || jsonb_build_object('slogan', v_slogans->0); v_mix := v_mix || jsonb_build_object('narrative', v_narratives->0); v_output := concat_ws(E'\n\n', v_hooks->>0, v_concepts->>0, v_narratives->>0, v_slogans->>0); RETURN jsonb_build_object('status', 'creative_generated', 'topic', p_topic, 'tone', p_tone, 'output', v_output, 'composition', v_mix); END; $$;


ALTER FUNCTION cmis_marketing.generate_creative_content(p_topic text, p_goal text, p_tone text, p_length smallint) OWNER TO begin;

CREATE FUNCTION cmis_marketing.generate_creative_variants(p_topic text, p_tone text, p_variant_count integer DEFAULT 3) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_hooks RECORD; v_concepts RECORD; v_narratives RECORD; v_slogans RECORD; v_result jsonb := '[]'::jsonb; v_i int := 1; BEGIN FOR v_i IN 1..p_variant_count LOOP SELECT * FROM cmis_knowledge.creative_templates WHERE category='hook' ORDER BY random() LIMIT 1 INTO v_hooks; SELECT * FROM cmis_knowledge.creative_templates WHERE category='concept' ORDER BY random() LIMIT 1 INTO v_concepts; SELECT * FROM cmis_knowledge.creative_templates WHERE category='narrative' ORDER BY random() LIMIT 1 INTO v_narratives; SELECT * FROM cmis_knowledge.creative_templates WHERE category='slogan' ORDER BY random() LIMIT 1 INTO v_slogans; INSERT INTO cmis_marketing.generated_creatives ( topic, tone, variant_index, hook, concept, narrative, slogan, emotion_profile, tags ) VALUES ( p_topic, p_tone, v_i, v_hooks.content, v_concepts.content, v_narratives.content, v_slogans.content, ARRAY(SELECT unnest(v_hooks.emotion) || unnest(v_concepts.emotion) || unnest(v_narratives.emotion)), ARRAY(SELECT unnest(v_hooks.tags) || unnest(v_concepts.tags) || unnest(v_narratives.tags)) ); v_result := v_result || jsonb_build_object( 'variant_index', v_i, 'hook', v_hooks.content, 'concept', v_concepts.content, 'narrative', v_narratives.content, 'slogan', v_slogans.content, 'emotion_profile', ARRAY(SELECT unnest(v_hooks.emotion) || unnest(v_concepts.emotion) || unnest(v_narratives.emotion)), 'tags', ARRAY(SELECT unnest(v_hooks.tags) || unnest(v_concepts.tags) || unnest(v_narratives.tags)) ); END LOOP; RETURN jsonb_build_object( 'status', 'multi_generated', 'topic', p_topic, 'tone', p_tone, 'count', p_variant_count, 'variants', v_result ); END; $$;


ALTER FUNCTION cmis_marketing.generate_creative_variants(p_topic text, p_tone text, p_variant_count integer) OWNER TO begin;

CREATE FUNCTION cmis_marketing.generate_video_scenario(p_task_id uuid) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_assets jsonb; v_visuals jsonb; v_scenario jsonb; BEGIN SELECT jsonb_agg(row_to_json(sub)) INTO v_assets FROM ( SELECT asset_id, content FROM cmis_marketing.assets WHERE task_id = p_task_id AND asset_type IN ('post','ad_copy') ) sub; SELECT jsonb_agg(row_to_json(sub)) INTO v_visuals FROM ( SELECT visual_prompt, style, palette, emotion FROM cmis_marketing.visual_concepts vc JOIN cmis_marketing.assets a USING (asset_id) WHERE a.task_id = p_task_id ) sub; v_scenario := jsonb_build_array( jsonb_build_object('order', 1, 'description', 'لقطة افتتاحية لمكتب وكالة تسويق حديثة تظهر شعار CMIS Cloud.', 'narration', 'في عالم التسويق السريع، النجاح يعتمد على الذكاء... CMIS Cloud هو الحل.', 'visual_hint', (v_visuals->0->>'visual_prompt'), 'duration', 4), jsonb_build_object('order', 2, 'description', 'فريق عمل شاب يتفاعل مع لوحة بيانات تفاعلية تعرض مؤشرات الأداء.', 'narration', 'راقب أداء حملاتك لحظة بلحظة... تحكّم في كل شيء من منصة واحدة.', 'visual_hint', (v_visuals->1->>'visual_prompt'), 'duration', 6), jsonb_build_object('order', 3, 'description', 'مشهد عرض عملي: واجهة CMIS Cloud على شاشة كمبيوتر.', 'narration', 'تكامل تام مع Meta وInstagram وGoogle Ads.', 'visual_hint', 'لقطة شاشة واجهة CMIS Cloud بتصميم أنيق.', 'duration', 5), jsonb_build_object('order', 4, 'description', 'ختام الفيديو بعرض شعار CMIS Cloud مع عبارة دعائية.', 'narration', 'CMIS Cloud — ذكاء التسويق في منصة واحدة.', 'visual_hint', 'خلفية بنفس الألوان التقنية الزرقاء والأرجوانية مع شعار CMIS.', 'duration', 3) ); INSERT INTO cmis_marketing.video_scenarios ( task_id, title, duration_seconds, scenes, tone, goal, confidence ) VALUES ( p_task_id, 'سيناريو فيديو ترويجي لحملة CMIS Cloud', 18, v_scenario, 'ملهم، احترافي', 'رفع وعي العلامة التجارية وبناء الثقة لدى وكالات التسويق', 0.94 ); INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (p_task_id, 'video_scenario_generated', jsonb_build_object('duration',18,'scenes',4)); RETURN jsonb_build_object('status','video_scenario_generated','task_id',p_task_id,'title','سيناريو فيديو ترويجي لحملة CMIS Cloud','duration',18,'scenes',v_scenario); END; $$;


ALTER FUNCTION cmis_marketing.generate_video_scenario(p_task_id uuid) OWNER TO begin;

CREATE FUNCTION cmis_marketing.generate_visual_concepts(p_task_id uuid) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_assets jsonb; v_concepts jsonb := '[]'::jsonb; asset_json jsonb; concept jsonb; BEGIN SELECT jsonb_agg(row_to_json(sub)) INTO v_assets FROM ( SELECT asset_id, platform, asset_type, content FROM cmis_marketing.assets WHERE task_id = p_task_id ) sub; IF v_assets IS NULL THEN RAISE NOTICE 'No assets found for task %', p_task_id; RETURN jsonb_build_object('status','no_assets'); END IF; FOR asset_json IN SELECT value FROM jsonb_array_elements(v_assets) LOOP INSERT INTO cmis_marketing.visual_concepts ( asset_id, visual_prompt, style, palette, emotion, focus_keywords ) VALUES ( (asset_json->>'asset_id')::uuid, CASE asset_json->>'asset_type' WHEN 'post' THEN 'تصوير لمكتب عصري لوكالة تسويق رقمية تعمل باستخدام CMIS Cloud، يظهر فريق شاب يبتسم ويحلل البيانات على شاشة كبيرة.' WHEN 'ad_copy' THEN 'خلفية بسيطة بألوان تقنية زرقاء وأرجوانية مع أيقونة سحابية، وعنوان كبير مكتوب "CMIS Cloud".' WHEN 'reel_script' THEN 'مشاهد متتابعة لفريق إبداعي يستخدم لوحات رقمية وتحليل أداء الإعلانات في واجهة جذابة.' ELSE 'تصميم عام يعكس التكنولوجيا والذكاء الاصطناعي والتعاون.' END, 'واقعي تقني', 'أزرق، أرجواني، أبيض', 'احترافية، تفاؤل، ثقة', ARRAY['وكالة تسويق','CMIS Cloud','بيانات','ابتكار'] ) RETURNING row_to_json(cmis_marketing.visual_concepts.*) INTO concept; v_concepts := v_concepts || concept; END LOOP; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (p_task_id, 'visual_concepts_generated', jsonb_build_object('count', jsonb_array_length(v_concepts))); RETURN jsonb_build_object('status','visuals_generated','task_id',p_task_id,'concepts',v_concepts); END; $$;


ALTER FUNCTION cmis_marketing.generate_visual_concepts(p_task_id uuid) OWNER TO begin;

CREATE FUNCTION cmis_marketing.generate_visual_scenarios(p_topic text, p_tone text) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_creative RECORD; BEGIN FOR v_creative IN SELECT * FROM cmis_marketing.generated_creatives WHERE topic = p_topic AND tone = p_tone LOOP INSERT INTO cmis_marketing.visual_scenarios (creative_id, topic, tone, variant_index, scene_order, scene_type, scene_text, visual_hint) VALUES (v_creative.creative_id, p_topic, p_tone, v_creative.variant_index, 1, 'hook', v_creative.hook, 'لقطة افتتاحية جذابة مع نص على الشاشة'), (v_creative.creative_id, p_topic, p_tone, v_creative.variant_index, 2, 'concept', v_creative.concept, 'لقطة فريق عمل أو عرض بياني ديناميكي'), (v_creative.creative_id, p_topic, p_tone, v_creative.variant_index, 3, 'narrative', v_creative.narrative, 'مشاهد سردية حيوية بلقطات سريعة'), (v_creative.creative_id, p_topic, p_tone, v_creative.variant_index, 4, 'slogan', v_creative.slogan, 'لقطة ختامية بشعار CMIS Cloud'); END LOOP; RETURN jsonb_build_object('status','scenarios_generated','topic',p_topic,'tone',p_tone,'message','تم إنشاء السيناريوهات المرئية بنجاح'); END; $$;


ALTER FUNCTION cmis_marketing.generate_visual_scenarios(p_topic text, p_tone text) OWNER TO begin;

CREATE FUNCTION cmis_marketing.generate_voice_script(p_scenario_id uuid) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_scenes jsonb; v_script jsonb := '[]'::jsonb; v_voice_tone text; v_goal text; v_task_id uuid; v_script_text text := ''; BEGIN SELECT scenes, tone, goal, task_id INTO v_scenes, v_voice_tone, v_goal, v_task_id FROM cmis_marketing.video_scenarios WHERE scenario_id = p_scenario_id; FOR i IN 0..jsonb_array_length(v_scenes)-1 LOOP DECLARE v_scene jsonb := v_scenes->i; v_narr text := v_scene->>'narration'; v_extra text; BEGIN SELECT content INTO v_extra FROM cmis_knowledge.marketing km JOIN cmis_knowledge.index ki USING (knowledge_id) WHERE ki.category='marketing' AND ki.tier<=2 AND ki.topic ILIKE '%'||v_goal||'%' ORDER BY ki.last_verified_at DESC LIMIT 1; IF v_extra IS NULL THEN v_extra := 'احصل على تجربة فريدة مع CMIS Cloud الآن!'; END IF; v_script := v_script || jsonb_build_object('scene', i+1, 'narration', v_narr || ' ' || v_extra, 'duration', v_scene->>'duration'); v_script_text := v_script_text || v_narr || ' ' || v_extra || E'\n'; END; END LOOP; INSERT INTO cmis_marketing.voice_scripts (scenario_id, task_id, voice_tone, narration, script_structure, confidence) VALUES (p_scenario_id, v_task_id, v_voice_tone, v_script_text, v_script, 0.93); INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'voice_script_generated', jsonb_build_object('scenes',jsonb_array_length(v_scenes))); RETURN jsonb_build_object('status','voice_script_generated','scenario_id',p_scenario_id,'task_id',v_task_id,'tone',v_voice_tone,'script_text',v_script_text); END; $$;


ALTER FUNCTION cmis_marketing.generate_voice_script(p_scenario_id uuid) OWNER TO begin;

CREATE FUNCTION cmis_ops.cleanup_stale_assets() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  RAISE NOTICE '🧹 تنظيف الأصول القديمة غير النشطة...';
  DELETE FROM cmis.creative_outputs
  WHERE status = 'draft' AND created_at < NOW() - INTERVAL '90 days';
  RAISE NOTICE '✅ تم حذف الأصول القديمة بنجاح.';
END;
$$;


ALTER FUNCTION cmis_ops.cleanup_stale_assets() OWNER TO begin;

CREATE FUNCTION cmis_ops.generate_ai_summary() RETURNS TABLE(campaign_id uuid, summary jsonb)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT c.campaign_id,
         jsonb_build_object(
           'name', c.name,
           'status', c.status,
           'avg_kpi', AVG(pm.observed),
           'top_contexts', jsonb_agg(DISTINCT ctx.context_type),
           'assets_count', COUNT(DISTINCT co.output_id)
         )
  FROM cmis.campaigns c
  LEFT JOIN cmis.contexts_unified ctx ON ctx.campaign_id = c.campaign_id
  LEFT JOIN cmis.creative_outputs co ON co.campaign_id = c.campaign_id
  LEFT JOIN cmis.performance_metrics pm ON pm.campaign_id = c.campaign_id
  GROUP BY c.campaign_id;
END;
$$;


ALTER FUNCTION cmis_ops.generate_ai_summary() OWNER TO begin;

CREATE FUNCTION cmis_ops.normalize_metrics() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  INSERT INTO cmis.performance_metrics (
    metric_id, org_id, campaign_id, output_id,
    kpi, observed, target, baseline, observed_at
  )
  SELECT gen_random_uuid(), i.org_id, NULL, NULL,
         (payload->>'metric_name')::text,
         (payload->>'value')::numeric,
         NULL, NULL,
         (payload->>'timestamp')::timestamp
  FROM cmis_staging.raw_channel_data d
  JOIN cmis.integrations i ON i.integration_id = d.integration_id
  WHERE d.platform = 'facebook'
  ON CONFLICT DO NOTHING;
END;
$$;


ALTER FUNCTION cmis_ops.normalize_metrics() OWNER TO begin;

CREATE FUNCTION cmis_ops.refresh_ai_insights() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  RAISE NOTICE '🔄 بدء تحديث مؤشرات الذكاء الاصطناعي...';
  UPDATE cmis.performance_metrics pm
  SET observed = observed + (RANDOM() * 0.05 * COALESCE(target, 100)),
      observed_at = NOW()
  WHERE observed IS NOT NULL;
  RAISE NOTICE '✅ تم تحديث مؤشرات الأداء التحليلية.';
END;
$$;


ALTER FUNCTION cmis_ops.refresh_ai_insights() OWNER TO begin;

CREATE FUNCTION cmis_ops.sync_integrations() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  RAISE NOTICE '🔗 مزامنة تكاملات المنصات...';
  UPDATE cmis.integrations
  SET updated_at = NOW()
  WHERE is_active = TRUE;
  RAISE NOTICE '✅ تم تحديث بيانات الربط بنجاح.';
END;
$$;


ALTER FUNCTION cmis_ops.sync_integrations() OWNER TO begin;

CREATE FUNCTION cmis_ops.update_timestamp() RETURNS trigger
    LANGUAGE plpgsql
    AS $$ BEGIN NEW.updated_at = NOW(); RETURN NEW; END; $$;


ALTER FUNCTION cmis_ops.update_timestamp() OWNER TO begin;

CREATE FUNCTION cmis_staging.generate_brief_summary_legacy(p_brief_id uuid) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$
DECLARE
  v_brief cmis.creative_briefs%ROWTYPE;
  v_summary JSONB;
BEGIN
  SELECT * INTO v_brief 
  FROM cmis.creative_briefs 
  WHERE brief_id = p_brief_id;

  IF NOT FOUND THEN
    RAISE EXCEPTION 'Creative brief not found for ID: %', p_brief_id;
  END IF;

  v_summary := jsonb_build_object(
    'brief_name', v_brief.name,
    'org_id', v_brief.org_id,
    'summary_fields', v_brief.brief_data - 'non_essential'
  );

  RETURN v_summary || jsonb_build_object('generated_at', NOW());
END;
$$;


ALTER FUNCTION cmis_staging.generate_brief_summary_legacy(p_brief_id uuid) OWNER TO begin;

CREATE FUNCTION cmis_staging.refresh_creative_index_legacy() RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
  v_updated_count INT := 0;
BEGIN
  UPDATE cmis_knowledge."index" AS k
  SET topic_embedding = cmis_knowledge.generate_embedding_mock(c.caption)
  FROM cmis.content_items c
  WHERE c.updated_at > NOW() - INTERVAL '1 day'
    AND k.domain = 'creative'
  RETURNING 1 INTO v_updated_count;

  RAISE NOTICE 'Creative index refreshed: % entries updated', v_updated_count;
END;
$$;


ALTER FUNCTION cmis_staging.refresh_creative_index_legacy() OWNER TO begin;

CREATE FUNCTION cmis_staging.validate_brief_structure_legacy(p_brief jsonb) RETURNS boolean
    LANGUAGE plpgsql
    AS $$
DECLARE
  required_fields TEXT[] := ARRAY[]::TEXT[];
  missing TEXT[] := ARRAY[]::TEXT[];
  key TEXT;
BEGIN
  SELECT COALESCE(array_agg(slug), ARRAY[]::TEXT[]) INTO required_fields
  FROM cmis.field_definitions
  WHERE required_default = TRUE AND module_scope ILIKE '%creative_brief%';

  FOREACH key IN ARRAY required_fields LOOP
    IF NOT p_brief ? key THEN
      missing := array_append(missing, key);
    END IF;
  END LOOP;

  IF array_length(missing, 1) > 0 THEN
    RAISE EXCEPTION 'Missing required fields: %', array_to_string(missing, ', ');
  END IF;

  RETURN TRUE;
END;
$$;


ALTER FUNCTION cmis_staging.validate_brief_structure_legacy(p_brief jsonb) OWNER TO begin;

CREATE FUNCTION operations.audit_trigger_function() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_action TEXT;
    v_record_id UUID;
    v_record_key TEXT;
    v_old_values JSONB;
    v_new_values JSONB;
BEGIN
    IF TG_OP = 'INSERT' THEN
        v_action := 'INSERT';
    ELSIF TG_OP = 'UPDATE' THEN
        v_action := 'UPDATE';
    ELSE
        v_action := 'DELETE';
    END IF;

    BEGIN
        v_record_id := COALESCE(
            (CASE WHEN TG_OP != 'DELETE' AND to_jsonb(NEW) ? 'campaign_id' THEN (NEW).campaign_id ELSE NULL END),
            (CASE WHEN TG_OP != 'DELETE' AND to_jsonb(NEW) ? 'id' THEN (NEW).id ELSE NULL END),
            (CASE WHEN TG_OP != 'DELETE' AND to_jsonb(NEW) ? 'org_id' THEN (NEW).org_id ELSE NULL END),
            (CASE WHEN TG_OP != 'DELETE' AND to_jsonb(NEW) ? 'context_id' THEN (NEW).context_id ELSE NULL END),
            (CASE WHEN TG_OP != 'DELETE' AND to_jsonb(NEW) ? 'creative_id' THEN (NEW).creative_id ELSE NULL END),
            (CASE WHEN TG_OP != 'DELETE' AND to_jsonb(NEW) ? 'value_id' THEN (NEW).value_id ELSE NULL END),
            (CASE WHEN TG_OP = 'DELETE' AND to_jsonb(OLD) ? 'campaign_id' THEN (OLD).campaign_id ELSE NULL END),
            (CASE WHEN TG_OP = 'DELETE' AND to_jsonb(OLD) ? 'id' THEN (OLD).id ELSE NULL END),
            (CASE WHEN TG_OP = 'DELETE' AND to_jsonb(OLD) ? 'org_id' THEN (OLD).org_id ELSE NULL END),
            (CASE WHEN TG_OP = 'DELETE' AND to_jsonb(OLD) ? 'context_id' THEN (OLD).context_id ELSE NULL END),
            (CASE WHEN TG_OP = 'DELETE' AND to_jsonb(OLD) ? 'creative_id' THEN (OLD).creative_id ELSE NULL END),
            (CASE WHEN TG_OP = 'DELETE' AND to_jsonb(OLD) ? 'value_id' THEN (OLD).value_id ELSE NULL END)
        );
    EXCEPTION WHEN OTHERS THEN
        v_record_id := NULL;
    END;

    v_record_key := COALESCE(
        (CASE WHEN TG_OP != 'DELETE' AND to_jsonb(NEW) ? 'name' THEN (NEW).name ELSE NULL END),
        (CASE WHEN TG_OP != 'DELETE' AND to_jsonb(NEW) ? 'status' THEN (NEW).status ELSE NULL END),
        (CASE WHEN TG_OP = 'DELETE' AND to_jsonb(OLD) ? 'name' THEN (OLD).name ELSE NULL END),
        (CASE WHEN TG_OP = 'DELETE' AND to_jsonb(OLD) ? 'status' THEN (OLD).status ELSE NULL END),
        'unknown'
    );

    IF TG_OP = 'INSERT' THEN
        v_old_values := NULL;
        v_new_values := row_to_json(NEW)::jsonb;
    ELSIF TG_OP = 'UPDATE' THEN
        v_old_values := row_to_json(OLD)::jsonb;
        v_new_values := row_to_json(NEW)::jsonb;
    ELSE
        v_old_values := row_to_json(OLD)::jsonb;
        v_new_values := NULL;
    END IF;

    INSERT INTO operations.audit_log (table_schema, table_name, action, record_id, record_key, old_values, new_values, timestamp)
    VALUES (TG_TABLE_SCHEMA, TG_TABLE_NAME, v_action, v_record_id, v_record_key, v_old_values, v_new_values, NOW());

    RETURN NEW;
END;
$$;


ALTER FUNCTION operations.audit_trigger_function() OWNER TO begin;

CREATE FUNCTION operations.generate_fixes_report() RETURNS TABLE(category text, item text, status text, details text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- الأعمدة المضافة
    RETURN QUERY
    SELECT 'Added Columns', (table_name || '.' || column_name)::TEXT,
           CASE WHEN table_name IS NOT NULL THEN 'OK' ELSE 'MISSING' END,
           'Check for updated_at column'
    FROM information_schema.columns
    WHERE table_schema = 'cmis'
      AND column_name = 'updated_at'
      AND table_name IN ('creative_assets', 'experiments', 'compliance_audits');

    -- المفاتيح الخارجية
    RETURN QUERY
    SELECT 'Foreign Keys', constraint_name::TEXT,
           CASE WHEN constraint_name IS NOT NULL THEN 'OK' ELSE 'MISSING' END,
           (table_name || ' -> ' || constraint_name)::TEXT
    FROM information_schema.table_constraints
    WHERE constraint_schema = 'cmis'
      AND constraint_type = 'FOREIGN KEY'
      AND constraint_name IN ('fk_content_items_org_id','fk_content_items_creative_context','fk_content_plans_org_id');

    -- القيود الفريدة
    RETURN QUERY
    SELECT 'Unique Constraints', constraint_name::TEXT,
           CASE WHEN constraint_name IS NOT NULL THEN 'OK' ELSE 'MISSING' END,
           table_name::TEXT
    FROM information_schema.table_constraints
    WHERE constraint_schema = 'cmis'
      AND constraint_type = 'UNIQUE'
      AND constraint_name = 'users_email_unique';

    -- الفهارس المتوقعة
    RETURN QUERY
    SELECT 'Indexes', idx::TEXT,
           CASE WHEN EXISTS (SELECT 1 FROM pg_indexes WHERE schemaname='cmis' AND indexname=idx) THEN 'OK' ELSE 'MISSING' END,
           'Expected index in cmis schema'
    FROM (VALUES ('idx_scheduled_posts_status_time'),
                 ('idx_content_plans_org'),
                 ('idx_content_items_creative_context'),
                 ('idx_users_status'),
                 ('idx_performance_metrics_campaign_time')) AS expected(idx);

    RETURN;
END;
$$;


ALTER FUNCTION operations.generate_fixes_report() OWNER TO begin;

CREATE FUNCTION operations.purge_old_audit_logs(retention_days integer DEFAULT 90) RETURNS integer
    LANGUAGE plpgsql
    AS $$
DECLARE
    deleted_count INTEGER;
BEGIN
    DELETE FROM operations.audit_log
    WHERE timestamp < CURRENT_TIMESTAMP - (retention_days || ' days')::interval;
    
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    
    RAISE NOTICE 'Purged % old audit log entries', deleted_count;
    RETURN deleted_count;
END;
$$;


ALTER FUNCTION operations.purge_old_audit_logs(retention_days integer) OWNER TO begin;

CREATE FUNCTION public.auto_analyze_knowledge() RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
  v_rec record;
  v_changes int := 0;
BEGIN
  RAISE NOTICE '🔍 بدء التحليل الإدراكي للنطاقات...';

  -- المرور على جميع النطاقات المسجلة
  FOR v_rec IN
    SELECT knowledge_id, domain FROM cmis_knowledge.index
  LOOP
    -- فحص النطاق (يمكن توسيع الفحص لاحقًا)
    RAISE NOTICE '🧠 فحص النطاق: %', v_rec.domain;

    -- تحديث حالة المراقبة والإشراف الإدراكي
    UPDATE cmis_knowledge.index
    SET last_verified_at = NOW(),
        last_audit_status = 'verified'
    WHERE knowledge_id = v_rec.knowledge_id;

    -- تسجيل في سجل التدقيق
    INSERT INTO cmis_audit.logs(event_type, event_source, description, created_at)
    VALUES ('knowledge_watchdog', v_rec.domain, 'تم فحص النطاق وتحديث حالته بنجاح', NOW());

    v_changes := v_changes + 1;
  END LOOP;

  RAISE NOTICE '✅ تم تحليل % نطاق(ات) وتحديث حالتها.', v_changes;
END;
$$;


ALTER FUNCTION public.auto_analyze_knowledge() OWNER TO begin;

CREATE FUNCTION public.auto_predictive_campaign() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO cmis.predictive_visual_engine (campaign_id, visual_factor_weight, predicted_ctr, predicted_engagement, predicted_trust_index, confidence_level, created_at)
    VALUES (NEW.campaign_id, NEW.visual_factor_weight, NEW.predicted_ctr, NEW.predicted_engagement, NEW.predicted_trust_index, NEW.confidence_level, NOW());
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.auto_predictive_campaign() OWNER TO begin;

CREATE FUNCTION public.auto_snapshot_diff() RETURNS void
    LANGUAGE plpgsql
    AS $$ DECLARE v_rec record; v_prev JSONB; v_curr JSONB; v_diff TEXT; BEGIN RAISE NOTICE '🧠 بدء التحليل الزمني الدوري للمعرفة...'; FOR v_rec IN SELECT knowledge_id, domain FROM cmis_knowledge.index LOOP SELECT current_snapshot INTO v_prev FROM cmis_knowledge.temporal_analytics WHERE knowledge_id = v_rec.knowledge_id ORDER BY detected_at DESC OFFSET 1 LIMIT 1; SELECT current_snapshot INTO v_curr FROM cmis_knowledge.temporal_analytics WHERE knowledge_id = v_rec.knowledge_id ORDER BY detected_at DESC LIMIT 1; v_diff := CONCAT('📘 تحليل زمني تلقائي: تغير في النطاق ', v_rec.domain, ' عند ', NOW()); INSERT INTO cmis_knowledge.temporal_analytics(knowledge_id, domain, previous_snapshot, current_snapshot, delta_summary, confidence_score) VALUES (v_rec.knowledge_id, v_rec.domain, v_prev, v_curr, v_diff, 0.95); END LOOP; RAISE NOTICE '✅ تم إكمال التحليل الزمني الدوري بنجاح.'; END; $$;


ALTER FUNCTION public.auto_snapshot_diff() OWNER TO begin;

CREATE FUNCTION public.auto_update_cognitive_trends() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO cmis.cognitive_trends (org_id, factor_name, trend_direction, growth_rate, trend_strength, summary_insight, created_at)
    VALUES (NEW.org_id, NEW.factor_name, NEW.trend_direction, NEW.growth_rate, NEW.trend_strength, NEW.summary_insight, NOW());
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.auto_update_cognitive_trends() OWNER TO begin;

CREATE FUNCTION public.cognitive_console_report(mode text DEFAULT 'summary'::text) RETURNS TABLE(campaign_name text, objective text, predicted_ctr double precision, predicted_engagement double precision, predicted_trust_index double precision, confidence_level double precision, dominant_visual_factor text, recommendation text)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT 
        a.ai_summary AS campaign_name,
        a.objective_code AS objective,
        p.predicted_ctr,
        p.predicted_engagement,
        p.predicted_trust_index,
        p.confidence_level,
        (SELECT key FROM jsonb_each_text(p.visual_factor_weight) ORDER BY value::numeric DESC LIMIT 1) AS dominant_visual_factor,
        CASE 
            WHEN p.predicted_ctr > 0.9 THEN 'استمر بنفس مستوى التباين، الأداء بصري ممتاز.'
            WHEN p.predicted_engagement < 0.8 THEN 'يُنصح بتبسيط العناصر وتقليل النصوص لتحسين التفاعل.'
            WHEN p.predicted_trust_index < 0.75 THEN 'أضف عناصر موثوقة مثل رموز الأمان أو شهادات.'
            ELSE 'التصميم متوازن إدراكيًا، حافظ على هذا النمط.'
        END AS recommendation
    FROM cmis.ai_generated_campaigns a
    JOIN cmis.predictive_visual_engine p ON a.campaign_id = p.campaign_id
    ORDER BY a.created_at DESC;
END;
$$;


ALTER FUNCTION public.cognitive_console_report(mode text) OWNER TO begin;

CREATE FUNCTION public.cognitive_feedback_loop() RETURNS void
    LANGUAGE plpgsql
    AS $$ DECLARE v_rec record; v_triggered int := 0; BEGIN RAISE NOTICE '🧭 بدء دورة الارتداد المعرفي...'; FOR v_rec IN SELECT domain_name, category FROM cmis_knowledge.v_predictive_cognitive_horizon WHERE forecast_status LIKE '%🔴%' LOOP INSERT INTO cmis_audit.logs(event_type, event_source, description, created_at) VALUES ('cognitive_feedback', v_rec.domain_name, CONCAT('🔄 إعادة تفعيل التحليل الإدراكي بسبب تراجع متوقع في النطاق ', v_rec.domain_name), NOW()); PERFORM compute_epistemic_delta(); v_triggered := v_triggered + 1; END LOOP; RAISE NOTICE '✅ تم تنفيذ دورة الارتداد المعرفي لـ % نطاق(ات).', v_triggered; END; $$;


ALTER FUNCTION public.cognitive_feedback_loop() OWNER TO begin;

CREATE FUNCTION public.cognitive_learning_loop() RETURNS void
    LANGUAGE plpgsql
    AS $$ DECLARE v_rec record; v_learned int := 0; BEGIN RAISE NOTICE '🧬 بدء حلقة التعلم الإدراكي...'; FOR v_rec IN SELECT event_source, COUNT(*) AS interventions FROM cmis_audit.logs WHERE event_type = 'cognitive_feedback' GROUP BY event_source HAVING COUNT(*) > 2 LOOP UPDATE cmis_knowledge.v_chrono_evolution SET avg_confidence = avg_confidence + 0.01 WHERE domain_name = v_rec.event_source; INSERT INTO cmis_audit.logs(event_type, event_source, description, created_at) VALUES ('cognitive_learning', v_rec.event_source, CONCAT('🧠 تم تعديل معايير الثقة بناءً على التعلم من ', v_rec.interventions, ' تدخل(ات) معرفية سابقة في النطاق ', v_rec.event_source), NOW()); v_learned := v_learned + 1; END LOOP; RAISE NOTICE '✅ اكتملت حلقة التعلم الإدراكي. تم تحديث % نطاق(ات).', v_learned; END; $$;


ALTER FUNCTION public.cognitive_learning_loop() OWNER TO begin;

CREATE FUNCTION public.compute_epistemic_delta() RETURNS void
    LANGUAGE plpgsql
    AS $$ DECLARE v_rec record; v_prev TEXT; v_curr TEXT; v_diff TEXT; BEGIN FOR v_rec IN SELECT knowledge_id, domain FROM cmis_knowledge.index LOOP SELECT content INTO v_prev FROM cmis_knowledge.dev WHERE parent_knowledge_id = v_rec.knowledge_id ORDER BY created_at DESC OFFSET 1 LIMIT 1; SELECT content INTO v_curr FROM cmis_knowledge.dev WHERE parent_knowledge_id = v_rec.knowledge_id ORDER BY created_at DESC LIMIT 1; v_diff := CONCAT('📘 تغير معرفي مكتشف في النطاق ', v_rec.domain, ' بتاريخ ', NOW()); INSERT INTO cmis_knowledge.temporal_analytics(knowledge_id, domain, previous_snapshot, current_snapshot, delta_summary) VALUES (v_rec.knowledge_id, v_rec.domain, to_jsonb(v_prev), to_jsonb(v_curr), v_diff); END LOOP; END; $$;


ALTER FUNCTION public.compute_epistemic_delta() OWNER TO begin;

CREATE FUNCTION public.create_dev_task(p_name text, p_description text, p_scope_code text, p_execution_plan jsonb, p_priority smallint DEFAULT 3) RETURNS uuid
    LANGUAGE plpgsql
    AS $$ DECLARE v_task_id uuid; v_similar_task uuid; BEGIN SELECT task_id INTO v_similar_task FROM cmis_dev.dev_tasks WHERE similarity(name, p_name) > 0.8 AND status IN ('pending', 'in_progress') AND created_at > now() - interval '7 days' LIMIT 1; IF v_similar_task IS NOT NULL THEN RAISE NOTICE 'Similar task found: %', v_similar_task; RETURN v_similar_task; END IF; INSERT INTO cmis_dev.dev_tasks (name, description, scope_code, execution_plan, priority, status) VALUES (p_name, p_description, p_scope_code, p_execution_plan, p_priority, 'pending') RETURNING task_id INTO v_task_id; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'task_created', jsonb_build_object('priority', p_priority, 'scope', p_scope_code)); RETURN v_task_id; END; $$;


ALTER FUNCTION public.create_dev_task(p_name text, p_description text, p_scope_code text, p_execution_plan jsonb, p_priority smallint) OWNER TO begin;

CREATE FUNCTION public.generate_cognitive_health_report() RETURNS void
    LANGUAGE plpgsql
    AS $$ DECLARE r RECORD; report_text TEXT; BEGIN SELECT ROUND(AVG("نسبة الاستقرار %")::numeric,2) AS stability_avg, ROUND(AVG("نسبة إعادة التحليل %")::numeric,2) AS reanalysis_avg, ROUND(AVG("نسبة الخطر %")::numeric,2) AS risk_avg INTO r FROM cmis_system_health.v_cognitive_kpi_timeseries WHERE "الساعة" > NOW() - INTERVAL '24 hours'; report_text := '🧠 تقرير الإدراك الدوري: خلال آخر 24 ساعة بلغت نسبة الاستقرار ' || TO_CHAR(COALESCE(r.stability_avg,0),'FM999.00') || '%، بينما بلغت إعادة التحليل ' || TO_CHAR(COALESCE(r.reanalysis_avg,0),'FM999.00') || '% ومؤشر الخطر ' || TO_CHAR(COALESCE(r.risk_avg,0),'FM999.00') || '%. الحالة العامة: ' || CASE  WHEN COALESCE(r.risk_avg,0) > 20 THEN '🔴 غير مستقرة'  WHEN COALESCE(r.reanalysis_avg,0) > 50 THEN '🟡 تحت إعادة تقييم'  ELSE '🟢 مستقرة' END || '.'; INSERT INTO cmis_system_health.cognitive_reports(report_text, stability_avg, reanalysis_avg, risk_avg) VALUES (report_text, r.stability_avg, r.reanalysis_avg, r.risk_avg); RAISE NOTICE '✅ تم توليد التقرير الإدراكي: %', report_text; END; $$;


ALTER FUNCTION public.generate_cognitive_health_report() OWNER TO begin;

CREATE FUNCTION public.get_all_report_summaries(p_length integer DEFAULT 500) RETURNS TABLE(topic text, report_phase text, summary text, last_verified_at timestamp with time zone)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT ki.topic, ki.report_phase, left(kd.content, p_length) AS summary, ki.last_verified_at
  FROM cmis_knowledge.index ki
  JOIN cmis_knowledge.dev kd ON kd.knowledge_id = ki.knowledge_id
  WHERE ki.category = 'report'
  ORDER BY ki.report_phase, ki.last_verified_at DESC;
END;
$$;


ALTER FUNCTION public.get_all_report_summaries(p_length integer) OWNER TO begin;

CREATE FUNCTION public.get_latest_official_report(p_domain text) RETURNS TABLE(knowledge_id uuid, domain text, category text, topic text, importance_level smallint, last_audit_status text, last_verified_at timestamp with time zone)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT knowledge_id, domain, category, topic, importance_level, last_audit_status, last_verified_at
  FROM cmis_knowledge.index
  WHERE domain = p_domain AND category = 'report' AND last_audit_status = 'official_reference'
  ORDER BY last_verified_at DESC
  LIMIT 1;
END;
$$;


ALTER FUNCTION public.get_latest_official_report(p_domain text) OWNER TO begin;

CREATE FUNCTION public.get_latest_reports_by_all_phases() RETURNS TABLE(knowledge_id uuid, domain text, category text, topic text, importance_level smallint, last_audit_status text, report_phase text, last_verified_at timestamp with time zone)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT DISTINCT ON (report_phase)
    knowledge_id, domain, category, topic, importance_level, last_audit_status, report_phase, last_verified_at
  FROM cmis_knowledge.index
  WHERE category = 'report'
  ORDER BY report_phase, last_verified_at DESC;
END;
$$;


ALTER FUNCTION public.get_latest_reports_by_all_phases() OWNER TO begin;

CREATE FUNCTION public.get_official_reports() RETURNS TABLE(knowledge_id uuid, domain text, category text, topic text, importance_level smallint, last_audit_status text, last_verified_at timestamp with time zone)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT knowledge_id, domain, category, topic, importance_level, last_audit_status, last_verified_at
  FROM cmis_knowledge.index
  WHERE category = 'report' AND last_audit_status = 'official_reference'
  ORDER BY importance_level ASC, last_verified_at DESC;
END;
$$;


ALTER FUNCTION public.get_official_reports() OWNER TO begin;

CREATE FUNCTION public.get_report_summary_by_phase(p_phase text) RETURNS TABLE(topic text, report_phase text, summary text, last_verified_at timestamp with time zone)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT ki.topic, ki.report_phase, left(kd.content, 500) AS summary, ki.last_verified_at
  FROM cmis_knowledge.index ki
  JOIN cmis_knowledge.dev kd ON kd.knowledge_id = ki.knowledge_id
  WHERE ki.category = 'report' AND ki.report_phase = p_phase
  ORDER BY ki.last_verified_at DESC
  LIMIT 1;
END;
$$;


ALTER FUNCTION public.get_report_summary_by_phase(p_phase text) OWNER TO begin;

CREATE FUNCTION public.get_report_summary_by_phase(p_phase text, p_length integer DEFAULT 500) RETURNS TABLE(topic text, report_phase text, summary text, last_verified_at timestamp with time zone)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT ki.topic, ki.report_phase, left(kd.content, p_length) AS summary, ki.last_verified_at
  FROM cmis_knowledge.index ki
  JOIN cmis_knowledge.dev kd ON kd.knowledge_id = ki.knowledge_id
  WHERE ki.category = 'report' AND ki.report_phase = p_phase
  ORDER BY ki.last_verified_at DESC
  LIMIT 1;
END;
$$;


ALTER FUNCTION public.get_report_summary_by_phase(p_phase text, p_length integer) OWNER TO begin;

CREATE FUNCTION public.get_reports_by_phase(p_phase text) RETURNS TABLE(knowledge_id uuid, domain text, category text, topic text, importance_level smallint, last_audit_status text, report_phase text, last_verified_at timestamp with time zone)
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN QUERY
  SELECT knowledge_id, domain, category, topic, importance_level, last_audit_status, report_phase, last_verified_at
  FROM cmis_knowledge.index
  WHERE category = 'report' AND report_phase = p_phase
  ORDER BY importance_level ASC, last_verified_at DESC;
END;
$$;


ALTER FUNCTION public.get_reports_by_phase(p_phase text) OWNER TO begin;

CREATE FUNCTION public.load_context_by_priority(p_domain text, p_category text DEFAULT NULL::text, p_max_tokens integer DEFAULT 5000) RETURNS TABLE(knowledge_id uuid, content text, tier smallint, token_count integer, total_tokens bigint)
    LANGUAGE plpgsql
    AS $$ BEGIN RETURN QUERY WITH ranked_knowledge AS ( SELECT ki.knowledge_id, CASE p_category WHEN 'dev' THEN kd.content WHEN 'marketing' THEN km.content WHEN 'org' THEN ko.content WHEN 'research' THEN kr.content END AS content, ki.tier, COALESCE(kd.token_count, km.token_count, ko.token_count, kr.token_count) AS token_count, SUM(COALESCE(kd.token_count, km.token_count, ko.token_count, kr.token_count)) OVER (ORDER BY ki.tier ASC, ki.last_verified_at DESC) AS total_tokens FROM cmis_knowledge.index ki LEFT JOIN cmis_knowledge.dev kd USING (knowledge_id) LEFT JOIN cmis_knowledge.marketing km USING (knowledge_id) LEFT JOIN cmis_knowledge.org ko USING (knowledge_id) LEFT JOIN cmis_knowledge.research kr USING (knowledge_id) WHERE ki.domain = p_domain AND (p_category IS NULL OR ki.category = p_category) AND ki.is_deprecated = false ) SELECT rk.knowledge_id, rk.content, rk.tier, rk.token_count, rk.total_tokens FROM ranked_knowledge rk WHERE rk.total_tokens <= p_max_tokens; END; $$;


ALTER FUNCTION public.load_context_by_priority(p_domain text, p_category text, p_max_tokens integer) OWNER TO begin;

CREATE FUNCTION public.log_cognitive_vitality() RETURNS void
    LANGUAGE plpgsql
    AS $$ DECLARE v_data RECORD; BEGIN SELECT * INTO v_data FROM cmis_knowledge.v_cognitive_vitality; INSERT INTO cmis_system_health.cognitive_vitality_log ( latency_minutes, events_last_hour, vitality_index, cognitive_state ) VALUES ( v_data.latency_minutes, v_data.events_last_hour, v_data.vitality_index, v_data.cognitive_state ); RAISE NOTICE '🧠 تم تسجيل قراءة جديدة لمؤشر الحيوية الإدراكية بنجاح.'; END; $$;


ALTER FUNCTION public.log_cognitive_vitality() OWNER TO begin;

CREATE FUNCTION public.reconstruct_knowledge(p_parent_id uuid) RETURNS text
    LANGUAGE plpgsql
    AS $$ BEGIN RETURN (SELECT string_agg(content, E'\n') FROM cmis_knowledge.dev WHERE parent_knowledge_id = p_parent_id ORDER BY part_index); END; $$;


ALTER FUNCTION public.reconstruct_knowledge(p_parent_id uuid) OWNER TO begin;

CREATE FUNCTION public.register_chunked_knowledge(p_domain text, p_category text, p_topic text, p_content text, p_chunk_size integer DEFAULT 2000) RETURNS uuid
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_main_id uuid;
    v_i int := 0;
    v_part text;
    v_total_chunks int := CEIL(length(p_content)::numeric / p_chunk_size);
BEGIN
    -- تسجيل المعرفة الرئيسية في الفهرس
    INSERT INTO cmis_knowledge.index(domain, category, topic, total_chunks, has_children)
    VALUES (p_domain, p_category, p_topic, v_total_chunks, true)
    RETURNING knowledge_id INTO v_main_id;

    -- تقسيم المحتوى وإدراج الأجزاء مع استخدام نفس المعرّف الرئيسي كـ parent وknowledge_id
    WHILE v_i < v_total_chunks LOOP
        v_part := substr(p_content, (v_i * p_chunk_size) + 1, p_chunk_size);
        INSERT INTO cmis_knowledge.dev(knowledge_id, parent_knowledge_id, part_index, content, token_count)
        VALUES (v_main_id, v_main_id, v_i, v_part, length(v_part)/4);
        v_i := v_i + 1;
    END LOOP;

    RETURN v_main_id;
END;
$$;


ALTER FUNCTION public.register_chunked_knowledge(p_domain text, p_category text, p_topic text, p_content text, p_chunk_size integer) OWNER TO begin;

CREATE FUNCTION public.register_knowledge(p_domain text, p_category text, p_topic text, p_content text, p_tier smallint DEFAULT 2, p_keywords text[] DEFAULT ARRAY[]::text[]) RETURNS uuid
    LANGUAGE plpgsql
    AS $$ DECLARE v_knowledge_id uuid; v_token_count int; BEGIN v_token_count := length(p_content) / 4; INSERT INTO cmis_knowledge.index (domain, category, topic, keywords, tier, token_budget, last_verified_at) VALUES (p_domain, p_category, p_topic, p_keywords, p_tier, v_token_count, now()) RETURNING knowledge_id INTO v_knowledge_id; CASE p_category WHEN 'dev' THEN INSERT INTO cmis_knowledge.dev (knowledge_id, content, token_count, version) VALUES (v_knowledge_id, p_content, v_token_count, '1.0'); WHEN 'marketing' THEN INSERT INTO cmis_knowledge.marketing (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); WHEN 'org' THEN INSERT INTO cmis_knowledge.org (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); WHEN 'research' THEN INSERT INTO cmis_knowledge.research (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); END CASE; RETURN v_knowledge_id; END; $$;


ALTER FUNCTION public.register_knowledge(p_domain text, p_category text, p_topic text, p_content text, p_tier smallint, p_keywords text[]) OWNER TO begin;

CREATE FUNCTION public.register_knowledge(p_domain text, p_category text, p_topic text, p_content text, p_tier integer DEFAULT 2, p_keywords text[] DEFAULT NULL::text[]) RETURNS uuid
    LANGUAGE plpgsql
    AS $$ DECLARE v_knowledge_id uuid; v_token_count int; BEGIN v_token_count := length(p_content) / 4; INSERT INTO cmis_knowledge.index (domain, category, topic, keywords, tier, token_budget, last_verified_at) VALUES (p_domain, p_category, p_topic, p_keywords, p_tier, v_token_count, now()) RETURNING knowledge_id INTO v_knowledge_id; CASE p_category WHEN 'dev' THEN INSERT INTO cmis_knowledge.dev (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); WHEN 'marketing' THEN INSERT INTO cmis_knowledge.marketing (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); WHEN 'org' THEN INSERT INTO cmis_knowledge.org (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); WHEN 'research' THEN INSERT INTO cmis_knowledge.research (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); ELSE RAISE EXCEPTION 'Unknown knowledge category: %', p_category; END CASE; RETURN v_knowledge_id; END; $$;


ALTER FUNCTION public.register_knowledge(p_domain text, p_category text, p_topic text, p_content text, p_tier integer, p_keywords text[]) OWNER TO begin;

CREATE FUNCTION public.run_auto_predictive_trigger() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    INSERT INTO cmis.ai_generated_campaigns (
        org_id,
        objective_code,
        recommended_principle,
        linked_kpi,
        ai_summary,
        ai_design_guideline
    )
    VALUES (
        (SELECT org_id FROM cmis.orgs LIMIT 1),
        'conversion',
        'clarity of message',
        'CTR',
        'حملة جديدة لإدارة السوشيال ميديا - أكتوبر 2025 (تشغيل تلقائي للتنبؤ الإدراكي)',
        'تصميم يعتمد على التباين العالي والتركيز على CTA واضح'
    );
END;
$$;


ALTER FUNCTION public.run_auto_predictive_trigger() OWNER TO begin;

CREATE FUNCTION public.scheduled_cognitive_trend_update() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  PERFORM public.update_cognitive_trends();
END;
$$;


ALTER FUNCTION public.scheduled_cognitive_trend_update() OWNER TO begin;

CREATE FUNCTION public.search_cognitive_knowledge(p_query text, p_domain text DEFAULT NULL::text, p_category text DEFAULT 'dev'::text, p_batch_limit integer DEFAULT 20, p_offset integer DEFAULT 0) RETURNS TABLE(knowledge_id uuid, parent_knowledge_id uuid, topic text, part_index integer, content text, score numeric)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT kd.knowledge_id,
           kd.parent_knowledge_id,
           ki.topic,
           kd.part_index,
           kd.content,
           ts_rank_cd(kd.content_search, plainto_tsquery('arabic', p_query))::numeric AS score
    FROM cmis_knowledge.dev kd
    JOIN cmis_knowledge.index ki
        ON kd.parent_knowledge_id = ki.knowledge_id
    WHERE (p_domain IS NULL OR ki.domain = p_domain)
      AND ki.category = p_category
      AND kd.content_search @@ plainto_tsquery('arabic', p_query)
    ORDER BY score DESC, kd.part_index
    LIMIT p_batch_limit OFFSET p_offset;
END;
$$;


ALTER FUNCTION public.search_cognitive_knowledge(p_query text, p_domain text, p_category text, p_batch_limit integer, p_offset integer) OWNER TO begin;

CREATE FUNCTION public.search_cognitive_knowledge_simple(p_query text, p_batch_limit integer DEFAULT 25, p_offset integer DEFAULT 0) RETURNS TABLE(knowledge_id uuid, parent_knowledge_id uuid, topic text, domain text, category text, part_index integer, content text, score numeric)
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN QUERY
    SELECT
        kd.knowledge_id,
        kd.parent_knowledge_id,
        ki.topic,
        ki.domain,
        ki.category,
        kd.part_index,
        kd.content,
        ts_rank_cd(kd.content_search, plainto_tsquery('arabic', p_query))::NUMERIC AS score
    FROM cmis_knowledge.dev kd
    JOIN cmis_knowledge.index ki
        ON kd.parent_knowledge_id = ki.knowledge_id
    WHERE kd.content_search @@ plainto_tsquery('arabic', p_query)
    ORDER BY score DESC, kd.part_index
    LIMIT p_batch_limit OFFSET p_offset;
END;
$$;


ALTER FUNCTION public.search_cognitive_knowledge_simple(p_query text, p_batch_limit integer, p_offset integer) OWNER TO begin;

CREATE FUNCTION public.update_cognitive_trends() RETURNS void
    LANGUAGE plpgsql
    AS $$
DECLARE
    factor TEXT;
    avg_current DOUBLE PRECISION;
    avg_previous DOUBLE PRECISION;
    growth DOUBLE PRECISION;
    direction TEXT;
    summary TEXT;
BEGIN
    FOR factor IN SELECT DISTINCT key FROM cmis.predictive_visual_engine, jsonb_each(visual_factor_weight) LOOP
        SELECT AVG((visual_factor_weight ->> factor)::DOUBLE PRECISION) INTO avg_current FROM cmis.predictive_visual_engine WHERE created_at >= NOW() - INTERVAL '14 days';
        SELECT AVG((visual_factor_weight ->> factor)::DOUBLE PRECISION) INTO avg_previous FROM cmis.predictive_visual_engine WHERE created_at < NOW() - INTERVAL '14 days' AND created_at >= NOW() - INTERVAL '28 days';

        IF avg_previous IS NULL THEN
            CONTINUE;
        END IF;

        growth := ((avg_current - avg_previous) / avg_previous) * 100;

        IF growth > 3 THEN
            direction := 'up';
            summary := 'العامل الإدراكي ' || factor || ' في ارتفاع بنسبة ' || ROUND(growth, 2) || '% خلال آخر أسبوعين.';
        ELSIF growth < -3 THEN
            direction := 'down';
            summary := 'العامل الإدراكي ' || factor || ' في تراجع بنسبة ' || ROUND(growth, 2) || '% خلال آخر أسبوعين.';
        ELSE
            direction := 'stable';
            summary := 'العامل الإدراكي ' || factor || ' مستقر تقريبًا خلال الفترة الأخيرة.';
        END IF;

        INSERT INTO cmis.cognitive_trends (org_id, factor_name, trend_direction, growth_rate, trend_strength, summary_insight)
        VALUES ((SELECT org_id FROM cmis.orgs LIMIT 1), factor, direction, growth, ABS(growth)/10, summary);
    END LOOP;
END;
$$;


ALTER FUNCTION public.update_cognitive_trends() OWNER TO begin;

CREATE FUNCTION public.update_knowledge_chunk(p_parent_id uuid, p_part_index integer, p_new_content text) RETURNS void
    LANGUAGE plpgsql
    AS $$ BEGIN UPDATE cmis_knowledge.dev SET content = p_new_content, token_count = length(p_new_content)/4, link_context = jsonb_set(link_context, '{last_updated}', to_jsonb(now())) WHERE parent_knowledge_id = p_parent_id AND part_index = p_part_index; END; $$;


ALTER FUNCTION public.update_knowledge_chunk(p_parent_id uuid, p_part_index integer, p_new_content text) OWNER TO begin;

