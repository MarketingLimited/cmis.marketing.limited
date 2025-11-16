<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add Audit System Permissions
 *
 * This migration adds the necessary permissions for the audit system
 * to the permissions table and creates default role-permission mappings.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Define audit permissions
        $permissions = [
            [
                'name' => 'audit.view_dashboard',
                'description' => 'View audit dashboard and overview',
                'category' => 'audit'
            ],
            [
                'name' => 'audit.view_realtime',
                'description' => 'View realtime audit status',
                'category' => 'audit'
            ],
            [
                'name' => 'audit.view_reports',
                'description' => 'View audit reports (daily, weekly, summary)',
                'category' => 'audit'
            ],
            [
                'name' => 'audit.view_activity_log',
                'description' => 'View activity log entries',
                'category' => 'audit'
            ],
            [
                'name' => 'audit.log_event',
                'description' => 'Log events to audit system',
                'category' => 'audit'
            ],
            [
                'name' => 'audit.view_alerts',
                'description' => 'View audit alerts and warnings',
                'category' => 'audit'
            ],
            [
                'name' => 'audit.export_reports',
                'description' => 'Export audit reports to CSV',
                'category' => 'audit'
            ],
            [
                'name' => 'audit.view_all',
                'description' => 'View all organization audit data',
                'category' => 'audit'
            ],
            [
                'name' => 'audit.view_security_logs',
                'description' => 'View security-related audit logs',
                'category' => 'audit'
            ],
            [
                'name' => 'audit.manage_settings',
                'description' => 'Manage audit system settings',
                'category' => 'audit'
            ],
        ];

        // Insert permissions
        foreach ($permissions as $permission) {
            DB::table('cmis.permissions')->insertOrIgnore([
                'permission_id' => DB::raw('gen_random_uuid()'),
                'permission_code' => $permission['name'],
                'permission_name' => str_replace('.', ' ', ucwords(str_replace('_', ' ', $permission['name']))),
                'description' => $permission['description'],
                'category' => $permission['category'],
            ]);
        }

        // Create a function to help assign default permissions to roles
        DB::unprepared("
            CREATE OR REPLACE FUNCTION cmis.assign_audit_permissions_to_role(
                p_role_name text,
                p_permissions text[]
            ) RETURNS void AS $$
            DECLARE
                v_role_id uuid;
                v_permission_id uuid;
                v_permission text;
            BEGIN
                -- Get role ID
                SELECT role_id INTO v_role_id
                FROM cmis.roles
                WHERE role_code = p_role_name
                LIMIT 1;

                IF v_role_id IS NULL THEN
                    RAISE NOTICE 'Role % not found', p_role_name;
                    RETURN;
                END IF;

                -- Assign each permission
                FOREACH v_permission IN ARRAY p_permissions
                LOOP
                    SELECT permission_id INTO v_permission_id
                    FROM cmis.permissions
                    WHERE permission_code = v_permission
                    LIMIT 1;

                    IF v_permission_id IS NOT NULL THEN
                        INSERT INTO cmis.role_permissions (role_id, permission_id)
                        VALUES (v_role_id, v_permission_id)
                        ON CONFLICT DO NOTHING;
                    END IF;
                END LOOP;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Assign permissions to default roles

        // Admin & Owner: Full access
        DB::unprepared("
            SELECT cmis.assign_audit_permissions_to_role(
                'admin',
                ARRAY[
                    'audit.view_dashboard',
                    'audit.view_realtime',
                    'audit.view_reports',
                    'audit.view_activity_log',
                    'audit.log_event',
                    'audit.view_alerts',
                    'audit.export_reports',
                    'audit.view_all',
                    'audit.view_security_logs',
                    'audit.manage_settings'
                ]
            );
        ");

        DB::unprepared("
            SELECT cmis.assign_audit_permissions_to_role(
                'owner',
                ARRAY[
                    'audit.view_dashboard',
                    'audit.view_realtime',
                    'audit.view_reports',
                    'audit.view_activity_log',
                    'audit.log_event',
                    'audit.view_alerts',
                    'audit.export_reports',
                    'audit.view_all',
                    'audit.view_security_logs',
                    'audit.manage_settings'
                ]
            );
        ");

        // Manager: Most access except security logs and settings
        DB::unprepared("
            SELECT cmis.assign_audit_permissions_to_role(
                'manager',
                ARRAY[
                    'audit.view_dashboard',
                    'audit.view_realtime',
                    'audit.view_reports',
                    'audit.view_activity_log',
                    'audit.log_event',
                    'audit.view_alerts',
                    'audit.export_reports'
                ]
            );
        ");

        // Editor: Basic viewing and logging
        DB::unprepared("
            SELECT cmis.assign_audit_permissions_to_role(
                'editor',
                ARRAY[
                    'audit.view_dashboard',
                    'audit.view_realtime',
                    'audit.log_event'
                ]
            );
        ");

        // Viewer: Read-only access
        DB::unprepared("
            SELECT cmis.assign_audit_permissions_to_role(
                'viewer',
                ARRAY[
                    'audit.view_dashboard',
                    'audit.view_realtime'
                ]
            );
        ");
    }

    public function down(): void
    {
        // Remove function
        DB::unprepared('DROP FUNCTION IF EXISTS cmis.assign_audit_permissions_to_role(text, text[])');

        // Remove permissions
        DB::table('cmis.permissions')
            ->where('category', 'audit')
            ->delete();
    }
};
