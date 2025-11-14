CREATE VIEW cmis.awareness_stages AS
 SELECT stage
   FROM public.awareness_stages;


ALTER VIEW cmis.awareness_stages OWNER TO begin;

CREATE VIEW cmis.channel_formats AS
 SELECT format_id,
    channel_id,
    code,
    ratio,
    length_hint
   FROM public.channel_formats;


ALTER VIEW cmis.channel_formats OWNER TO begin;

CREATE VIEW cmis.channels AS
 SELECT channel_id,
    code,
    name,
    constraints
   FROM public.channels;


ALTER VIEW cmis.channels OWNER TO begin;

CREATE VIEW cmis.component_types AS
 SELECT type_code
   FROM public.component_types;


ALTER VIEW cmis.component_types OWNER TO begin;

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

CREATE VIEW cmis.frameworks AS
 SELECT framework_id,
    framework_name,
    framework_type,
    description,
    created_at
   FROM public.frameworks;


ALTER VIEW cmis.frameworks OWNER TO begin;

CREATE VIEW cmis.funnel_stages AS
 SELECT stage
   FROM public.funnel_stages;


ALTER VIEW cmis.funnel_stages OWNER TO begin;

CREATE VIEW cmis.industries AS
 SELECT industry_id,
    name
   FROM public.industries;


ALTER VIEW cmis.industries OWNER TO begin;

CREATE VIEW cmis.kpis AS
 SELECT kpi,
    description
   FROM public.kpis;


ALTER VIEW cmis.kpis OWNER TO begin;

CREATE VIEW cmis.marketing_objectives AS
 SELECT objective,
    display_name,
    category,
    description
   FROM public.marketing_objectives;


ALTER VIEW cmis.marketing_objectives OWNER TO begin;

CREATE VIEW cmis.markets AS
 SELECT market_id,
    market_name,
    language_code,
    currency_code,
    text_direction
   FROM public.markets;


ALTER VIEW cmis.markets OWNER TO begin;

CREATE VIEW cmis.playbook_steps AS
 SELECT step_id,
    flow_id AS playbook_id,
    ord AS step_order,
    COALESCE(name, type) AS step_name,
    NULL::text AS step_instructions,
    NULL::text AS module_reference
   FROM cmis.flow_steps s;


ALTER VIEW cmis.playbook_steps OWNER TO begin;

CREATE VIEW cmis.playbooks AS
 SELECT flow_id AS playbook_id,
    name AS playbook_name,
    description
   FROM cmis.flows f;


ALTER VIEW cmis.playbooks OWNER TO begin;

CREATE VIEW cmis.proof_layers AS
 SELECT level
   FROM public.proof_layers;


ALTER VIEW cmis.proof_layers OWNER TO begin;

CREATE VIEW cmis.strategies AS
 SELECT strategy
   FROM public.strategies;


ALTER VIEW cmis.strategies OWNER TO begin;

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

CREATE VIEW cmis.tones AS
 SELECT tone
   FROM public.tones;


ALTER VIEW cmis.tones OWNER TO begin;

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

CREATE VIEW cmis.v_system_monitoring AS
 SELECT now() AS "timestamp",
    count(*) AS table_count
   FROM information_schema.tables
  WHERE ((table_schema)::name = 'cmis'::name);


ALTER VIEW cmis.v_system_monitoring OWNER TO begin;

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

CREATE VIEW public.modules AS
 SELECT module_id,
    code,
    name,
    version
   FROM cmis.modules;


ALTER VIEW public.modules OWNER TO begin;

CREATE VIEW public.naming_templates AS
 SELECT naming_id,
    scope,
    template
   FROM cmis.naming_templates;


ALTER VIEW public.naming_templates OWNER TO begin;

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

