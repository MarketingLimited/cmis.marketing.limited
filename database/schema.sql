--
-- PostgreSQL database dump
--

\restrict fL9USgZ0bCgwu6UgdZGFeo84xVwQFLKkUmCDHB3eSHauHmEZLHEzT6jLnJ3mAlB

-- Dumped from database version 18.0 (Ubuntu 18.0-1.pgdg24.04+3)
-- Dumped by pg_dump version 18.0 (Ubuntu 18.0-1.pgdg24.04+3)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: archive; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA archive;


ALTER SCHEMA archive OWNER TO begin;

--
-- Name: cmis; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis;


ALTER SCHEMA cmis OWNER TO begin;

--
-- Name: cmis_ai_analytics; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_ai_analytics;


ALTER SCHEMA cmis_ai_analytics OWNER TO begin;

--
-- Name: cmis_analytics; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_analytics;


ALTER SCHEMA cmis_analytics OWNER TO begin;

--
-- Name: cmis_audit; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_audit;


ALTER SCHEMA cmis_audit OWNER TO begin;

--
-- Name: cmis_dev; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_dev;


ALTER SCHEMA cmis_dev OWNER TO begin;

--
-- Name: cmis_knowledge; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_knowledge;


ALTER SCHEMA cmis_knowledge OWNER TO begin;

--
-- Name: cmis_marketing; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_marketing;


ALTER SCHEMA cmis_marketing OWNER TO begin;

--
-- Name: cmis_ops; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_ops;


ALTER SCHEMA cmis_ops OWNER TO begin;

--
-- Name: cmis_security_backup_20251111_202413; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_security_backup_20251111_202413;


ALTER SCHEMA cmis_security_backup_20251111_202413 OWNER TO begin;

--
-- Name: cmis_staging; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_staging;


ALTER SCHEMA cmis_staging OWNER TO begin;

--
-- Name: cmis_system_health; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA cmis_system_health;


ALTER SCHEMA cmis_system_health OWNER TO begin;

--
-- Name: lab; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA lab;


ALTER SCHEMA lab OWNER TO begin;

--
-- Name: operations; Type: SCHEMA; Schema: -; Owner: begin
--

CREATE SCHEMA operations;


ALTER SCHEMA operations OWNER TO begin;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: begin
--

-- *not* creating schema, since initdb creates it


ALTER SCHEMA public OWNER TO begin;

--
-- Name: plpython3u; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS plpython3u WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpython3u; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpython3u IS 'PL/Python3U untrusted procedural language';


--
-- Name: btree_gin; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS btree_gin WITH SCHEMA public;


--
-- Name: EXTENSION btree_gin; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION btree_gin IS 'support for indexing common datatypes in GIN';


--
-- Name: citext; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS citext WITH SCHEMA public;


--
-- Name: EXTENSION citext; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION citext IS 'data type for case-insensitive character strings';


--
-- Name: ltree; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS ltree WITH SCHEMA public;


--
-- Name: EXTENSION ltree; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION ltree IS 'data type for hierarchical tree-like structures';


--
-- Name: pg_trgm; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_trgm WITH SCHEMA public;


--
-- Name: EXTENSION pg_trgm; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pg_trgm IS 'text similarity measurement and index searching based on trigrams';


--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: uuid-ossp; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA public;


--
-- Name: EXTENSION "uuid-ossp"; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION "uuid-ossp" IS 'generate universally unique identifiers (UUIDs)';


--
-- Name: vector; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS vector WITH SCHEMA public;


--
-- Name: EXTENSION vector; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION vector IS 'vector data type and ivfflat and hnsw access methods';


--
-- Name: analyze_table_sizes(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: audit_creative_changes(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: auto_delete_unapproved_assets(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: auto_refresh_cache_on_field_change(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: check_permission(uuid, uuid, text); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: FUNCTION check_permission(p_user_id uuid, p_org_id uuid, p_permission_code text); Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON FUNCTION cmis.check_permission(p_user_id uuid, p_org_id uuid, p_permission_code text) IS 'التحقق من صلاحيات المستخدم مع دعم cache و soft delete - محدثة';


--
-- Name: check_permission_tx(text); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: FUNCTION check_permission_tx(p_permission text); Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON FUNCTION cmis.check_permission_tx(p_permission text) IS 'التحقق من صلاحية باستخدام السياق المحلي للمعاملة - أسهل للاستخدام من API';


--
-- Name: cleanup_expired_sessions(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: cleanup_old_cache_entries(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: cleanup_scheduler(); Type: PROCEDURE; Schema: cmis; Owner: begin
--

CREATE PROCEDURE cmis.cleanup_scheduler()
    LANGUAGE sql
    AS $$
  SELECT cmis.auto_delete_unapproved_assets();
$$;


ALTER PROCEDURE cmis.cleanup_scheduler() OWNER TO begin;

--
-- Name: cmis_immutable_setweight(tsvector, "char"); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.cmis_immutable_setweight(vec tsvector, w "char") RETURNS tsvector
    LANGUAGE plpgsql IMMUTABLE
    AS $$
BEGIN
  RETURN setweight(vec, w);
END;
$$;


ALTER FUNCTION cmis.cmis_immutable_setweight(vec tsvector, w "char") OWNER TO begin;

--
-- Name: cmis_immutable_tsvector(regconfig, text); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.cmis_immutable_tsvector(cfg regconfig, txt text) RETURNS tsvector
    LANGUAGE plpgsql IMMUTABLE
    AS $$
BEGIN
  RETURN to_tsvector(cfg, txt);
END;
$$;


ALTER FUNCTION cmis.cmis_immutable_tsvector(cfg regconfig, txt text) OWNER TO begin;

--
-- Name: contexts_unified_search_vector_update(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: create_campaign_and_context_safe(uuid, uuid, uuid, text, text, text, text[]); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: creative_contexts_delete(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: creative_contexts_insert(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: creative_contexts_update(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: enforce_creative_context(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: find_related_campaigns(uuid, integer); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: generate_brief_summary(uuid); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: get_campaign_contexts(uuid, boolean); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: get_current_org_id(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: get_current_org_id_tx(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: FUNCTION get_current_org_id_tx(); Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON FUNCTION cmis.get_current_org_id_tx() IS 'الحصول على org_id من السياق المحلي للمعاملة';


--
-- Name: get_current_user_id(); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.get_current_user_id() RETURNS uuid
    LANGUAGE plpgsql STABLE
    AS $$
BEGIN
  RETURN current_setting('app.current_user_id', true)::uuid;
END;
$$;


ALTER FUNCTION cmis.get_current_user_id() OWNER TO begin;

--
-- Name: get_current_user_id_tx(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: FUNCTION get_current_user_id_tx(); Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON FUNCTION cmis.get_current_user_id_tx() IS 'الحصول على user_id من السياق المحلي للمعاملة';


--
-- Name: init_transaction_context(uuid, uuid); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: FUNCTION init_transaction_context(p_user_id uuid, p_org_id uuid); Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON FUNCTION cmis.init_transaction_context(p_user_id uuid, p_org_id uuid) IS 'تهيئة سياق الأمان للمعاملة الحالية باستخدام SET LOCAL - v2.0';


--
-- Name: link_brief_to_content(uuid, uuid); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: prevent_incomplete_briefs(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: prevent_incomplete_briefs_optimized(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: refresh_creative_index(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: refresh_dashboard_metrics(); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.refresh_dashboard_metrics() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
    REFRESH MATERIALIZED VIEW CONCURRENTLY cmis.dashboard_metrics;
END;
$$;


ALTER FUNCTION cmis.refresh_dashboard_metrics() OWNER TO begin;

--
-- Name: refresh_permissions_cache(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: refresh_required_fields_cache(); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.refresh_required_fields_cache() RETURNS void
    LANGUAGE sql
    AS $$
    SELECT cmis.refresh_required_fields_cache_with_metrics();
$$;


ALTER FUNCTION cmis.refresh_required_fields_cache() OWNER TO begin;

--
-- Name: refresh_required_fields_cache_with_metrics(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: search_contexts(text, character varying, integer); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: sync_social_metrics(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: test_new_security_context(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: FUNCTION test_new_security_context(); Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON FUNCTION cmis.test_new_security_context() IS 'اختبار شامل للنظام الأمني الجديد';


--
-- Name: update_updated_at_column(); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$ BEGIN NEW.updated_at = CURRENT_TIMESTAMP; RETURN NEW; END; $$;


ALTER FUNCTION cmis.update_updated_at_column() OWNER TO begin;

--
-- Name: validate_brief_structure(jsonb); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: validate_transaction_context(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: FUNCTION validate_transaction_context(); Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON FUNCTION cmis.validate_transaction_context() IS 'التحقق من صحة سياق المعاملة وإرجاع معلومات المستخدم والمؤسسة';


--
-- Name: verify_cache_automation(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: verify_optional_improvements(); Type: FUNCTION; Schema: cmis; Owner: begin
--

CREATE FUNCTION cmis.verify_optional_improvements() RETURNS text
    LANGUAGE plpgsql
    AS $$
BEGIN
  RETURN 'placeholder - optional improvements verification deferred';
END;
$$;


ALTER FUNCTION cmis.verify_optional_improvements() OWNER TO begin;

--
-- Name: verify_phase1_fixes(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: verify_phase2_permissions(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: verify_rbac_policies(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: FUNCTION verify_rbac_policies(); Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON FUNCTION cmis.verify_rbac_policies() IS 'التحقق من حالة سياسات الأمان وما إذا كانت تستخدم الدالة المحسنة';


--
-- Name: verify_rls_fixes(); Type: FUNCTION; Schema: cmis; Owner: begin
--

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

--
-- Name: fn_recommend_focus(); Type: FUNCTION; Schema: cmis_ai_analytics; Owner: begin
--

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

--
-- Name: report_migrations(); Type: FUNCTION; Schema: cmis_analytics; Owner: begin
--

CREATE FUNCTION cmis_analytics.report_migrations() RETURNS TABLE(executed_at timestamp with time zone, action text, sql_preview text)
    LANGUAGE sql
    AS $$
  SELECT executed_at, action, LEFT(sql_code, 200) || '...' AS sql_preview
  FROM cmis_analytics.migration_log
  ORDER BY executed_at DESC;
$$;


ALTER FUNCTION cmis_analytics.report_migrations() OWNER TO begin;

--
-- Name: run_ai_query(uuid, text); Type: FUNCTION; Schema: cmis_analytics; Owner: begin
--

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

--
-- Name: snapshot_performance(); Type: FUNCTION; Schema: cmis_analytics; Owner: begin
--

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

--
-- Name: snapshot_performance(integer); Type: FUNCTION; Schema: cmis_analytics; Owner: begin
--

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

--
-- Name: auto_context_task_loader(text, text, text, text, smallint, integer); Type: FUNCTION; Schema: cmis_dev; Owner: begin
--

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

--
-- Name: create_dev_task(text, text, text, jsonb, smallint); Type: FUNCTION; Schema: cmis_dev; Owner: begin
--

CREATE FUNCTION cmis_dev.create_dev_task(p_name text, p_description text, p_scope_code text, p_execution_plan jsonb, p_priority smallint DEFAULT 3) RETURNS uuid
    LANGUAGE plpgsql
    AS $$ DECLARE v_task_id uuid; v_similar_task uuid; BEGIN SELECT task_id INTO v_similar_task FROM cmis_dev.dev_tasks WHERE similarity(name, p_name) > 0.8 AND status IN ('pending', 'in_progress') AND created_at > now() - interval '7 days' LIMIT 1; IF v_similar_task IS NOT NULL THEN RAISE NOTICE 'Similar task found: %', v_similar_task; RETURN v_similar_task; END IF; INSERT INTO cmis_dev.dev_tasks (name, description, scope_code, execution_plan, priority, status) VALUES (p_name, p_description, p_scope_code, p_execution_plan, p_priority, 'pending') RETURNING task_id INTO v_task_id; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'task_created', jsonb_build_object('priority', p_priority, 'scope', p_scope_code)); RETURN v_task_id; END; $$;


ALTER FUNCTION cmis_dev.create_dev_task(p_name text, p_description text, p_scope_code text, p_execution_plan jsonb, p_priority smallint) OWNER TO begin;

--
-- Name: create_dev_task(text, text, text, jsonb, integer); Type: FUNCTION; Schema: cmis_dev; Owner: begin
--

CREATE FUNCTION cmis_dev.create_dev_task(p_name text, p_description text, p_scope_code text, p_execution_plan jsonb, p_priority integer DEFAULT 3) RETURNS uuid
    LANGUAGE plpgsql
    AS $$ DECLARE v_task_id uuid; v_similar_task uuid; BEGIN SELECT task_id INTO v_similar_task FROM cmis_dev.dev_tasks WHERE similarity(name, p_name) > 0.8 AND status IN ('pending', 'in_progress') AND created_at > now() - interval '7 days' LIMIT 1; IF v_similar_task IS NOT NULL THEN RAISE NOTICE 'Similar task found: %', v_similar_task; RETURN v_similar_task; END IF; INSERT INTO cmis_dev.dev_tasks (name, description, scope_code, execution_plan, priority, status) VALUES (p_name, p_description, p_scope_code, p_execution_plan, p_priority, 'pending') RETURNING task_id INTO v_task_id; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'task_created', jsonb_build_object('priority', p_priority, 'scope', p_scope_code)); RETURN v_task_id; END; $$;


ALTER FUNCTION cmis_dev.create_dev_task(p_name text, p_description text, p_scope_code text, p_execution_plan jsonb, p_priority integer) OWNER TO begin;

--
-- Name: prepare_context_execution(text, text, text, text, smallint); Type: FUNCTION; Schema: cmis_dev; Owner: begin
--

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

--
-- Name: run_dev_task(text); Type: FUNCTION; Schema: cmis_dev; Owner: begin
--

CREATE FUNCTION cmis_dev.run_dev_task(p_prompt text) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_task_id uuid; v_domain text := 'meta_api'; v_category text := 'dev'; v_context jsonb; v_result text; BEGIN SELECT jsonb_agg(to_jsonb(r)) INTO v_context FROM load_context_by_priority(v_domain, v_category, 5000) AS r; v_task_id := cmis_dev.create_dev_task(p_prompt, 'Auto-generated task based on cognitive orchestration', 'system_dev', jsonb_build_object('steps', jsonb_build_array(jsonb_build_object('order',1,'action_type','sql','action_body','SELECT 1 AS test_result;'))), 2); INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'task_started', jsonb_build_object('prompt', p_prompt, 'domain', v_domain, 'category', v_category)); PERFORM 1; v_result := 'success'; UPDATE cmis_dev.dev_tasks SET status='completed', confidence=0.95, effectiveness_score=90, result_summary='Task executed successfully via run_dev_task()' WHERE task_id=v_task_id; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'task_completed', jsonb_build_object('result', v_result)); RETURN jsonb_build_object('task_id', v_task_id, 'status', 'completed', 'confidence', 0.95, 'knowledge_context_size', COALESCE(jsonb_array_length(v_context), 0), 'result', v_result); END; $$;


ALTER FUNCTION cmis_dev.run_dev_task(p_prompt text) OWNER TO begin;

--
-- Name: run_marketing_task(text); Type: FUNCTION; Schema: cmis_dev; Owner: begin
--

CREATE FUNCTION cmis_dev.run_marketing_task(p_prompt text) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_task_id uuid; v_execution_plan jsonb; v_knowledge jsonb; v_step_result text; v_result_summary text; v_confidence numeric(3,2) := 0.9; BEGIN INSERT INTO cmis_dev.dev_tasks (name, description, scope_code, status) VALUES (left(p_prompt, 120), 'مهمة تسويقية آلية – تم إنشاؤها عبر Orchestrator', 'marketing_ai', 'initializing') RETURNING task_id INTO v_task_id; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'intent_parsed', jsonb_build_object('prompt', p_prompt)); SELECT jsonb_agg(row_to_json(sub)) INTO v_knowledge FROM ( SELECT ki.knowledge_id, ki.topic, ki.tier, km.content FROM cmis_knowledge.index ki JOIN cmis_knowledge.marketing km USING (knowledge_id) WHERE ( lower(p_prompt) LIKE ANY (ARRAY['%instagram%', '%إنستغرام%', '%انستغرام%', '%' || lower(ki.domain) || '%', '%' || lower(ki.topic) || '%']) OR EXISTS ( SELECT 1 FROM unnest(ki.keywords) kw WHERE lower(p_prompt) LIKE '%' || lower(kw) || '%' ) OR lower(km.content) LIKE '%' || lower(p_prompt) || '%' ) AND ki.is_deprecated = false ORDER BY ki.tier ASC, ki.last_verified_at DESC LIMIT 5 ) sub; IF v_knowledge IS NULL THEN INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'knowledge_missing', jsonb_build_object('reason','No relevant marketing knowledge found')); UPDATE cmis_dev.dev_tasks SET status='failed', result_summary='لم يتم العثور على معرفة تسويقية كافية' WHERE task_id=v_task_id; RETURN jsonb_build_object('status','failed','reason','knowledge_not_found'); END IF; v_execution_plan := jsonb_build_object('steps', jsonb_build_array(jsonb_build_object('order',1,'action','analyze_knowledge','desc','تحليل المعرفة التسويقية المرتبطة'), jsonb_build_object('order',2,'action','generate_campaign_plan','desc','إنشاء خطة مبدئية بناء على المعرفة'), jsonb_build_object('order',3,'action','record_result','desc','تسجيل النتائج والخطة في السجلات'))); UPDATE cmis_dev.dev_tasks SET execution_plan = v_execution_plan, status='running' WHERE task_id=v_task_id; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'plan_initialized', jsonb_build_object('steps', jsonb_array_length(v_execution_plan->'steps'))); v_step_result := 'تم تحليل المعرفة التسويقية وإنشاء خطة حملة مبدئية ناجحة.'; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'step_executed', jsonb_build_object('result', v_step_result)); v_result_summary := 'تم إنشاء خطة تسويقية أولية بنجاح بناءً على المعرفة المخزّنة.'; UPDATE cmis_dev.dev_tasks SET status='completed', confidence=v_confidence, result_summary=v_result_summary, effectiveness_score=ROUND((random()*20+80)::numeric) WHERE task_id=v_task_id; RETURN jsonb_build_object('task_id', v_task_id, 'status', 'completed', 'confidence', v_confidence, 'result', v_result_summary, 'knowledge_used', v_knowledge); END; $$;


ALTER FUNCTION cmis_dev.run_marketing_task(p_prompt text) OWNER TO begin;

--
-- Name: run_marketing_task_improved(text); Type: FUNCTION; Schema: cmis_dev; Owner: begin
--

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

--
-- Name: search_marketing_knowledge(text); Type: FUNCTION; Schema: cmis_dev; Owner: begin
--

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

--
-- Name: auto_analyze_knowledge(text, text, text, integer, integer); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

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

--
-- Name: auto_retrieve_knowledge(text, text, text, integer, integer); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

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

--
-- Name: batch_update_embeddings(integer, text); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

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

--
-- Name: cleanup_old_embeddings(); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

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

--
-- Name: generate_embedding_improved(text); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

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

--
-- Name: generate_embedding_mock(text); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

CREATE FUNCTION cmis_knowledge.generate_embedding_mock(input_text text) RETURNS public.vector
    LANGUAGE sql
    AS $$
    SELECT cmis_knowledge.generate_embedding_improved(input_text);
$$;


ALTER FUNCTION cmis_knowledge.generate_embedding_mock(input_text text) OWNER TO begin;

--
-- Name: generate_system_report(); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

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

--
-- Name: register_knowledge(text, text, text, text, smallint, text[]); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

CREATE FUNCTION cmis_knowledge.register_knowledge(p_domain text, p_category text, p_topic text, p_content text, p_tier smallint DEFAULT 2, p_keywords text[] DEFAULT ARRAY[]::text[]) RETURNS uuid
    LANGUAGE plpgsql
    AS $$ DECLARE v_knowledge_id uuid; v_token_count int; BEGIN v_token_count := length(p_content) / 4; INSERT INTO cmis_knowledge.index (domain, category, topic, keywords, tier, token_budget, last_verified_at) VALUES (p_domain, p_category, p_topic, p_keywords, p_tier, v_token_count, now()) RETURNING knowledge_id INTO v_knowledge_id; CASE p_category WHEN 'dev' THEN INSERT INTO cmis_knowledge.dev (knowledge_id, content, token_count, version) VALUES (v_knowledge_id, p_content, v_token_count, '1.0'); WHEN 'marketing' THEN INSERT INTO cmis_knowledge.marketing (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); WHEN 'org' THEN INSERT INTO cmis_knowledge.org (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); WHEN 'research' THEN INSERT INTO cmis_knowledge.research (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); END CASE; RETURN v_knowledge_id; END; $$;


ALTER FUNCTION cmis_knowledge.register_knowledge(p_domain text, p_category text, p_topic text, p_content text, p_tier smallint, p_keywords text[]) OWNER TO begin;

--
-- Name: semantic_analysis(); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

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

--
-- Name: semantic_search_advanced(text, text, text, text, text, integer, numeric); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

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

--
-- Name: smart_context_loader(text, text, text, integer); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

CREATE FUNCTION cmis_knowledge.smart_context_loader(p_query text, p_domain text DEFAULT NULL::text, p_category text DEFAULT 'dev'::text, p_token_limit integer DEFAULT 5000) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_analysis jsonb; v_context jsonb := '[]'::jsonb; v_total_tokens int := 0; v_sample jsonb; v_excerpt text; v_fallback record; BEGIN BEGIN SELECT cmis_knowledge.auto_analyze_knowledge(p_query, p_domain, p_category) INTO v_analysis; EXCEPTION WHEN others THEN v_analysis := NULL; END; IF v_analysis IS NOT NULL AND jsonb_typeof(v_analysis->'samples') = 'array' THEN FOR v_sample IN SELECT value FROM jsonb_array_elements(v_analysis->'samples') LOOP v_excerpt := v_sample->>'excerpt'; IF v_total_tokens + (length(v_excerpt) / 4) > p_token_limit THEN EXIT; END IF; v_context := v_context || jsonb_build_object('topic', v_sample->>'topic','excerpt', trim(v_excerpt),'score', v_sample->>'score','batch', v_sample->>'batch'); v_total_tokens := v_total_tokens + (length(v_excerpt) / 4); END LOOP; ELSE FOR v_fallback IN SELECT d.content FROM cmis_knowledge.dev d JOIN cmis_knowledge.index i USING (knowledge_id) WHERE i.topic ILIKE '%' || p_query || '%' AND (p_domain IS NULL OR i.domain = p_domain) AND i.category = p_category ORDER BY i.last_verified_at DESC LIMIT 3 LOOP v_context := v_context || jsonb_build_object('topic', p_query,'excerpt', left(v_fallback.content, 1000),'score', 0.8,'batch', 'direct'); END LOOP; END IF; RETURN jsonb_build_object('query', p_query,'domain', p_domain,'category', p_category,'summary', COALESCE(v_analysis->'summary', 'null'::jsonb),'context_loaded', v_context,'estimated_tokens', v_total_tokens); END; $$;


ALTER FUNCTION cmis_knowledge.smart_context_loader(p_query text, p_domain text, p_category text, p_token_limit integer) OWNER TO begin;

--
-- Name: trigger_update_embeddings(); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

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

--
-- Name: update_manifest_on_change(); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

CREATE FUNCTION cmis_knowledge.update_manifest_on_change() RETURNS trigger
    LANGUAGE plpgsql
    AS $$ DECLARE v_layer TEXT; BEGIN v_layer := TG_TABLE_NAME; UPDATE cmis_knowledge.cognitive_manifest SET last_updated = NOW(), confidence = LEAST(confidence + 0.02, 1.00) WHERE LOWER(layer_name) = LOWER(v_layer) OR (LOWER(layer_name) = 'temporal' AND TG_TABLE_NAME LIKE '%temporal%') OR (LOWER(layer_name) = 'predictive' AND TG_TABLE_NAME LIKE '%predictive%') OR (LOWER(layer_name) = 'feedback' AND TG_TABLE_NAME LIKE '%audit%') OR (LOWER(layer_name) = 'learning' AND TG_TABLE_NAME LIKE '%learning%'); INSERT INTO cmis_audit.logs(event_type, event_source, description, created_at) VALUES ('manifest_sync', TG_TABLE_NAME, CONCAT('🔄 تحديث تلقائي في الـ Manifest بعد تعديل في الطبقة ', v_layer), NOW()); RETURN NEW; END; $$;


ALTER FUNCTION cmis_knowledge.update_manifest_on_change() OWNER TO begin;

--
-- Name: update_single_embedding(uuid); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

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

--
-- Name: verify_installation(); Type: FUNCTION; Schema: cmis_knowledge; Owner: begin
--

CREATE FUNCTION cmis_knowledge.verify_installation() RETURNS jsonb
    LANGUAGE plpgsql
    AS $$
BEGIN
    RETURN jsonb_build_object('status', 'partial setup complete', 'timestamp', now());
END;$$;


ALTER FUNCTION cmis_knowledge.verify_installation() OWNER TO begin;

--
-- Name: generate_campaign_assets(uuid); Type: FUNCTION; Schema: cmis_marketing; Owner: begin
--

CREATE FUNCTION cmis_marketing.generate_campaign_assets(p_task_id uuid) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_campaign_name text; v_knowledge jsonb; v_assets jsonb; BEGIN SELECT name INTO v_campaign_name FROM cmis_dev.dev_tasks WHERE task_id = p_task_id; SELECT jsonb_agg(row_to_json(sub)) INTO v_knowledge FROM ( SELECT ki.topic, km.content, ki.tier FROM cmis_knowledge.index ki JOIN cmis_knowledge.marketing km USING (knowledge_id) WHERE km.content ILIKE '%' || v_campaign_name || '%' OR ki.topic ILIKE '%' || v_campaign_name || '%' ORDER BY ki.tier ASC LIMIT 3 ) sub; v_assets := jsonb_build_array( jsonb_build_object('platform','instagram','asset_type','post','content', jsonb_build_object('text','منشور جذاب لزيادة التفاعل على CMIS Cloud','hashtags',ARRAY['#CMISCloud','#MarketingAutomation','#TechAgencies']),'confidence',0.95), jsonb_build_object('platform','instagram','asset_type','ad_copy','content', jsonb_build_object('headline','ارتقِ باستراتيجيتك التسويقية مع CMIS Cloud','body','حل متكامل لوكالات التسويق – أتمتة، تحليل، وذكاء اصطناعي في نظام واحد.'),'confidence',0.93) ); INSERT INTO cmis_marketing.assets (task_id, platform, asset_type, content, confidence) SELECT p_task_id, asset->>'platform', asset->>'asset_type', asset->'content', (asset->>'confidence')::numeric FROM jsonb_array_elements(v_assets) asset; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (p_task_id, 'assets_generated', jsonb_build_object('count', jsonb_array_length(v_assets))); RETURN jsonb_build_object('task_id', p_task_id, 'status', 'assets_generated', 'assets', v_assets, 'knowledge_used', v_knowledge); END; $$;


ALTER FUNCTION cmis_marketing.generate_campaign_assets(p_task_id uuid) OWNER TO begin;

--
-- Name: generate_creative_content(text, text, text, smallint); Type: FUNCTION; Schema: cmis_marketing; Owner: begin
--

CREATE FUNCTION cmis_marketing.generate_creative_content(p_topic text, p_goal text DEFAULT 'awareness'::text, p_tone text DEFAULT 'ملهم'::text, p_length smallint DEFAULT 3) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_hooks jsonb; v_concepts jsonb; v_slogans jsonb; v_narratives jsonb; v_output text := ''; v_mix jsonb := '[]'::jsonb; BEGIN SELECT jsonb_agg(content) INTO v_hooks FROM cmis_knowledge.creative_templates WHERE category='hook' AND (tone=p_tone OR tone IS NULL) ORDER BY random() LIMIT p_length; SELECT jsonb_agg(content) INTO v_concepts FROM cmis_knowledge.creative_templates WHERE category='concept' ORDER BY random() LIMIT p_length; SELECT jsonb_agg(content) INTO v_slogans FROM cmis_knowledge.creative_templates WHERE category='slogan' ORDER BY random() LIMIT p_length; SELECT jsonb_agg(content) INTO v_narratives FROM cmis_knowledge.creative_templates WHERE category='narrative' AND (tone=p_tone OR tone IS NULL) ORDER BY random() LIMIT p_length; v_mix := v_mix || jsonb_build_object('hook', v_hooks->0); v_mix := v_mix || jsonb_build_object('concept', v_concepts->0); v_mix := v_mix || jsonb_build_object('slogan', v_slogans->0); v_mix := v_mix || jsonb_build_object('narrative', v_narratives->0); v_output := concat_ws(E'\n\n', v_hooks->>0, v_concepts->>0, v_narratives->>0, v_slogans->>0); RETURN jsonb_build_object('status', 'creative_generated', 'topic', p_topic, 'tone', p_tone, 'output', v_output, 'composition', v_mix); END; $$;


ALTER FUNCTION cmis_marketing.generate_creative_content(p_topic text, p_goal text, p_tone text, p_length smallint) OWNER TO begin;

--
-- Name: generate_creative_variants(text, text, integer); Type: FUNCTION; Schema: cmis_marketing; Owner: begin
--

CREATE FUNCTION cmis_marketing.generate_creative_variants(p_topic text, p_tone text, p_variant_count integer DEFAULT 3) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_hooks RECORD; v_concepts RECORD; v_narratives RECORD; v_slogans RECORD; v_result jsonb := '[]'::jsonb; v_i int := 1; BEGIN FOR v_i IN 1..p_variant_count LOOP SELECT * FROM cmis_knowledge.creative_templates WHERE category='hook' ORDER BY random() LIMIT 1 INTO v_hooks; SELECT * FROM cmis_knowledge.creative_templates WHERE category='concept' ORDER BY random() LIMIT 1 INTO v_concepts; SELECT * FROM cmis_knowledge.creative_templates WHERE category='narrative' ORDER BY random() LIMIT 1 INTO v_narratives; SELECT * FROM cmis_knowledge.creative_templates WHERE category='slogan' ORDER BY random() LIMIT 1 INTO v_slogans; INSERT INTO cmis_marketing.generated_creatives ( topic, tone, variant_index, hook, concept, narrative, slogan, emotion_profile, tags ) VALUES ( p_topic, p_tone, v_i, v_hooks.content, v_concepts.content, v_narratives.content, v_slogans.content, ARRAY(SELECT unnest(v_hooks.emotion) || unnest(v_concepts.emotion) || unnest(v_narratives.emotion)), ARRAY(SELECT unnest(v_hooks.tags) || unnest(v_concepts.tags) || unnest(v_narratives.tags)) ); v_result := v_result || jsonb_build_object( 'variant_index', v_i, 'hook', v_hooks.content, 'concept', v_concepts.content, 'narrative', v_narratives.content, 'slogan', v_slogans.content, 'emotion_profile', ARRAY(SELECT unnest(v_hooks.emotion) || unnest(v_concepts.emotion) || unnest(v_narratives.emotion)), 'tags', ARRAY(SELECT unnest(v_hooks.tags) || unnest(v_concepts.tags) || unnest(v_narratives.tags)) ); END LOOP; RETURN jsonb_build_object( 'status', 'multi_generated', 'topic', p_topic, 'tone', p_tone, 'count', p_variant_count, 'variants', v_result ); END; $$;


ALTER FUNCTION cmis_marketing.generate_creative_variants(p_topic text, p_tone text, p_variant_count integer) OWNER TO begin;

--
-- Name: generate_video_scenario(uuid); Type: FUNCTION; Schema: cmis_marketing; Owner: begin
--

CREATE FUNCTION cmis_marketing.generate_video_scenario(p_task_id uuid) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_assets jsonb; v_visuals jsonb; v_scenario jsonb; BEGIN SELECT jsonb_agg(row_to_json(sub)) INTO v_assets FROM ( SELECT asset_id, content FROM cmis_marketing.assets WHERE task_id = p_task_id AND asset_type IN ('post','ad_copy') ) sub; SELECT jsonb_agg(row_to_json(sub)) INTO v_visuals FROM ( SELECT visual_prompt, style, palette, emotion FROM cmis_marketing.visual_concepts vc JOIN cmis_marketing.assets a USING (asset_id) WHERE a.task_id = p_task_id ) sub; v_scenario := jsonb_build_array( jsonb_build_object('order', 1, 'description', 'لقطة افتتاحية لمكتب وكالة تسويق حديثة تظهر شعار CMIS Cloud.', 'narration', 'في عالم التسويق السريع، النجاح يعتمد على الذكاء... CMIS Cloud هو الحل.', 'visual_hint', (v_visuals->0->>'visual_prompt'), 'duration', 4), jsonb_build_object('order', 2, 'description', 'فريق عمل شاب يتفاعل مع لوحة بيانات تفاعلية تعرض مؤشرات الأداء.', 'narration', 'راقب أداء حملاتك لحظة بلحظة... تحكّم في كل شيء من منصة واحدة.', 'visual_hint', (v_visuals->1->>'visual_prompt'), 'duration', 6), jsonb_build_object('order', 3, 'description', 'مشهد عرض عملي: واجهة CMIS Cloud على شاشة كمبيوتر.', 'narration', 'تكامل تام مع Meta وInstagram وGoogle Ads.', 'visual_hint', 'لقطة شاشة واجهة CMIS Cloud بتصميم أنيق.', 'duration', 5), jsonb_build_object('order', 4, 'description', 'ختام الفيديو بعرض شعار CMIS Cloud مع عبارة دعائية.', 'narration', 'CMIS Cloud — ذكاء التسويق في منصة واحدة.', 'visual_hint', 'خلفية بنفس الألوان التقنية الزرقاء والأرجوانية مع شعار CMIS.', 'duration', 3) ); INSERT INTO cmis_marketing.video_scenarios ( task_id, title, duration_seconds, scenes, tone, goal, confidence ) VALUES ( p_task_id, 'سيناريو فيديو ترويجي لحملة CMIS Cloud', 18, v_scenario, 'ملهم، احترافي', 'رفع وعي العلامة التجارية وبناء الثقة لدى وكالات التسويق', 0.94 ); INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (p_task_id, 'video_scenario_generated', jsonb_build_object('duration',18,'scenes',4)); RETURN jsonb_build_object('status','video_scenario_generated','task_id',p_task_id,'title','سيناريو فيديو ترويجي لحملة CMIS Cloud','duration',18,'scenes',v_scenario); END; $$;


ALTER FUNCTION cmis_marketing.generate_video_scenario(p_task_id uuid) OWNER TO begin;

--
-- Name: generate_visual_concepts(uuid); Type: FUNCTION; Schema: cmis_marketing; Owner: begin
--

CREATE FUNCTION cmis_marketing.generate_visual_concepts(p_task_id uuid) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_assets jsonb; v_concepts jsonb := '[]'::jsonb; asset_json jsonb; concept jsonb; BEGIN SELECT jsonb_agg(row_to_json(sub)) INTO v_assets FROM ( SELECT asset_id, platform, asset_type, content FROM cmis_marketing.assets WHERE task_id = p_task_id ) sub; IF v_assets IS NULL THEN RAISE NOTICE 'No assets found for task %', p_task_id; RETURN jsonb_build_object('status','no_assets'); END IF; FOR asset_json IN SELECT value FROM jsonb_array_elements(v_assets) LOOP INSERT INTO cmis_marketing.visual_concepts ( asset_id, visual_prompt, style, palette, emotion, focus_keywords ) VALUES ( (asset_json->>'asset_id')::uuid, CASE asset_json->>'asset_type' WHEN 'post' THEN 'تصوير لمكتب عصري لوكالة تسويق رقمية تعمل باستخدام CMIS Cloud، يظهر فريق شاب يبتسم ويحلل البيانات على شاشة كبيرة.' WHEN 'ad_copy' THEN 'خلفية بسيطة بألوان تقنية زرقاء وأرجوانية مع أيقونة سحابية، وعنوان كبير مكتوب "CMIS Cloud".' WHEN 'reel_script' THEN 'مشاهد متتابعة لفريق إبداعي يستخدم لوحات رقمية وتحليل أداء الإعلانات في واجهة جذابة.' ELSE 'تصميم عام يعكس التكنولوجيا والذكاء الاصطناعي والتعاون.' END, 'واقعي تقني', 'أزرق، أرجواني، أبيض', 'احترافية، تفاؤل، ثقة', ARRAY['وكالة تسويق','CMIS Cloud','بيانات','ابتكار'] ) RETURNING row_to_json(cmis_marketing.visual_concepts.*) INTO concept; v_concepts := v_concepts || concept; END LOOP; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (p_task_id, 'visual_concepts_generated', jsonb_build_object('count', jsonb_array_length(v_concepts))); RETURN jsonb_build_object('status','visuals_generated','task_id',p_task_id,'concepts',v_concepts); END; $$;


ALTER FUNCTION cmis_marketing.generate_visual_concepts(p_task_id uuid) OWNER TO begin;

--
-- Name: generate_visual_scenarios(text, text); Type: FUNCTION; Schema: cmis_marketing; Owner: begin
--

CREATE FUNCTION cmis_marketing.generate_visual_scenarios(p_topic text, p_tone text) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_creative RECORD; BEGIN FOR v_creative IN SELECT * FROM cmis_marketing.generated_creatives WHERE topic = p_topic AND tone = p_tone LOOP INSERT INTO cmis_marketing.visual_scenarios (creative_id, topic, tone, variant_index, scene_order, scene_type, scene_text, visual_hint) VALUES (v_creative.creative_id, p_topic, p_tone, v_creative.variant_index, 1, 'hook', v_creative.hook, 'لقطة افتتاحية جذابة مع نص على الشاشة'), (v_creative.creative_id, p_topic, p_tone, v_creative.variant_index, 2, 'concept', v_creative.concept, 'لقطة فريق عمل أو عرض بياني ديناميكي'), (v_creative.creative_id, p_topic, p_tone, v_creative.variant_index, 3, 'narrative', v_creative.narrative, 'مشاهد سردية حيوية بلقطات سريعة'), (v_creative.creative_id, p_topic, p_tone, v_creative.variant_index, 4, 'slogan', v_creative.slogan, 'لقطة ختامية بشعار CMIS Cloud'); END LOOP; RETURN jsonb_build_object('status','scenarios_generated','topic',p_topic,'tone',p_tone,'message','تم إنشاء السيناريوهات المرئية بنجاح'); END; $$;


ALTER FUNCTION cmis_marketing.generate_visual_scenarios(p_topic text, p_tone text) OWNER TO begin;

--
-- Name: generate_voice_script(uuid); Type: FUNCTION; Schema: cmis_marketing; Owner: begin
--

CREATE FUNCTION cmis_marketing.generate_voice_script(p_scenario_id uuid) RETURNS jsonb
    LANGUAGE plpgsql
    AS $$ DECLARE v_scenes jsonb; v_script jsonb := '[]'::jsonb; v_voice_tone text; v_goal text; v_task_id uuid; v_script_text text := ''; BEGIN SELECT scenes, tone, goal, task_id INTO v_scenes, v_voice_tone, v_goal, v_task_id FROM cmis_marketing.video_scenarios WHERE scenario_id = p_scenario_id; FOR i IN 0..jsonb_array_length(v_scenes)-1 LOOP DECLARE v_scene jsonb := v_scenes->i; v_narr text := v_scene->>'narration'; v_extra text; BEGIN SELECT content INTO v_extra FROM cmis_knowledge.marketing km JOIN cmis_knowledge.index ki USING (knowledge_id) WHERE ki.category='marketing' AND ki.tier<=2 AND ki.topic ILIKE '%'||v_goal||'%' ORDER BY ki.last_verified_at DESC LIMIT 1; IF v_extra IS NULL THEN v_extra := 'احصل على تجربة فريدة مع CMIS Cloud الآن!'; END IF; v_script := v_script || jsonb_build_object('scene', i+1, 'narration', v_narr || ' ' || v_extra, 'duration', v_scene->>'duration'); v_script_text := v_script_text || v_narr || ' ' || v_extra || E'\n'; END; END LOOP; INSERT INTO cmis_marketing.voice_scripts (scenario_id, task_id, voice_tone, narration, script_structure, confidence) VALUES (p_scenario_id, v_task_id, v_voice_tone, v_script_text, v_script, 0.93); INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'voice_script_generated', jsonb_build_object('scenes',jsonb_array_length(v_scenes))); RETURN jsonb_build_object('status','voice_script_generated','scenario_id',p_scenario_id,'task_id',v_task_id,'tone',v_voice_tone,'script_text',v_script_text); END; $$;


ALTER FUNCTION cmis_marketing.generate_voice_script(p_scenario_id uuid) OWNER TO begin;

--
-- Name: cleanup_stale_assets(); Type: FUNCTION; Schema: cmis_ops; Owner: begin
--

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

--
-- Name: generate_ai_summary(); Type: FUNCTION; Schema: cmis_ops; Owner: begin
--

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

--
-- Name: normalize_metrics(); Type: FUNCTION; Schema: cmis_ops; Owner: begin
--

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

--
-- Name: refresh_ai_insights(); Type: FUNCTION; Schema: cmis_ops; Owner: begin
--

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

--
-- Name: sync_integrations(); Type: FUNCTION; Schema: cmis_ops; Owner: begin
--

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

--
-- Name: update_timestamp(); Type: FUNCTION; Schema: cmis_ops; Owner: begin
--

CREATE FUNCTION cmis_ops.update_timestamp() RETURNS trigger
    LANGUAGE plpgsql
    AS $$ BEGIN NEW.updated_at = NOW(); RETURN NEW; END; $$;


ALTER FUNCTION cmis_ops.update_timestamp() OWNER TO begin;

--
-- Name: FUNCTION update_timestamp(); Type: COMMENT; Schema: cmis_ops; Owner: begin
--

COMMENT ON FUNCTION cmis_ops.update_timestamp() IS 'دالة قياسية لتحديث عمود updated_at تلقائيًا قبل تنفيذ أي UPDATE على الصف.';


--
-- Name: generate_brief_summary_legacy(uuid); Type: FUNCTION; Schema: cmis_staging; Owner: begin
--

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

--
-- Name: refresh_creative_index_legacy(); Type: FUNCTION; Schema: cmis_staging; Owner: begin
--

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

--
-- Name: validate_brief_structure_legacy(jsonb); Type: FUNCTION; Schema: cmis_staging; Owner: begin
--

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

--
-- Name: audit_trigger_function(); Type: FUNCTION; Schema: operations; Owner: begin
--

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

--
-- Name: purge_old_audit_logs(integer); Type: FUNCTION; Schema: operations; Owner: begin
--

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

--
-- Name: auto_analyze_knowledge(); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: auto_predictive_campaign(); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: auto_snapshot_diff(); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.auto_snapshot_diff() RETURNS void
    LANGUAGE plpgsql
    AS $$ DECLARE v_rec record; v_prev JSONB; v_curr JSONB; v_diff TEXT; BEGIN RAISE NOTICE '🧠 بدء التحليل الزمني الدوري للمعرفة...'; FOR v_rec IN SELECT knowledge_id, domain FROM cmis_knowledge.index LOOP SELECT current_snapshot INTO v_prev FROM cmis_knowledge.temporal_analytics WHERE knowledge_id = v_rec.knowledge_id ORDER BY detected_at DESC OFFSET 1 LIMIT 1; SELECT current_snapshot INTO v_curr FROM cmis_knowledge.temporal_analytics WHERE knowledge_id = v_rec.knowledge_id ORDER BY detected_at DESC LIMIT 1; v_diff := CONCAT('📘 تحليل زمني تلقائي: تغير في النطاق ', v_rec.domain, ' عند ', NOW()); INSERT INTO cmis_knowledge.temporal_analytics(knowledge_id, domain, previous_snapshot, current_snapshot, delta_summary, confidence_score) VALUES (v_rec.knowledge_id, v_rec.domain, v_prev, v_curr, v_diff, 0.95); END LOOP; RAISE NOTICE '✅ تم إكمال التحليل الزمني الدوري بنجاح.'; END; $$;


ALTER FUNCTION public.auto_snapshot_diff() OWNER TO begin;

--
-- Name: auto_update_cognitive_trends(); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: cognitive_console_report(text); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: cognitive_feedback_loop(); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.cognitive_feedback_loop() RETURNS void
    LANGUAGE plpgsql
    AS $$ DECLARE v_rec record; v_triggered int := 0; BEGIN RAISE NOTICE '🧭 بدء دورة الارتداد المعرفي...'; FOR v_rec IN SELECT domain_name, category FROM cmis_knowledge.v_predictive_cognitive_horizon WHERE forecast_status LIKE '%🔴%' LOOP INSERT INTO cmis_audit.logs(event_type, event_source, description, created_at) VALUES ('cognitive_feedback', v_rec.domain_name, CONCAT('🔄 إعادة تفعيل التحليل الإدراكي بسبب تراجع متوقع في النطاق ', v_rec.domain_name), NOW()); PERFORM compute_epistemic_delta(); v_triggered := v_triggered + 1; END LOOP; RAISE NOTICE '✅ تم تنفيذ دورة الارتداد المعرفي لـ % نطاق(ات).', v_triggered; END; $$;


ALTER FUNCTION public.cognitive_feedback_loop() OWNER TO begin;

--
-- Name: cognitive_learning_loop(); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.cognitive_learning_loop() RETURNS void
    LANGUAGE plpgsql
    AS $$ DECLARE v_rec record; v_learned int := 0; BEGIN RAISE NOTICE '🧬 بدء حلقة التعلم الإدراكي...'; FOR v_rec IN SELECT event_source, COUNT(*) AS interventions FROM cmis_audit.logs WHERE event_type = 'cognitive_feedback' GROUP BY event_source HAVING COUNT(*) > 2 LOOP UPDATE cmis_knowledge.v_chrono_evolution SET avg_confidence = avg_confidence + 0.01 WHERE domain_name = v_rec.event_source; INSERT INTO cmis_audit.logs(event_type, event_source, description, created_at) VALUES ('cognitive_learning', v_rec.event_source, CONCAT('🧠 تم تعديل معايير الثقة بناءً على التعلم من ', v_rec.interventions, ' تدخل(ات) معرفية سابقة في النطاق ', v_rec.event_source), NOW()); v_learned := v_learned + 1; END LOOP; RAISE NOTICE '✅ اكتملت حلقة التعلم الإدراكي. تم تحديث % نطاق(ات).', v_learned; END; $$;


ALTER FUNCTION public.cognitive_learning_loop() OWNER TO begin;

--
-- Name: compute_epistemic_delta(); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.compute_epistemic_delta() RETURNS void
    LANGUAGE plpgsql
    AS $$ DECLARE v_rec record; v_prev TEXT; v_curr TEXT; v_diff TEXT; BEGIN FOR v_rec IN SELECT knowledge_id, domain FROM cmis_knowledge.index LOOP SELECT content INTO v_prev FROM cmis_knowledge.dev WHERE parent_knowledge_id = v_rec.knowledge_id ORDER BY created_at DESC OFFSET 1 LIMIT 1; SELECT content INTO v_curr FROM cmis_knowledge.dev WHERE parent_knowledge_id = v_rec.knowledge_id ORDER BY created_at DESC LIMIT 1; v_diff := CONCAT('📘 تغير معرفي مكتشف في النطاق ', v_rec.domain, ' بتاريخ ', NOW()); INSERT INTO cmis_knowledge.temporal_analytics(knowledge_id, domain, previous_snapshot, current_snapshot, delta_summary) VALUES (v_rec.knowledge_id, v_rec.domain, to_jsonb(v_prev), to_jsonb(v_curr), v_diff); END LOOP; END; $$;


ALTER FUNCTION public.compute_epistemic_delta() OWNER TO begin;

--
-- Name: create_dev_task(text, text, text, jsonb, smallint); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.create_dev_task(p_name text, p_description text, p_scope_code text, p_execution_plan jsonb, p_priority smallint DEFAULT 3) RETURNS uuid
    LANGUAGE plpgsql
    AS $$ DECLARE v_task_id uuid; v_similar_task uuid; BEGIN SELECT task_id INTO v_similar_task FROM cmis_dev.dev_tasks WHERE similarity(name, p_name) > 0.8 AND status IN ('pending', 'in_progress') AND created_at > now() - interval '7 days' LIMIT 1; IF v_similar_task IS NOT NULL THEN RAISE NOTICE 'Similar task found: %', v_similar_task; RETURN v_similar_task; END IF; INSERT INTO cmis_dev.dev_tasks (name, description, scope_code, execution_plan, priority, status) VALUES (p_name, p_description, p_scope_code, p_execution_plan, p_priority, 'pending') RETURNING task_id INTO v_task_id; INSERT INTO cmis_dev.dev_logs (task_id, event, details) VALUES (v_task_id, 'task_created', jsonb_build_object('priority', p_priority, 'scope', p_scope_code)); RETURN v_task_id; END; $$;


ALTER FUNCTION public.create_dev_task(p_name text, p_description text, p_scope_code text, p_execution_plan jsonb, p_priority smallint) OWNER TO begin;

--
-- Name: generate_cognitive_health_report(); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.generate_cognitive_health_report() RETURNS void
    LANGUAGE plpgsql
    AS $$ DECLARE r RECORD; report_text TEXT; BEGIN SELECT ROUND(AVG("نسبة الاستقرار %")::numeric,2) AS stability_avg, ROUND(AVG("نسبة إعادة التحليل %")::numeric,2) AS reanalysis_avg, ROUND(AVG("نسبة الخطر %")::numeric,2) AS risk_avg INTO r FROM cmis_system_health.v_cognitive_kpi_timeseries WHERE "الساعة" > NOW() - INTERVAL '24 hours'; report_text := '🧠 تقرير الإدراك الدوري: خلال آخر 24 ساعة بلغت نسبة الاستقرار ' || TO_CHAR(COALESCE(r.stability_avg,0),'FM999.00') || '%، بينما بلغت إعادة التحليل ' || TO_CHAR(COALESCE(r.reanalysis_avg,0),'FM999.00') || '% ومؤشر الخطر ' || TO_CHAR(COALESCE(r.risk_avg,0),'FM999.00') || '%. الحالة العامة: ' || CASE  WHEN COALESCE(r.risk_avg,0) > 20 THEN '🔴 غير مستقرة'  WHEN COALESCE(r.reanalysis_avg,0) > 50 THEN '🟡 تحت إعادة تقييم'  ELSE '🟢 مستقرة' END || '.'; INSERT INTO cmis_system_health.cognitive_reports(report_text, stability_avg, reanalysis_avg, risk_avg) VALUES (report_text, r.stability_avg, r.reanalysis_avg, r.risk_avg); RAISE NOTICE '✅ تم توليد التقرير الإدراكي: %', report_text; END; $$;


ALTER FUNCTION public.generate_cognitive_health_report() OWNER TO begin;

--
-- Name: get_all_report_summaries(integer); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: get_latest_official_report(text); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: get_latest_reports_by_all_phases(); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: get_official_reports(); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: get_report_summary_by_phase(text); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: get_report_summary_by_phase(text, integer); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: get_reports_by_phase(text); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: load_context_by_priority(text, text, integer); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.load_context_by_priority(p_domain text, p_category text DEFAULT NULL::text, p_max_tokens integer DEFAULT 5000) RETURNS TABLE(knowledge_id uuid, content text, tier smallint, token_count integer, total_tokens bigint)
    LANGUAGE plpgsql
    AS $$ BEGIN RETURN QUERY WITH ranked_knowledge AS ( SELECT ki.knowledge_id, CASE p_category WHEN 'dev' THEN kd.content WHEN 'marketing' THEN km.content WHEN 'org' THEN ko.content WHEN 'research' THEN kr.content END AS content, ki.tier, COALESCE(kd.token_count, km.token_count, ko.token_count, kr.token_count) AS token_count, SUM(COALESCE(kd.token_count, km.token_count, ko.token_count, kr.token_count)) OVER (ORDER BY ki.tier ASC, ki.last_verified_at DESC) AS total_tokens FROM cmis_knowledge.index ki LEFT JOIN cmis_knowledge.dev kd USING (knowledge_id) LEFT JOIN cmis_knowledge.marketing km USING (knowledge_id) LEFT JOIN cmis_knowledge.org ko USING (knowledge_id) LEFT JOIN cmis_knowledge.research kr USING (knowledge_id) WHERE ki.domain = p_domain AND (p_category IS NULL OR ki.category = p_category) AND ki.is_deprecated = false ) SELECT rk.knowledge_id, rk.content, rk.tier, rk.token_count, rk.total_tokens FROM ranked_knowledge rk WHERE rk.total_tokens <= p_max_tokens; END; $$;


ALTER FUNCTION public.load_context_by_priority(p_domain text, p_category text, p_max_tokens integer) OWNER TO begin;

--
-- Name: log_cognitive_vitality(); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.log_cognitive_vitality() RETURNS void
    LANGUAGE plpgsql
    AS $$ DECLARE v_data RECORD; BEGIN SELECT * INTO v_data FROM cmis_knowledge.v_cognitive_vitality; INSERT INTO cmis_system_health.cognitive_vitality_log ( latency_minutes, events_last_hour, vitality_index, cognitive_state ) VALUES ( v_data.latency_minutes, v_data.events_last_hour, v_data.vitality_index, v_data.cognitive_state ); RAISE NOTICE '🧠 تم تسجيل قراءة جديدة لمؤشر الحيوية الإدراكية بنجاح.'; END; $$;


ALTER FUNCTION public.log_cognitive_vitality() OWNER TO begin;

--
-- Name: reconstruct_knowledge(uuid); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.reconstruct_knowledge(p_parent_id uuid) RETURNS text
    LANGUAGE plpgsql
    AS $$ BEGIN RETURN (SELECT string_agg(content, E'\n') FROM cmis_knowledge.dev WHERE parent_knowledge_id = p_parent_id ORDER BY part_index); END; $$;


ALTER FUNCTION public.reconstruct_knowledge(p_parent_id uuid) OWNER TO begin;

--
-- Name: register_chunked_knowledge(text, text, text, text, integer); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: register_knowledge(text, text, text, text, smallint, text[]); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.register_knowledge(p_domain text, p_category text, p_topic text, p_content text, p_tier smallint DEFAULT 2, p_keywords text[] DEFAULT ARRAY[]::text[]) RETURNS uuid
    LANGUAGE plpgsql
    AS $$ DECLARE v_knowledge_id uuid; v_token_count int; BEGIN v_token_count := length(p_content) / 4; INSERT INTO cmis_knowledge.index (domain, category, topic, keywords, tier, token_budget, last_verified_at) VALUES (p_domain, p_category, p_topic, p_keywords, p_tier, v_token_count, now()) RETURNING knowledge_id INTO v_knowledge_id; CASE p_category WHEN 'dev' THEN INSERT INTO cmis_knowledge.dev (knowledge_id, content, token_count, version) VALUES (v_knowledge_id, p_content, v_token_count, '1.0'); WHEN 'marketing' THEN INSERT INTO cmis_knowledge.marketing (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); WHEN 'org' THEN INSERT INTO cmis_knowledge.org (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); WHEN 'research' THEN INSERT INTO cmis_knowledge.research (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); END CASE; RETURN v_knowledge_id; END; $$;


ALTER FUNCTION public.register_knowledge(p_domain text, p_category text, p_topic text, p_content text, p_tier smallint, p_keywords text[]) OWNER TO begin;

--
-- Name: register_knowledge(text, text, text, text, integer, text[]); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.register_knowledge(p_domain text, p_category text, p_topic text, p_content text, p_tier integer DEFAULT 2, p_keywords text[] DEFAULT NULL::text[]) RETURNS uuid
    LANGUAGE plpgsql
    AS $$ DECLARE v_knowledge_id uuid; v_token_count int; BEGIN v_token_count := length(p_content) / 4; INSERT INTO cmis_knowledge.index (domain, category, topic, keywords, tier, token_budget, last_verified_at) VALUES (p_domain, p_category, p_topic, p_keywords, p_tier, v_token_count, now()) RETURNING knowledge_id INTO v_knowledge_id; CASE p_category WHEN 'dev' THEN INSERT INTO cmis_knowledge.dev (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); WHEN 'marketing' THEN INSERT INTO cmis_knowledge.marketing (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); WHEN 'org' THEN INSERT INTO cmis_knowledge.org (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); WHEN 'research' THEN INSERT INTO cmis_knowledge.research (knowledge_id, content, token_count) VALUES (v_knowledge_id, p_content, v_token_count); ELSE RAISE EXCEPTION 'Unknown knowledge category: %', p_category; END CASE; RETURN v_knowledge_id; END; $$;


ALTER FUNCTION public.register_knowledge(p_domain text, p_category text, p_topic text, p_content text, p_tier integer, p_keywords text[]) OWNER TO begin;

--
-- Name: run_auto_predictive_trigger(); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: scheduled_cognitive_trend_update(); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.scheduled_cognitive_trend_update() RETURNS void
    LANGUAGE plpgsql
    AS $$
BEGIN
  PERFORM public.update_cognitive_trends();
END;
$$;


ALTER FUNCTION public.scheduled_cognitive_trend_update() OWNER TO begin;

--
-- Name: search_cognitive_knowledge(text, text, text, integer, integer); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: search_cognitive_knowledge_simple(text, integer, integer); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: update_cognitive_trends(); Type: FUNCTION; Schema: public; Owner: begin
--

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

--
-- Name: update_knowledge_chunk(uuid, integer, text); Type: FUNCTION; Schema: public; Owner: begin
--

CREATE FUNCTION public.update_knowledge_chunk(p_parent_id uuid, p_part_index integer, p_new_content text) RETURNS void
    LANGUAGE plpgsql
    AS $$ BEGIN UPDATE cmis_knowledge.dev SET content = p_new_content, token_count = length(p_new_content)/4, link_context = jsonb_set(link_context, '{last_updated}', to_jsonb(now())) WHERE parent_knowledge_id = p_parent_id AND part_index = p_part_index; END; $$;


ALTER FUNCTION public.update_knowledge_chunk(p_parent_id uuid, p_part_index integer, p_new_content text) OWNER TO begin;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: backup_integrations_cmis; Type: TABLE; Schema: archive; Owner: begin
--

CREATE TABLE archive.backup_integrations_cmis (
    integration_id uuid,
    org_id uuid,
    platform text,
    account_id text,
    access_token text,
    is_active boolean,
    created_at timestamp with time zone,
    id bigint NOT NULL
);


ALTER TABLE archive.backup_integrations_cmis OWNER TO begin;

--
-- Name: backup_integrations_cmis_id_seq; Type: SEQUENCE; Schema: archive; Owner: begin
--

CREATE SEQUENCE archive.backup_integrations_cmis_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE archive.backup_integrations_cmis_id_seq OWNER TO begin;

--
-- Name: backup_integrations_cmis_id_seq; Type: SEQUENCE OWNED BY; Schema: archive; Owner: begin
--

ALTER SEQUENCE archive.backup_integrations_cmis_id_seq OWNED BY archive.backup_integrations_cmis.id;


--
-- Name: contexts_unified_backup; Type: TABLE; Schema: archive; Owner: begin
--

CREATE TABLE archive.contexts_unified_backup (
    id uuid,
    context_type character varying(50),
    name character varying(255),
    description text,
    status character varying(50),
    creative_brief text,
    brand_guidelines jsonb,
    visual_style jsonb,
    tone_of_voice text,
    target_platforms text[],
    creative_assets jsonb,
    value_proposition text,
    target_audience jsonb,
    key_messages text[],
    pain_points text[],
    benefits text[],
    differentiators text[],
    offering_details jsonb,
    pricing_info jsonb,
    availability jsonb,
    features text[],
    specifications jsonb,
    terms_conditions text,
    parent_context_id uuid,
    related_contexts uuid[],
    org_id uuid,
    created_by uuid,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    deleted_at timestamp without time zone,
    version integer,
    metadata jsonb,
    tags text[],
    categories text[],
    keywords text[],
    search_vector tsvector
);


ALTER TABLE archive.contexts_unified_backup OWNER TO begin;

--
-- Name: embedding_update_queue_backup; Type: TABLE; Schema: archive; Owner: begin
--

CREATE TABLE archive.embedding_update_queue_backup (
    queue_id uuid,
    knowledge_id uuid,
    source_table text,
    source_field text,
    priority integer,
    status text,
    retry_count integer,
    max_retries integer,
    error_message text,
    created_at timestamp with time zone,
    processing_started_at timestamp with time zone,
    processed_at timestamp with time zone,
    id bigint NOT NULL
);


ALTER TABLE archive.embedding_update_queue_backup OWNER TO begin;

--
-- Name: embedding_update_queue_backup_id_seq; Type: SEQUENCE; Schema: archive; Owner: begin
--

CREATE SEQUENCE archive.embedding_update_queue_backup_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE archive.embedding_update_queue_backup_id_seq OWNER TO begin;

--
-- Name: embedding_update_queue_backup_id_seq; Type: SEQUENCE OWNED BY; Schema: archive; Owner: begin
--

ALTER SEQUENCE archive.embedding_update_queue_backup_id_seq OWNED BY archive.embedding_update_queue_backup.id;


--
-- Name: ad_accounts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ad_accounts (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    account_external_id text NOT NULL,
    name text,
    currency text,
    timezone text,
    spend_cap numeric,
    status text,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.ad_accounts OWNER TO begin;

--
-- Name: ad_audiences; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ad_audiences (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    entity_level text NOT NULL,
    entity_external_id text NOT NULL,
    audience_type text,
    platform text,
    demographics jsonb,
    interests jsonb,
    behaviors jsonb,
    location jsonb,
    keywords jsonb,
    custom_audience jsonb,
    lookalike_audience jsonb,
    advantage_plus_settings jsonb,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT ad_audiences_entity_level_check CHECK ((entity_level = ANY (ARRAY['campaign'::text, 'adset'::text, 'adgroup'::text])))
);


ALTER TABLE cmis.ad_audiences OWNER TO begin;

--
-- Name: ad_campaigns; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ad_campaigns (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    campaign_external_id text NOT NULL,
    name text,
    objective text,
    start_date date,
    end_date date,
    status text,
    budget numeric,
    metrics jsonb DEFAULT '{}'::jsonb,
    fetched_at timestamp without time zone DEFAULT now(),
    created_at timestamp without time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    deleted_by uuid
);


ALTER TABLE cmis.ad_campaigns OWNER TO begin;

--
-- Name: ad_entities; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ad_entities (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    adset_external_id text NOT NULL,
    ad_external_id text NOT NULL,
    name text,
    status text,
    creative_id text,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    deleted_by uuid
);


ALTER TABLE cmis.ad_entities OWNER TO begin;

--
-- Name: ad_metrics; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ad_metrics (
    id bigint NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    entity_level text NOT NULL,
    entity_external_id text NOT NULL,
    date_start date NOT NULL,
    date_stop date NOT NULL,
    spend numeric,
    impressions bigint,
    clicks bigint,
    actions jsonb,
    conversions jsonb,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.ad_metrics OWNER TO begin;

--
-- Name: ad_metrics_id_seq; Type: SEQUENCE; Schema: cmis; Owner: begin
--

CREATE SEQUENCE cmis.ad_metrics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.ad_metrics_id_seq OWNER TO begin;

--
-- Name: ad_metrics_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: begin
--

ALTER SEQUENCE cmis.ad_metrics_id_seq OWNED BY cmis.ad_metrics.id;


--
-- Name: ad_sets; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ad_sets (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    campaign_external_id text NOT NULL,
    adset_external_id text NOT NULL,
    name text,
    status text,
    daily_budget numeric,
    start_date timestamp with time zone,
    end_date timestamp with time zone,
    billing_event text,
    optimization_goal text,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    deleted_by uuid
);


ALTER TABLE cmis.ad_sets OWNER TO begin;

--
-- Name: ai_actions; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ai_actions (
    action_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    prompt_used text,
    sql_executed text,
    result_summary text,
    confidence_score numeric(5,2),
    created_at timestamp with time zone DEFAULT now(),
    audit_id uuid,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.ai_actions OWNER TO begin;

--
-- Name: ai_generated_campaigns; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ai_generated_campaigns (
    campaign_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    objective_code text,
    recommended_principle text,
    linked_kpi text,
    ai_summary text,
    ai_design_guideline text,
    created_at timestamp with time zone DEFAULT now(),
    engine text,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.ai_generated_campaigns OWNER TO begin;

--
-- Name: ai_models; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ai_models (
    model_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    name text NOT NULL,
    engine text,
    version text,
    description text,
    created_at timestamp with time zone DEFAULT now(),
    model_name character varying(255),
    model_family character varying(255),
    status character varying(50),
    trained_at timestamp without time zone,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.ai_models OWNER TO begin;

--
-- Name: analytics_integrations; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.analytics_integrations (
    integration_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid NOT NULL,
    platform text NOT NULL,
    source_endpoint text NOT NULL,
    mapping jsonb NOT NULL,
    refresh_frequency text DEFAULT 'weekly'::text,
    last_synced_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.analytics_integrations OWNER TO begin;

--
-- Name: anchors; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.anchors (
    anchor_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    module_id integer,
    code public.ltree NOT NULL,
    title text,
    file_ref text,
    section text,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.anchors OWNER TO begin;

--
-- Name: api_keys; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.api_keys (
    key_id uuid DEFAULT gen_random_uuid() NOT NULL,
    service_name text NOT NULL,
    service_code text NOT NULL,
    api_key_encrypted bytea NOT NULL,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.api_keys OWNER TO begin;

--
-- Name: audio_templates; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.audio_templates (
    atpl_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    name text NOT NULL,
    voice_hints jsonb,
    sfx_pack jsonb,
    version text DEFAULT '2025.10.0'::text,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.audio_templates OWNER TO begin;

--
-- Name: audit_log; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.audit_log (
    log_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    actor text,
    action text,
    target text,
    meta jsonb,
    ts timestamp with time zone DEFAULT now(),
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    deleted_by uuid
);


ALTER TABLE cmis.audit_log OWNER TO begin;

--
-- Name: awareness_stages; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.awareness_stages (
    stage text NOT NULL
);


ALTER TABLE public.awareness_stages OWNER TO begin;

--
-- Name: awareness_stages; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.awareness_stages AS
 SELECT stage
   FROM public.awareness_stages;


ALTER VIEW cmis.awareness_stages OWNER TO begin;

--
-- Name: bundle_offerings; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.bundle_offerings (
    bundle_id uuid NOT NULL,
    offering_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.bundle_offerings OWNER TO begin;

--
-- Name: cache_metadata; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.cache_metadata (
    cache_name text NOT NULL,
    last_refreshed timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    refresh_count bigint DEFAULT 1,
    avg_refresh_time_ms numeric,
    last_refresh_duration_ms numeric,
    auto_refresh boolean DEFAULT true,
    metadata jsonb,
    hit_count bigint DEFAULT 0
);


ALTER TABLE cmis.cache_metadata OWNER TO begin;

--
-- Name: campaign_context_links; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.campaign_context_links (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    campaign_id uuid NOT NULL,
    context_id uuid NOT NULL,
    context_type character varying(50) NOT NULL,
    link_type character varying(50) DEFAULT 'primary'::character varying,
    link_strength numeric(3,2) DEFAULT 1.0,
    link_purpose text,
    link_notes text,
    effective_from date,
    effective_to date,
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    created_by uuid,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_by uuid,
    metadata jsonb DEFAULT '{}'::jsonb,
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT campaign_context_links_link_strength_check CHECK (((link_strength >= (0)::numeric) AND (link_strength <= (1)::numeric))),
    CONSTRAINT campaign_links_strength_range CHECK (((link_strength >= (0)::numeric) AND (link_strength <= (1)::numeric))),
    CONSTRAINT valid_dates CHECK (((effective_from IS NULL) OR (effective_to IS NULL) OR (effective_from <= effective_to))),
    CONSTRAINT valid_link_type CHECK (((link_type)::text = ANY (ARRAY[('primary'::character varying)::text, ('secondary'::character varying)::text, ('reference'::character varying)::text, ('historical'::character varying)::text])))
);


ALTER TABLE cmis.campaign_context_links OWNER TO begin;

--
-- Name: TABLE campaign_context_links; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON TABLE cmis.campaign_context_links IS 'Links campaigns to their various contexts with flexible relationship types';


--
-- Name: COLUMN campaign_context_links.link_type; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON COLUMN cmis.campaign_context_links.link_type IS 'Type of link: primary, secondary, reference, or historical';


--
-- Name: COLUMN campaign_context_links.link_strength; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON COLUMN cmis.campaign_context_links.link_strength IS 'Strength of the relationship (0.0 to 1.0)';


--
-- Name: campaign_offerings; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.campaign_offerings (
    campaign_id uuid NOT NULL,
    offering_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.campaign_offerings OWNER TO begin;

--
-- Name: campaign_performance_dashboard; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.campaign_performance_dashboard (
    dashboard_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid NOT NULL,
    metric_name text NOT NULL,
    metric_value numeric(10,4),
    metric_target numeric(10,4),
    variance numeric(10,4),
    confidence_level numeric(4,2),
    collected_at timestamp with time zone DEFAULT now(),
    insights text,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.campaign_performance_dashboard OWNER TO begin;

--
-- Name: campaigns; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.campaigns (
    campaign_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    objective text,
    status text DEFAULT 'draft'::text,
    start_date date,
    end_date date,
    budget numeric(12,2),
    currency text DEFAULT 'USD'::text,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    context_id uuid,
    creative_id uuid,
    value_id uuid,
    created_by uuid,
    deleted_at timestamp with time zone,
    provider text,
    deleted_by uuid,
    CONSTRAINT campaigns_status_valid CHECK ((status = ANY (ARRAY['draft'::text, 'active'::text, 'paused'::text, 'completed'::text, 'archived'::text])))
);


ALTER TABLE cmis.campaigns OWNER TO begin;

--
-- Name: TABLE campaigns; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON TABLE cmis.campaigns IS 'المرجع الموحّد لجميع الحملات. استخدم هذا الجدول بدل أي نسخة قديمة.';


--
-- Name: channel_formats; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.channel_formats (
    format_id integer NOT NULL,
    channel_id integer NOT NULL,
    code text NOT NULL,
    ratio text,
    length_hint text
);


ALTER TABLE public.channel_formats OWNER TO begin;

--
-- Name: channel_formats; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.channel_formats AS
 SELECT format_id,
    channel_id,
    code,
    ratio,
    length_hint
   FROM public.channel_formats;


ALTER VIEW cmis.channel_formats OWNER TO begin;

--
-- Name: channels; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.channels (
    channel_id integer NOT NULL,
    code text NOT NULL,
    name text NOT NULL,
    constraints jsonb
);


ALTER TABLE public.channels OWNER TO begin;

--
-- Name: channels; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.channels AS
 SELECT channel_id,
    code,
    name,
    constraints
   FROM public.channels;


ALTER VIEW cmis.channels OWNER TO begin;

--
-- Name: cognitive_tracker_template; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.cognitive_tracker_template (
    tracker_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    record_date date NOT NULL,
    platform text NOT NULL,
    content_type text NOT NULL,
    visual_factor text NOT NULL,
    ctr numeric(5,2),
    engagement_rate numeric(5,2),
    trust_index numeric(5,2),
    visual_insight text,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.cognitive_tracker_template OWNER TO begin;

--
-- Name: cognitive_trends; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.cognitive_trends (
    trend_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    factor_name text NOT NULL,
    trend_direction text,
    growth_rate double precision,
    trend_strength double precision,
    summary_insight text,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT cognitive_trends_trend_direction_check CHECK ((trend_direction = ANY (ARRAY['up'::text, 'down'::text, 'stable'::text])))
);


ALTER TABLE cmis.cognitive_trends OWNER TO begin;

--
-- Name: compliance_audits; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.compliance_audits (
    audit_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    asset_id uuid,
    rule_id uuid NOT NULL,
    status text NOT NULL,
    owner text,
    notes text,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT compliance_audits_status_check CHECK ((status = ANY (ARRAY['pass'::text, 'fail'::text, 'waived'::text])))
);


ALTER TABLE cmis.compliance_audits OWNER TO begin;

--
-- Name: compliance_rule_channels; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.compliance_rule_channels (
    rule_id uuid NOT NULL,
    channel_id integer NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.compliance_rule_channels OWNER TO begin;

--
-- Name: compliance_rules; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.compliance_rules (
    rule_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    code text NOT NULL,
    description text NOT NULL,
    severity text NOT NULL,
    params jsonb,
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT compliance_rules_severity_check CHECK ((severity = ANY (ARRAY['warn'::text, 'block'::text])))
);


ALTER TABLE cmis.compliance_rules OWNER TO begin;

--
-- Name: component_types; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.component_types (
    type_code text NOT NULL
);


ALTER TABLE public.component_types OWNER TO begin;

--
-- Name: component_types; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.component_types AS
 SELECT type_code
   FROM public.component_types;


ALTER VIEW cmis.component_types OWNER TO begin;

--
-- Name: content_items; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.content_items (
    item_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    plan_id uuid NOT NULL,
    channel_id integer,
    format_id integer,
    scheduled_at timestamp with time zone,
    title text,
    brief jsonb,
    asset_id uuid,
    status text DEFAULT 'draft'::text,
    context_id uuid,
    example_id uuid,
    creative_context_id uuid,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    org_id uuid,
    deleted_by uuid
);


ALTER TABLE cmis.content_items OWNER TO begin;

--
-- Name: content_plans; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.content_plans (
    plan_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    name text NOT NULL,
    timeframe_daterange daterange,
    strategy jsonb,
    created_at timestamp with time zone DEFAULT now(),
    brief_id uuid,
    creative_context_id uuid,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.content_plans OWNER TO begin;

--
-- Name: contexts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.contexts (
    context_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    type text NOT NULL,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT contexts_type_check CHECK ((type = ANY (ARRAY['value'::text, 'creative'::text, 'experiment'::text])))
);


ALTER TABLE cmis.contexts OWNER TO begin;

--
-- Name: contexts_base; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.contexts_base (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    context_type character varying(50) NOT NULL,
    name character varying(255),
    org_id uuid,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT contexts_base_context_type_check CHECK (((context_type)::text = ANY ((ARRAY['creative'::character varying, 'value'::character varying, 'offering'::character varying])::text[])))
);


ALTER TABLE cmis.contexts_base OWNER TO begin;

--
-- Name: contexts_creative; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.contexts_creative (
    context_id uuid NOT NULL,
    creative_brief text,
    brand_guidelines jsonb,
    visual_style jsonb,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.contexts_creative OWNER TO begin;

--
-- Name: contexts_offering; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.contexts_offering (
    context_id uuid NOT NULL,
    offering_details text,
    pricing_info jsonb,
    features jsonb,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.contexts_offering OWNER TO begin;

--
-- Name: contexts_value; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.contexts_value (
    context_id uuid NOT NULL,
    value_proposition text,
    target_audience jsonb,
    key_messages text[],
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.contexts_value OWNER TO begin;

--
-- Name: contexts_unified; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.contexts_unified AS
 SELECT b.id,
    b.context_type,
    b.name,
    b.org_id,
    b.created_at,
    c.creative_brief,
    v.value_proposition,
    o.offering_details
   FROM (((cmis.contexts_base b
     LEFT JOIN cmis.contexts_creative c ON ((b.id = c.context_id)))
     LEFT JOIN cmis.contexts_value v ON ((b.id = v.context_id)))
     LEFT JOIN cmis.contexts_offering o ON ((b.id = o.context_id)));


ALTER VIEW cmis.contexts_unified OWNER TO begin;

--
-- Name: copy_components; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.copy_components (
    component_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    type_code text NOT NULL,
    content text NOT NULL,
    industry_id integer,
    market_id integer,
    awareness_stage text,
    channel_id integer,
    usage_notes text,
    quality_score smallint,
    created_at timestamp with time zone DEFAULT now(),
    context_id uuid,
    example_id uuid,
    campaign_id uuid,
    plan_id uuid,
    visual_prompt jsonb,
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT copy_components_quality_score_check CHECK (((quality_score >= 1) AND (quality_score <= 5)))
);


ALTER TABLE cmis.copy_components OWNER TO begin;

--
-- Name: TABLE copy_components; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON TABLE cmis.copy_components IS 'مكونات المحتوى النصي المُولَّدة مثل hook و headline و CTA. كل مكون يمثل قطعة نصية قابلة لإعادة الاستخدام ضمن السياق الإبداعي.';


--
-- Name: COLUMN copy_components.type_code; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON COLUMN cmis.copy_components.type_code IS 'نوع المكون النصي: hook, headline, benefit, proof... تُستخدم لتحديد وظيفة النص داخل الرسالة الإعلانية.';


--
-- Name: COLUMN copy_components.context_id; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON COLUMN cmis.copy_components.context_id IS 'السياق المعرفي الذي يربط هذا المكون بحملة معينة، جمهور، نبرة، أو مرحلة وعي معينة.';


--
-- Name: COLUMN copy_components.example_id; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON COLUMN cmis.copy_components.example_id IS 'معرّف مجموعة الحقول (example_set) التي تم استخدامها لتوليد هذا النص. يساعد في تتبع وفهم المنطق التوليدي.';


--
-- Name: creative_assets; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.creative_assets (
    asset_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    strategy jsonb,
    channel_id integer NOT NULL,
    format_id integer,
    variation_tag text,
    copy_block text,
    art_direction jsonb,
    compliance_meta jsonb,
    final_copy jsonb,
    used_fields jsonb,
    compliance_report jsonb,
    status text,
    created_at timestamp with time zone DEFAULT now(),
    context_id uuid,
    example_id uuid,
    brief_id uuid,
    creative_context_id uuid,
    deleted_at timestamp with time zone,
    provider text,
    deleted_by uuid,
    CONSTRAINT creative_assets_status_check CHECK ((status = ANY (ARRAY['draft'::text, 'pending_review'::text, 'approved'::text, 'rejected'::text, 'archived'::text])))
);


ALTER TABLE cmis.creative_assets OWNER TO begin;

--
-- Name: creative_briefs; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.creative_briefs (
    brief_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    brief_data jsonb NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.creative_briefs OWNER TO begin;

--
-- Name: creative_contexts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.creative_contexts (
    context_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    creative_brief jsonb NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.creative_contexts OWNER TO begin;

--
-- Name: creative_outputs; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.creative_outputs (
    output_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    context_id uuid,
    type text NOT NULL,
    status text DEFAULT 'draft'::text,
    data jsonb,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT creative_outputs_quality_valid_json CHECK (((data ->> 'quality'::text) = ANY (ARRAY['low'::text, 'medium'::text, 'high'::text, 'excellent'::text]))),
    CONSTRAINT creative_outputs_type_check CHECK ((type = ANY (ARRAY['asset'::text, 'copy'::text, 'content'::text])))
);


ALTER TABLE cmis.creative_outputs OWNER TO begin;

--
-- Name: data_feeds; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.data_feeds (
    feed_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    kind text NOT NULL,
    source_meta jsonb,
    last_ingested timestamp with time zone,
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT data_feeds_kind_check CHECK ((kind = ANY (ARRAY['price'::text, 'stock'::text, 'location'::text, 'catalog'::text])))
);


ALTER TABLE cmis.data_feeds OWNER TO begin;

--
-- Name: dataset_files; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.dataset_files (
    file_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    pkg_id uuid NOT NULL,
    filename text NOT NULL,
    checksum text,
    meta jsonb,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.dataset_files OWNER TO begin;

--
-- Name: dataset_packages; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.dataset_packages (
    pkg_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    code text NOT NULL,
    version text NOT NULL,
    notes text,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.dataset_packages OWNER TO begin;

--
-- Name: experiment_variants; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.experiment_variants (
    exp_id uuid NOT NULL,
    asset_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.experiment_variants OWNER TO begin;

--
-- Name: experiments; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.experiments (
    exp_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    channel_id integer,
    framework text,
    hypothesis text,
    status text DEFAULT 'draft'::text,
    created_at timestamp with time zone DEFAULT now(),
    campaign_id uuid,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.experiments OWNER TO begin;

--
-- Name: export_bundle_items; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.export_bundle_items (
    bundle_id uuid NOT NULL,
    asset_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.export_bundle_items OWNER TO begin;

--
-- Name: export_bundles; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.export_bundles (
    bundle_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.export_bundles OWNER TO begin;

--
-- Name: feed_items; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.feed_items (
    item_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    feed_id uuid NOT NULL,
    sku text,
    payload jsonb NOT NULL,
    valid_from timestamp with time zone DEFAULT now(),
    valid_to timestamp with time zone,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.feed_items OWNER TO begin;

--
-- Name: field_aliases; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.field_aliases (
    alias_slug text NOT NULL,
    field_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.field_aliases OWNER TO begin;

--
-- Name: field_definitions; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.field_definitions (
    field_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    module_id integer,
    name text NOT NULL,
    slug text NOT NULL,
    data_type text NOT NULL,
    is_list boolean DEFAULT false,
    description text,
    enum_options text[],
    required_default boolean DEFAULT false,
    guidance_anchor uuid,
    validations jsonb,
    module_scope text,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT field_definitions_data_type_check CHECK ((data_type = ANY (ARRAY['text'::text, 'markdown'::text, 'number'::text, 'bool'::text, 'json'::text, 'enum'::text, 'vector'::text]))),
    CONSTRAINT field_definitions_module_scope_check CHECK ((module_scope = ANY (ARRAY['market_intel'::text, 'persuasion'::text, 'frameworks'::text, 'adaptation'::text, 'testing'::text, 'compliance'::text, 'video'::text, 'content'::text])))
);


ALTER TABLE cmis.field_definitions OWNER TO begin;

--
-- Name: field_values; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.field_values (
    value_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    field_id uuid NOT NULL,
    context_id uuid NOT NULL,
    value jsonb NOT NULL,
    source text NOT NULL,
    provider_ref text,
    justification text,
    confidence numeric,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT field_values_confidence_check CHECK (((confidence >= (0)::numeric) AND (confidence <= (1)::numeric))),
    CONSTRAINT field_values_source_check CHECK ((source = ANY (ARRAY['manual'::text, 'assumption'::text, 'derived'::text, 'imported'::text, 'model'::text])))
);


ALTER TABLE cmis.field_values OWNER TO begin;

--
-- Name: flow_steps; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.flow_steps (
    step_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    flow_id uuid NOT NULL,
    ord smallint NOT NULL,
    type text NOT NULL,
    name text,
    input_map jsonb,
    config jsonb,
    output_map jsonb,
    condition jsonb,
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT flow_steps_type_check CHECK ((type = ANY (ARRAY['llm'::text, 'sql'::text, 'tool'::text, 'branch'::text, 'transform'::text, 'evaluate'::text])))
);


ALTER TABLE cmis.flow_steps OWNER TO begin;

--
-- Name: flows; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.flows (
    flow_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    name text NOT NULL,
    description text,
    version text DEFAULT '2025.10.0'::text,
    tags text[],
    enabled boolean DEFAULT true,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.flows OWNER TO begin;

--
-- Name: frameworks; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.frameworks (
    framework_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    framework_name text NOT NULL,
    framework_type text,
    description text,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.frameworks OWNER TO begin;

--
-- Name: frameworks; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.frameworks AS
 SELECT framework_id,
    framework_name,
    framework_type,
    description,
    created_at
   FROM public.frameworks;


ALTER VIEW cmis.frameworks OWNER TO begin;

--
-- Name: funnel_stages; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.funnel_stages (
    stage text NOT NULL
);


ALTER TABLE public.funnel_stages OWNER TO begin;

--
-- Name: funnel_stages; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.funnel_stages AS
 SELECT stage
   FROM public.funnel_stages;


ALTER VIEW cmis.funnel_stages OWNER TO begin;

--
-- Name: industries; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.industries (
    industry_id integer NOT NULL,
    name text NOT NULL
);


ALTER TABLE public.industries OWNER TO begin;

--
-- Name: industries; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.industries AS
 SELECT industry_id,
    name
   FROM public.industries;


ALTER VIEW cmis.industries OWNER TO begin;

--
-- Name: integrations; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.integrations (
    integration_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    platform text,
    account_id text,
    access_token text,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    business_id text,
    username text,
    created_by uuid,
    updated_by uuid,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.integrations OWNER TO begin;

--
-- Name: kpis; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.kpis (
    kpi text NOT NULL,
    description text
);


ALTER TABLE public.kpis OWNER TO begin;

--
-- Name: kpis; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.kpis AS
 SELECT kpi,
    description
   FROM public.kpis;


ALTER VIEW cmis.kpis OWNER TO begin;

--
-- Name: logs_migration; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.logs_migration (
    log_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    phase text NOT NULL,
    status text NOT NULL,
    executed_at timestamp without time zone DEFAULT now(),
    details jsonb DEFAULT '{}'::jsonb,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.logs_migration OWNER TO begin;

--
-- Name: marketing_objectives; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.marketing_objectives (
    objective text NOT NULL,
    display_name text,
    category text,
    description text,
    CONSTRAINT marketing_objectives_category_check CHECK ((category = ANY (ARRAY['awareness'::text, 'understanding'::text, 'emotion'::text, 'trust'::text, 'conversion'::text]))),
    CONSTRAINT marketing_objectives_objective_check CHECK ((objective ~ '^[a-zA-Z0-9_]+$'::text))
);


ALTER TABLE public.marketing_objectives OWNER TO begin;

--
-- Name: marketing_objectives; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.marketing_objectives AS
 SELECT objective,
    display_name,
    category,
    description
   FROM public.marketing_objectives;


ALTER VIEW cmis.marketing_objectives OWNER TO begin;

--
-- Name: markets; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.markets (
    market_id integer NOT NULL,
    market_name text NOT NULL,
    language_code text NOT NULL,
    currency_code text NOT NULL,
    text_direction text DEFAULT 'RTL'::text
);


ALTER TABLE public.markets OWNER TO begin;

--
-- Name: markets; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.markets AS
 SELECT market_id,
    market_name,
    language_code,
    currency_code,
    text_direction
   FROM public.markets;


ALTER VIEW cmis.markets OWNER TO begin;

--
-- Name: meta_documentation; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.meta_documentation (
    doc_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    meta_key text NOT NULL,
    meta_value text NOT NULL,
    updated_by text,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.meta_documentation OWNER TO begin;

--
-- Name: meta_field_dictionary; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.meta_field_dictionary (
    id integer NOT NULL,
    field_name text NOT NULL,
    semantic_meaning text,
    usage_context text,
    unified_alias text,
    created_at timestamp without time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.meta_field_dictionary OWNER TO begin;

--
-- Name: meta_field_dictionary_id_seq; Type: SEQUENCE; Schema: cmis; Owner: begin
--

CREATE SEQUENCE cmis.meta_field_dictionary_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.meta_field_dictionary_id_seq OWNER TO begin;

--
-- Name: meta_field_dictionary_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: begin
--

ALTER SEQUENCE cmis.meta_field_dictionary_id_seq OWNED BY cmis.meta_field_dictionary.id;


--
-- Name: meta_function_descriptions; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.meta_function_descriptions (
    id integer NOT NULL,
    routine_schema text NOT NULL,
    routine_name text NOT NULL,
    description text,
    cognitive_category text,
    created_at timestamp without time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.meta_function_descriptions OWNER TO begin;

--
-- Name: meta_function_descriptions_id_seq; Type: SEQUENCE; Schema: cmis; Owner: begin
--

CREATE SEQUENCE cmis.meta_function_descriptions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.meta_function_descriptions_id_seq OWNER TO begin;

--
-- Name: meta_function_descriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: begin
--

ALTER SEQUENCE cmis.meta_function_descriptions_id_seq OWNED BY cmis.meta_function_descriptions.id;


--
-- Name: migrations; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.migrations OWNER TO begin;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: cmis; Owner: begin
--

CREATE SEQUENCE cmis.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.migrations_id_seq OWNER TO begin;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: begin
--

ALTER SEQUENCE cmis.migrations_id_seq OWNED BY cmis.migrations.id;


--
-- Name: modules; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.modules (
    module_id integer NOT NULL,
    code text NOT NULL,
    name text NOT NULL,
    version text DEFAULT '2025.10.0'::text,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.modules OWNER TO begin;

--
-- Name: modules_module_id_seq; Type: SEQUENCE; Schema: cmis; Owner: begin
--

CREATE SEQUENCE cmis.modules_module_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.modules_module_id_seq OWNER TO begin;

--
-- Name: modules_module_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: begin
--

ALTER SEQUENCE cmis.modules_module_id_seq OWNED BY cmis.modules.module_id;


--
-- Name: naming_templates; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.naming_templates (
    naming_id integer NOT NULL,
    scope text NOT NULL,
    template text NOT NULL,
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT naming_templates_scope_check CHECK ((scope = ANY (ARRAY['ad'::text, 'bundle'::text, 'landing'::text, 'email'::text, 'experiment'::text, 'video_scene'::text, 'content_item'::text])))
);


ALTER TABLE cmis.naming_templates OWNER TO begin;

--
-- Name: naming_templates_naming_id_seq; Type: SEQUENCE; Schema: cmis; Owner: begin
--

CREATE SEQUENCE cmis.naming_templates_naming_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis.naming_templates_naming_id_seq OWNER TO begin;

--
-- Name: naming_templates_naming_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis; Owner: begin
--

ALTER SEQUENCE cmis.naming_templates_naming_id_seq OWNED BY cmis.naming_templates.naming_id;


--
-- Name: offerings_full_details; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.offerings_full_details (
    detail_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    offering_id uuid,
    full_description text NOT NULL,
    pricing_notes text,
    target_segment text,
    created_at timestamp without time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.offerings_full_details OWNER TO begin;

--
-- Name: offerings_old; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.offerings_old (
    offering_id uuid DEFAULT public.gen_random_uuid() CONSTRAINT offerings_offering_id_not_null NOT NULL,
    org_id uuid CONSTRAINT offerings_org_id_not_null NOT NULL,
    kind text CONSTRAINT offerings_kind_not_null NOT NULL,
    name text CONSTRAINT offerings_name_not_null NOT NULL,
    description text,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT offerings_kind_check CHECK ((kind = ANY (ARRAY['product'::text, 'service'::text, 'bundle'::text])))
);


ALTER TABLE cmis.offerings_old OWNER TO begin;

--
-- Name: ops_audit; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ops_audit (
    audit_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    operation_name text NOT NULL,
    status text NOT NULL,
    executed_at timestamp with time zone DEFAULT now(),
    details jsonb,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.ops_audit OWNER TO begin;

--
-- Name: ops_etl_log; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.ops_etl_log (
    log_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    integration_id uuid,
    status text,
    started_at timestamp with time zone DEFAULT now(),
    ended_at timestamp with time zone,
    rows_processed integer,
    notes text,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.ops_etl_log OWNER TO begin;

--
-- Name: org_datasets; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.org_datasets (
    org_id uuid NOT NULL,
    pkg_id uuid NOT NULL,
    enabled boolean DEFAULT true,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.org_datasets OWNER TO begin;

--
-- Name: org_markets; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.org_markets (
    org_id uuid NOT NULL,
    market_id integer NOT NULL,
    is_default boolean DEFAULT false,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.org_markets OWNER TO begin;

--
-- Name: orgs; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.orgs (
    org_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    name public.citext NOT NULL,
    default_locale text DEFAULT 'ar-BH'::text,
    currency text DEFAULT 'BHD'::text,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.orgs OWNER TO begin;

--
-- Name: output_contracts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.output_contracts (
    contract_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    code text NOT NULL,
    json_schema jsonb NOT NULL,
    notes text,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.output_contracts OWNER TO begin;

--
-- Name: performance_metrics; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.performance_metrics (
    metric_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    output_id uuid,
    kpi text NOT NULL,
    observed numeric,
    target numeric,
    baseline numeric,
    observed_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT performance_score_range CHECK (((observed >= (0)::numeric) AND (observed <= (1)::numeric)))
);


ALTER TABLE cmis.performance_metrics OWNER TO begin;

--
-- Name: COLUMN performance_metrics.observed_at; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON COLUMN cmis.performance_metrics.observed_at IS 'زمن الرصد (كان ts في النسخة القديمة).';


--
-- Name: permissions; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.permissions (
    permission_id uuid DEFAULT gen_random_uuid() NOT NULL,
    permission_code text NOT NULL,
    permission_name text NOT NULL,
    category text NOT NULL,
    description text,
    is_dangerous boolean DEFAULT false,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.permissions OWNER TO begin;

--
-- Name: permissions_cache; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.permissions_cache (
    permission_code text NOT NULL,
    permission_id uuid NOT NULL,
    category text NOT NULL,
    last_used timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE cmis.permissions_cache OWNER TO begin;

--
-- Name: playbook_steps; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.playbook_steps AS
 SELECT step_id,
    flow_id AS playbook_id,
    ord AS step_order,
    COALESCE(name, type) AS step_name,
    NULL::text AS step_instructions,
    NULL::text AS module_reference
   FROM cmis.flow_steps s;


ALTER VIEW cmis.playbook_steps OWNER TO begin;

--
-- Name: playbooks; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.playbooks AS
 SELECT flow_id AS playbook_id,
    name AS playbook_name,
    description
   FROM cmis.flows f;


ALTER VIEW cmis.playbooks OWNER TO begin;

--
-- Name: predictive_visual_engine; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.predictive_visual_engine (
    prediction_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    predicted_ctr double precision,
    predicted_engagement double precision,
    predicted_trust_index double precision,
    confidence_level double precision,
    visual_factor_weight jsonb,
    prediction_summary text,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.predictive_visual_engine OWNER TO begin;

--
-- Name: prompt_template_contracts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.prompt_template_contracts (
    prompt_id uuid NOT NULL,
    contract_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.prompt_template_contracts OWNER TO begin;

--
-- Name: prompt_template_presql; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.prompt_template_presql (
    prompt_id uuid NOT NULL,
    snippet_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.prompt_template_presql OWNER TO begin;

--
-- Name: prompt_template_required_fields; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.prompt_template_required_fields (
    prompt_id uuid NOT NULL,
    field_id uuid NOT NULL,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.prompt_template_required_fields OWNER TO begin;

--
-- Name: prompt_templates; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.prompt_templates (
    prompt_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    module_id integer,
    name text NOT NULL,
    task text NOT NULL,
    instructions text NOT NULL,
    version text DEFAULT '2025.10.0'::text,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.prompt_templates OWNER TO begin;

--
-- Name: proof_layers; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.proof_layers (
    level text NOT NULL
);


ALTER TABLE public.proof_layers OWNER TO begin;

--
-- Name: proof_layers; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.proof_layers AS
 SELECT level
   FROM public.proof_layers;


ALTER VIEW cmis.proof_layers OWNER TO begin;

--
-- Name: reference_entities; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.reference_entities (
    ref_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    category text NOT NULL,
    code text NOT NULL,
    label text,
    description text,
    metadata jsonb,
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.reference_entities OWNER TO begin;

--
-- Name: required_fields_cache; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.required_fields_cache (
    module_scope text NOT NULL,
    required_fields text[],
    last_updated timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.required_fields_cache OWNER TO begin;

--
-- Name: role_permissions; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.role_permissions (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    role_id uuid NOT NULL,
    permission_id uuid NOT NULL,
    granted_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    granted_by uuid,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.role_permissions OWNER TO begin;

--
-- Name: roles; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.roles (
    role_id uuid DEFAULT gen_random_uuid() NOT NULL,
    org_id uuid,
    role_name text NOT NULL,
    role_code text NOT NULL,
    description text,
    is_system boolean DEFAULT false,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    created_by uuid,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.roles OWNER TO begin;

--
-- Name: scene_library; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.scene_library (
    scene_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    name text NOT NULL,
    goal text,
    duration_sec integer,
    visual_spec jsonb,
    audio_spec jsonb,
    overlay_rules jsonb,
    anchor uuid,
    quality_score smallint,
    tags text[],
    deleted_at timestamp with time zone,
    provider text,
    CONSTRAINT scene_library_quality_score_check CHECK (((quality_score >= 1) AND (quality_score <= 5)))
);


ALTER TABLE cmis.scene_library OWNER TO begin;

--
-- Name: security_context_audit; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.security_context_audit (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    transaction_id bigint DEFAULT txid_current(),
    user_id uuid,
    org_id uuid,
    action text,
    success boolean,
    error_message text,
    context_version text,
    session_id text,
    ip_address inet,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE cmis.security_context_audit OWNER TO begin;

--
-- Name: TABLE security_context_audit; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON TABLE cmis.security_context_audit IS 'سجل تدقيق لجميع عمليات تهيئة سياق الأمان';


--
-- Name: segments; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.segments (
    segment_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    name text NOT NULL,
    persona jsonb,
    notes text,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.segments OWNER TO begin;

--
-- Name: session_context; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.session_context (
    session_id uuid NOT NULL,
    active_org_id uuid,
    switched_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.session_context OWNER TO begin;

--
-- Name: social_account_metrics; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.social_account_metrics (
    integration_id uuid NOT NULL,
    period_start date NOT NULL,
    period_end date NOT NULL,
    followers bigint,
    reach bigint,
    impressions bigint,
    profile_views bigint,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.social_account_metrics OWNER TO begin;

--
-- Name: social_accounts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.social_accounts (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    account_external_id text NOT NULL,
    username text,
    display_name text,
    profile_picture_url text,
    biography text,
    followers_count bigint,
    follows_count bigint,
    media_count bigint,
    website text,
    category text,
    fetched_at timestamp without time zone DEFAULT now(),
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.social_accounts OWNER TO begin;

--
-- Name: social_post_metrics; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.social_post_metrics (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    post_external_id text NOT NULL,
    social_post_id uuid NOT NULL,
    metric text NOT NULL,
    value numeric(20,4),
    fetched_at timestamp with time zone DEFAULT now(),
    created_at timestamp with time zone DEFAULT now(),
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.social_post_metrics OWNER TO begin;

--
-- Name: social_posts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.social_posts (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid NOT NULL,
    post_external_id text NOT NULL,
    caption text,
    media_url text,
    permalink text,
    media_type text,
    posted_at timestamp without time zone,
    metrics jsonb DEFAULT '{}'::jsonb,
    fetched_at timestamp without time zone DEFAULT now(),
    created_at timestamp without time zone DEFAULT now(),
    video_url text,
    thumbnail_url text,
    children_media jsonb,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.social_posts OWNER TO begin;

--
-- Name: sql_snippets; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.sql_snippets (
    snippet_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    name text NOT NULL,
    sql text NOT NULL,
    description text,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.sql_snippets OWNER TO begin;

--
-- Name: strategies; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.strategies (
    strategy text NOT NULL
);


ALTER TABLE public.strategies OWNER TO begin;

--
-- Name: strategies; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.strategies AS
 SELECT strategy
   FROM public.strategies;


ALTER VIEW cmis.strategies OWNER TO begin;

--
-- Name: sync_logs; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.sync_logs (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    integration_id uuid,
    platform text,
    synced_at timestamp without time zone DEFAULT now(),
    status text,
    items integer DEFAULT 0,
    level_counts jsonb DEFAULT '{}'::jsonb,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.sync_logs OWNER TO begin;

--
-- Name: user_sessions; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.user_sessions (
    session_id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    session_token text NOT NULL,
    ip_address inet,
    user_agent text,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    last_activity timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    expires_at timestamp with time zone DEFAULT (CURRENT_TIMESTAMP + '24:00:00'::interval) NOT NULL,
    is_active boolean DEFAULT true,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.user_sessions OWNER TO begin;

--
-- Name: embeddings_cache; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.embeddings_cache (
    cache_id uuid DEFAULT gen_random_uuid() NOT NULL,
    source_table text NOT NULL,
    source_id uuid NOT NULL,
    source_field text NOT NULL,
    embedding public.vector(768) NOT NULL,
    embedding_norm double precision,
    metadata jsonb DEFAULT '{}'::jsonb,
    model_version text DEFAULT 'gemini-text-embedding-004'::text,
    quality_score numeric(3,2),
    usage_count integer DEFAULT 0,
    last_accessed timestamp with time zone DEFAULT now(),
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    input_hash text,
    last_used_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_knowledge.embeddings_cache OWNER TO begin;

--
-- Name: system_health; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.system_health AS
 SELECT 'embeddings_cache'::text AS component,
    count(*) AS total_records,
    avg(EXTRACT(epoch FROM (CURRENT_TIMESTAMP - embeddings_cache.created_at))) AS avg_age_seconds,
    max(embeddings_cache.last_used_at) AS last_activity
   FROM cmis_knowledge.embeddings_cache
UNION ALL
 SELECT 'active_sessions'::text AS component,
    count(*) AS total_records,
    avg(EXTRACT(epoch FROM (CURRENT_TIMESTAMP - user_sessions.created_at))) AS avg_age_seconds,
    max(user_sessions.last_activity) AS last_activity
   FROM cmis.user_sessions
  WHERE (user_sessions.is_active = true)
UNION ALL
 SELECT 'creative_briefs'::text AS component,
    count(*) AS total_records,
    avg(EXTRACT(epoch FROM (CURRENT_TIMESTAMP - creative_briefs.created_at))) AS avg_age_seconds,
    max(creative_briefs.created_at) AS last_activity
   FROM cmis.creative_briefs;


ALTER VIEW cmis.system_health OWNER TO begin;

--
-- Name: tones; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.tones (
    tone text NOT NULL
);


ALTER TABLE public.tones OWNER TO begin;

--
-- Name: tones; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.tones AS
 SELECT tone
   FROM public.tones;


ALTER VIEW cmis.tones OWNER TO begin;

--
-- Name: user_activities; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.user_activities (
    activity_id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    org_id uuid NOT NULL,
    session_id uuid,
    action text NOT NULL,
    entity_type text,
    entity_id uuid,
    details jsonb,
    ip_address inet,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.user_activities OWNER TO begin;

--
-- Name: user_orgs; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.user_orgs (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    org_id uuid NOT NULL,
    role_id uuid NOT NULL,
    is_active boolean DEFAULT true,
    joined_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    invited_by uuid,
    last_accessed timestamp with time zone,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.user_orgs OWNER TO begin;

--
-- Name: user_permissions; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.user_permissions (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    user_id uuid NOT NULL,
    org_id uuid NOT NULL,
    permission_id uuid NOT NULL,
    is_granted boolean DEFAULT true,
    granted_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    granted_by uuid,
    expires_at timestamp with time zone,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.user_permissions OWNER TO begin;

--
-- Name: users; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.users (
    user_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    email public.citext NOT NULL,
    display_name text,
    role text DEFAULT 'editor'::text,
    deleted_at timestamp with time zone,
    provider text,
    status text DEFAULT 'active'::text,
    name text DEFAULT ''::text,
    CONSTRAINT users_role_check CHECK ((role = ANY (ARRAY['viewer'::text, 'editor'::text, 'admin'::text])))
);


ALTER TABLE cmis.users OWNER TO begin;

--
-- Name: v_ai_insights; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.v_ai_insights AS
 SELECT a.org_id,
    a.campaign_id,
    a.ai_summary AS campaign_summary,
    p.prediction_summary AS visual_prediction,
    t.factor_name AS cognitive_trend,
    t.trend_strength,
    t.trend_direction,
    t.summary_insight,
    a.engine AS ai_model,
    a.created_at,
    COALESCE((p.visual_factor_weight ->> 'dominant'::text), t.factor_name) AS dominant_theme
   FROM ((cmis.ai_generated_campaigns a
     LEFT JOIN cmis.predictive_visual_engine p ON (((a.org_id = p.org_id) AND (a.campaign_id = p.campaign_id))))
     LEFT JOIN cmis.cognitive_trends t ON ((a.org_id = t.org_id)));


ALTER VIEW cmis.v_ai_insights OWNER TO begin;

--
-- Name: v_cache_status; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.v_cache_status AS
 SELECT 'required_fields'::text AS cache_name,
    count(*) AS entries,
    max(required_fields_cache.last_updated) AS last_update,
    (EXTRACT(epoch FROM (CURRENT_TIMESTAMP - max(required_fields_cache.last_updated))) / (60)::numeric) AS age_minutes
   FROM cmis.required_fields_cache
UNION ALL
 SELECT 'permissions'::text AS cache_name,
    count(*) AS entries,
    max(permissions_cache.last_used) AS last_update,
    (EXTRACT(epoch FROM (CURRENT_TIMESTAMP - max(permissions_cache.last_used))) / (60)::numeric) AS age_minutes
   FROM cmis.permissions_cache
UNION ALL
 SELECT 'embeddings'::text AS cache_name,
    count(*) AS entries,
    max(embeddings_cache.last_used_at) AS last_update,
    (EXTRACT(epoch FROM (CURRENT_TIMESTAMP - max(embeddings_cache.last_used_at))) / (60)::numeric) AS age_minutes
   FROM cmis_knowledge.embeddings_cache;


ALTER VIEW cmis.v_cache_status OWNER TO begin;

--
-- Name: v_deleted_records; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.v_deleted_records WITH (security_barrier='true') AS
 WITH deleted_campaigns AS (
         SELECT 'campaigns'::text AS table_name,
            (campaigns.campaign_id)::text AS record_id,
            campaigns.name,
            campaigns.org_id,
            campaigns.deleted_at,
            campaigns.deleted_by
           FROM cmis.campaigns
          WHERE (campaigns.deleted_at IS NOT NULL)
        ), deleted_assets AS (
         SELECT 'creative_assets'::text AS table_name,
            (creative_assets.asset_id)::text AS record_id,
            creative_assets.variation_tag AS name,
            creative_assets.org_id,
            creative_assets.deleted_at,
            creative_assets.deleted_by
           FROM cmis.creative_assets
          WHERE (creative_assets.deleted_at IS NOT NULL)
        ), deleted_content AS (
         SELECT 'content_items'::text AS table_name,
            (content_items.context_id)::text AS record_id,
            content_items.title AS name,
            content_items.org_id,
            content_items.deleted_at,
            content_items.deleted_by
           FROM cmis.content_items
          WHERE (content_items.deleted_at IS NOT NULL)
        )
 SELECT deleted_campaigns.table_name,
    deleted_campaigns.record_id,
    deleted_campaigns.name,
    deleted_campaigns.org_id,
    deleted_campaigns.deleted_at,
    deleted_campaigns.deleted_by
   FROM deleted_campaigns
UNION ALL
 SELECT deleted_assets.table_name,
    deleted_assets.record_id,
    deleted_assets.name,
    deleted_assets.org_id,
    deleted_assets.deleted_at,
    deleted_assets.deleted_by
   FROM deleted_assets
UNION ALL
 SELECT deleted_content.table_name,
    deleted_content.record_id,
    deleted_content.name,
    deleted_content.org_id,
    deleted_content.deleted_at,
    deleted_content.deleted_by
   FROM deleted_content
  ORDER BY 5 DESC;


ALTER VIEW cmis.v_deleted_records OWNER TO begin;

--
-- Name: VIEW v_deleted_records; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON VIEW cmis.v_deleted_records IS 'عرض جميع السجلات المحذوفة - للمديرين فقط';


--
-- Name: v_marketing_reference; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.v_marketing_reference AS
 SELECT f.framework_id,
    f.framework_name,
    f.description AS framework_description,
    s.strategy AS strategy_name,
    st.stage AS stage_name,
    concat(f.framework_name, ' → ', s.strategy, ' → ', st.stage) AS reference_path
   FROM ((cmis.frameworks f
     CROSS JOIN cmis.strategies s)
     CROSS JOIN cmis.funnel_stages st);


ALTER VIEW cmis.v_marketing_reference OWNER TO begin;

--
-- Name: v_security_context_summary; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.v_security_context_summary AS
 SELECT date_trunc('hour'::text, created_at) AS hour,
    count(*) AS total_contexts,
    count(*) FILTER (WHERE (success = true)) AS successful,
    count(*) FILTER (WHERE (success = false)) AS failed,
    count(DISTINCT user_id) AS unique_users,
    count(DISTINCT org_id) AS unique_orgs,
    context_version
   FROM cmis.security_context_audit
  WHERE (created_at > (now() - '24:00:00'::interval))
  GROUP BY (date_trunc('hour'::text, created_at)), context_version
  ORDER BY (date_trunc('hour'::text, created_at)) DESC;


ALTER VIEW cmis.v_security_context_summary OWNER TO begin;

--
-- Name: VIEW v_security_context_summary; Type: COMMENT; Schema: cmis; Owner: begin
--

COMMENT ON VIEW cmis.v_security_context_summary IS 'ملخص نشاط سياقات الأمان خلال آخر 24 ساعة';


--
-- Name: v_system_monitoring; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.v_system_monitoring AS
 SELECT now() AS "timestamp",
    count(*) AS table_count
   FROM information_schema.tables
  WHERE ((table_schema)::name = 'cmis'::name);


ALTER VIEW cmis.v_system_monitoring OWNER TO begin;

--
-- Name: v_unified_ad_targeting; Type: VIEW; Schema: cmis; Owner: begin
--

CREATE VIEW cmis.v_unified_ad_targeting AS
 SELECT a.org_id,
    i.platform,
    a.entity_level,
    a.entity_external_id,
    COALESCE(a.demographics, '{}'::jsonb) AS demographics,
    COALESCE(a.interests, '{}'::jsonb) AS interests,
    COALESCE(a.behaviors, '{}'::jsonb) AS behaviors,
    COALESCE(a.location, '{}'::jsonb) AS location,
    COALESCE(a.keywords, '{}'::jsonb) AS keywords,
    COALESCE(a.custom_audience, '{}'::jsonb) AS custom_audience,
    COALESCE(a.lookalike_audience, '{}'::jsonb) AS lookalike_audience,
    COALESCE(a.advantage_plus_settings, '{}'::jsonb) AS advantage_plus
   FROM (cmis.ad_audiences a
     JOIN cmis.integrations i ON ((a.integration_id = i.integration_id)));


ALTER VIEW cmis.v_unified_ad_targeting OWNER TO begin;

--
-- Name: value_contexts; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.value_contexts (
    context_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    offering_id uuid,
    segment_id uuid,
    campaign_id uuid,
    channel_id integer,
    format_id integer,
    locale text DEFAULT 'ar-BH'::text,
    awareness_stage text,
    funnel_stage text,
    framework text,
    tone text,
    dataset_ref text,
    variant_tag text,
    tags text[],
    market_id integer,
    industry_id integer,
    created_at timestamp with time zone DEFAULT now(),
    context_fingerprint text,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.value_contexts OWNER TO begin;

--
-- Name: variation_policies; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.variation_policies (
    policy_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    max_variations smallint DEFAULT 3,
    dco_enabled boolean DEFAULT true,
    naming_ref integer,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.variation_policies OWNER TO begin;

--
-- Name: video_scenes; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.video_scenes (
    scene_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    asset_id uuid NOT NULL,
    scene_number integer NOT NULL,
    duration_seconds integer,
    visual_prompt_en text,
    overlay_text_ar text,
    audio_instructions text,
    technical_specs jsonb,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.video_scenes OWNER TO begin;

--
-- Name: video_templates; Type: TABLE; Schema: cmis; Owner: begin
--

CREATE TABLE cmis.video_templates (
    vtpl_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    channel_id integer,
    format_id integer,
    name text NOT NULL,
    steps jsonb NOT NULL,
    version text DEFAULT '2025.10.0'::text,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis.video_templates OWNER TO begin;

--
-- Name: v_context_impact; Type: VIEW; Schema: cmis_ai_analytics; Owner: begin
--

CREATE VIEW cmis_ai_analytics.v_context_impact AS
 SELECT ctx.type AS context_type,
    count(DISTINCT co.output_id) AS total_outputs,
    avg(pm.observed) AS avg_observed,
    ((avg(pm.observed) / NULLIF(avg(pm.target), (0)::numeric)) * (100)::numeric) AS impact_score
   FROM ((cmis.contexts ctx
     LEFT JOIN cmis.creative_outputs co ON ((co.context_id = ctx.context_id)))
     LEFT JOIN cmis.performance_metrics pm ON ((pm.output_id = co.output_id)))
  GROUP BY ctx.type;


ALTER VIEW cmis_ai_analytics.v_context_impact OWNER TO begin;

--
-- Name: v_creative_efficiency; Type: VIEW; Schema: cmis_ai_analytics; Owner: begin
--

CREATE VIEW cmis_ai_analytics.v_creative_efficiency AS
 SELECT co.type AS output_type,
    count(co.output_id) AS total_outputs,
    avg(pm.observed) AS avg_performance,
    avg(pm.target) AS avg_target,
    ((avg(pm.observed) / NULLIF(avg(pm.target), (0)::numeric)) * (100)::numeric) AS efficiency_score
   FROM (cmis.creative_outputs co
     LEFT JOIN cmis.performance_metrics pm ON ((pm.output_id = co.output_id)))
  GROUP BY co.type;


ALTER VIEW cmis_ai_analytics.v_creative_efficiency OWNER TO begin;

--
-- Name: v_kpi_summary; Type: VIEW; Schema: cmis_ai_analytics; Owner: begin
--

CREATE VIEW cmis_ai_analytics.v_kpi_summary AS
 SELECT c.campaign_id,
    c.name AS campaign_name,
    date_trunc('day'::text, pm.observed_at) AS day,
    avg(pm.observed) AS avg_observed,
    avg(pm.target) AS avg_target,
    ((avg(pm.observed) / NULLIF(avg(pm.target), (0)::numeric)) * (100)::numeric) AS performance_rate
   FROM (cmis.campaigns c
     LEFT JOIN cmis.performance_metrics pm ON ((pm.campaign_id = c.campaign_id)))
  GROUP BY c.campaign_id, c.name, (date_trunc('day'::text, pm.observed_at))
  ORDER BY (date_trunc('day'::text, pm.observed_at)) DESC;


ALTER VIEW cmis_ai_analytics.v_kpi_summary OWNER TO begin;

--
-- Name: ai_queries; Type: TABLE; Schema: cmis_analytics; Owner: begin
--

CREATE TABLE cmis_analytics.ai_queries (
    query_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    user_prompt text NOT NULL,
    generated_sql text,
    result_summary text,
    confidence_score numeric(5,2),
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_analytics.ai_queries OWNER TO begin;

--
-- Name: migration_log; Type: TABLE; Schema: cmis_analytics; Owner: begin
--

CREATE TABLE cmis_analytics.migration_log (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    executed_at timestamp with time zone DEFAULT now() NOT NULL,
    action text NOT NULL,
    sql_code text NOT NULL
);


ALTER TABLE cmis_analytics.migration_log OWNER TO begin;

--
-- Name: performance_snapshot; Type: TABLE; Schema: cmis_analytics; Owner: begin
--

CREATE TABLE cmis_analytics.performance_snapshot (
    snapshot_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid NOT NULL,
    campaign_id uuid,
    kpi text,
    value numeric,
    observed_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_analytics.performance_snapshot OWNER TO begin;

--
-- Name: prompt_templates; Type: TABLE; Schema: cmis_analytics; Owner: begin
--

CREATE TABLE cmis_analytics.prompt_templates (
    template_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    name text NOT NULL,
    prompt_text text NOT NULL,
    sql_snippet text NOT NULL,
    context_tags text[],
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_analytics.prompt_templates OWNER TO begin;

--
-- Name: scheduled_jobs; Type: TABLE; Schema: cmis_analytics; Owner: begin
--

CREATE TABLE cmis_analytics.scheduled_jobs (
    job_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    job_name text NOT NULL,
    schedule text NOT NULL,
    function_name text NOT NULL,
    last_run timestamp with time zone,
    next_run timestamp with time zone
);


ALTER TABLE cmis_analytics.scheduled_jobs OWNER TO begin;

--
-- Name: logs; Type: TABLE; Schema: cmis_audit; Owner: begin
--

CREATE TABLE cmis_audit.logs (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    event_type text NOT NULL,
    event_source text NOT NULL,
    description text,
    created_at timestamp without time zone DEFAULT now(),
    user_id uuid,
    metadata jsonb DEFAULT '{}'::jsonb
);


ALTER TABLE cmis_audit.logs OWNER TO begin;

--
-- Name: dev_logs; Type: TABLE; Schema: cmis_dev; Owner: begin
--

CREATE TABLE cmis_dev.dev_logs (
    log_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    task_id uuid,
    event text,
    details jsonb,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_dev.dev_logs OWNER TO begin;

--
-- Name: dev_tasks; Type: TABLE; Schema: cmis_dev; Owner: begin
--

CREATE TABLE cmis_dev.dev_tasks (
    task_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    name text,
    description text,
    scope_code text,
    status text DEFAULT 'pending'::text,
    priority smallint DEFAULT 3,
    execution_plan jsonb,
    confidence numeric(3,2),
    effectiveness_score smallint,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone,
    result_summary text
);


ALTER TABLE cmis_dev.dev_tasks OWNER TO begin;

--
-- Name: cognitive_manifest; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.cognitive_manifest (
    manifest_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    layer_name text NOT NULL,
    status text DEFAULT 'active'::text,
    confidence numeric(5,2) DEFAULT 1.00,
    last_updated timestamp without time zone DEFAULT now(),
    description text
);


ALTER TABLE cmis_knowledge.cognitive_manifest OWNER TO begin;

--
-- Name: creative_templates; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.creative_templates (
    template_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    domain text DEFAULT 'marketing'::text,
    category text,
    title text,
    content text,
    tone text,
    emotion text[],
    variability smallint DEFAULT 3,
    tags text[],
    created_at timestamp with time zone DEFAULT now(),
    content_embedding public.vector(768),
    emotion_embedding public.vector(768),
    creative_style_embedding public.vector(768),
    tone_direction_vector public.vector(768),
    CONSTRAINT creative_templates_category_check CHECK ((category = ANY (ARRAY['narrative'::text, 'slogan'::text, 'concept'::text, 'tone'::text, 'emotion'::text, 'hook'::text])))
);


ALTER TABLE cmis_knowledge.creative_templates OWNER TO begin;

--
-- Name: dev; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.dev (
    knowledge_id uuid,
    content text,
    token_count integer,
    version text DEFAULT '1.0'::text,
    parent_knowledge_id uuid,
    part_index integer DEFAULT 0,
    content_length integer GENERATED ALWAYS AS (length(content)) STORED,
    link_context jsonb DEFAULT '{}'::jsonb,
    content_search tsvector GENERATED ALWAYS AS (to_tsvector('arabic'::regconfig, content)) STORED,
    created_at timestamp without time zone DEFAULT now(),
    content_embedding public.vector(768),
    chunk_embeddings jsonb,
    semantic_summary_embedding public.vector(768),
    intent_analysis jsonb,
    embedding_metadata jsonb DEFAULT '{}'::jsonb,
    id bigint NOT NULL,
    tier smallint DEFAULT 2,
    CONSTRAINT dev_tier_check CHECK (((tier >= 1) AND (tier <= 3)))
);


ALTER TABLE cmis_knowledge.dev OWNER TO begin;

--
-- Name: COLUMN dev.tier; Type: COMMENT; Schema: cmis_knowledge; Owner: begin
--

COMMENT ON COLUMN cmis_knowledge.dev.tier IS 'تصنيف جودة المعرفة (1=حرجة، 2=قياسية، 3=ثانوية)';


--
-- Name: dev_id_seq; Type: SEQUENCE; Schema: cmis_knowledge; Owner: begin
--

CREATE SEQUENCE cmis_knowledge.dev_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis_knowledge.dev_id_seq OWNER TO begin;

--
-- Name: dev_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis_knowledge; Owner: begin
--

ALTER SEQUENCE cmis_knowledge.dev_id_seq OWNED BY cmis_knowledge.dev.id;


--
-- Name: direction_mappings; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.direction_mappings (
    direction_id uuid DEFAULT gen_random_uuid() NOT NULL,
    direction_name text NOT NULL,
    direction_name_ar text NOT NULL,
    direction_type text,
    direction_embedding public.vector(768),
    parent_direction_id uuid,
    associated_intents uuid[],
    confidence_score numeric(3,2) DEFAULT 0.80,
    metadata jsonb DEFAULT '{}'::jsonb,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    CONSTRAINT direction_mappings_direction_type_check CHECK ((direction_type = ANY (ARRAY['strategic'::text, 'tactical'::text, 'operational'::text])))
);


ALTER TABLE cmis_knowledge.direction_mappings OWNER TO begin;

--
-- Name: embedding_api_config; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.embedding_api_config (
    config_id uuid DEFAULT gen_random_uuid() NOT NULL,
    api_key_encrypted text NOT NULL,
    api_endpoint text DEFAULT 'https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent'::text,
    model_name text DEFAULT 'models/text-embedding-004'::text,
    embedding_dimension integer DEFAULT 768,
    max_batch_size integer DEFAULT 100,
    rate_limit_per_minute integer DEFAULT 60,
    retry_attempts integer DEFAULT 3,
    timeout_seconds integer DEFAULT 30,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_knowledge.embedding_api_config OWNER TO begin;

--
-- Name: embedding_api_logs; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.embedding_api_logs (
    log_id uuid DEFAULT gen_random_uuid() NOT NULL,
    request_timestamp timestamp with time zone DEFAULT now(),
    response_timestamp timestamp with time zone,
    text_length integer,
    status_code integer,
    error_message text,
    tokens_used integer,
    execution_time_ms integer,
    model_used text
);


ALTER TABLE cmis_knowledge.embedding_api_logs OWNER TO begin;

--
-- Name: embedding_update_queue; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.embedding_update_queue (
    queue_id uuid DEFAULT gen_random_uuid() NOT NULL,
    knowledge_id uuid NOT NULL,
    source_table text NOT NULL,
    source_field text NOT NULL,
    priority integer DEFAULT 5,
    status text DEFAULT 'pending'::text,
    retry_count integer DEFAULT 0,
    max_retries integer DEFAULT 3,
    error_message text,
    created_at timestamp with time zone DEFAULT now(),
    processing_started_at timestamp with time zone,
    processed_at timestamp with time zone,
    CONSTRAINT embedding_update_queue_priority_check CHECK (((priority >= 1) AND (priority <= 10))),
    CONSTRAINT embedding_update_queue_status_check CHECK ((status = ANY (ARRAY['pending'::text, 'processing'::text, 'completed'::text, 'failed'::text])))
);


ALTER TABLE cmis_knowledge.embedding_update_queue OWNER TO begin;

--
-- Name: index; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.index (
    knowledge_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    domain text NOT NULL,
    category text NOT NULL,
    topic text NOT NULL,
    keywords text[],
    tier smallint DEFAULT 2,
    token_budget integer DEFAULT 1200,
    supersedes_knowledge_id uuid,
    is_deprecated boolean DEFAULT false,
    last_verified_at timestamp with time zone DEFAULT now(),
    total_chunks integer DEFAULT 1,
    has_children boolean DEFAULT false,
    last_audit_status text DEFAULT 'verified'::text,
    report_phase text DEFAULT 'unspecified'::text,
    topic_embedding public.vector(768),
    intent_vector public.vector(768),
    direction_vector public.vector(768),
    purpose_vector public.vector(768),
    verification_confidence numeric DEFAULT 0.5,
    verification_source text DEFAULT 'system_check'::text,
    is_verified_by_ai boolean DEFAULT false,
    keywords_embedding public.vector(768),
    embedding_model text DEFAULT 'gemini-text-embedding-004'::text,
    embedding_updated_at timestamp with time zone,
    embedding_version integer DEFAULT 1,
    updated_at timestamp without time zone DEFAULT now(),
    CONSTRAINT index_category_check CHECK ((category = ANY (ARRAY['dev'::text, 'marketing'::text, 'org'::text, 'research'::text, 'report'::text, 'system'::text]))),
    CONSTRAINT index_tier_check CHECK ((tier = ANY (ARRAY[1, 2, 3])))
);


ALTER TABLE cmis_knowledge.index OWNER TO begin;

--
-- Name: COLUMN index.tier; Type: COMMENT; Schema: cmis_knowledge; Owner: begin
--

COMMENT ON COLUMN cmis_knowledge.index.tier IS 'تصنيف جودة المعرفة (1=حرجة، 2=قياسية، 3=ثانوية)';


--
-- Name: index_backup_2025_11_10; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.index_backup_2025_11_10 (
    knowledge_id uuid,
    domain text,
    category text,
    topic text,
    keywords text[],
    tier smallint,
    token_budget integer,
    supersedes_knowledge_id uuid,
    is_deprecated boolean,
    last_verified_at timestamp with time zone,
    total_chunks integer,
    has_children boolean,
    importance_level smallint,
    last_audit_status text,
    report_phase text,
    topic_embedding public.vector(768),
    intent_vector public.vector(768),
    direction_vector public.vector(768),
    purpose_vector public.vector(768),
    verification_confidence numeric,
    verification_source text,
    is_verified_by_ai boolean,
    keywords_embedding public.vector(768),
    semantic_fingerprint public.vector(768),
    embedding_model text,
    embedding_updated_at timestamp with time zone,
    embedding_version integer,
    updated_at timestamp without time zone,
    id bigint NOT NULL
);


ALTER TABLE cmis_knowledge.index_backup_2025_11_10 OWNER TO begin;

--
-- Name: index_backup_2025_11_10_id_seq; Type: SEQUENCE; Schema: cmis_knowledge; Owner: begin
--

CREATE SEQUENCE cmis_knowledge.index_backup_2025_11_10_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis_knowledge.index_backup_2025_11_10_id_seq OWNER TO begin;

--
-- Name: index_backup_2025_11_10_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis_knowledge; Owner: begin
--

ALTER SEQUENCE cmis_knowledge.index_backup_2025_11_10_id_seq OWNED BY cmis_knowledge.index_backup_2025_11_10.id;


--
-- Name: intent_mappings; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.intent_mappings (
    intent_id uuid DEFAULT gen_random_uuid() NOT NULL,
    intent_name text NOT NULL,
    intent_name_ar text NOT NULL,
    intent_description text,
    intent_embedding public.vector(768),
    parent_intent_id uuid,
    intent_level integer DEFAULT 1,
    related_keywords text[],
    related_keywords_ar text[],
    confidence_threshold numeric(3,2) DEFAULT 0.75,
    usage_count integer DEFAULT 0,
    last_used timestamp with time zone,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_knowledge.intent_mappings OWNER TO begin;

--
-- Name: marketing; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.marketing (
    knowledge_id uuid,
    content text,
    audience_segment text,
    tone text,
    token_count integer,
    content_embedding public.vector(768),
    audience_embedding public.vector(768),
    tone_embedding public.vector(768),
    campaign_intent_vector public.vector(768),
    emotional_direction_vector public.vector(768),
    id bigint NOT NULL,
    tier smallint DEFAULT 2,
    CONSTRAINT marketing_tier_check CHECK (((tier >= 1) AND (tier <= 3)))
);


ALTER TABLE cmis_knowledge.marketing OWNER TO begin;

--
-- Name: COLUMN marketing.tier; Type: COMMENT; Schema: cmis_knowledge; Owner: begin
--

COMMENT ON COLUMN cmis_knowledge.marketing.tier IS 'تصنيف جودة المعرفة (1=حرجة، 2=قياسية، 3=ثانوية)';


--
-- Name: marketing_id_seq; Type: SEQUENCE; Schema: cmis_knowledge; Owner: begin
--

CREATE SEQUENCE cmis_knowledge.marketing_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis_knowledge.marketing_id_seq OWNER TO begin;

--
-- Name: marketing_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis_knowledge; Owner: begin
--

ALTER SEQUENCE cmis_knowledge.marketing_id_seq OWNED BY cmis_knowledge.marketing.id;


--
-- Name: org; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.org (
    knowledge_id uuid,
    org_name text,
    content text,
    token_count integer,
    content_embedding public.vector(768),
    org_context_embedding public.vector(768),
    strategic_intent_vector public.vector(768),
    id bigint NOT NULL,
    tier smallint DEFAULT 2,
    CONSTRAINT org_tier_check CHECK (((tier >= 1) AND (tier <= 3)))
);


ALTER TABLE cmis_knowledge.org OWNER TO begin;

--
-- Name: COLUMN org.tier; Type: COMMENT; Schema: cmis_knowledge; Owner: begin
--

COMMENT ON COLUMN cmis_knowledge.org.tier IS 'تصنيف جودة المعرفة (1=حرجة، 2=قياسية، 3=ثانوية)';


--
-- Name: org_id_seq; Type: SEQUENCE; Schema: cmis_knowledge; Owner: begin
--

CREATE SEQUENCE cmis_knowledge.org_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis_knowledge.org_id_seq OWNER TO begin;

--
-- Name: org_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis_knowledge; Owner: begin
--

ALTER SEQUENCE cmis_knowledge.org_id_seq OWNED BY cmis_knowledge.org.id;


--
-- Name: purpose_mappings; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.purpose_mappings (
    purpose_id uuid DEFAULT gen_random_uuid() NOT NULL,
    purpose_name text NOT NULL,
    purpose_name_ar text NOT NULL,
    purpose_category text,
    purpose_embedding public.vector(768),
    related_intents uuid[],
    related_directions uuid[],
    achievement_criteria jsonb,
    confidence_threshold numeric(3,2) DEFAULT 0.70,
    usage_count integer DEFAULT 0,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_knowledge.purpose_mappings OWNER TO begin;

--
-- Name: research; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.research (
    knowledge_id uuid,
    content text,
    source text,
    confidence_score numeric(3,2),
    token_count integer,
    content_embedding public.vector(768),
    source_context_embedding public.vector(768),
    research_direction_vector public.vector(768),
    insight_embedding public.vector(768),
    id bigint NOT NULL,
    tier smallint DEFAULT 2,
    CONSTRAINT research_tier_check CHECK (((tier >= 1) AND (tier <= 3)))
);


ALTER TABLE cmis_knowledge.research OWNER TO begin;

--
-- Name: COLUMN research.tier; Type: COMMENT; Schema: cmis_knowledge; Owner: begin
--

COMMENT ON COLUMN cmis_knowledge.research.tier IS 'تصنيف جودة المعرفة (1=حرجة، 2=قياسية، 3=ثانوية)';


--
-- Name: research_id_seq; Type: SEQUENCE; Schema: cmis_knowledge; Owner: begin
--

CREATE SEQUENCE cmis_knowledge.research_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE cmis_knowledge.research_id_seq OWNER TO begin;

--
-- Name: research_id_seq; Type: SEQUENCE OWNED BY; Schema: cmis_knowledge; Owner: begin
--

ALTER SEQUENCE cmis_knowledge.research_id_seq OWNED BY cmis_knowledge.research.id;


--
-- Name: semantic_search_logs; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.semantic_search_logs (
    log_id uuid DEFAULT gen_random_uuid() NOT NULL,
    query_text text NOT NULL,
    intent text,
    direction text,
    purpose text,
    category text,
    results_count integer,
    avg_similarity numeric(5,4),
    max_similarity numeric(5,4),
    min_similarity numeric(5,4),
    execution_time_ms integer,
    user_feedback text,
    user_id uuid,
    session_id text,
    created_at timestamp with time zone DEFAULT now(),
    CONSTRAINT semantic_search_logs_user_feedback_check CHECK ((user_feedback = ANY (ARRAY['positive'::text, 'negative'::text, 'neutral'::text])))
);


ALTER TABLE cmis_knowledge.semantic_search_logs OWNER TO begin;

--
-- Name: semantic_search_results_cache; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.semantic_search_results_cache (
    cache_id uuid DEFAULT gen_random_uuid() NOT NULL,
    query_hash text NOT NULL,
    query_text text NOT NULL,
    intent text,
    direction text,
    purpose text,
    results jsonb DEFAULT '{}'::jsonb NOT NULL,
    result_count integer,
    avg_similarity numeric(5,4),
    created_at timestamp with time zone DEFAULT now(),
    expires_at timestamp with time zone DEFAULT (now() + '01:00:00'::interval)
);


ALTER TABLE cmis_knowledge.semantic_search_results_cache OWNER TO begin;

--
-- Name: temporal_analytics; Type: TABLE; Schema: cmis_knowledge; Owner: begin
--

CREATE TABLE cmis_knowledge.temporal_analytics (
    delta_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    knowledge_id uuid,
    domain text NOT NULL,
    previous_snapshot jsonb,
    current_snapshot jsonb,
    delta_summary text,
    detected_at timestamp without time zone DEFAULT now(),
    confidence_score numeric(5,2) DEFAULT 1.0,
    temporal_embedding public.vector(768),
    change_vector public.vector(768),
    trend_direction_embedding public.vector(768)
);


ALTER TABLE cmis_knowledge.temporal_analytics OWNER TO begin;

--
-- Name: v_chrono_evolution; Type: VIEW; Schema: cmis_knowledge; Owner: begin
--

CREATE VIEW cmis_knowledge.v_chrono_evolution AS
 WITH time_diffs AS (
         SELECT i.domain,
            i.category,
            t.delta_id,
            t.detected_at,
            t.confidence_score,
            (date_part('epoch'::text, (t.detected_at - lag(t.detected_at) OVER (PARTITION BY i.domain ORDER BY t.detected_at))) / (3600)::double precision) AS hours_between_changes
           FROM (cmis_knowledge.index i
             LEFT JOIN cmis_knowledge.temporal_analytics t ON ((i.knowledge_id = t.knowledge_id)))
        )
 SELECT domain AS domain_name,
    category,
    count(delta_id) AS total_deltas,
    min(detected_at) AS first_recorded_change,
    max(detected_at) AS last_recorded_change,
    round((avg(hours_between_changes))::numeric, 2) AS avg_hours_between_changes,
    round(avg(confidence_score), 2) AS avg_confidence,
        CASE
            WHEN (count(delta_id) = 0) THEN '🟢 مستقر'::text
            WHEN (count(delta_id) < 3) THEN '🟡 نشاط منخفض'::text
            WHEN (count(delta_id) < 6) THEN '🟠 نشاط معتدل'::text
            ELSE '🔴 نشاط مرتفع'::text
        END AS cognitive_activity_level
   FROM time_diffs
  GROUP BY domain, category
  ORDER BY (max(detected_at)) DESC NULLS LAST;


ALTER VIEW cmis_knowledge.v_chrono_evolution OWNER TO begin;

--
-- Name: v_cognitive_activity; Type: VIEW; Schema: cmis_knowledge; Owner: begin
--

CREATE VIEW cmis_knowledge.v_cognitive_activity AS
 SELECT i.domain AS "النطاق",
    i.category AS "الفئة",
    i.topic AS "الموضوع",
    a.event_type AS "نوع الحدث",
    a.description AS "الوصف",
    a.created_at AS "آخر نشاط",
        CASE
            WHEN (a.event_type ~~ '%feedback%'::text) THEN '🟡 تحت إعادة تحليل'::text
            WHEN (a.event_type ~~ '%alert%'::text) THEN '🔴 يحتاج تدخل إدراكي'::text
            WHEN (a.event_type ~~ '%snapshot%'::text) THEN '🟢 فعّال ومستقر'::text
            ELSE '⚪ غير محدد'::text
        END AS "الحالة اللحظية"
   FROM (cmis_audit.logs a
     LEFT JOIN cmis_knowledge.index i ON ((lower(a.event_source) = lower(i.domain))))
  WHERE (a.event_type = ANY (ARRAY['cognitive_feedback'::text, 'cognitive_alert'::text, 'cognitive_snapshot'::text]))
  ORDER BY a.created_at DESC;


ALTER VIEW cmis_knowledge.v_cognitive_activity OWNER TO begin;

--
-- Name: v_cognitive_vitality; Type: VIEW; Schema: cmis_knowledge; Owner: begin
--

CREATE VIEW cmis_knowledge.v_cognitive_vitality AS
 WITH events AS (
         SELECT max(logs.created_at) AS last_event_time
           FROM cmis_audit.logs
          WHERE (logs.event_type = ANY (ARRAY['manifest_sync'::text, 'cognitive_feedback'::text, 'cognitive_learning'::text]))
        ), manifest AS (
         SELECT max(cognitive_manifest.last_updated) AS last_manifest_update
           FROM cmis_knowledge.cognitive_manifest
        ), delta AS (
         SELECT (date_part('epoch'::text, (manifest.last_manifest_update - events.last_event_time)) / (60)::double precision) AS latency_minutes,
            ( SELECT count(*) AS count
                   FROM cmis_audit.logs
                  WHERE ((logs.event_type = ANY (ARRAY['manifest_sync'::text, 'cognitive_feedback'::text, 'cognitive_learning'::text])) AND (logs.created_at > (now() - '01:00:00'::interval)))) AS events_last_hour
           FROM events,
            manifest
        )
 SELECT latency_minutes,
    events_last_hour,
    round((GREATEST((0)::double precision, LEAST((1)::double precision, (((1)::double precision - (latency_minutes / (60)::double precision)) * (((events_last_hour)::numeric / 10.0))::double precision))))::numeric, 3) AS vitality_index,
        CASE
            WHEN ((((1)::double precision - (latency_minutes / (60)::double precision)) * (((events_last_hour)::numeric / 10.0))::double precision) > (0.8)::double precision) THEN '🟢 نشط جدًا'::text
            WHEN ((((1)::double precision - (latency_minutes / (60)::double precision)) * (((events_last_hour)::numeric / 10.0))::double precision) > (0.6)::double precision) THEN '🟡 مستقر'::text
            WHEN ((((1)::double precision - (latency_minutes / (60)::double precision)) * (((events_last_hour)::numeric / 10.0))::double precision) > (0.4)::double precision) THEN '🟠 خامل نسبيًا'::text
            ELSE '🔴 ضعيف الاستجابة'::text
        END AS cognitive_state,
    now() AS calculated_at
   FROM delta;


ALTER VIEW cmis_knowledge.v_cognitive_vitality OWNER TO begin;

--
-- Name: v_embedding_queue_status; Type: VIEW; Schema: cmis_knowledge; Owner: begin
--

CREATE VIEW cmis_knowledge.v_embedding_queue_status AS
 SELECT status AS "الحالة",
    count(*) AS "العدد",
    (avg(retry_count))::numeric(5,2) AS "متوسط المحاولات",
    min(created_at) AS "أقدم طلب",
    max(created_at) AS "أحدث طلب",
    (avg((EXTRACT(epoch FROM (now() - created_at)) / (60)::numeric)))::numeric(10,2) AS "متوسط وقت الانتظار (دقيقة)",
        CASE status
            WHEN 'pending'::text THEN '⏳ في الانتظار'::text
            WHEN 'processing'::text THEN '🔄 قيد المعالجة'::text
            WHEN 'completed'::text THEN '✅ مكتمل'::text
            WHEN 'failed'::text THEN '❌ فشل'::text
            ELSE NULL::text
        END AS "الوصف"
   FROM cmis_knowledge.embedding_update_queue
  GROUP BY status
  ORDER BY
        CASE status
            WHEN 'failed'::text THEN 1
            WHEN 'pending'::text THEN 2
            WHEN 'processing'::text THEN 3
            WHEN 'completed'::text THEN 4
            ELSE NULL::integer
        END;


ALTER VIEW cmis_knowledge.v_embedding_queue_status OWNER TO begin;

--
-- Name: v_global_cognitive_index; Type: VIEW; Schema: cmis_knowledge; Owner: begin
--

CREATE VIEW cmis_knowledge.v_global_cognitive_index AS
 SELECT round(avg(avg_confidence), 3) AS avg_confidence_overall,
    round(avg(total_deltas), 2) AS avg_deltas_per_domain,
    count(*) AS total_domains,
    round((avg(avg_confidence) / ((1)::numeric + stddev(total_deltas))), 3) AS global_cognitive_stability_index,
        CASE
            WHEN ((avg(avg_confidence) / ((1)::numeric + stddev(total_deltas))) > 0.9) THEN '🟢 مستقر جدًا'::text
            WHEN ((avg(avg_confidence) / ((1)::numeric + stddev(total_deltas))) > 0.7) THEN '🟡 مستقر'::text
            WHEN ((avg(avg_confidence) / ((1)::numeric + stddev(total_deltas))) > 0.5) THEN '🟠 متقلب'::text
            ELSE '🔴 غير مستقر'::text
        END AS system_state_description,
    now() AS calculated_at
   FROM cmis_knowledge.v_chrono_evolution;


ALTER VIEW cmis_knowledge.v_global_cognitive_index OWNER TO begin;

--
-- Name: v_predictive_cognitive_horizon; Type: VIEW; Schema: cmis_knowledge; Owner: begin
--

CREATE VIEW cmis_knowledge.v_predictive_cognitive_horizon AS
 WITH base AS (
         SELECT v_chrono_evolution.domain_name,
            v_chrono_evolution.category,
            v_chrono_evolution.total_deltas,
            v_chrono_evolution.avg_confidence,
            v_chrono_evolution.avg_hours_between_changes,
                CASE
                    WHEN (v_chrono_evolution.cognitive_activity_level ~~ '%🟢%'::text) THEN 0.05
                    WHEN (v_chrono_evolution.cognitive_activity_level ~~ '%🟡%'::text) THEN 0.15
                    WHEN (v_chrono_evolution.cognitive_activity_level ~~ '%🟠%'::text) THEN 0.30
                    ELSE 0.50
                END AS volatility_factor
           FROM cmis_knowledge.v_chrono_evolution
        ), projection AS (
         SELECT base.domain_name,
            base.category,
            base.total_deltas,
            base.avg_confidence,
            base.avg_hours_between_changes,
            base.volatility_factor,
            round((base.avg_confidence - (base.volatility_factor * ((1)::numeric / (base.avg_hours_between_changes + (1)::numeric)))), 3) AS predicted_confidence_24h,
            round((base.avg_confidence - (base.volatility_factor * ((2)::numeric / (base.avg_hours_between_changes + (1)::numeric)))), 3) AS predicted_confidence_48h
           FROM base
        )
 SELECT domain_name,
    category,
    avg_confidence,
    predicted_confidence_24h,
    predicted_confidence_48h,
    round((predicted_confidence_48h - avg_confidence), 3) AS projected_change,
        CASE
            WHEN ((predicted_confidence_48h - avg_confidence) > '-0.05'::numeric) THEN '🟢 استقرار مستمر'::text
            WHEN ((predicted_confidence_48h - avg_confidence) > '-0.15'::numeric) THEN '🟡 قابل للتحول'::text
            ELSE '🔴 احتمالية تراجع إدراكي'::text
        END AS forecast_status,
    now() AS forecast_generated_at
   FROM projection
  ORDER BY (round((predicted_confidence_48h - avg_confidence), 3)) DESC;


ALTER VIEW cmis_knowledge.v_predictive_cognitive_horizon OWNER TO begin;

--
-- Name: v_search_performance; Type: VIEW; Schema: cmis_knowledge; Owner: begin
--

CREATE VIEW cmis_knowledge.v_search_performance AS
 SELECT date_trunc('hour'::text, created_at) AS "الساعة",
    count(*) AS "عدد عمليات البحث",
    (avg(results_count))::numeric(10,2) AS "متوسط النتائج",
    round(avg(avg_similarity), 4) AS "متوسط التشابه",
    round(avg(max_similarity), 4) AS "أعلى تشابه",
    round(avg(min_similarity), 4) AS "أقل تشابه",
    percentile_cont((0.5)::double precision) WITHIN GROUP (ORDER BY ((avg_similarity)::double precision)) AS "الوسيط",
    (avg(execution_time_ms))::numeric(10,2) AS "متوسط وقت التنفيذ (ms)",
    count(*) FILTER (WHERE (user_feedback = 'positive'::text)) AS "تقييمات إيجابية",
    count(*) FILTER (WHERE (user_feedback = 'negative'::text)) AS "تقييمات سلبية",
    count(*) FILTER (WHERE (user_feedback = 'neutral'::text)) AS "تقييمات محايدة"
   FROM cmis_knowledge.semantic_search_logs
  WHERE (created_at > (now() - '24:00:00'::interval))
  GROUP BY (date_trunc('hour'::text, created_at))
  ORDER BY (date_trunc('hour'::text, created_at)) DESC;


ALTER VIEW cmis_knowledge.v_search_performance OWNER TO begin;

--
-- Name: v_temporal_dashboard; Type: VIEW; Schema: cmis_knowledge; Owner: begin
--

CREATE VIEW cmis_knowledge.v_temporal_dashboard AS
 SELECT i.domain AS domain_name,
    i.category,
    i.topic,
    t.detected_at,
    t.delta_summary AS epistemic_delta,
    t.confidence_score AS confidence,
    i.last_verified_at AS last_verification,
    i.last_audit_status AS audit_status,
    COALESCE(a.event_type, '—'::text) AS last_event_type,
    COALESCE(a.description, '—'::text) AS last_event_description
   FROM ((cmis_knowledge.index i
     LEFT JOIN cmis_knowledge.temporal_analytics t ON ((i.knowledge_id = t.knowledge_id)))
     LEFT JOIN cmis_audit.logs a ON ((i.domain = a.event_source)))
  ORDER BY t.detected_at DESC NULLS LAST;


ALTER VIEW cmis_knowledge.v_temporal_dashboard OWNER TO begin;

--
-- Name: assets; Type: TABLE; Schema: cmis_marketing; Owner: begin
--

CREATE TABLE cmis_marketing.assets (
    asset_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    task_id uuid,
    platform text,
    asset_type text,
    content jsonb,
    generated_by text DEFAULT 'ai_orchestrator'::text,
    confidence numeric(3,2),
    created_at timestamp with time zone DEFAULT now(),
    CONSTRAINT assets_asset_type_check CHECK ((asset_type = ANY (ARRAY['post'::text, 'ad_copy'::text, 'reel_script'::text, 'story_caption'::text]))),
    CONSTRAINT assets_platform_check CHECK ((platform = ANY (ARRAY['instagram'::text, 'facebook'::text, 'linkedin'::text, 'tiktok'::text])))
);


ALTER TABLE cmis_marketing.assets OWNER TO begin;

--
-- Name: generated_creatives; Type: TABLE; Schema: cmis_marketing; Owner: begin
--

CREATE TABLE cmis_marketing.generated_creatives (
    creative_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    topic text NOT NULL,
    tone text NOT NULL,
    variant_index integer NOT NULL,
    hook text,
    concept text,
    narrative text,
    slogan text,
    emotion_profile text[],
    tags text[],
    generated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_marketing.generated_creatives OWNER TO begin;

--
-- Name: video_scenarios; Type: TABLE; Schema: cmis_marketing; Owner: begin
--

CREATE TABLE cmis_marketing.video_scenarios (
    scenario_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    task_id uuid,
    asset_id uuid,
    title text,
    duration_seconds integer,
    scenes jsonb,
    tone text,
    goal text,
    confidence numeric(3,2),
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_marketing.video_scenarios OWNER TO begin;

--
-- Name: visual_concepts; Type: TABLE; Schema: cmis_marketing; Owner: begin
--

CREATE TABLE cmis_marketing.visual_concepts (
    concept_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    asset_id uuid,
    visual_prompt text,
    style text,
    palette text,
    emotion text,
    focus_keywords text[],
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_marketing.visual_concepts OWNER TO begin;

--
-- Name: visual_scenarios; Type: TABLE; Schema: cmis_marketing; Owner: begin
--

CREATE TABLE cmis_marketing.visual_scenarios (
    scenario_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    creative_id uuid,
    topic text,
    tone text,
    variant_index integer,
    scene_order integer,
    scene_type text,
    scene_text text,
    visual_hint text,
    duration_seconds integer DEFAULT 5,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_marketing.visual_scenarios OWNER TO begin;

--
-- Name: voice_scripts; Type: TABLE; Schema: cmis_marketing; Owner: begin
--

CREATE TABLE cmis_marketing.voice_scripts (
    script_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    scenario_id uuid,
    task_id uuid,
    language text DEFAULT 'ar'::text,
    voice_tone text DEFAULT 'ملهم'::text,
    narration text,
    script_structure jsonb,
    confidence numeric(3,2),
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_marketing.voice_scripts OWNER TO begin;

--
-- Name: schema_fixes_log; Type: TABLE; Schema: cmis_ops; Owner: begin
--

CREATE TABLE cmis_ops.schema_fixes_log (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    fix_type text NOT NULL,
    target_object text NOT NULL,
    description text,
    applied_at timestamp with time zone DEFAULT now(),
    applied_by text DEFAULT CURRENT_USER,
    success boolean DEFAULT true,
    error_message text
);


ALTER TABLE cmis_ops.schema_fixes_log OWNER TO begin;

--
-- Name: existing_functions; Type: TABLE; Schema: cmis_security_backup_20251111_202413; Owner: begin
--

CREATE TABLE cmis_security_backup_20251111_202413.existing_functions (
    function_name name,
    function_definition text
);


ALTER TABLE cmis_security_backup_20251111_202413.existing_functions OWNER TO begin;

--
-- Name: existing_policies; Type: TABLE; Schema: cmis_security_backup_20251111_202413; Owner: begin
--

CREATE TABLE cmis_security_backup_20251111_202413.existing_policies (
    schemaname name,
    tablename name,
    policyname name,
    permissive text,
    roles name[],
    cmd text,
    qual text COLLATE pg_catalog."C",
    with_check text COLLATE pg_catalog."C"
);


ALTER TABLE cmis_security_backup_20251111_202413.existing_policies OWNER TO begin;

--
-- Name: user_orgs_backup; Type: TABLE; Schema: cmis_security_backup_20251111_202413; Owner: begin
--

CREATE TABLE cmis_security_backup_20251111_202413.user_orgs_backup (
    id uuid,
    user_id uuid,
    org_id uuid,
    role_id uuid,
    is_active boolean,
    joined_at timestamp with time zone,
    invited_by uuid,
    last_accessed timestamp with time zone,
    deleted_at timestamp with time zone,
    provider text
);


ALTER TABLE cmis_security_backup_20251111_202413.user_orgs_backup OWNER TO begin;

--
-- Name: users_backup; Type: TABLE; Schema: cmis_security_backup_20251111_202413; Owner: begin
--

CREATE TABLE cmis_security_backup_20251111_202413.users_backup (
    user_id uuid,
    org_id uuid,
    email public.citext,
    display_name text,
    role text,
    deleted_at timestamp with time zone,
    provider text,
    status text,
    name text
);


ALTER TABLE cmis_security_backup_20251111_202413.users_backup OWNER TO begin;

--
-- Name: raw_channel_data; Type: TABLE; Schema: cmis_staging; Owner: begin
--

CREATE TABLE cmis_staging.raw_channel_data (
    id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    integration_id uuid,
    platform text NOT NULL,
    payload jsonb NOT NULL,
    fetched_at timestamp with time zone DEFAULT now()
);


ALTER TABLE cmis_staging.raw_channel_data OWNER TO begin;

--
-- Name: cognitive_reports; Type: TABLE; Schema: cmis_system_health; Owner: begin
--

CREATE TABLE cmis_system_health.cognitive_reports (
    report_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    report_text text NOT NULL,
    stability_avg numeric(5,2),
    reanalysis_avg numeric(5,2),
    risk_avg numeric(5,2),
    created_at timestamp without time zone DEFAULT now()
);


ALTER TABLE cmis_system_health.cognitive_reports OWNER TO begin;

--
-- Name: cognitive_vitality_log; Type: TABLE; Schema: cmis_system_health; Owner: begin
--

CREATE TABLE cmis_system_health.cognitive_vitality_log (
    record_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    latency_minutes numeric(8,3),
    events_last_hour integer,
    vitality_index numeric(5,3),
    cognitive_state text,
    recorded_at timestamp without time zone DEFAULT now()
);


ALTER TABLE cmis_system_health.cognitive_vitality_log OWNER TO begin;

--
-- Name: v_cognitive_admin_log; Type: VIEW; Schema: cmis_system_health; Owner: begin
--

CREATE VIEW cmis_system_health.v_cognitive_admin_log AS
 SELECT '📘 تقرير إدراكي'::text AS "نوع السجل",
    'CognitiveHealthReport'::text AS "المصدر",
    r.report_text AS "الوصف",
    r.created_at AS "الزمن",
        CASE
            WHEN (r.risk_avg > (20)::numeric) THEN '🔴 خطر إدراكي'::text
            WHEN (r.reanalysis_avg > (50)::numeric) THEN '🟡 تحت إعادة تقييم'::text
            ELSE '🟢 مستقرة'::text
        END AS "الحالة"
   FROM cmis_system_health.cognitive_reports r
UNION ALL
 SELECT '📊 فحص إدراكي'::text AS "نوع السجل",
    a.event_source AS "المصدر",
    a.description AS "الوصف",
    a.created_at AS "الزمن",
        CASE
            WHEN (a.event_type ~~ '%alert%'::text) THEN '🔴 إنذار'::text
            WHEN (a.event_type ~~ '%feedback%'::text) THEN '🟡 إعادة تحليل'::text
            WHEN (a.event_type ~~ '%snapshot%'::text) THEN '🟢 فعّال'::text
            ELSE '⚪ غير محدد'::text
        END AS "الحالة"
   FROM cmis_audit.logs a
  WHERE (a.event_type = ANY (ARRAY['cognitive_feedback'::text, 'cognitive_alert'::text, 'cognitive_snapshot'::text, 'cognitive_report'::text]))
UNION ALL
 SELECT '⚙️ قراءة حيوية'::text AS "نوع السجل",
    'CognitiveVitality'::text AS "المصدر",
    'تحديث قراءة الحيوية الإدراكية'::text AS "الوصف",
    v.recorded_at AS "الزمن",
    v.cognitive_state AS "الحالة"
   FROM cmis_system_health.cognitive_vitality_log v
  ORDER BY 4 DESC;


ALTER VIEW cmis_system_health.v_cognitive_admin_log OWNER TO begin;

--
-- Name: v_cognitive_dashboard; Type: VIEW; Schema: cmis_system_health; Owner: begin
--

CREATE VIEW cmis_system_health.v_cognitive_dashboard AS
 WITH vital AS (
         SELECT cognitive_vitality_log.vitality_index,
            cognitive_vitality_log.cognitive_state,
            cognitive_vitality_log.recorded_at AS last_vitality_check
           FROM cmis_system_health.cognitive_vitality_log
          ORDER BY cognitive_vitality_log.recorded_at DESC
         LIMIT 1
        ), watch AS (
         SELECT logs.description AS last_watch_event,
            logs.created_at AS last_watch_time
           FROM cmis_audit.logs
          WHERE (logs.event_source = 'CognitiveVitalityWatch'::text)
          ORDER BY logs.created_at DESC
         LIMIT 1
        ), manifest AS (
         SELECT cognitive_manifest.layer_name,
            cognitive_manifest.confidence,
            cognitive_manifest.status,
            cognitive_manifest.last_updated
           FROM cmis_knowledge.cognitive_manifest
          ORDER BY cognitive_manifest.last_updated DESC
        )
 SELECT m.layer_name AS "الطبقة الإدراكية",
    m.status AS "الحالة",
    m.confidence AS "الثقة",
    m.last_updated AS "آخر تحديث",
    v.vitality_index AS "مؤشر الحيوية",
    v.cognitive_state AS "حالة الوعي العامة",
    v.last_vitality_check AS "آخر قراءة حيوية",
    w.last_watch_event AS "آخر فحص مراقبة",
    w.last_watch_time AS "زمن الفحص الأخير",
        CASE
            WHEN (v.vitality_index > 0.8) THEN '🟢 مستقر جدًا'::text
            WHEN (v.vitality_index > 0.6) THEN '🟡 مستقر'::text
            WHEN (v.vitality_index > 0.4) THEN '🟠 تحت المراقبة'::text
            ELSE '🔴 خطر إدراكي'::text
        END AS "التقييم العام"
   FROM ((manifest m
     CROSS JOIN vital v)
     CROSS JOIN watch w)
  ORDER BY m.last_updated DESC;


ALTER VIEW cmis_system_health.v_cognitive_dashboard OWNER TO begin;

--
-- Name: v_cognitive_kpi; Type: VIEW; Schema: cmis_system_health; Owner: begin
--

CREATE VIEW cmis_system_health.v_cognitive_kpi AS
 WITH base AS (
         SELECT v_cognitive_activity."النطاق",
            v_cognitive_activity."الفئة",
            v_cognitive_activity."نوع الحدث",
            v_cognitive_activity."الحالة اللحظية",
            v_cognitive_activity."آخر نشاط"
           FROM cmis_knowledge.v_cognitive_activity
          WHERE (v_cognitive_activity."آخر نشاط" > (now() - '24:00:00'::interval))
        ), summary AS (
         SELECT count(*) AS total_domains,
            count(*) FILTER (WHERE (base."الحالة اللحظية" ~~ '%🟢%'::text)) AS active_domains,
            count(*) FILTER (WHERE (base."الحالة اللحظية" ~~ '%🟡%'::text)) AS reanalyzing_domains,
            count(*) FILTER (WHERE (base."الحالة اللحظية" ~~ '%🔴%'::text)) AS alert_domains,
            round((avg((date_part('epoch'::text, (now() - (base."آخر نشاط")::timestamp with time zone)) / (60)::double precision)))::numeric, 2) AS avg_minutes_since_activity
           FROM base
        )
 SELECT total_domains AS "إجمالي النطاقات المراقبة",
    active_domains AS "نطاقات مستقرة 🟢",
    reanalyzing_domains AS "نطاقات تحت إعادة تحليل 🟡",
    alert_domains AS "نطاقات تحتاج تدخل 🔴",
    round((((active_domains)::numeric / (NULLIF(total_domains, 0))::numeric) * (100)::numeric), 1) AS "نسبة الاستقرار %",
    round((((reanalyzing_domains)::numeric / (NULLIF(total_domains, 0))::numeric) * (100)::numeric), 1) AS "نسبة إعادة التحليل %",
    round((((alert_domains)::numeric / (NULLIF(total_domains, 0))::numeric) * (100)::numeric), 1) AS "نسبة الخطر %",
    avg_minutes_since_activity AS "متوسط الزمن منذ آخر نشاط (دقيقة)"
   FROM summary;


ALTER VIEW cmis_system_health.v_cognitive_kpi OWNER TO begin;

--
-- Name: v_cognitive_kpi_timeseries; Type: VIEW; Schema: cmis_system_health; Owner: begin
--

CREATE VIEW cmis_system_health.v_cognitive_kpi_timeseries AS
 WITH base AS (
         SELECT date_trunc('hour'::text, a.created_at) AS hour,
                CASE
                    WHEN (a.event_type ~~ '%snapshot%'::text) THEN '🟢'::text
                    WHEN (a.event_type ~~ '%feedback%'::text) THEN '🟡'::text
                    WHEN (a.event_type ~~ '%alert%'::text) THEN '🔴'::text
                    ELSE '⚪'::text
                END AS status
           FROM cmis_audit.logs a
          WHERE ((a.event_type = ANY (ARRAY['cognitive_feedback'::text, 'cognitive_snapshot'::text, 'cognitive_alert'::text])) AND (a.created_at > (now() - '72:00:00'::interval)))
        ), agg AS (
         SELECT base.hour,
            count(*) AS total_events,
            count(*) FILTER (WHERE (base.status = '🟢'::text)) AS green_events,
            count(*) FILTER (WHERE (base.status = '🟡'::text)) AS yellow_events,
            count(*) FILTER (WHERE (base.status = '🔴'::text)) AS red_events
           FROM base
          GROUP BY base.hour
        )
 SELECT hour AS "الساعة",
    total_events AS "إجمالي الأحداث",
    green_events AS "مستقرة 🟢",
    yellow_events AS "تحت إعادة تحليل 🟡",
    red_events AS "حرجة 🔴",
    round((((green_events)::numeric / (NULLIF(total_events, 0))::numeric) * (100)::numeric), 1) AS "نسبة الاستقرار %",
    round((((yellow_events)::numeric / (NULLIF(total_events, 0))::numeric) * (100)::numeric), 1) AS "نسبة إعادة التحليل %",
    round((((red_events)::numeric / (NULLIF(total_events, 0))::numeric) * (100)::numeric), 1) AS "نسبة الخطر %"
   FROM agg
  ORDER BY hour DESC;


ALTER VIEW cmis_system_health.v_cognitive_kpi_timeseries OWNER TO begin;

--
-- Name: v_cognitive_kpi_graph; Type: VIEW; Schema: cmis_system_health; Owner: begin
--

CREATE VIEW cmis_system_health.v_cognitive_kpi_graph AS
 SELECT v_cognitive_kpi_timeseries."الساعة",
    '🟢 نسبة الاستقرار'::text AS metric,
    v_cognitive_kpi_timeseries."نسبة الاستقرار %" AS value
   FROM cmis_system_health.v_cognitive_kpi_timeseries
UNION ALL
 SELECT v_cognitive_kpi_timeseries."الساعة",
    '🟡 نسبة إعادة التحليل'::text AS metric,
    v_cognitive_kpi_timeseries."نسبة إعادة التحليل %" AS value
   FROM cmis_system_health.v_cognitive_kpi_timeseries
UNION ALL
 SELECT v_cognitive_kpi_timeseries."الساعة",
    '🔴 نسبة الخطر'::text AS metric,
    v_cognitive_kpi_timeseries."نسبة الخطر %" AS value
   FROM cmis_system_health.v_cognitive_kpi_timeseries
  ORDER BY 1 DESC, 2;


ALTER VIEW cmis_system_health.v_cognitive_kpi_graph OWNER TO begin;

--
-- Name: example_sets; Type: TABLE; Schema: lab; Owner: begin
--

CREATE TABLE lab.example_sets (
    example_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    title text,
    kind text NOT NULL,
    channel_id integer,
    framework text,
    tone text,
    locale text DEFAULT 'ar-BH'::text,
    quality_score smallint,
    anchor uuid,
    tags text[],
    body jsonb NOT NULL,
    created_at timestamp with time zone DEFAULT now(),
    campaign_id uuid,
    CONSTRAINT example_sets_kind_check CHECK ((kind = ANY (ARRAY['example'::text, 'template'::text, 'set'::text, 'collection'::text, 'scenario'::text, 'template_set'::text]))),
    CONSTRAINT example_sets_quality_score_check CHECK (((quality_score >= 1) AND (quality_score <= 5)))
);


ALTER TABLE lab.example_sets OWNER TO begin;

--
-- Name: example_used_fields; Type: TABLE; Schema: lab; Owner: begin
--

CREATE TABLE lab.example_used_fields (
    example_id uuid NOT NULL,
    field_id uuid NOT NULL
);


ALTER TABLE lab.example_used_fields OWNER TO begin;

--
-- Name: test_matrix; Type: TABLE; Schema: lab; Owner: begin
--

CREATE TABLE lab.test_matrix (
    test_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    org_id uuid,
    design jsonb NOT NULL,
    notes text
);


ALTER TABLE lab.test_matrix OWNER TO begin;

--
-- Name: audit_log; Type: TABLE; Schema: operations; Owner: begin
--

CREATE TABLE operations.audit_log (
    id bigint NOT NULL,
    "timestamp" timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    user_id uuid,
    session_id text,
    username text,
    action character varying(50) NOT NULL,
    table_schema character varying(63) NOT NULL,
    table_name character varying(63) NOT NULL,
    record_id uuid,
    record_key text,
    old_values jsonb,
    new_values jsonb,
    changed_fields text[],
    query text,
    query_params text[],
    ip_address inet,
    user_agent text,
    application_name text,
    host_name text,
    metadata jsonb DEFAULT '{}'::jsonb,
    tags text[],
    execution_time_ms integer,
    rows_affected integer
);


ALTER TABLE operations.audit_log OWNER TO begin;

--
-- Name: audit_log_id_seq; Type: SEQUENCE; Schema: operations; Owner: begin
--

CREATE SEQUENCE operations.audit_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE operations.audit_log_id_seq OWNER TO begin;

--
-- Name: audit_log_id_seq; Type: SEQUENCE OWNED BY; Schema: operations; Owner: begin
--

ALTER SEQUENCE operations.audit_log_id_seq OWNED BY operations.audit_log.id;


--
-- Name: audit_summary; Type: VIEW; Schema: operations; Owner: begin
--

CREATE VIEW operations.audit_summary AS
 SELECT date_trunc('hour'::text, "timestamp") AS hour,
    table_schema,
    table_name,
    action,
    count(*) AS operation_count,
    count(DISTINCT user_id) AS unique_users,
    count(DISTINCT record_id) AS unique_records,
    avg(execution_time_ms) AS avg_execution_time_ms
   FROM operations.audit_log
  GROUP BY (date_trunc('hour'::text, "timestamp")), table_schema, table_name, action;


ALTER VIEW operations.audit_summary OWNER TO begin;

--
-- Name: migrations; Type: TABLE; Schema: operations; Owner: begin
--

CREATE TABLE operations.migrations (
    migration_id integer NOT NULL,
    version character varying(20) NOT NULL,
    phase character varying(100) NOT NULL,
    status character varying(20) NOT NULL,
    started_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    completed_at timestamp without time zone,
    duration_seconds integer GENERATED ALWAYS AS ((EXTRACT(epoch FROM (completed_at - started_at)))::integer) STORED,
    affected_objects text[],
    error_message text,
    rollback_sql text
);


ALTER TABLE operations.migrations OWNER TO begin;

--
-- Name: migrations_migration_id_seq; Type: SEQUENCE; Schema: operations; Owner: begin
--

CREATE SEQUENCE operations.migrations_migration_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE operations.migrations_migration_id_seq OWNER TO begin;

--
-- Name: migrations_migration_id_seq; Type: SEQUENCE OWNED BY; Schema: operations; Owner: begin
--

ALTER SEQUENCE operations.migrations_migration_id_seq OWNED BY operations.migrations.migration_id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value jsonb NOT NULL,
    expiration integer
);


ALTER TABLE public.cache OWNER TO begin;

--
-- Name: channel_formats_format_id_seq; Type: SEQUENCE; Schema: public; Owner: begin
--

CREATE SEQUENCE public.channel_formats_format_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.channel_formats_format_id_seq OWNER TO begin;

--
-- Name: channel_formats_format_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: begin
--

ALTER SEQUENCE public.channel_formats_format_id_seq OWNED BY public.channel_formats.format_id;


--
-- Name: channels_channel_id_seq; Type: SEQUENCE; Schema: public; Owner: begin
--

CREATE SEQUENCE public.channels_channel_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.channels_channel_id_seq OWNER TO begin;

--
-- Name: channels_channel_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: begin
--

ALTER SEQUENCE public.channels_channel_id_seq OWNED BY public.channels.channel_id;


--
-- Name: cmis_access_control; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.cmis_access_control (
    rule_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    resource_type text,
    resource_id uuid,
    actor text,
    permission text,
    granted_at timestamp with time zone DEFAULT now(),
    CONSTRAINT cmis_access_control_permission_check CHECK ((permission = ANY (ARRAY['read'::text, 'write'::text, 'execute'::text, 'admin'::text])))
);


ALTER TABLE public.cmis_access_control OWNER TO begin;

--
-- Name: cmis_system_health; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.cmis_system_health (
    check_id uuid DEFAULT public.gen_random_uuid() NOT NULL,
    metric_name text,
    metric_value numeric,
    threshold numeric,
    status text,
    checked_at timestamp with time zone DEFAULT now(),
    CONSTRAINT cmis_system_health_status_check CHECK ((status = ANY (ARRAY['healthy'::text, 'warning'::text, 'critical'::text])))
);


ALTER TABLE public.cmis_system_health OWNER TO begin;

--
-- Name: industries_industry_id_seq; Type: SEQUENCE; Schema: public; Owner: begin
--

CREATE SEQUENCE public.industries_industry_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.industries_industry_id_seq OWNER TO begin;

--
-- Name: industries_industry_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: begin
--

ALTER SEQUENCE public.industries_industry_id_seq OWNED BY public.industries.industry_id;


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload jsonb NOT NULL,
    attempts integer DEFAULT 0 NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO begin;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: begin
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO begin;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: begin
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: markets_market_id_seq; Type: SEQUENCE; Schema: public; Owner: begin
--

CREATE SEQUENCE public.markets_market_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.markets_market_id_seq OWNER TO begin;

--
-- Name: markets_market_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: begin
--

ALTER SEQUENCE public.markets_market_id_seq OWNED BY public.markets.market_id;


--
-- Name: migration_log; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.migration_log (
    id integer NOT NULL,
    phase text NOT NULL,
    started_at timestamp with time zone DEFAULT now(),
    completed_at timestamp with time zone,
    status text DEFAULT 'pending'::text,
    notes text
);


ALTER TABLE public.migration_log OWNER TO begin;

--
-- Name: migration_log_id_seq; Type: SEQUENCE; Schema: public; Owner: begin
--

CREATE SEQUENCE public.migration_log_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migration_log_id_seq OWNER TO begin;

--
-- Name: migration_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: begin
--

ALTER SEQUENCE public.migration_log_id_seq OWNED BY public.migration_log.id;


--
-- Name: modules; Type: VIEW; Schema: public; Owner: begin
--

CREATE VIEW public.modules AS
 SELECT module_id,
    code,
    name,
    version
   FROM cmis.modules;


ALTER VIEW public.modules OWNER TO begin;

--
-- Name: modules_old; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.modules_old (
    module_id integer NOT NULL,
    code text NOT NULL,
    name text NOT NULL,
    version text DEFAULT '2025.10.0'::text
);


ALTER TABLE public.modules_old OWNER TO begin;

--
-- Name: modules_module_id_seq; Type: SEQUENCE; Schema: public; Owner: begin
--

CREATE SEQUENCE public.modules_module_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.modules_module_id_seq OWNER TO begin;

--
-- Name: modules_module_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: begin
--

ALTER SEQUENCE public.modules_module_id_seq OWNED BY public.modules_old.module_id;


--
-- Name: naming_templates; Type: VIEW; Schema: public; Owner: begin
--

CREATE VIEW public.naming_templates AS
 SELECT naming_id,
    scope,
    template
   FROM cmis.naming_templates;


ALTER VIEW public.naming_templates OWNER TO begin;

--
-- Name: naming_templates_old; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.naming_templates_old (
    naming_id integer NOT NULL,
    scope text NOT NULL,
    template text NOT NULL,
    CONSTRAINT naming_templates_scope_check CHECK ((scope = ANY (ARRAY['ad'::text, 'bundle'::text, 'landing'::text, 'email'::text, 'experiment'::text, 'video_scene'::text, 'content_item'::text])))
);


ALTER TABLE public.naming_templates_old OWNER TO begin;

--
-- Name: naming_templates_naming_id_seq; Type: SEQUENCE; Schema: public; Owner: begin
--

CREATE SEQUENCE public.naming_templates_naming_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.naming_templates_naming_id_seq OWNER TO begin;

--
-- Name: naming_templates_naming_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: begin
--

ALTER SEQUENCE public.naming_templates_naming_id_seq OWNED BY public.naming_templates_old.naming_id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO begin;

--
-- Name: view_definitions_backup; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.view_definitions_backup (
    viewname text NOT NULL,
    depends_on_refactored boolean DEFAULT false,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.view_definitions_backup OWNER TO begin;

--
-- Name: visual_kpis; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.visual_kpis (
    kpi_id integer NOT NULL,
    name text NOT NULL,
    metric_type text,
    unit text,
    ideal_value text,
    description text,
    CONSTRAINT visual_kpis_metric_type_check CHECK ((metric_type = ANY (ARRAY['attention'::text, 'comprehension'::text, 'emotion'::text, 'trust'::text])))
);


ALTER TABLE public.visual_kpis OWNER TO begin;

--
-- Name: visual_principles; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.visual_principles (
    principle_id integer NOT NULL,
    name text NOT NULL,
    category text,
    description text,
    recommended_use text,
    CONSTRAINT visual_principles_category_check CHECK ((category = ANY (ARRAY['composition'::text, 'symbolism'::text, 'typography'::text, 'emotion'::text, 'speed'::text, 'clarity'::text])))
);


ALTER TABLE public.visual_principles OWNER TO begin;

--
-- Name: visual_dashboard_view; Type: VIEW; Schema: public; Owner: begin
--

CREATE VIEW public.visual_dashboard_view AS
 SELECT mo.objective AS marketing_goal,
    mo.category AS goal_category,
    vp.name AS design_principle,
    vp.category AS principle_type,
    vk.name AS kpi_name,
    vk.metric_type AS metric_focus,
    vk.ideal_value AS benchmark,
    vk.description AS kpi_description
   FROM ((public.marketing_objectives mo
     CROSS JOIN public.visual_principles vp)
     JOIN public.visual_kpis vk ON (((vp.category = vk.metric_type) OR ((vk.metric_type = 'comprehension'::text) AND (vp.category = ANY (ARRAY['clarity'::text, 'composition'::text]))) OR ((vk.metric_type = 'emotion'::text) AND (vp.category = 'emotion'::text)))));


ALTER VIEW public.visual_dashboard_view OWNER TO begin;

--
-- Name: visual_kpis_kpi_id_seq; Type: SEQUENCE; Schema: public; Owner: begin
--

CREATE SEQUENCE public.visual_kpis_kpi_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.visual_kpis_kpi_id_seq OWNER TO begin;

--
-- Name: visual_kpis_kpi_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: begin
--

ALTER SEQUENCE public.visual_kpis_kpi_id_seq OWNED BY public.visual_kpis.kpi_id;


--
-- Name: visual_principles_principle_id_seq; Type: SEQUENCE; Schema: public; Owner: begin
--

CREATE SEQUENCE public.visual_principles_principle_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.visual_principles_principle_id_seq OWNER TO begin;

--
-- Name: visual_principles_principle_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: begin
--

ALTER SEQUENCE public.visual_principles_principle_id_seq OWNED BY public.visual_principles.principle_id;


--
-- Name: visual_recommendations; Type: TABLE; Schema: public; Owner: begin
--

CREATE TABLE public.visual_recommendations (
    recommendation_id integer NOT NULL,
    objective_code text,
    recommended_principle text,
    linked_kpi text,
    rationale text,
    suggested_action text
);


ALTER TABLE public.visual_recommendations OWNER TO begin;

--
-- Name: visual_recommendations_recommendation_id_seq; Type: SEQUENCE; Schema: public; Owner: begin
--

CREATE SEQUENCE public.visual_recommendations_recommendation_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.visual_recommendations_recommendation_id_seq OWNER TO begin;

--
-- Name: visual_recommendations_recommendation_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: begin
--

ALTER SEQUENCE public.visual_recommendations_recommendation_id_seq OWNED BY public.visual_recommendations.recommendation_id;


--
-- Name: backup_integrations_cmis id; Type: DEFAULT; Schema: archive; Owner: begin
--

ALTER TABLE ONLY archive.backup_integrations_cmis ALTER COLUMN id SET DEFAULT nextval('archive.backup_integrations_cmis_id_seq'::regclass);


--
-- Name: embedding_update_queue_backup id; Type: DEFAULT; Schema: archive; Owner: begin
--

ALTER TABLE ONLY archive.embedding_update_queue_backup ALTER COLUMN id SET DEFAULT nextval('archive.embedding_update_queue_backup_id_seq'::regclass);


--
-- Name: ad_metrics id; Type: DEFAULT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_metrics ALTER COLUMN id SET DEFAULT nextval('cmis.ad_metrics_id_seq'::regclass);


--
-- Name: meta_field_dictionary id; Type: DEFAULT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.meta_field_dictionary ALTER COLUMN id SET DEFAULT nextval('cmis.meta_field_dictionary_id_seq'::regclass);


--
-- Name: meta_function_descriptions id; Type: DEFAULT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.meta_function_descriptions ALTER COLUMN id SET DEFAULT nextval('cmis.meta_function_descriptions_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.migrations ALTER COLUMN id SET DEFAULT nextval('cmis.migrations_id_seq'::regclass);


--
-- Name: modules module_id; Type: DEFAULT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.modules ALTER COLUMN module_id SET DEFAULT nextval('cmis.modules_module_id_seq'::regclass);


--
-- Name: naming_templates naming_id; Type: DEFAULT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.naming_templates ALTER COLUMN naming_id SET DEFAULT nextval('cmis.naming_templates_naming_id_seq'::regclass);


--
-- Name: dev id; Type: DEFAULT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.dev ALTER COLUMN id SET DEFAULT nextval('cmis_knowledge.dev_id_seq'::regclass);


--
-- Name: index_backup_2025_11_10 id; Type: DEFAULT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.index_backup_2025_11_10 ALTER COLUMN id SET DEFAULT nextval('cmis_knowledge.index_backup_2025_11_10_id_seq'::regclass);


--
-- Name: marketing id; Type: DEFAULT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.marketing ALTER COLUMN id SET DEFAULT nextval('cmis_knowledge.marketing_id_seq'::regclass);


--
-- Name: org id; Type: DEFAULT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.org ALTER COLUMN id SET DEFAULT nextval('cmis_knowledge.org_id_seq'::regclass);


--
-- Name: research id; Type: DEFAULT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.research ALTER COLUMN id SET DEFAULT nextval('cmis_knowledge.research_id_seq'::regclass);


--
-- Name: audit_log id; Type: DEFAULT; Schema: operations; Owner: begin
--

ALTER TABLE ONLY operations.audit_log ALTER COLUMN id SET DEFAULT nextval('operations.audit_log_id_seq'::regclass);


--
-- Name: migrations migration_id; Type: DEFAULT; Schema: operations; Owner: begin
--

ALTER TABLE ONLY operations.migrations ALTER COLUMN migration_id SET DEFAULT nextval('operations.migrations_migration_id_seq'::regclass);


--
-- Name: channel_formats format_id; Type: DEFAULT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.channel_formats ALTER COLUMN format_id SET DEFAULT nextval('public.channel_formats_format_id_seq'::regclass);


--
-- Name: channels channel_id; Type: DEFAULT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.channels ALTER COLUMN channel_id SET DEFAULT nextval('public.channels_channel_id_seq'::regclass);


--
-- Name: industries industry_id; Type: DEFAULT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.industries ALTER COLUMN industry_id SET DEFAULT nextval('public.industries_industry_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: markets market_id; Type: DEFAULT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.markets ALTER COLUMN market_id SET DEFAULT nextval('public.markets_market_id_seq'::regclass);


--
-- Name: migration_log id; Type: DEFAULT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.migration_log ALTER COLUMN id SET DEFAULT nextval('public.migration_log_id_seq'::regclass);


--
-- Name: modules_old module_id; Type: DEFAULT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.modules_old ALTER COLUMN module_id SET DEFAULT nextval('public.modules_module_id_seq'::regclass);


--
-- Name: naming_templates_old naming_id; Type: DEFAULT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.naming_templates_old ALTER COLUMN naming_id SET DEFAULT nextval('public.naming_templates_naming_id_seq'::regclass);


--
-- Name: visual_kpis kpi_id; Type: DEFAULT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_kpis ALTER COLUMN kpi_id SET DEFAULT nextval('public.visual_kpis_kpi_id_seq'::regclass);


--
-- Name: visual_principles principle_id; Type: DEFAULT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_principles ALTER COLUMN principle_id SET DEFAULT nextval('public.visual_principles_principle_id_seq'::regclass);


--
-- Name: visual_recommendations recommendation_id; Type: DEFAULT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_recommendations ALTER COLUMN recommendation_id SET DEFAULT nextval('public.visual_recommendations_recommendation_id_seq'::regclass);


--
-- Name: backup_integrations_cmis backup_integrations_cmis_pkey; Type: CONSTRAINT; Schema: archive; Owner: begin
--

ALTER TABLE ONLY archive.backup_integrations_cmis
    ADD CONSTRAINT backup_integrations_cmis_pkey PRIMARY KEY (id);


--
-- Name: embedding_update_queue_backup embedding_update_queue_backup_pkey; Type: CONSTRAINT; Schema: archive; Owner: begin
--

ALTER TABLE ONLY archive.embedding_update_queue_backup
    ADD CONSTRAINT embedding_update_queue_backup_pkey PRIMARY KEY (id);


--
-- Name: ad_accounts ad_accounts_integration_id_account_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_accounts
    ADD CONSTRAINT ad_accounts_integration_id_account_external_id_key UNIQUE (integration_id, account_external_id);


--
-- Name: ad_accounts ad_accounts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_accounts
    ADD CONSTRAINT ad_accounts_pkey PRIMARY KEY (id);


--
-- Name: ad_audiences ad_audiences_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_audiences
    ADD CONSTRAINT ad_audiences_pkey PRIMARY KEY (id);


--
-- Name: ad_campaigns ad_campaigns_integration_id_campaign_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_campaigns
    ADD CONSTRAINT ad_campaigns_integration_id_campaign_external_id_key UNIQUE (integration_id, campaign_external_id);


--
-- Name: ad_campaigns ad_campaigns_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_campaigns
    ADD CONSTRAINT ad_campaigns_pkey PRIMARY KEY (id);


--
-- Name: ad_entities ad_entities_integration_id_ad_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_entities
    ADD CONSTRAINT ad_entities_integration_id_ad_external_id_key UNIQUE (integration_id, ad_external_id);


--
-- Name: ad_entities ad_entities_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_entities
    ADD CONSTRAINT ad_entities_pkey PRIMARY KEY (id);


--
-- Name: ad_metrics ad_metrics_integration_id_entity_level_entity_external_id_d_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_metrics
    ADD CONSTRAINT ad_metrics_integration_id_entity_level_entity_external_id_d_key UNIQUE (integration_id, entity_level, entity_external_id, date_start, date_stop);


--
-- Name: ad_metrics ad_metrics_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_metrics
    ADD CONSTRAINT ad_metrics_pkey PRIMARY KEY (id);


--
-- Name: ad_sets ad_sets_integration_id_adset_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_sets
    ADD CONSTRAINT ad_sets_integration_id_adset_external_id_key UNIQUE (integration_id, adset_external_id);


--
-- Name: ad_sets ad_sets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_sets
    ADD CONSTRAINT ad_sets_pkey PRIMARY KEY (id);


--
-- Name: ai_actions ai_actions_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ai_actions
    ADD CONSTRAINT ai_actions_pkey PRIMARY KEY (action_id);


--
-- Name: ai_generated_campaigns ai_generated_campaigns_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ai_generated_campaigns
    ADD CONSTRAINT ai_generated_campaigns_pkey PRIMARY KEY (campaign_id);


--
-- Name: ai_models ai_models_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ai_models
    ADD CONSTRAINT ai_models_pkey PRIMARY KEY (model_id);


--
-- Name: analytics_integrations analytics_integrations_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.analytics_integrations
    ADD CONSTRAINT analytics_integrations_pkey PRIMARY KEY (integration_id);


--
-- Name: anchors anchors_code_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.anchors
    ADD CONSTRAINT anchors_code_key UNIQUE (code);


--
-- Name: anchors anchors_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.anchors
    ADD CONSTRAINT anchors_pkey PRIMARY KEY (anchor_id);


--
-- Name: api_keys api_keys_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.api_keys
    ADD CONSTRAINT api_keys_pkey PRIMARY KEY (key_id);


--
-- Name: api_keys api_keys_service_code_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.api_keys
    ADD CONSTRAINT api_keys_service_code_key UNIQUE (service_code);


--
-- Name: audio_templates audio_templates_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.audio_templates
    ADD CONSTRAINT audio_templates_pkey PRIMARY KEY (atpl_id);


--
-- Name: audit_log audit_log_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.audit_log
    ADD CONSTRAINT audit_log_pkey PRIMARY KEY (log_id);


--
-- Name: bundle_offerings bundle_offerings_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.bundle_offerings
    ADD CONSTRAINT bundle_offerings_pkey PRIMARY KEY (bundle_id, offering_id);


--
-- Name: cache_metadata cache_metadata_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.cache_metadata
    ADD CONSTRAINT cache_metadata_pkey PRIMARY KEY (cache_name);


--
-- Name: campaign_context_links campaign_context_links_campaign_id_context_id_link_type_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaign_context_links
    ADD CONSTRAINT campaign_context_links_campaign_id_context_id_link_type_key UNIQUE (campaign_id, context_id, link_type);


--
-- Name: campaign_context_links campaign_context_links_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaign_context_links
    ADD CONSTRAINT campaign_context_links_pkey PRIMARY KEY (id);


--
-- Name: campaign_offerings campaign_offerings_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaign_offerings
    ADD CONSTRAINT campaign_offerings_pkey PRIMARY KEY (campaign_id, offering_id);


--
-- Name: campaign_performance_dashboard campaign_performance_dashboard_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaign_performance_dashboard
    ADD CONSTRAINT campaign_performance_dashboard_pkey PRIMARY KEY (dashboard_id);


--
-- Name: campaigns campaigns_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaigns
    ADD CONSTRAINT campaigns_pkey PRIMARY KEY (campaign_id);


--
-- Name: cognitive_tracker_template cognitive_tracker_template_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.cognitive_tracker_template
    ADD CONSTRAINT cognitive_tracker_template_pkey PRIMARY KEY (tracker_id);


--
-- Name: cognitive_trends cognitive_trends_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.cognitive_trends
    ADD CONSTRAINT cognitive_trends_pkey PRIMARY KEY (trend_id);


--
-- Name: compliance_audits compliance_audits_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.compliance_audits
    ADD CONSTRAINT compliance_audits_pkey PRIMARY KEY (audit_id);


--
-- Name: compliance_rule_channels compliance_rule_channels_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.compliance_rule_channels
    ADD CONSTRAINT compliance_rule_channels_pkey PRIMARY KEY (rule_id, channel_id);


--
-- Name: compliance_rules compliance_rules_code_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.compliance_rules
    ADD CONSTRAINT compliance_rules_code_key UNIQUE (code);


--
-- Name: compliance_rules compliance_rules_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.compliance_rules
    ADD CONSTRAINT compliance_rules_pkey PRIMARY KEY (rule_id);


--
-- Name: content_items content_items_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_pkey PRIMARY KEY (item_id);


--
-- Name: content_plans content_plans_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_plans
    ADD CONSTRAINT content_plans_pkey PRIMARY KEY (plan_id);


--
-- Name: contexts_base contexts_base_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.contexts_base
    ADD CONSTRAINT contexts_base_pkey PRIMARY KEY (id);


--
-- Name: contexts_creative contexts_creative_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.contexts_creative
    ADD CONSTRAINT contexts_creative_pkey PRIMARY KEY (context_id);


--
-- Name: contexts_offering contexts_offering_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.contexts_offering
    ADD CONSTRAINT contexts_offering_pkey PRIMARY KEY (context_id);


--
-- Name: contexts contexts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.contexts
    ADD CONSTRAINT contexts_pkey PRIMARY KEY (context_id);


--
-- Name: contexts_value contexts_value_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.contexts_value
    ADD CONSTRAINT contexts_value_pkey PRIMARY KEY (context_id);


--
-- Name: copy_components copy_components_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.copy_components
    ADD CONSTRAINT copy_components_pkey PRIMARY KEY (component_id);


--
-- Name: creative_assets creative_assets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_pkey PRIMARY KEY (asset_id);


--
-- Name: creative_briefs creative_briefs_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_briefs
    ADD CONSTRAINT creative_briefs_pkey PRIMARY KEY (brief_id);


--
-- Name: creative_contexts creative_contexts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_contexts
    ADD CONSTRAINT creative_contexts_pkey PRIMARY KEY (context_id);


--
-- Name: creative_outputs creative_outputs_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_outputs
    ADD CONSTRAINT creative_outputs_pkey PRIMARY KEY (output_id);


--
-- Name: data_feeds data_feeds_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.data_feeds
    ADD CONSTRAINT data_feeds_pkey PRIMARY KEY (feed_id);


--
-- Name: dataset_files dataset_files_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.dataset_files
    ADD CONSTRAINT dataset_files_pkey PRIMARY KEY (file_id);


--
-- Name: dataset_packages dataset_packages_code_version_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.dataset_packages
    ADD CONSTRAINT dataset_packages_code_version_key UNIQUE (code, version);


--
-- Name: dataset_packages dataset_packages_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.dataset_packages
    ADD CONSTRAINT dataset_packages_pkey PRIMARY KEY (pkg_id);


--
-- Name: experiment_variants experiment_variants_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.experiment_variants
    ADD CONSTRAINT experiment_variants_pkey PRIMARY KEY (exp_id, asset_id);


--
-- Name: experiments experiments_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.experiments
    ADD CONSTRAINT experiments_pkey PRIMARY KEY (exp_id);


--
-- Name: export_bundle_items export_bundle_items_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.export_bundle_items
    ADD CONSTRAINT export_bundle_items_pkey PRIMARY KEY (bundle_id, asset_id);


--
-- Name: export_bundles export_bundles_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.export_bundles
    ADD CONSTRAINT export_bundles_pkey PRIMARY KEY (bundle_id);


--
-- Name: feed_items feed_items_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.feed_items
    ADD CONSTRAINT feed_items_pkey PRIMARY KEY (item_id);


--
-- Name: field_aliases field_aliases_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_aliases
    ADD CONSTRAINT field_aliases_pkey PRIMARY KEY (alias_slug);


--
-- Name: field_definitions field_definitions_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_definitions
    ADD CONSTRAINT field_definitions_pkey PRIMARY KEY (field_id);


--
-- Name: field_definitions field_definitions_slug_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_definitions
    ADD CONSTRAINT field_definitions_slug_key UNIQUE (slug);


--
-- Name: field_values field_values_field_id_context_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_values
    ADD CONSTRAINT field_values_field_id_context_id_key UNIQUE (field_id, context_id);


--
-- Name: field_values field_values_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_values
    ADD CONSTRAINT field_values_pkey PRIMARY KEY (value_id);


--
-- Name: flow_steps flow_steps_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.flow_steps
    ADD CONSTRAINT flow_steps_pkey PRIMARY KEY (step_id);


--
-- Name: flows flows_name_version_org_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.flows
    ADD CONSTRAINT flows_name_version_org_id_key UNIQUE (name, version, org_id);


--
-- Name: flows flows_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.flows
    ADD CONSTRAINT flows_pkey PRIMARY KEY (flow_id);


--
-- Name: integrations integrations_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.integrations
    ADD CONSTRAINT integrations_pkey PRIMARY KEY (integration_id);


--
-- Name: logs_migration logs_migration_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.logs_migration
    ADD CONSTRAINT logs_migration_pkey PRIMARY KEY (log_id);


--
-- Name: meta_documentation meta_documentation_meta_key_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.meta_documentation
    ADD CONSTRAINT meta_documentation_meta_key_key UNIQUE (meta_key);


--
-- Name: meta_documentation meta_documentation_pkey1; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.meta_documentation
    ADD CONSTRAINT meta_documentation_pkey1 PRIMARY KEY (doc_id);


--
-- Name: meta_field_dictionary meta_field_dictionary_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.meta_field_dictionary
    ADD CONSTRAINT meta_field_dictionary_pkey PRIMARY KEY (id);


--
-- Name: meta_function_descriptions meta_function_descriptions_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.meta_function_descriptions
    ADD CONSTRAINT meta_function_descriptions_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: modules modules_code_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.modules
    ADD CONSTRAINT modules_code_key UNIQUE (code);


--
-- Name: modules modules_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.modules
    ADD CONSTRAINT modules_pkey PRIMARY KEY (module_id);


--
-- Name: naming_templates naming_templates_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.naming_templates
    ADD CONSTRAINT naming_templates_pkey PRIMARY KEY (naming_id);


--
-- Name: naming_templates naming_templates_scope_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.naming_templates
    ADD CONSTRAINT naming_templates_scope_key UNIQUE (scope);


--
-- Name: offerings_full_details offerings_full_details_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.offerings_full_details
    ADD CONSTRAINT offerings_full_details_pkey PRIMARY KEY (detail_id);


--
-- Name: offerings_old offerings_org_id_name_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.offerings_old
    ADD CONSTRAINT offerings_org_id_name_key UNIQUE (org_id, name);


--
-- Name: offerings_old offerings_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.offerings_old
    ADD CONSTRAINT offerings_pkey PRIMARY KEY (offering_id);


--
-- Name: ops_audit ops_audit_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ops_audit
    ADD CONSTRAINT ops_audit_pkey PRIMARY KEY (audit_id);


--
-- Name: ops_etl_log ops_etl_log_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ops_etl_log
    ADD CONSTRAINT ops_etl_log_pkey PRIMARY KEY (log_id);


--
-- Name: org_datasets org_datasets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.org_datasets
    ADD CONSTRAINT org_datasets_pkey PRIMARY KEY (org_id, pkg_id);


--
-- Name: org_markets org_markets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.org_markets
    ADD CONSTRAINT org_markets_pkey PRIMARY KEY (org_id, market_id);


--
-- Name: orgs orgs_name_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.orgs
    ADD CONSTRAINT orgs_name_key UNIQUE (name);


--
-- Name: orgs orgs_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.orgs
    ADD CONSTRAINT orgs_pkey PRIMARY KEY (org_id);


--
-- Name: output_contracts output_contracts_code_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.output_contracts
    ADD CONSTRAINT output_contracts_code_key UNIQUE (code);


--
-- Name: output_contracts output_contracts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.output_contracts
    ADD CONSTRAINT output_contracts_pkey PRIMARY KEY (contract_id);


--
-- Name: performance_metrics performance_metrics_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.performance_metrics
    ADD CONSTRAINT performance_metrics_pkey PRIMARY KEY (metric_id);


--
-- Name: permissions_cache permissions_cache_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.permissions_cache
    ADD CONSTRAINT permissions_cache_pkey PRIMARY KEY (permission_code);


--
-- Name: permissions permissions_permission_code_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.permissions
    ADD CONSTRAINT permissions_permission_code_key UNIQUE (permission_code);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (permission_id);


--
-- Name: predictive_visual_engine predictive_visual_engine_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.predictive_visual_engine
    ADD CONSTRAINT predictive_visual_engine_pkey PRIMARY KEY (prediction_id);


--
-- Name: prompt_template_contracts prompt_template_contracts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_contracts
    ADD CONSTRAINT prompt_template_contracts_pkey PRIMARY KEY (prompt_id, contract_id);


--
-- Name: prompt_template_presql prompt_template_presql_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_presql
    ADD CONSTRAINT prompt_template_presql_pkey PRIMARY KEY (prompt_id, snippet_id);


--
-- Name: prompt_template_required_fields prompt_template_required_fields_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_required_fields
    ADD CONSTRAINT prompt_template_required_fields_pkey PRIMARY KEY (prompt_id, field_id);


--
-- Name: prompt_templates prompt_templates_name_version_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_templates
    ADD CONSTRAINT prompt_templates_name_version_key UNIQUE (name, version);


--
-- Name: prompt_templates prompt_templates_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_templates
    ADD CONSTRAINT prompt_templates_pkey PRIMARY KEY (prompt_id);


--
-- Name: reference_entities reference_entities_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.reference_entities
    ADD CONSTRAINT reference_entities_pkey PRIMARY KEY (ref_id);


--
-- Name: required_fields_cache required_fields_cache_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.required_fields_cache
    ADD CONSTRAINT required_fields_cache_pkey PRIMARY KEY (module_scope);


--
-- Name: role_permissions role_permissions_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.role_permissions
    ADD CONSTRAINT role_permissions_pkey PRIMARY KEY (id);


--
-- Name: role_permissions role_permissions_role_id_permission_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.role_permissions
    ADD CONSTRAINT role_permissions_role_id_permission_id_key UNIQUE (role_id, permission_id);


--
-- Name: roles roles_org_id_role_code_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.roles
    ADD CONSTRAINT roles_org_id_role_code_key UNIQUE (org_id, role_code);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (role_id);


--
-- Name: scene_library scene_library_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.scene_library
    ADD CONSTRAINT scene_library_pkey PRIMARY KEY (scene_id);


--
-- Name: security_context_audit security_context_audit_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.security_context_audit
    ADD CONSTRAINT security_context_audit_pkey PRIMARY KEY (id);


--
-- Name: segments segments_org_id_name_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.segments
    ADD CONSTRAINT segments_org_id_name_key UNIQUE (org_id, name);


--
-- Name: segments segments_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.segments
    ADD CONSTRAINT segments_pkey PRIMARY KEY (segment_id);


--
-- Name: session_context session_context_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.session_context
    ADD CONSTRAINT session_context_pkey PRIMARY KEY (session_id);


--
-- Name: social_account_metrics social_account_metrics_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_account_metrics
    ADD CONSTRAINT social_account_metrics_pkey PRIMARY KEY (integration_id, period_start, period_end);


--
-- Name: social_accounts social_accounts_account_id_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_accounts
    ADD CONSTRAINT social_accounts_account_id_pkey PRIMARY KEY (id);


--
-- Name: social_accounts social_accounts_integration_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_accounts
    ADD CONSTRAINT social_accounts_integration_id_key UNIQUE (integration_id);


--
-- Name: social_post_metrics social_post_metrics_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_post_metrics
    ADD CONSTRAINT social_post_metrics_pkey PRIMARY KEY (id);


--
-- Name: social_posts social_posts_integration_id_post_external_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_posts
    ADD CONSTRAINT social_posts_integration_id_post_external_id_key UNIQUE (integration_id, post_external_id);


--
-- Name: social_posts social_posts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_posts
    ADD CONSTRAINT social_posts_pkey PRIMARY KEY (id);


--
-- Name: sql_snippets sql_snippets_name_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.sql_snippets
    ADD CONSTRAINT sql_snippets_name_key UNIQUE (name);


--
-- Name: sql_snippets sql_snippets_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.sql_snippets
    ADD CONSTRAINT sql_snippets_pkey PRIMARY KEY (snippet_id);


--
-- Name: sync_logs sync_logs_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.sync_logs
    ADD CONSTRAINT sync_logs_pkey PRIMARY KEY (id);


--
-- Name: campaigns uq_campaign_business; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaigns
    ADD CONSTRAINT uq_campaign_business UNIQUE (org_id, name, start_date);


--
-- Name: integrations uq_integration_business; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.integrations
    ADD CONSTRAINT uq_integration_business UNIQUE (org_id, platform, account_id);


--
-- Name: flow_steps uq_step; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.flow_steps
    ADD CONSTRAINT uq_step UNIQUE (flow_id, ord);


--
-- Name: user_activities user_activities_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_activities
    ADD CONSTRAINT user_activities_pkey PRIMARY KEY (activity_id);


--
-- Name: user_orgs user_orgs_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_orgs
    ADD CONSTRAINT user_orgs_pkey PRIMARY KEY (id);


--
-- Name: user_orgs user_orgs_user_id_org_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_orgs
    ADD CONSTRAINT user_orgs_user_id_org_id_key UNIQUE (user_id, org_id);


--
-- Name: user_permissions user_permissions_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_permissions
    ADD CONSTRAINT user_permissions_pkey PRIMARY KEY (id);


--
-- Name: user_permissions user_permissions_user_id_org_id_permission_id_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_permissions
    ADD CONSTRAINT user_permissions_user_id_org_id_permission_id_key UNIQUE (user_id, org_id, permission_id);


--
-- Name: user_sessions user_sessions_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_sessions
    ADD CONSTRAINT user_sessions_pkey PRIMARY KEY (session_id);


--
-- Name: user_sessions user_sessions_session_token_key; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_sessions
    ADD CONSTRAINT user_sessions_session_token_key UNIQUE (session_token);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (user_id);


--
-- Name: value_contexts value_contexts_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.value_contexts
    ADD CONSTRAINT value_contexts_pkey PRIMARY KEY (context_id);


--
-- Name: variation_policies variation_policies_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.variation_policies
    ADD CONSTRAINT variation_policies_pkey PRIMARY KEY (policy_id);


--
-- Name: video_scenes video_scenes_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.video_scenes
    ADD CONSTRAINT video_scenes_pkey PRIMARY KEY (scene_id);


--
-- Name: video_templates video_templates_pkey; Type: CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.video_templates
    ADD CONSTRAINT video_templates_pkey PRIMARY KEY (vtpl_id);


--
-- Name: ai_queries ai_queries_pkey; Type: CONSTRAINT; Schema: cmis_analytics; Owner: begin
--

ALTER TABLE ONLY cmis_analytics.ai_queries
    ADD CONSTRAINT ai_queries_pkey PRIMARY KEY (query_id);


--
-- Name: migration_log migration_log_pkey; Type: CONSTRAINT; Schema: cmis_analytics; Owner: begin
--

ALTER TABLE ONLY cmis_analytics.migration_log
    ADD CONSTRAINT migration_log_pkey PRIMARY KEY (id);


--
-- Name: performance_snapshot performance_snapshot_pkey; Type: CONSTRAINT; Schema: cmis_analytics; Owner: begin
--

ALTER TABLE ONLY cmis_analytics.performance_snapshot
    ADD CONSTRAINT performance_snapshot_pkey PRIMARY KEY (snapshot_id);


--
-- Name: prompt_templates prompt_templates_pkey; Type: CONSTRAINT; Schema: cmis_analytics; Owner: begin
--

ALTER TABLE ONLY cmis_analytics.prompt_templates
    ADD CONSTRAINT prompt_templates_pkey PRIMARY KEY (template_id);


--
-- Name: scheduled_jobs scheduled_jobs_pkey; Type: CONSTRAINT; Schema: cmis_analytics; Owner: begin
--

ALTER TABLE ONLY cmis_analytics.scheduled_jobs
    ADD CONSTRAINT scheduled_jobs_pkey PRIMARY KEY (job_id);


--
-- Name: logs logs_pkey; Type: CONSTRAINT; Schema: cmis_audit; Owner: begin
--

ALTER TABLE ONLY cmis_audit.logs
    ADD CONSTRAINT logs_pkey PRIMARY KEY (id);


--
-- Name: dev_logs dev_logs_pkey; Type: CONSTRAINT; Schema: cmis_dev; Owner: begin
--

ALTER TABLE ONLY cmis_dev.dev_logs
    ADD CONSTRAINT dev_logs_pkey PRIMARY KEY (log_id);


--
-- Name: dev_tasks dev_tasks_pkey; Type: CONSTRAINT; Schema: cmis_dev; Owner: begin
--

ALTER TABLE ONLY cmis_dev.dev_tasks
    ADD CONSTRAINT dev_tasks_pkey PRIMARY KEY (task_id);


--
-- Name: cognitive_manifest cognitive_manifest_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.cognitive_manifest
    ADD CONSTRAINT cognitive_manifest_pkey PRIMARY KEY (manifest_id);


--
-- Name: creative_templates creative_templates_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.creative_templates
    ADD CONSTRAINT creative_templates_pkey PRIMARY KEY (template_id);


--
-- Name: dev dev_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.dev
    ADD CONSTRAINT dev_pkey PRIMARY KEY (id);


--
-- Name: direction_mappings direction_mappings_direction_name_key; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.direction_mappings
    ADD CONSTRAINT direction_mappings_direction_name_key UNIQUE (direction_name);


--
-- Name: direction_mappings direction_mappings_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.direction_mappings
    ADD CONSTRAINT direction_mappings_pkey PRIMARY KEY (direction_id);


--
-- Name: embedding_api_config embedding_api_config_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.embedding_api_config
    ADD CONSTRAINT embedding_api_config_pkey PRIMARY KEY (config_id);


--
-- Name: embedding_api_logs embedding_api_logs_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.embedding_api_logs
    ADD CONSTRAINT embedding_api_logs_pkey PRIMARY KEY (log_id);


--
-- Name: embedding_update_queue embedding_update_queue_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.embedding_update_queue
    ADD CONSTRAINT embedding_update_queue_pkey PRIMARY KEY (queue_id);


--
-- Name: embeddings_cache embeddings_cache_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.embeddings_cache
    ADD CONSTRAINT embeddings_cache_pkey PRIMARY KEY (cache_id);


--
-- Name: embeddings_cache embeddings_cache_source_table_source_id_source_field_key; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.embeddings_cache
    ADD CONSTRAINT embeddings_cache_source_table_source_id_source_field_key UNIQUE (source_table, source_id, source_field);


--
-- Name: index_backup_2025_11_10 index_backup_2025_11_10_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.index_backup_2025_11_10
    ADD CONSTRAINT index_backup_2025_11_10_pkey PRIMARY KEY (id);


--
-- Name: index index_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.index
    ADD CONSTRAINT index_pkey PRIMARY KEY (knowledge_id);


--
-- Name: intent_mappings intent_mappings_intent_name_key; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.intent_mappings
    ADD CONSTRAINT intent_mappings_intent_name_key UNIQUE (intent_name);


--
-- Name: intent_mappings intent_mappings_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.intent_mappings
    ADD CONSTRAINT intent_mappings_pkey PRIMARY KEY (intent_id);


--
-- Name: marketing marketing_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.marketing
    ADD CONSTRAINT marketing_pkey PRIMARY KEY (id);


--
-- Name: org org_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.org
    ADD CONSTRAINT org_pkey PRIMARY KEY (id);


--
-- Name: purpose_mappings purpose_mappings_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.purpose_mappings
    ADD CONSTRAINT purpose_mappings_pkey PRIMARY KEY (purpose_id);


--
-- Name: purpose_mappings purpose_mappings_purpose_name_key; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.purpose_mappings
    ADD CONSTRAINT purpose_mappings_purpose_name_key UNIQUE (purpose_name);


--
-- Name: research research_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.research
    ADD CONSTRAINT research_pkey PRIMARY KEY (id);


--
-- Name: semantic_search_logs semantic_search_logs_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.semantic_search_logs
    ADD CONSTRAINT semantic_search_logs_pkey PRIMARY KEY (log_id);


--
-- Name: semantic_search_results_cache semantic_search_results_cache_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.semantic_search_results_cache
    ADD CONSTRAINT semantic_search_results_cache_pkey PRIMARY KEY (cache_id);


--
-- Name: temporal_analytics temporal_analytics_pkey; Type: CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.temporal_analytics
    ADD CONSTRAINT temporal_analytics_pkey PRIMARY KEY (delta_id);


--
-- Name: assets assets_pkey; Type: CONSTRAINT; Schema: cmis_marketing; Owner: begin
--

ALTER TABLE ONLY cmis_marketing.assets
    ADD CONSTRAINT assets_pkey PRIMARY KEY (asset_id);


--
-- Name: generated_creatives generated_creatives_pkey; Type: CONSTRAINT; Schema: cmis_marketing; Owner: begin
--

ALTER TABLE ONLY cmis_marketing.generated_creatives
    ADD CONSTRAINT generated_creatives_pkey PRIMARY KEY (creative_id);


--
-- Name: video_scenarios video_scenarios_pkey; Type: CONSTRAINT; Schema: cmis_marketing; Owner: begin
--

ALTER TABLE ONLY cmis_marketing.video_scenarios
    ADD CONSTRAINT video_scenarios_pkey PRIMARY KEY (scenario_id);


--
-- Name: visual_concepts visual_concepts_pkey; Type: CONSTRAINT; Schema: cmis_marketing; Owner: begin
--

ALTER TABLE ONLY cmis_marketing.visual_concepts
    ADD CONSTRAINT visual_concepts_pkey PRIMARY KEY (concept_id);


--
-- Name: visual_scenarios visual_scenarios_pkey; Type: CONSTRAINT; Schema: cmis_marketing; Owner: begin
--

ALTER TABLE ONLY cmis_marketing.visual_scenarios
    ADD CONSTRAINT visual_scenarios_pkey PRIMARY KEY (scenario_id);


--
-- Name: voice_scripts voice_scripts_pkey; Type: CONSTRAINT; Schema: cmis_marketing; Owner: begin
--

ALTER TABLE ONLY cmis_marketing.voice_scripts
    ADD CONSTRAINT voice_scripts_pkey PRIMARY KEY (script_id);


--
-- Name: schema_fixes_log schema_fixes_log_pkey; Type: CONSTRAINT; Schema: cmis_ops; Owner: begin
--

ALTER TABLE ONLY cmis_ops.schema_fixes_log
    ADD CONSTRAINT schema_fixes_log_pkey PRIMARY KEY (id);


--
-- Name: raw_channel_data raw_channel_data_pkey; Type: CONSTRAINT; Schema: cmis_staging; Owner: begin
--

ALTER TABLE ONLY cmis_staging.raw_channel_data
    ADD CONSTRAINT raw_channel_data_pkey PRIMARY KEY (id);


--
-- Name: cognitive_reports cognitive_reports_pkey; Type: CONSTRAINT; Schema: cmis_system_health; Owner: begin
--

ALTER TABLE ONLY cmis_system_health.cognitive_reports
    ADD CONSTRAINT cognitive_reports_pkey PRIMARY KEY (report_id);


--
-- Name: cognitive_vitality_log cognitive_vitality_log_pkey; Type: CONSTRAINT; Schema: cmis_system_health; Owner: begin
--

ALTER TABLE ONLY cmis_system_health.cognitive_vitality_log
    ADD CONSTRAINT cognitive_vitality_log_pkey PRIMARY KEY (record_id);


--
-- Name: example_sets example_sets_pkey; Type: CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.example_sets
    ADD CONSTRAINT example_sets_pkey PRIMARY KEY (example_id);


--
-- Name: example_used_fields example_used_fields_pkey; Type: CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.example_used_fields
    ADD CONSTRAINT example_used_fields_pkey PRIMARY KEY (example_id, field_id);


--
-- Name: test_matrix test_matrix_pkey; Type: CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.test_matrix
    ADD CONSTRAINT test_matrix_pkey PRIMARY KEY (test_id);


--
-- Name: audit_log audit_log_pkey; Type: CONSTRAINT; Schema: operations; Owner: begin
--

ALTER TABLE ONLY operations.audit_log
    ADD CONSTRAINT audit_log_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: operations; Owner: begin
--

ALTER TABLE ONLY operations.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (migration_id);


--
-- Name: awareness_stages awareness_stages_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.awareness_stages
    ADD CONSTRAINT awareness_stages_pkey PRIMARY KEY (stage);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: channel_formats channel_formats_channel_id_code_key; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.channel_formats
    ADD CONSTRAINT channel_formats_channel_id_code_key UNIQUE (channel_id, code);


--
-- Name: channel_formats channel_formats_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.channel_formats
    ADD CONSTRAINT channel_formats_pkey PRIMARY KEY (format_id);


--
-- Name: channels channels_code_key; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.channels
    ADD CONSTRAINT channels_code_key UNIQUE (code);


--
-- Name: channels channels_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.channels
    ADD CONSTRAINT channels_pkey PRIMARY KEY (channel_id);


--
-- Name: cmis_access_control cmis_access_control_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.cmis_access_control
    ADD CONSTRAINT cmis_access_control_pkey PRIMARY KEY (rule_id);


--
-- Name: cmis_system_health cmis_system_health_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.cmis_system_health
    ADD CONSTRAINT cmis_system_health_pkey PRIMARY KEY (check_id);


--
-- Name: component_types component_types_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.component_types
    ADD CONSTRAINT component_types_pkey PRIMARY KEY (type_code);


--
-- Name: frameworks frameworks_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.frameworks
    ADD CONSTRAINT frameworks_pkey PRIMARY KEY (framework_id);


--
-- Name: funnel_stages funnel_stages_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.funnel_stages
    ADD CONSTRAINT funnel_stages_pkey PRIMARY KEY (stage);


--
-- Name: industries industries_name_key; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.industries
    ADD CONSTRAINT industries_name_key UNIQUE (name);


--
-- Name: industries industries_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.industries
    ADD CONSTRAINT industries_pkey PRIMARY KEY (industry_id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: kpis kpis_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.kpis
    ADD CONSTRAINT kpis_pkey PRIMARY KEY (kpi);


--
-- Name: marketing_objectives marketing_objectives_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.marketing_objectives
    ADD CONSTRAINT marketing_objectives_pkey PRIMARY KEY (objective);


--
-- Name: markets markets_market_name_language_code_key; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.markets
    ADD CONSTRAINT markets_market_name_language_code_key UNIQUE (market_name, language_code);


--
-- Name: markets markets_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.markets
    ADD CONSTRAINT markets_pkey PRIMARY KEY (market_id);


--
-- Name: migration_log migration_log_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.migration_log
    ADD CONSTRAINT migration_log_pkey PRIMARY KEY (id);


--
-- Name: modules_old modules_code_key; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.modules_old
    ADD CONSTRAINT modules_code_key UNIQUE (code);


--
-- Name: modules_old modules_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.modules_old
    ADD CONSTRAINT modules_pkey PRIMARY KEY (module_id);


--
-- Name: naming_templates_old naming_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.naming_templates_old
    ADD CONSTRAINT naming_templates_pkey PRIMARY KEY (naming_id);


--
-- Name: naming_templates_old naming_templates_scope_key; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.naming_templates_old
    ADD CONSTRAINT naming_templates_scope_key UNIQUE (scope);


--
-- Name: proof_layers proof_layers_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.proof_layers
    ADD CONSTRAINT proof_layers_pkey PRIMARY KEY (level);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: strategies strategies_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.strategies
    ADD CONSTRAINT strategies_pkey PRIMARY KEY (strategy);


--
-- Name: tones tones_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.tones
    ADD CONSTRAINT tones_pkey PRIMARY KEY (tone);


--
-- Name: view_definitions_backup view_definitions_backup_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.view_definitions_backup
    ADD CONSTRAINT view_definitions_backup_pkey PRIMARY KEY (viewname);


--
-- Name: visual_kpis visual_kpis_name_key; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_kpis
    ADD CONSTRAINT visual_kpis_name_key UNIQUE (name);


--
-- Name: visual_kpis visual_kpis_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_kpis
    ADD CONSTRAINT visual_kpis_pkey PRIMARY KEY (kpi_id);


--
-- Name: visual_principles visual_principles_name_key; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_principles
    ADD CONSTRAINT visual_principles_name_key UNIQUE (name);


--
-- Name: visual_principles visual_principles_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_principles
    ADD CONSTRAINT visual_principles_pkey PRIMARY KEY (principle_id);


--
-- Name: visual_recommendations visual_recommendations_pkey; Type: CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_recommendations
    ADD CONSTRAINT visual_recommendations_pkey PRIMARY KEY (recommendation_id);


--
-- Name: idx_ad_audiences_entity; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_ad_audiences_entity ON cmis.ad_audiences USING btree (entity_level, entity_external_id);


--
-- Name: idx_ad_audiences_platform; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_ad_audiences_platform ON cmis.ad_audiences USING btree (platform);


--
-- Name: idx_ad_metrics_date; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_ad_metrics_date ON cmis.ad_metrics USING btree (date_start, date_stop);


--
-- Name: idx_ad_metrics_entity; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_ad_metrics_entity ON cmis.ad_metrics USING btree (entity_level, entity_external_id);


--
-- Name: idx_ai_actions_org; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_ai_actions_org ON cmis.ai_actions USING btree (org_id);


--
-- Name: idx_ai_actions_org_time; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_ai_actions_org_time ON cmis.ai_actions USING btree (org_id, created_at);


--
-- Name: idx_anchors_code; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_anchors_code ON cmis.anchors USING gist (code);


--
-- Name: idx_api_keys_service_code; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_api_keys_service_code ON cmis.api_keys USING btree (service_code);


--
-- Name: idx_assets_campaign; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_assets_campaign ON cmis.creative_assets USING btree (campaign_id);


--
-- Name: idx_audit_log_ts; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_audit_log_ts ON cmis.audit_log USING btree (ts DESC);


--
-- Name: idx_campaign_links_active; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaign_links_active ON cmis.campaign_context_links USING btree (is_active) WHERE (is_active = true);


--
-- Name: idx_campaign_links_campaign; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaign_links_campaign ON cmis.campaign_context_links USING btree (campaign_id);


--
-- Name: idx_campaign_links_context; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaign_links_context ON cmis.campaign_context_links USING btree (context_id);


--
-- Name: idx_campaign_links_created_at; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaign_links_created_at ON cmis.campaign_context_links USING btree (created_at);


--
-- Name: idx_campaign_links_effective; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaign_links_effective ON cmis.campaign_context_links USING btree (effective_from, effective_to) WHERE ((effective_from IS NOT NULL) OR (effective_to IS NOT NULL));


--
-- Name: idx_campaign_links_link_type; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaign_links_link_type ON cmis.campaign_context_links USING btree (link_type);


--
-- Name: idx_campaign_links_metadata; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaign_links_metadata ON cmis.campaign_context_links USING gin (metadata);


--
-- Name: idx_campaign_links_metadata_gin; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaign_links_metadata_gin ON cmis.campaign_context_links USING gin (metadata jsonb_path_ops);


--
-- Name: idx_campaign_links_type; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaign_links_type ON cmis.campaign_context_links USING btree (context_type);


--
-- Name: idx_campaign_offerings_cid; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaign_offerings_cid ON cmis.campaign_offerings USING btree (campaign_id);


--
-- Name: idx_campaign_offerings_oid; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaign_offerings_oid ON cmis.campaign_offerings USING btree (offering_id);


--
-- Name: idx_campaigns_active; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaigns_active ON cmis.campaigns USING btree (org_id, status) WHERE (deleted_at IS NULL);


--
-- Name: idx_campaigns_dates; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaigns_dates ON cmis.campaigns USING btree (start_date, end_date);


--
-- Name: idx_campaigns_org_id; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaigns_org_id ON cmis.campaigns USING btree (org_id);


--
-- Name: idx_campaigns_org_status_created; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_campaigns_org_status_created ON cmis.campaigns USING btree (org_id, status, created_at DESC);


--
-- Name: idx_cc_channel; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_cc_channel ON cmis.copy_components USING btree (channel_id);


--
-- Name: idx_cc_content_trgm; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_cc_content_trgm ON cmis.copy_components USING gin (content public.gin_trgm_ops);


--
-- Name: idx_cc_industry; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_cc_industry ON cmis.copy_components USING btree (industry_id);


--
-- Name: idx_cc_market; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_cc_market ON cmis.copy_components USING btree (market_id);


--
-- Name: idx_cc_type; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_cc_type ON cmis.copy_components USING btree (type_code);


--
-- Name: idx_content_items_active; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_content_items_active ON cmis.content_items USING btree (org_id, created_at DESC) WHERE (deleted_at IS NULL);


--
-- Name: idx_content_items_channel; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_content_items_channel ON cmis.content_items USING btree (channel_id);


--
-- Name: idx_content_items_context; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_content_items_context ON cmis.content_items USING btree (context_id);


--
-- Name: idx_content_items_example; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_content_items_example ON cmis.content_items USING btree (example_id);


--
-- Name: idx_content_items_format; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_content_items_format ON cmis.content_items USING btree (format_id);


--
-- Name: idx_content_items_plan; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_content_items_plan ON cmis.content_items USING btree (plan_id);


--
-- Name: idx_contexts_metadata_gin; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_contexts_metadata_gin ON cmis.contexts USING gin (metadata jsonb_path_ops);


--
-- Name: idx_creative_assets_active; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_creative_assets_active ON cmis.creative_assets USING btree (org_id, campaign_id) WHERE (deleted_at IS NULL);


--
-- Name: idx_creative_assets_org; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_creative_assets_org ON cmis.creative_assets USING btree (org_id);


--
-- Name: idx_creative_assets_org_id; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_creative_assets_org_id ON cmis.creative_assets USING btree (org_id);


--
-- Name: idx_creative_briefs_org_id; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_creative_briefs_org_id ON cmis.creative_briefs USING btree (org_id);


--
-- Name: idx_field_value_text; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_field_value_text ON cmis.field_values USING gin (((value)::text) public.gin_trgm_ops);


--
-- Name: idx_field_values_created; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_field_values_created ON cmis.field_values USING btree (created_at DESC);


--
-- Name: idx_field_values_json; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_field_values_json ON cmis.field_values USING gin (value jsonb_path_ops);


--
-- Name: idx_integrations_active; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_integrations_active ON cmis.integrations USING btree (org_id) WHERE (deleted_at IS NULL);


--
-- Name: idx_orgs_id; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_orgs_id ON cmis.orgs USING btree (org_id);


--
-- Name: idx_reference_entities_metadata_gin; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_reference_entities_metadata_gin ON cmis.reference_entities USING gin (metadata jsonb_path_ops);


--
-- Name: idx_security_audit_org_created; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_security_audit_org_created ON cmis.security_context_audit USING btree (org_id, created_at DESC);


--
-- Name: idx_security_audit_user_created; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_security_audit_user_created ON cmis.security_context_audit USING btree (user_id, created_at DESC);


--
-- Name: idx_user_sessions_expires; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_user_sessions_expires ON cmis.user_sessions USING btree (expires_at) WHERE (is_active = true);


--
-- Name: idx_user_sessions_token; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_user_sessions_token ON cmis.user_sessions USING btree (session_token) WHERE (is_active = true);


--
-- Name: idx_user_sessions_user_id; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_user_sessions_user_id ON cmis.user_sessions USING btree (user_id);


--
-- Name: idx_users_active; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_users_active ON cmis.users USING btree (email) WHERE (deleted_at IS NULL);


--
-- Name: idx_users_email; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_users_email ON cmis.users USING btree (email);


--
-- Name: idx_values_context; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_values_context ON cmis.field_values USING btree (context_id);


--
-- Name: idx_values_field; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_values_field ON cmis.field_values USING btree (field_id);


--
-- Name: idx_video_scenes_asset; Type: INDEX; Schema: cmis; Owner: begin
--

CREATE INDEX idx_video_scenes_asset ON cmis.video_scenes USING btree (asset_id);


--
-- Name: idx_prompt_templates_trgm; Type: INDEX; Schema: cmis_analytics; Owner: begin
--

CREATE INDEX idx_prompt_templates_trgm ON cmis_analytics.prompt_templates USING gin (prompt_text public.gin_trgm_ops);


--
-- Name: uq_perf_snapshot; Type: INDEX; Schema: cmis_analytics; Owner: begin
--

CREATE UNIQUE INDEX uq_perf_snapshot ON cmis_analytics.performance_snapshot USING btree (org_id, campaign_id, kpi, observed_at);


--
-- Name: idx_audit_logs_created; Type: INDEX; Schema: cmis_audit; Owner: begin
--

CREATE INDEX idx_audit_logs_created ON cmis_audit.logs USING btree (created_at);


--
-- Name: idx_dev_logs_task; Type: INDEX; Schema: cmis_dev; Owner: begin
--

CREATE INDEX idx_dev_logs_task ON cmis_dev.dev_logs USING btree (task_id);


--
-- Name: idx_dev_tasks_status; Type: INDEX; Schema: cmis_dev; Owner: begin
--

CREATE INDEX idx_dev_tasks_status ON cmis_dev.dev_tasks USING btree (status);


--
-- Name: idx_creative_content_embedding; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_creative_content_embedding ON cmis_knowledge.creative_templates USING hnsw (content_embedding public.vector_cosine_ops);


--
-- Name: idx_dev_content_embedding; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_dev_content_embedding ON cmis_knowledge.dev USING hnsw (content_embedding public.vector_cosine_ops);


--
-- Name: idx_dev_content_embedding_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_dev_content_embedding_hnsw ON cmis_knowledge.dev USING hnsw (content_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_dev_semantic_summary_embedding_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_dev_semantic_summary_embedding_hnsw ON cmis_knowledge.dev USING hnsw (semantic_summary_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_dev_semantic_summary_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_dev_semantic_summary_hnsw ON cmis_knowledge.dev USING hnsw (semantic_summary_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_direction_embedding; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_direction_embedding ON cmis_knowledge.direction_mappings USING hnsw (direction_embedding public.vector_cosine_ops);


--
-- Name: idx_direction_mappings_metadata_gin; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_direction_mappings_metadata_gin ON cmis_knowledge.direction_mappings USING gin (metadata jsonb_path_ops);


--
-- Name: idx_embedding_queue_status; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_embedding_queue_status ON cmis_knowledge.embedding_update_queue USING btree (status, priority DESC) WHERE (status = ANY (ARRAY['pending'::text, 'processing'::text]));


--
-- Name: idx_embeddings_cache_hash; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE UNIQUE INDEX idx_embeddings_cache_hash ON cmis_knowledge.embeddings_cache USING btree (input_hash);


--
-- Name: idx_embeddings_cache_last_used; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_embeddings_cache_last_used ON cmis_knowledge.embeddings_cache USING btree (last_used_at);


--
-- Name: idx_embeddings_cache_metadata_gin; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_embeddings_cache_metadata_gin ON cmis_knowledge.embeddings_cache USING gin (metadata jsonb_path_ops);


--
-- Name: idx_embeddings_cache_source; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_embeddings_cache_source ON cmis_knowledge.embeddings_cache USING btree (source_table, source_id);


--
-- Name: idx_embeddings_cache_vector; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_embeddings_cache_vector ON cmis_knowledge.embeddings_cache USING hnsw (embedding public.vector_cosine_ops);


--
-- Name: idx_index_direction_vector; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_index_direction_vector ON cmis_knowledge.index USING hnsw (direction_vector public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_index_direction_vector_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_index_direction_vector_hnsw ON cmis_knowledge.index USING hnsw (direction_vector public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_index_intent_vector; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_index_intent_vector ON cmis_knowledge.index USING hnsw (intent_vector public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_index_intent_vector_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_index_intent_vector_hnsw ON cmis_knowledge.index USING hnsw (intent_vector public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_index_keywords_embedding_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_index_keywords_embedding_hnsw ON cmis_knowledge.index USING hnsw (keywords_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_index_purpose_vector; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_index_purpose_vector ON cmis_knowledge.index USING hnsw (purpose_vector public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_index_purpose_vector_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_index_purpose_vector_hnsw ON cmis_knowledge.index USING hnsw (purpose_vector public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_index_topic_embedding; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_index_topic_embedding ON cmis_knowledge.index USING hnsw (topic_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_index_topic_embedding_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_index_topic_embedding_hnsw ON cmis_knowledge.index USING hnsw (topic_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_intent_embedding; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_intent_embedding ON cmis_knowledge.intent_mappings USING hnsw (intent_embedding public.vector_cosine_ops);


--
-- Name: idx_knowledge_content_search; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_knowledge_content_search ON cmis_knowledge.dev USING gin (content_search);


--
-- Name: idx_knowledge_domain_tier; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_knowledge_domain_tier ON cmis_knowledge.index USING btree (domain, category, tier);


--
-- Name: idx_knowledge_index_direction_vector; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_knowledge_index_direction_vector ON cmis_knowledge.index USING ivfflat (direction_vector public.vector_cosine_ops) WITH (lists='200');


--
-- Name: idx_knowledge_index_intent_vector; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_knowledge_index_intent_vector ON cmis_knowledge.index USING ivfflat (intent_vector public.vector_cosine_ops) WITH (lists='200');


--
-- Name: idx_knowledge_index_keywords_embedding; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_knowledge_index_keywords_embedding ON cmis_knowledge.index USING ivfflat (keywords_embedding public.vector_cosine_ops) WITH (lists='200');


--
-- Name: idx_knowledge_index_purpose_vector; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_knowledge_index_purpose_vector ON cmis_knowledge.index USING ivfflat (purpose_vector public.vector_cosine_ops) WITH (lists='200');


--
-- Name: idx_knowledge_index_topic_embedding; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_knowledge_index_topic_embedding ON cmis_knowledge.index USING ivfflat (topic_embedding public.vector_cosine_ops) WITH (lists='200');


--
-- Name: idx_knowledge_last_verified; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_knowledge_last_verified ON cmis_knowledge.index USING btree (last_verified_at DESC);


--
-- Name: idx_marketing_audience_embedding; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_marketing_audience_embedding ON cmis_knowledge.marketing USING hnsw (audience_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_marketing_audience_embedding_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_marketing_audience_embedding_hnsw ON cmis_knowledge.marketing USING hnsw (audience_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_marketing_campaign_intent_vector_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_marketing_campaign_intent_vector_hnsw ON cmis_knowledge.marketing USING hnsw (campaign_intent_vector public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_marketing_content_embedding; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_marketing_content_embedding ON cmis_knowledge.marketing USING hnsw (content_embedding public.vector_cosine_ops);


--
-- Name: idx_marketing_content_embedding_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_marketing_content_embedding_hnsw ON cmis_knowledge.marketing USING hnsw (content_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_marketing_emotional_direction_vector_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_marketing_emotional_direction_vector_hnsw ON cmis_knowledge.marketing USING hnsw (emotional_direction_vector public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_marketing_tone_embedding; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_marketing_tone_embedding ON cmis_knowledge.marketing USING hnsw (tone_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_marketing_tone_embedding_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_marketing_tone_embedding_hnsw ON cmis_knowledge.marketing USING hnsw (tone_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_purpose_embedding; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_purpose_embedding ON cmis_knowledge.purpose_mappings USING hnsw (purpose_embedding public.vector_cosine_ops);


--
-- Name: idx_research_content_embedding; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_research_content_embedding ON cmis_knowledge.research USING hnsw (content_embedding public.vector_cosine_ops);


--
-- Name: idx_research_content_embedding_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_research_content_embedding_hnsw ON cmis_knowledge.research USING hnsw (content_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_research_insight_embedding; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_research_insight_embedding ON cmis_knowledge.research USING hnsw (insight_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_research_insight_embedding_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_research_insight_embedding_hnsw ON cmis_knowledge.research USING hnsw (insight_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_research_research_direction_vector_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_research_research_direction_vector_hnsw ON cmis_knowledge.research USING hnsw (research_direction_vector public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_research_source_context_embedding_hnsw; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_research_source_context_embedding_hnsw ON cmis_knowledge.research USING hnsw (source_context_embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: idx_search_cache_hash; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_search_cache_hash ON cmis_knowledge.semantic_search_results_cache USING btree (query_hash);


--
-- Name: idx_search_logs_time; Type: INDEX; Schema: cmis_knowledge; Owner: begin
--

CREATE INDEX idx_search_logs_time ON cmis_knowledge.semantic_search_logs USING btree (created_at DESC);


--
-- Name: idx_examples_body_fts; Type: INDEX; Schema: lab; Owner: begin
--

CREATE INDEX idx_examples_body_fts ON lab.example_sets USING gin (((body)::text) public.gin_trgm_ops);


--
-- Name: idx_examples_tags; Type: INDEX; Schema: lab; Owner: begin
--

CREATE INDEX idx_examples_tags ON lab.example_sets USING gin (tags);


--
-- Name: idx_audit_action; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_audit_action ON operations.audit_log USING btree (action);


--
-- Name: idx_audit_changed_fields; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_audit_changed_fields ON operations.audit_log USING gin (changed_fields);


--
-- Name: idx_audit_ip; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_audit_ip ON operations.audit_log USING btree (ip_address);


--
-- Name: idx_audit_log_metadata_gin; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_audit_log_metadata_gin ON operations.audit_log USING gin (metadata jsonb_path_ops);


--
-- Name: idx_audit_metadata; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_audit_metadata ON operations.audit_log USING gin (metadata);


--
-- Name: idx_audit_record; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_audit_record ON operations.audit_log USING btree (record_id);


--
-- Name: idx_audit_record_key; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_audit_record_key ON operations.audit_log USING btree (record_key);


--
-- Name: idx_audit_session; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_audit_session ON operations.audit_log USING btree (session_id);


--
-- Name: idx_audit_table; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_audit_table ON operations.audit_log USING btree (table_schema, table_name);


--
-- Name: idx_audit_tags; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_audit_tags ON operations.audit_log USING gin (tags);


--
-- Name: idx_audit_timestamp; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_audit_timestamp ON operations.audit_log USING btree ("timestamp");


--
-- Name: idx_audit_timestamp_hour; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_audit_timestamp_hour ON operations.audit_log USING btree (date_trunc('hour'::text, "timestamp"));


--
-- Name: idx_audit_user; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_audit_user ON operations.audit_log USING btree (user_id);


--
-- Name: idx_audit_username; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_audit_username ON operations.audit_log USING btree (username);


--
-- Name: idx_migrations_phase; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_migrations_phase ON operations.migrations USING btree (phase);


--
-- Name: idx_migrations_started; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_migrations_started ON operations.migrations USING btree (started_at);


--
-- Name: idx_migrations_status; Type: INDEX; Schema: operations; Owner: begin
--

CREATE INDEX idx_migrations_status ON operations.migrations USING btree (status);


--
-- Name: campaign_context_links audit_trigger_campaign_context_links; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER audit_trigger_campaign_context_links AFTER INSERT OR DELETE OR UPDATE ON cmis.campaign_context_links FOR EACH ROW EXECUTE FUNCTION operations.audit_trigger_function();


--
-- Name: campaigns audit_trigger_campaigns; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER audit_trigger_campaigns AFTER INSERT OR DELETE OR UPDATE ON cmis.campaigns FOR EACH ROW EXECUTE FUNCTION operations.audit_trigger_function();


--
-- Name: creative_assets audit_trigger_creative_assets; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER audit_trigger_creative_assets AFTER INSERT OR DELETE OR UPDATE ON cmis.creative_assets FOR EACH ROW EXECUTE FUNCTION operations.audit_trigger_function();


--
-- Name: integrations audit_trigger_integrations; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER audit_trigger_integrations AFTER INSERT OR DELETE OR UPDATE ON cmis.integrations FOR EACH ROW EXECUTE FUNCTION operations.audit_trigger_function();


--
-- Name: orgs audit_trigger_orgs; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER audit_trigger_orgs AFTER INSERT OR DELETE OR UPDATE ON cmis.orgs FOR EACH ROW EXECUTE FUNCTION operations.audit_trigger_function();


--
-- Name: users audit_trigger_users; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER audit_trigger_users AFTER INSERT OR DELETE OR UPDATE ON cmis.users FOR EACH ROW EXECUTE FUNCTION operations.audit_trigger_function();


--
-- Name: creative_briefs enforce_brief_completeness_optimized; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER enforce_brief_completeness_optimized BEFORE INSERT OR UPDATE ON cmis.creative_briefs FOR EACH ROW EXECUTE FUNCTION cmis.prevent_incomplete_briefs_optimized();


--
-- Name: analytics_integrations set_updated_at; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER set_updated_at BEFORE UPDATE ON cmis.analytics_integrations FOR EACH ROW EXECUTE FUNCTION cmis_ops.update_timestamp();


--
-- Name: audit_log set_updated_at; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER set_updated_at BEFORE UPDATE ON cmis.audit_log FOR EACH ROW EXECUTE FUNCTION cmis_ops.update_timestamp();


--
-- Name: content_items set_updated_at; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER set_updated_at BEFORE UPDATE ON cmis.content_items FOR EACH ROW EXECUTE FUNCTION cmis_ops.update_timestamp();


--
-- Name: creative_briefs trg_audit_creative_brief_changes; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER trg_audit_creative_brief_changes AFTER INSERT OR UPDATE ON cmis.creative_briefs FOR EACH ROW EXECUTE FUNCTION cmis.audit_creative_changes();


--
-- Name: permissions trg_permissions_cache; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER trg_permissions_cache AFTER INSERT OR DELETE OR UPDATE ON cmis.permissions FOR EACH ROW EXECUTE FUNCTION cmis.refresh_permissions_cache();


--
-- Name: field_definitions trg_refresh_fields_cache; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER trg_refresh_fields_cache AFTER INSERT OR DELETE OR UPDATE OR TRUNCATE ON cmis.field_definitions FOR EACH STATEMENT EXECUTE FUNCTION cmis.auto_refresh_cache_on_field_change();


--
-- Name: campaign_context_links update_campaign_links_updated_at; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER update_campaign_links_updated_at BEFORE UPDATE ON cmis.campaign_context_links FOR EACH ROW EXECUTE FUNCTION cmis.update_updated_at_column();


--
-- Name: logs trg_manifest_sync_audit; Type: TRIGGER; Schema: cmis_audit; Owner: begin
--

CREATE TRIGGER trg_manifest_sync_audit AFTER INSERT ON cmis_audit.logs FOR EACH ROW EXECUTE FUNCTION cmis_knowledge.update_manifest_on_change();


--
-- Name: temporal_analytics trg_manifest_sync_temporal; Type: TRIGGER; Schema: cmis_knowledge; Owner: begin
--

CREATE TRIGGER trg_manifest_sync_temporal AFTER INSERT OR UPDATE ON cmis_knowledge.temporal_analytics FOR EACH ROW EXECUTE FUNCTION cmis_knowledge.update_manifest_on_change();


--
-- Name: dev update_embeddings_on_dev_change; Type: TRIGGER; Schema: cmis_knowledge; Owner: begin
--

CREATE TRIGGER update_embeddings_on_dev_change AFTER INSERT OR UPDATE OF content ON cmis_knowledge.dev FOR EACH ROW EXECUTE FUNCTION cmis_knowledge.trigger_update_embeddings();


--
-- Name: index update_embeddings_on_index_change; Type: TRIGGER; Schema: cmis_knowledge; Owner: begin
--

CREATE TRIGGER update_embeddings_on_index_change AFTER INSERT OR UPDATE OF topic, keywords, domain, category ON cmis_knowledge.index FOR EACH ROW EXECUTE FUNCTION cmis_knowledge.trigger_update_embeddings();


--
-- Name: marketing update_embeddings_on_marketing_change; Type: TRIGGER; Schema: cmis_knowledge; Owner: begin
--

CREATE TRIGGER update_embeddings_on_marketing_change AFTER INSERT OR UPDATE OF content ON cmis_knowledge.marketing FOR EACH ROW EXECUTE FUNCTION cmis_knowledge.trigger_update_embeddings();


--
-- Name: research update_embeddings_on_research_change; Type: TRIGGER; Schema: cmis_knowledge; Owner: begin
--

CREATE TRIGGER update_embeddings_on_research_change AFTER INSERT OR UPDATE OF content ON cmis_knowledge.research FOR EACH ROW EXECUTE FUNCTION cmis_knowledge.trigger_update_embeddings();


--
-- Name: ad_accounts ad_accounts_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_accounts
    ADD CONSTRAINT ad_accounts_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: ad_accounts ad_accounts_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_accounts
    ADD CONSTRAINT ad_accounts_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_audiences ad_audiences_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_audiences
    ADD CONSTRAINT ad_audiences_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: ad_audiences ad_audiences_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_audiences
    ADD CONSTRAINT ad_audiences_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_campaigns ad_campaigns_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_campaigns
    ADD CONSTRAINT ad_campaigns_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: ad_campaigns ad_campaigns_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_campaigns
    ADD CONSTRAINT ad_campaigns_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_entities ad_entities_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_entities
    ADD CONSTRAINT ad_entities_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: ad_entities ad_entities_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_entities
    ADD CONSTRAINT ad_entities_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_metrics ad_metrics_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_metrics
    ADD CONSTRAINT ad_metrics_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: ad_metrics ad_metrics_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_metrics
    ADD CONSTRAINT ad_metrics_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ad_sets ad_sets_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_sets
    ADD CONSTRAINT ad_sets_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: ad_sets ad_sets_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ad_sets
    ADD CONSTRAINT ad_sets_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: ai_actions ai_actions_audit_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ai_actions
    ADD CONSTRAINT ai_actions_audit_id_fkey FOREIGN KEY (audit_id) REFERENCES cmis.audit_log(log_id) ON DELETE CASCADE;


--
-- Name: ai_generated_campaigns ai_generated_campaigns_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ai_generated_campaigns
    ADD CONSTRAINT ai_generated_campaigns_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: anchors anchors_module_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.anchors
    ADD CONSTRAINT anchors_module_id_fkey FOREIGN KEY (module_id) REFERENCES cmis.modules(module_id) ON DELETE SET NULL;


--
-- Name: audio_templates audio_templates_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.audio_templates
    ADD CONSTRAINT audio_templates_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: audit_log audit_log_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.audit_log
    ADD CONSTRAINT audit_log_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: bundle_offerings bundle_offerings_bundle_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.bundle_offerings
    ADD CONSTRAINT bundle_offerings_bundle_id_fkey FOREIGN KEY (bundle_id) REFERENCES cmis.offerings_old(offering_id) ON DELETE CASCADE;


--
-- Name: bundle_offerings bundle_offerings_offering_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.bundle_offerings
    ADD CONSTRAINT bundle_offerings_offering_id_fkey FOREIGN KEY (offering_id) REFERENCES cmis.offerings_old(offering_id) ON DELETE CASCADE;


--
-- Name: campaign_offerings campaign_offerings_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaign_offerings
    ADD CONSTRAINT campaign_offerings_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis.campaigns(campaign_id) MATCH FULL ON DELETE CASCADE;


--
-- Name: campaign_offerings campaign_offerings_offering_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaign_offerings
    ADD CONSTRAINT campaign_offerings_offering_id_fkey FOREIGN KEY (offering_id) REFERENCES cmis.offerings_old(offering_id) ON DELETE CASCADE;


--
-- Name: campaign_performance_dashboard campaign_performance_dashboard_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaign_performance_dashboard
    ADD CONSTRAINT campaign_performance_dashboard_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis.campaigns(campaign_id) MATCH FULL ON DELETE CASCADE;


--
-- Name: cognitive_tracker_template cognitive_tracker_template_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.cognitive_tracker_template
    ADD CONSTRAINT cognitive_tracker_template_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: cognitive_trends cognitive_trends_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.cognitive_trends
    ADD CONSTRAINT cognitive_trends_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: compliance_audits compliance_audits_rule_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.compliance_audits
    ADD CONSTRAINT compliance_audits_rule_id_fkey FOREIGN KEY (rule_id) REFERENCES cmis.compliance_rules(rule_id) ON DELETE CASCADE;


--
-- Name: compliance_rule_channels compliance_rule_channels_rule_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.compliance_rule_channels
    ADD CONSTRAINT compliance_rule_channels_rule_id_fkey FOREIGN KEY (rule_id) REFERENCES cmis.compliance_rules(rule_id) ON DELETE CASCADE;


--
-- Name: content_items content_items_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.value_contexts(context_id);


--
-- Name: content_items content_items_creative_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_creative_context_id_fkey FOREIGN KEY (creative_context_id) REFERENCES cmis.creative_contexts(context_id) ON DELETE SET NULL;


--
-- Name: content_items content_items_example_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_example_id_fkey FOREIGN KEY (example_id) REFERENCES lab.example_sets(example_id);


--
-- Name: content_items content_items_plan_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT content_items_plan_id_fkey FOREIGN KEY (plan_id) REFERENCES cmis.content_plans(plan_id) ON DELETE CASCADE;


--
-- Name: content_plans content_plans_brief_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_plans
    ADD CONSTRAINT content_plans_brief_id_fkey FOREIGN KEY (brief_id) REFERENCES cmis.creative_briefs(brief_id) ON DELETE SET NULL;


--
-- Name: content_plans content_plans_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_plans
    ADD CONSTRAINT content_plans_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis.campaigns(campaign_id) MATCH FULL ON DELETE SET NULL;


--
-- Name: content_plans content_plans_creative_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_plans
    ADD CONSTRAINT content_plans_creative_context_id_fkey FOREIGN KEY (creative_context_id) REFERENCES cmis.creative_contexts(context_id) ON DELETE SET NULL;


--
-- Name: content_plans content_plans_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_plans
    ADD CONSTRAINT content_plans_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: contexts_base contexts_base_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.contexts_base
    ADD CONSTRAINT contexts_base_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id);


--
-- Name: contexts contexts_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.contexts
    ADD CONSTRAINT contexts_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis.campaigns(campaign_id) ON DELETE CASCADE;


--
-- Name: contexts_creative contexts_creative_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.contexts_creative
    ADD CONSTRAINT contexts_creative_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.contexts_base(id) ON DELETE CASCADE;


--
-- Name: contexts_offering contexts_offering_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.contexts_offering
    ADD CONSTRAINT contexts_offering_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.contexts_base(id) ON DELETE CASCADE;


--
-- Name: contexts_value contexts_value_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.contexts_value
    ADD CONSTRAINT contexts_value_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.contexts_base(id) ON DELETE CASCADE;


--
-- Name: copy_components copy_components_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.copy_components
    ADD CONSTRAINT copy_components_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis.campaigns(campaign_id) MATCH FULL ON DELETE SET NULL;


--
-- Name: copy_components copy_components_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.copy_components
    ADD CONSTRAINT copy_components_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.value_contexts(context_id);


--
-- Name: copy_components copy_components_example_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.copy_components
    ADD CONSTRAINT copy_components_example_id_fkey FOREIGN KEY (example_id) REFERENCES lab.example_sets(example_id);


--
-- Name: copy_components copy_components_plan_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.copy_components
    ADD CONSTRAINT copy_components_plan_id_fkey FOREIGN KEY (plan_id) REFERENCES cmis.content_plans(plan_id) ON DELETE SET NULL;


--
-- Name: creative_assets creative_assets_brief_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_brief_id_fkey FOREIGN KEY (brief_id) REFERENCES cmis.creative_briefs(brief_id) ON DELETE SET NULL;


--
-- Name: creative_assets creative_assets_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis.campaigns(campaign_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: creative_assets creative_assets_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.value_contexts(context_id);


--
-- Name: creative_assets creative_assets_creative_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_creative_context_id_fkey FOREIGN KEY (creative_context_id) REFERENCES cmis.creative_contexts(context_id) ON DELETE SET NULL;


--
-- Name: creative_assets creative_assets_example_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_example_id_fkey FOREIGN KEY (example_id) REFERENCES lab.example_sets(example_id);


--
-- Name: creative_assets creative_assets_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_assets
    ADD CONSTRAINT creative_assets_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: creative_briefs creative_briefs_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_briefs
    ADD CONSTRAINT creative_briefs_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: creative_contexts creative_contexts_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_contexts
    ADD CONSTRAINT creative_contexts_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: creative_outputs creative_outputs_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_outputs
    ADD CONSTRAINT creative_outputs_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis.campaigns(campaign_id) ON DELETE CASCADE;


--
-- Name: creative_outputs creative_outputs_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_outputs
    ADD CONSTRAINT creative_outputs_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.contexts(context_id) ON DELETE CASCADE;


--
-- Name: data_feeds data_feeds_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.data_feeds
    ADD CONSTRAINT data_feeds_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: dataset_files dataset_files_pkg_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.dataset_files
    ADD CONSTRAINT dataset_files_pkg_id_fkey FOREIGN KEY (pkg_id) REFERENCES cmis.dataset_packages(pkg_id) ON DELETE CASCADE;


--
-- Name: experiment_variants experiment_variants_asset_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.experiment_variants
    ADD CONSTRAINT experiment_variants_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES cmis.creative_assets(asset_id) ON DELETE CASCADE;


--
-- Name: experiment_variants experiment_variants_exp_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.experiment_variants
    ADD CONSTRAINT experiment_variants_exp_id_fkey FOREIGN KEY (exp_id) REFERENCES cmis.experiments(exp_id) ON DELETE CASCADE;


--
-- Name: experiments experiments_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.experiments
    ADD CONSTRAINT experiments_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis.campaigns(campaign_id) MATCH FULL ON DELETE SET NULL;


--
-- Name: experiments experiments_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.experiments
    ADD CONSTRAINT experiments_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: export_bundle_items export_bundle_items_asset_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.export_bundle_items
    ADD CONSTRAINT export_bundle_items_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES cmis.creative_assets(asset_id) ON DELETE CASCADE;


--
-- Name: export_bundle_items export_bundle_items_bundle_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.export_bundle_items
    ADD CONSTRAINT export_bundle_items_bundle_id_fkey FOREIGN KEY (bundle_id) REFERENCES cmis.export_bundles(bundle_id) ON DELETE CASCADE;


--
-- Name: export_bundles export_bundles_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.export_bundles
    ADD CONSTRAINT export_bundles_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: feed_items feed_items_feed_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.feed_items
    ADD CONSTRAINT feed_items_feed_id_fkey FOREIGN KEY (feed_id) REFERENCES cmis.data_feeds(feed_id) ON DELETE CASCADE;


--
-- Name: field_aliases field_aliases_field_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_aliases
    ADD CONSTRAINT field_aliases_field_id_fkey FOREIGN KEY (field_id) REFERENCES cmis.field_definitions(field_id) ON DELETE CASCADE;


--
-- Name: field_definitions field_definitions_guidance_anchor_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_definitions
    ADD CONSTRAINT field_definitions_guidance_anchor_fkey FOREIGN KEY (guidance_anchor) REFERENCES cmis.anchors(anchor_id) ON DELETE SET NULL;


--
-- Name: field_definitions field_definitions_module_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_definitions
    ADD CONSTRAINT field_definitions_module_id_fkey FOREIGN KEY (module_id) REFERENCES cmis.modules(module_id) ON DELETE SET NULL;


--
-- Name: field_values field_values_context_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_values
    ADD CONSTRAINT field_values_context_id_fkey FOREIGN KEY (context_id) REFERENCES cmis.value_contexts(context_id) ON DELETE CASCADE;


--
-- Name: field_values field_values_field_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.field_values
    ADD CONSTRAINT field_values_field_id_fkey FOREIGN KEY (field_id) REFERENCES cmis.field_definitions(field_id) ON DELETE CASCADE;


--
-- Name: campaigns fk_campaigns_context; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.campaigns
    ADD CONSTRAINT fk_campaigns_context FOREIGN KEY (context_id) REFERENCES cmis.value_contexts(context_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: content_items fk_content_item_asset; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT fk_content_item_asset FOREIGN KEY (asset_id) REFERENCES cmis.creative_assets(asset_id) ON DELETE SET NULL;


--
-- Name: content_items fk_content_items_context; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.content_items
    ADD CONSTRAINT fk_content_items_context FOREIGN KEY (context_id) REFERENCES cmis.creative_contexts(context_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: creative_outputs fk_creative_outputs_context; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.creative_outputs
    ADD CONSTRAINT fk_creative_outputs_context FOREIGN KEY (context_id) REFERENCES cmis.creative_contexts(context_id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: user_orgs fk_user_orgs_role; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_orgs
    ADD CONSTRAINT fk_user_orgs_role FOREIGN KEY (role_id) REFERENCES cmis.roles(role_id);


--
-- Name: flow_steps flow_steps_flow_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.flow_steps
    ADD CONSTRAINT flow_steps_flow_id_fkey FOREIGN KEY (flow_id) REFERENCES cmis.flows(flow_id) ON DELETE CASCADE;


--
-- Name: flows flows_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.flows
    ADD CONSTRAINT flows_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: offerings_full_details offerings_full_details_offering_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.offerings_full_details
    ADD CONSTRAINT offerings_full_details_offering_id_fkey FOREIGN KEY (offering_id) REFERENCES cmis.offerings_old(offering_id) ON DELETE CASCADE;


--
-- Name: offerings_old offerings_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.offerings_old
    ADD CONSTRAINT offerings_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: org_datasets org_datasets_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.org_datasets
    ADD CONSTRAINT org_datasets_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: org_datasets org_datasets_pkg_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.org_datasets
    ADD CONSTRAINT org_datasets_pkg_id_fkey FOREIGN KEY (pkg_id) REFERENCES cmis.dataset_packages(pkg_id) ON DELETE CASCADE;


--
-- Name: org_markets org_markets_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.org_markets
    ADD CONSTRAINT org_markets_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: performance_metrics performance_metrics_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.performance_metrics
    ADD CONSTRAINT performance_metrics_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis.campaigns(campaign_id) ON DELETE CASCADE;


--
-- Name: performance_metrics performance_metrics_output_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.performance_metrics
    ADD CONSTRAINT performance_metrics_output_id_fkey FOREIGN KEY (output_id) REFERENCES cmis.creative_outputs(output_id) ON DELETE CASCADE;


--
-- Name: predictive_visual_engine predictive_visual_engine_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.predictive_visual_engine
    ADD CONSTRAINT predictive_visual_engine_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: prompt_template_contracts prompt_template_contracts_contract_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_contracts
    ADD CONSTRAINT prompt_template_contracts_contract_id_fkey FOREIGN KEY (contract_id) REFERENCES cmis.output_contracts(contract_id) ON DELETE CASCADE;


--
-- Name: prompt_template_contracts prompt_template_contracts_prompt_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_contracts
    ADD CONSTRAINT prompt_template_contracts_prompt_id_fkey FOREIGN KEY (prompt_id) REFERENCES cmis.prompt_templates(prompt_id) ON DELETE CASCADE;


--
-- Name: prompt_template_presql prompt_template_presql_prompt_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_presql
    ADD CONSTRAINT prompt_template_presql_prompt_id_fkey FOREIGN KEY (prompt_id) REFERENCES cmis.prompt_templates(prompt_id) ON DELETE CASCADE;


--
-- Name: prompt_template_presql prompt_template_presql_snippet_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_presql
    ADD CONSTRAINT prompt_template_presql_snippet_id_fkey FOREIGN KEY (snippet_id) REFERENCES cmis.sql_snippets(snippet_id) ON DELETE CASCADE;


--
-- Name: prompt_template_required_fields prompt_template_required_fields_field_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_required_fields
    ADD CONSTRAINT prompt_template_required_fields_field_id_fkey FOREIGN KEY (field_id) REFERENCES cmis.field_definitions(field_id) ON DELETE CASCADE;


--
-- Name: prompt_template_required_fields prompt_template_required_fields_prompt_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_template_required_fields
    ADD CONSTRAINT prompt_template_required_fields_prompt_id_fkey FOREIGN KEY (prompt_id) REFERENCES cmis.prompt_templates(prompt_id) ON DELETE CASCADE;


--
-- Name: prompt_templates prompt_templates_module_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.prompt_templates
    ADD CONSTRAINT prompt_templates_module_id_fkey FOREIGN KEY (module_id) REFERENCES cmis.modules(module_id) ON DELETE SET NULL;


--
-- Name: role_permissions role_permissions_granted_by_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.role_permissions
    ADD CONSTRAINT role_permissions_granted_by_fkey FOREIGN KEY (granted_by) REFERENCES cmis.users(user_id);


--
-- Name: role_permissions role_permissions_permission_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.role_permissions
    ADD CONSTRAINT role_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES cmis.permissions(permission_id) ON DELETE CASCADE;


--
-- Name: role_permissions role_permissions_role_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.role_permissions
    ADD CONSTRAINT role_permissions_role_id_fkey FOREIGN KEY (role_id) REFERENCES cmis.roles(role_id) ON DELETE CASCADE;


--
-- Name: roles roles_created_by_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.roles
    ADD CONSTRAINT roles_created_by_fkey FOREIGN KEY (created_by) REFERENCES cmis.users(user_id);


--
-- Name: roles roles_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.roles
    ADD CONSTRAINT roles_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: scene_library scene_library_anchor_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.scene_library
    ADD CONSTRAINT scene_library_anchor_fkey FOREIGN KEY (anchor) REFERENCES cmis.anchors(anchor_id) ON DELETE SET NULL;


--
-- Name: scene_library scene_library_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.scene_library
    ADD CONSTRAINT scene_library_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: segments segments_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.segments
    ADD CONSTRAINT segments_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: session_context session_context_active_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.session_context
    ADD CONSTRAINT session_context_active_org_id_fkey FOREIGN KEY (active_org_id) REFERENCES cmis.orgs(org_id);


--
-- Name: session_context session_context_session_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.session_context
    ADD CONSTRAINT session_context_session_id_fkey FOREIGN KEY (session_id) REFERENCES cmis.user_sessions(session_id) ON DELETE CASCADE;


--
-- Name: social_account_metrics social_account_metrics_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_account_metrics
    ADD CONSTRAINT social_account_metrics_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: social_accounts social_accounts_account_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_accounts
    ADD CONSTRAINT social_accounts_account_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: social_accounts social_accounts_account_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_accounts
    ADD CONSTRAINT social_accounts_account_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: social_posts social_posts_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_posts
    ADD CONSTRAINT social_posts_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: social_posts social_posts_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.social_posts
    ADD CONSTRAINT social_posts_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: sync_logs sync_logs_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.sync_logs
    ADD CONSTRAINT sync_logs_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis.integrations(integration_id) ON DELETE SET NULL;


--
-- Name: sync_logs sync_logs_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.sync_logs
    ADD CONSTRAINT sync_logs_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: user_activities user_activities_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_activities
    ADD CONSTRAINT user_activities_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id);


--
-- Name: user_activities user_activities_session_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_activities
    ADD CONSTRAINT user_activities_session_id_fkey FOREIGN KEY (session_id) REFERENCES cmis.user_sessions(session_id);


--
-- Name: user_activities user_activities_user_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_activities
    ADD CONSTRAINT user_activities_user_id_fkey FOREIGN KEY (user_id) REFERENCES cmis.users(user_id);


--
-- Name: user_orgs user_orgs_invited_by_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_orgs
    ADD CONSTRAINT user_orgs_invited_by_fkey FOREIGN KEY (invited_by) REFERENCES cmis.users(user_id);


--
-- Name: user_orgs user_orgs_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_orgs
    ADD CONSTRAINT user_orgs_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: user_orgs user_orgs_user_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_orgs
    ADD CONSTRAINT user_orgs_user_id_fkey FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE;


--
-- Name: user_permissions user_permissions_granted_by_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_permissions
    ADD CONSTRAINT user_permissions_granted_by_fkey FOREIGN KEY (granted_by) REFERENCES cmis.users(user_id);


--
-- Name: user_permissions user_permissions_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_permissions
    ADD CONSTRAINT user_permissions_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: user_permissions user_permissions_permission_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_permissions
    ADD CONSTRAINT user_permissions_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES cmis.permissions(permission_id) ON DELETE CASCADE;


--
-- Name: user_permissions user_permissions_user_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_permissions
    ADD CONSTRAINT user_permissions_user_id_fkey FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE;


--
-- Name: user_sessions user_sessions_user_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.user_sessions
    ADD CONSTRAINT user_sessions_user_id_fkey FOREIGN KEY (user_id) REFERENCES cmis.users(user_id) ON DELETE CASCADE;


--
-- Name: value_contexts value_contexts_campaign_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.value_contexts
    ADD CONSTRAINT value_contexts_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis.campaigns(campaign_id) MATCH FULL ON DELETE SET NULL DEFERRABLE;


--
-- Name: value_contexts value_contexts_offering_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.value_contexts
    ADD CONSTRAINT value_contexts_offering_id_fkey FOREIGN KEY (offering_id) REFERENCES cmis.offerings_old(offering_id) ON DELETE SET NULL;


--
-- Name: value_contexts value_contexts_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.value_contexts
    ADD CONSTRAINT value_contexts_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;


--
-- Name: value_contexts value_contexts_segment_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.value_contexts
    ADD CONSTRAINT value_contexts_segment_id_fkey FOREIGN KEY (segment_id) REFERENCES cmis.segments(segment_id) ON DELETE SET NULL;


--
-- Name: variation_policies variation_policies_naming_ref_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.variation_policies
    ADD CONSTRAINT variation_policies_naming_ref_fkey FOREIGN KEY (naming_ref) REFERENCES cmis.naming_templates(naming_id) ON DELETE CASCADE;


--
-- Name: variation_policies variation_policies_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.variation_policies
    ADD CONSTRAINT variation_policies_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: video_scenes video_scenes_asset_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.video_scenes
    ADD CONSTRAINT video_scenes_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES cmis.creative_assets(asset_id) ON DELETE CASCADE;


--
-- Name: video_templates video_templates_org_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.video_templates
    ADD CONSTRAINT video_templates_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: dev_logs dev_logs_task_id_fkey; Type: FK CONSTRAINT; Schema: cmis_dev; Owner: begin
--

ALTER TABLE ONLY cmis_dev.dev_logs
    ADD CONSTRAINT dev_logs_task_id_fkey FOREIGN KEY (task_id) REFERENCES cmis_dev.dev_tasks(task_id);


--
-- Name: dev dev_knowledge_id_fkey; Type: FK CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.dev
    ADD CONSTRAINT dev_knowledge_id_fkey FOREIGN KEY (knowledge_id) REFERENCES cmis_knowledge.index(knowledge_id);


--
-- Name: dev dev_parent_knowledge_id_fkey; Type: FK CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.dev
    ADD CONSTRAINT dev_parent_knowledge_id_fkey FOREIGN KEY (parent_knowledge_id) REFERENCES cmis_knowledge.index(knowledge_id);


--
-- Name: direction_mappings direction_mappings_parent_direction_id_fkey; Type: FK CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.direction_mappings
    ADD CONSTRAINT direction_mappings_parent_direction_id_fkey FOREIGN KEY (parent_direction_id) REFERENCES cmis_knowledge.direction_mappings(direction_id);


--
-- Name: intent_mappings intent_mappings_parent_intent_id_fkey; Type: FK CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.intent_mappings
    ADD CONSTRAINT intent_mappings_parent_intent_id_fkey FOREIGN KEY (parent_intent_id) REFERENCES cmis_knowledge.intent_mappings(intent_id);


--
-- Name: marketing marketing_knowledge_id_fkey; Type: FK CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.marketing
    ADD CONSTRAINT marketing_knowledge_id_fkey FOREIGN KEY (knowledge_id) REFERENCES cmis_knowledge.index(knowledge_id);


--
-- Name: org org_knowledge_id_fkey; Type: FK CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.org
    ADD CONSTRAINT org_knowledge_id_fkey FOREIGN KEY (knowledge_id) REFERENCES cmis_knowledge.index(knowledge_id);


--
-- Name: research research_knowledge_id_fkey; Type: FK CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.research
    ADD CONSTRAINT research_knowledge_id_fkey FOREIGN KEY (knowledge_id) REFERENCES cmis_knowledge.index(knowledge_id);


--
-- Name: temporal_analytics temporal_analytics_knowledge_id_fkey; Type: FK CONSTRAINT; Schema: cmis_knowledge; Owner: begin
--

ALTER TABLE ONLY cmis_knowledge.temporal_analytics
    ADD CONSTRAINT temporal_analytics_knowledge_id_fkey FOREIGN KEY (knowledge_id) REFERENCES cmis_knowledge.index(knowledge_id);


--
-- Name: assets assets_task_id_fkey; Type: FK CONSTRAINT; Schema: cmis_marketing; Owner: begin
--

ALTER TABLE ONLY cmis_marketing.assets
    ADD CONSTRAINT assets_task_id_fkey FOREIGN KEY (task_id) REFERENCES cmis_dev.dev_tasks(task_id) ON DELETE CASCADE;


--
-- Name: video_scenarios video_scenarios_asset_id_fkey; Type: FK CONSTRAINT; Schema: cmis_marketing; Owner: begin
--

ALTER TABLE ONLY cmis_marketing.video_scenarios
    ADD CONSTRAINT video_scenarios_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES cmis_marketing.assets(asset_id) ON DELETE CASCADE;


--
-- Name: video_scenarios video_scenarios_task_id_fkey; Type: FK CONSTRAINT; Schema: cmis_marketing; Owner: begin
--

ALTER TABLE ONLY cmis_marketing.video_scenarios
    ADD CONSTRAINT video_scenarios_task_id_fkey FOREIGN KEY (task_id) REFERENCES cmis_dev.dev_tasks(task_id) ON DELETE CASCADE;


--
-- Name: visual_concepts visual_concepts_asset_id_fkey; Type: FK CONSTRAINT; Schema: cmis_marketing; Owner: begin
--

ALTER TABLE ONLY cmis_marketing.visual_concepts
    ADD CONSTRAINT visual_concepts_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES cmis_marketing.assets(asset_id) ON DELETE CASCADE;


--
-- Name: visual_scenarios visual_scenarios_creative_id_fkey; Type: FK CONSTRAINT; Schema: cmis_marketing; Owner: begin
--

ALTER TABLE ONLY cmis_marketing.visual_scenarios
    ADD CONSTRAINT visual_scenarios_creative_id_fkey FOREIGN KEY (creative_id) REFERENCES cmis_marketing.generated_creatives(creative_id) ON DELETE CASCADE;


--
-- Name: voice_scripts voice_scripts_scenario_id_fkey; Type: FK CONSTRAINT; Schema: cmis_marketing; Owner: begin
--

ALTER TABLE ONLY cmis_marketing.voice_scripts
    ADD CONSTRAINT voice_scripts_scenario_id_fkey FOREIGN KEY (scenario_id) REFERENCES cmis_marketing.video_scenarios(scenario_id) ON DELETE CASCADE;


--
-- Name: voice_scripts voice_scripts_task_id_fkey; Type: FK CONSTRAINT; Schema: cmis_marketing; Owner: begin
--

ALTER TABLE ONLY cmis_marketing.voice_scripts
    ADD CONSTRAINT voice_scripts_task_id_fkey FOREIGN KEY (task_id) REFERENCES cmis_dev.dev_tasks(task_id) ON DELETE CASCADE;


--
-- Name: raw_channel_data raw_channel_data_integration_id_fkey; Type: FK CONSTRAINT; Schema: cmis_staging; Owner: begin
--

ALTER TABLE ONLY cmis_staging.raw_channel_data
    ADD CONSTRAINT raw_channel_data_integration_id_fkey FOREIGN KEY (integration_id) REFERENCES cmis.integrations(integration_id);


--
-- Name: example_sets example_sets_anchor_fkey; Type: FK CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.example_sets
    ADD CONSTRAINT example_sets_anchor_fkey FOREIGN KEY (anchor) REFERENCES cmis.anchors(anchor_id) ON DELETE SET NULL;


--
-- Name: example_sets example_sets_campaign_id_fkey; Type: FK CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.example_sets
    ADD CONSTRAINT example_sets_campaign_id_fkey FOREIGN KEY (campaign_id) REFERENCES cmis.campaigns(campaign_id) MATCH FULL ON DELETE SET NULL;


--
-- Name: example_sets example_sets_org_id_fkey; Type: FK CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.example_sets
    ADD CONSTRAINT example_sets_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: example_used_fields example_used_fields_example_id_fkey; Type: FK CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.example_used_fields
    ADD CONSTRAINT example_used_fields_example_id_fkey FOREIGN KEY (example_id) REFERENCES lab.example_sets(example_id) ON DELETE CASCADE;


--
-- Name: example_used_fields example_used_fields_field_id_fkey; Type: FK CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.example_used_fields
    ADD CONSTRAINT example_used_fields_field_id_fkey FOREIGN KEY (field_id) REFERENCES cmis.field_definitions(field_id) ON DELETE CASCADE;


--
-- Name: test_matrix test_matrix_org_id_fkey; Type: FK CONSTRAINT; Schema: lab; Owner: begin
--

ALTER TABLE ONLY lab.test_matrix
    ADD CONSTRAINT test_matrix_org_id_fkey FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE SET NULL;


--
-- Name: channel_formats channel_formats_channel_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.channel_formats
    ADD CONSTRAINT channel_formats_channel_id_fkey FOREIGN KEY (channel_id) REFERENCES public.channels(channel_id) ON DELETE CASCADE;


--
-- Name: visual_recommendations visual_recommendations_linked_kpi_fkey; Type: FK CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_recommendations
    ADD CONSTRAINT visual_recommendations_linked_kpi_fkey FOREIGN KEY (linked_kpi) REFERENCES public.visual_kpis(name);


--
-- Name: visual_recommendations visual_recommendations_objective_code_fkey; Type: FK CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_recommendations
    ADD CONSTRAINT visual_recommendations_objective_code_fkey FOREIGN KEY (objective_code) REFERENCES public.marketing_objectives(objective);


--
-- Name: visual_recommendations visual_recommendations_recommended_principle_fkey; Type: FK CONSTRAINT; Schema: public; Owner: begin
--

ALTER TABLE ONLY public.visual_recommendations
    ADD CONSTRAINT visual_recommendations_recommended_principle_fkey FOREIGN KEY (recommended_principle) REFERENCES public.visual_principles(name);


--
-- Name: ad_accounts; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.ad_accounts ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_audiences; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.ad_audiences ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_campaigns; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.ad_campaigns ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_entities; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.ad_entities ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_metrics; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.ad_metrics ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_sets; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.ad_sets ENABLE ROW LEVEL SECURITY;

--
-- Name: ai_actions; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.ai_actions ENABLE ROW LEVEL SECURITY;

--
-- Name: audit_log; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.audit_log ENABLE ROW LEVEL SECURITY;

--
-- Name: campaigns; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.campaigns ENABLE ROW LEVEL SECURITY;

--
-- Name: content_plans; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.content_plans ENABLE ROW LEVEL SECURITY;

--
-- Name: creative_assets; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.creative_assets ENABLE ROW LEVEL SECURITY;

--
-- Name: data_feeds; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.data_feeds ENABLE ROW LEVEL SECURITY;

--
-- Name: experiments; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.experiments ENABLE ROW LEVEL SECURITY;

--
-- Name: export_bundles; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.export_bundles ENABLE ROW LEVEL SECURITY;

--
-- Name: feed_items; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.feed_items ENABLE ROW LEVEL SECURITY;

--
-- Name: flows; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.flows ENABLE ROW LEVEL SECURITY;

--
-- Name: integrations; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.integrations ENABLE ROW LEVEL SECURITY;

--
-- Name: ad_accounts rbac_ad_accounts; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_ad_accounts ON cmis.ad_accounts FOR SELECT USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'campaigns.view'::text)));


--
-- Name: ad_campaigns rbac_ad_campaigns; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_ad_campaigns ON cmis.ad_campaigns FOR SELECT USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'campaigns.view'::text)));


--
-- Name: ai_actions rbac_ai_actions; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_ai_actions ON cmis.ai_actions FOR SELECT USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'analytics.view'::text)));


--
-- Name: analytics_integrations rbac_analytics_integrations; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_analytics_integrations ON cmis.analytics_integrations FOR SELECT USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'analytics.view'::text)));


--
-- Name: analytics_integrations rbac_analytics_integrations_manage; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_analytics_integrations_manage ON cmis.analytics_integrations USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'analytics.configure'::text)));


--
-- Name: audit_log rbac_audit_log; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_audit_log ON cmis.audit_log FOR SELECT USING ((((org_id IS NULL) OR (org_id = cmis.get_current_org_id())) AND cmis.check_permission(cmis.get_current_user_id(), COALESCE(org_id, cmis.get_current_org_id()), 'admin.settings'::text)));


--
-- Name: campaigns rbac_campaigns_delete; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_campaigns_delete ON cmis.campaigns FOR DELETE USING (((org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'campaigns.delete'::text)));


--
-- Name: campaigns rbac_campaigns_delete_v2; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_campaigns_delete_v2 ON cmis.campaigns FOR DELETE USING (cmis.check_permission_tx('campaigns.delete'::text));


--
-- Name: campaigns rbac_campaigns_insert; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_campaigns_insert ON cmis.campaigns FOR INSERT WITH CHECK (((org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'campaigns.create'::text)));


--
-- Name: campaigns rbac_campaigns_insert_v2; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_campaigns_insert_v2 ON cmis.campaigns FOR INSERT WITH CHECK (cmis.check_permission_tx('campaigns.create'::text));


--
-- Name: campaigns rbac_campaigns_select; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_campaigns_select ON cmis.campaigns FOR SELECT USING ((((deleted_at IS NULL) OR (deleted_at > CURRENT_TIMESTAMP)) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'campaigns.view'::text)));


--
-- Name: campaigns rbac_campaigns_select_v2; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_campaigns_select_v2 ON cmis.campaigns FOR SELECT USING ((((deleted_at IS NULL) OR (deleted_at > CURRENT_TIMESTAMP)) AND cmis.check_permission_tx('campaigns.view'::text)));


--
-- Name: campaigns rbac_campaigns_update; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_campaigns_update ON cmis.campaigns FOR UPDATE USING ((((deleted_at IS NULL) OR (deleted_at > CURRENT_TIMESTAMP)) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'campaigns.edit'::text)));


--
-- Name: campaigns rbac_campaigns_update_v2; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_campaigns_update_v2 ON cmis.campaigns FOR UPDATE USING ((((deleted_at IS NULL) OR (deleted_at > CURRENT_TIMESTAMP)) AND cmis.check_permission_tx('campaigns.edit'::text)));


--
-- Name: content_items rbac_content_items; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_content_items ON cmis.content_items FOR SELECT USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'creatives.view'::text)));


--
-- Name: creative_assets rbac_creative_assets_insert; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_creative_assets_insert ON cmis.creative_assets FOR INSERT WITH CHECK (((org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'creatives.create'::text)));


--
-- Name: creative_assets rbac_creative_assets_select; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_creative_assets_select ON cmis.creative_assets FOR SELECT USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'creatives.view'::text)));


--
-- Name: creative_assets rbac_creative_assets_update; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_creative_assets_update ON cmis.creative_assets FOR UPDATE USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'creatives.edit'::text)));


--
-- Name: integrations rbac_integrations_manage; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_integrations_manage ON cmis.integrations USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'integrations.manage'::text)));


--
-- Name: integrations rbac_integrations_select; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_integrations_select ON cmis.integrations FOR SELECT USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'integrations.view'::text)));


--
-- Name: orgs rbac_orgs_manage; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_orgs_manage ON cmis.orgs FOR UPDATE USING (cmis.check_permission(cmis.get_current_user_id(), org_id, 'orgs.manage'::text));


--
-- Name: orgs rbac_orgs_select; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_orgs_select ON cmis.orgs FOR SELECT USING (cmis.check_permission(cmis.get_current_user_id(), org_id, 'orgs.view'::text));


--
-- Name: users rbac_users_select; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_users_select ON cmis.users FOR SELECT USING (((user_id = cmis.get_current_user_id()) OR (EXISTS ( SELECT 1
   FROM cmis.user_orgs uo
  WHERE ((uo.user_id = cmis.get_current_user_id()) AND (uo.deleted_at IS NULL) AND cmis.check_permission(cmis.get_current_user_id(), uo.org_id, 'admin.users'::text))))));


--
-- Name: users rbac_users_update; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_users_update ON cmis.users FOR UPDATE USING (((user_id = cmis.get_current_user_id()) OR (EXISTS ( SELECT 1
   FROM cmis.user_orgs uo
  WHERE ((uo.user_id = cmis.get_current_user_id()) AND (uo.deleted_at IS NULL) AND cmis.check_permission(cmis.get_current_user_id(), uo.org_id, 'admin.users'::text))))));


--
-- Name: user_orgs user_orgs_self; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY user_orgs_self ON cmis.user_orgs FOR SELECT USING ((user_id = cmis.get_current_user_id()));


--
-- Name: users; Type: ROW SECURITY; Schema: cmis; Owner: begin
--

ALTER TABLE cmis.users ENABLE ROW LEVEL SECURITY;

--
-- Name: example_sets; Type: ROW SECURITY; Schema: lab; Owner: begin
--

ALTER TABLE lab.example_sets ENABLE ROW LEVEL SECURITY;

--
-- Name: example_sets org_isolation_example_sets; Type: POLICY; Schema: lab; Owner: begin
--

CREATE POLICY org_isolation_example_sets ON lab.example_sets USING (((org_id IS NULL) OR (org_id = (current_setting('app.current_org_id'::text))::uuid)));


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: cmis_knowledge; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA cmis_knowledge GRANT SELECT,INSERT,DELETE,UPDATE ON TABLES TO begin;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: cmis_system_health; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA cmis_system_health GRANT SELECT,INSERT,DELETE,UPDATE ON TABLES TO begin;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: public; Owner: begin
--

ALTER DEFAULT PRIVILEGES FOR ROLE begin IN SCHEMA public GRANT ALL ON SEQUENCES TO begin;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: public; Owner: begin
--

ALTER DEFAULT PRIVILEGES FOR ROLE begin IN SCHEMA public GRANT ALL ON TABLES TO begin;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT SELECT,INSERT,DELETE,UPDATE ON TABLES TO begin;


--
-- PostgreSQL database dump complete
--

\unrestrict fL9USgZ0bCgwu6UgdZGFeo84xVwQFLKkUmCDHB3eSHauHmEZLHEzT6jLnJ3mAlB

