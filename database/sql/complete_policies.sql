CREATE POLICY rbac_ad_accounts ON cmis.ad_accounts FOR SELECT USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'campaigns.view'::text)));


--
-- Name: ad_campaigns rbac_ad_campaigns; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_ad_campaigns ON cmis.ad_campaigns FOR SELECT USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'campaigns.view'::text)));

CREATE POLICY rbac_ai_actions ON cmis.ai_actions FOR SELECT USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'analytics.view'::text)));


--
-- Name: analytics_integrations rbac_analytics_integrations; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_analytics_integrations ON cmis.analytics_integrations FOR SELECT USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'analytics.view'::text)));

CREATE POLICY rbac_analytics_integrations_manage ON cmis.analytics_integrations USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'analytics.configure'::text)));


--
-- Name: audit_log rbac_audit_log; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_audit_log ON cmis.audit_log FOR SELECT USING ((((org_id IS NULL) OR (org_id = cmis.get_current_org_id())) AND cmis.check_permission(cmis.get_current_user_id(), COALESCE(org_id, cmis.get_current_org_id()), 'admin.settings'::text)));

CREATE POLICY rbac_campaigns_delete ON cmis.campaigns FOR DELETE USING (((org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'campaigns.delete'::text)));


--
-- Name: campaigns rbac_campaigns_delete_v2; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_campaigns_delete_v2 ON cmis.campaigns FOR DELETE USING (cmis.check_permission_tx('campaigns.delete'::text));

CREATE POLICY rbac_campaigns_insert ON cmis.campaigns FOR INSERT WITH CHECK (((org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'campaigns.create'::text)));


--
-- Name: campaigns rbac_campaigns_insert_v2; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_campaigns_insert_v2 ON cmis.campaigns FOR INSERT WITH CHECK (cmis.check_permission_tx('campaigns.create'::text));

CREATE POLICY rbac_campaigns_select ON cmis.campaigns FOR SELECT USING ((((deleted_at IS NULL) OR (deleted_at > CURRENT_TIMESTAMP)) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'campaigns.view'::text)));


--
-- Name: campaigns rbac_campaigns_select_v2; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_campaigns_select_v2 ON cmis.campaigns FOR SELECT USING ((((deleted_at IS NULL) OR (deleted_at > CURRENT_TIMESTAMP)) AND cmis.check_permission_tx('campaigns.view'::text)));

CREATE POLICY rbac_campaigns_update ON cmis.campaigns FOR UPDATE USING ((((deleted_at IS NULL) OR (deleted_at > CURRENT_TIMESTAMP)) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'campaigns.edit'::text)));


--
-- Name: campaigns rbac_campaigns_update_v2; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_campaigns_update_v2 ON cmis.campaigns FOR UPDATE USING ((((deleted_at IS NULL) OR (deleted_at > CURRENT_TIMESTAMP)) AND cmis.check_permission_tx('campaigns.edit'::text)));

CREATE POLICY rbac_content_items ON cmis.content_items FOR SELECT USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'creatives.view'::text)));


--
-- Name: creative_assets rbac_creative_assets_insert; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_creative_assets_insert ON cmis.creative_assets FOR INSERT WITH CHECK (((org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'creatives.create'::text)));

CREATE POLICY rbac_creative_assets_select ON cmis.creative_assets FOR SELECT USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'creatives.view'::text)));


--
-- Name: creative_assets rbac_creative_assets_update; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_creative_assets_update ON cmis.creative_assets FOR UPDATE USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'creatives.edit'::text)));

CREATE POLICY rbac_integrations_manage ON cmis.integrations USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'integrations.manage'::text)));


--
-- Name: integrations rbac_integrations_select; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_integrations_select ON cmis.integrations FOR SELECT USING (((deleted_at IS NULL) AND (org_id = cmis.get_current_org_id()) AND cmis.check_permission(cmis.get_current_user_id(), org_id, 'integrations.view'::text)));

CREATE POLICY rbac_orgs_manage ON cmis.orgs FOR UPDATE USING (cmis.check_permission(cmis.get_current_user_id(), org_id, 'orgs.manage'::text));


--
-- Name: orgs rbac_orgs_select; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY rbac_orgs_select ON cmis.orgs FOR SELECT USING (cmis.check_permission(cmis.get_current_user_id(), org_id, 'orgs.view'::text));

CREATE POLICY scheduled_social_posts_org_isolation ON cmis.scheduled_social_posts USING ((org_id = (current_setting('app.current_org_id'::text, true))::uuid));


--
-- Name: user_orgs user_orgs_self; Type: POLICY; Schema: cmis; Owner: begin
--

CREATE POLICY user_orgs_self ON cmis.user_orgs FOR SELECT USING ((user_id = ( SELECT users.id
   FROM cmis.users
  WHERE ((users.email)::text = CURRENT_USER))));

CREATE POLICY org_isolation_example_sets ON lab.example_sets USING (((org_id IS NULL) OR (org_id = (current_setting('app.current_org_id'::text))::uuid)));


--
-- Name: FUNCTION get_next_available_slot(p_social_account_id uuid, p_after_time timestamp without time zone); Type: ACL; Schema: cmis; Owner: begin
--

GRANT ALL ON FUNCTION cmis.get_next_available_slot(p_social_account_id uuid, p_after_time timestamp without time zone) TO authenticated;

