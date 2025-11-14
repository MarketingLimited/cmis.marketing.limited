CREATE TRIGGER audit_trigger_campaign_context_links AFTER INSERT OR DELETE OR UPDATE ON cmis.campaign_context_links FOR EACH ROW EXECUTE FUNCTION operations.audit_trigger_function();


--
-- Name: campaigns audit_trigger_campaigns; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER audit_trigger_campaigns AFTER INSERT OR DELETE OR UPDATE ON cmis.campaigns FOR EACH ROW EXECUTE FUNCTION operations.audit_trigger_function();

ALTER TABLE cmis.campaigns DISABLE TRIGGER audit_trigger_campaigns;


--
-- Name: creative_assets audit_trigger_creative_assets; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER audit_trigger_creative_assets AFTER INSERT OR DELETE OR UPDATE ON cmis.creative_assets FOR EACH ROW EXECUTE FUNCTION operations.audit_trigger_function();

ALTER TABLE cmis.creative_assets DISABLE TRIGGER audit_trigger_creative_assets;


--
-- Name: integrations audit_trigger_integrations; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER audit_trigger_integrations AFTER INSERT OR DELETE OR UPDATE ON cmis.integrations FOR EACH ROW EXECUTE FUNCTION operations.audit_trigger_function();

ALTER TABLE cmis.integrations DISABLE TRIGGER audit_trigger_integrations;


--
-- Name: orgs audit_trigger_orgs; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER audit_trigger_orgs AFTER INSERT OR DELETE OR UPDATE ON cmis.orgs FOR EACH ROW EXECUTE FUNCTION operations.audit_trigger_function();

ALTER TABLE cmis.orgs DISABLE TRIGGER audit_trigger_orgs;


--
-- Name: users audit_trigger_users; Type: TRIGGER; Schema: cmis; Owner: begin
--

CREATE TRIGGER audit_trigger_users AFTER INSERT OR DELETE OR UPDATE ON cmis.users FOR EACH ROW EXECUTE FUNCTION operations.audit_trigger_function();

ALTER TABLE cmis.users DISABLE TRIGGER audit_trigger_users;


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
-- Name: ab_test_variations ab_test_variations_ab_test_id_fkey; Type: FK CONSTRAINT; Schema: cmis; Owner: begin
--

ALTER TABLE ONLY cmis.ab_test_variations
    ADD CONSTRAINT ab_test_variations_ab_test_id_fkey FOREIGN KEY (ab_test_id) REFERENCES cmis.ab_tests(ab_test_id) ON DELETE CASCADE;


