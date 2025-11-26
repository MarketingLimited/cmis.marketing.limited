<?php

namespace Database\Seeders;

/**
 * Shared constants for seeders to ensure data consistency
 * across different seeder classes without database queries.
 *
 * This eliminates transaction isolation issues when seeders
 * need to reference data inserted by previous seeders.
 */
class SeederConstants
{
    // ==========================================
    // ROLE UUIDs (used by RolesSeeder & DemoDataSeeder)
    // ==========================================
    public const ROLE_OWNER = '90def48b-062e-4c13-a8d9-a0c6361d6057';
    public const ROLE_ADMIN = 'a1b2c3d4-e5f6-4a5b-8c7d-9e0f1a2b3c4d';
    public const ROLE_MARKETING_MANAGER = 'b2c3d4e5-f6a7-5b6c-9d8e-0f1a2b3c4d5e';
    public const ROLE_CONTENT_CREATOR = 'c3d4e5f6-a7b8-6c7d-0e9f-1a2b3c4d5e6f';
    public const ROLE_SOCIAL_MANAGER = 'd4e5f6a7-b8c9-7d8e-1f0a-2b3c4d5e6f7a';
    public const ROLE_ANALYST = 'e5f6a7b8-c9d0-8e9f-2a1b-3c4d5e6f7a8b';
    public const ROLE_VIEWER = 'f6a7b8c9-d0e1-9f0a-3b2c-4d5e6f7a8b9c';

    // ==========================================
    // ORGANIZATION UUIDs (used by OrgsSeeder & DemoDataSeeder)
    // ==========================================
    public const ORG_CMIS = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';
    public const ORG_TECHVISION = '9a5e0b1c-3d4e-4f5a-8b7c-1d2e3f4a5b6c';
    public const ORG_ARABIC_MARKETING = '8b6f1a2d-4e5f-5a6b-9c8d-2e3f4a5b6c7d';
    public const ORG_FASHIONHUB = '7c8e2b3f-5f6a-6b7c-0d9e-3f4a5b6c7d8e';
    public const ORG_HEALTHWELL = '6d9f3c4a-6a7b-7c8d-1e0f-4a5b6c7d8e9f';

    // ==========================================
    // USER UUIDs (used by UsersSeeder & DemoDataSeeder)
    // ==========================================
    public const USER_ADMIN = '1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d';
    public const USER_SARAH = '2b3c4d5e-6f7a-8b9c-0d1e-2f3a4b5c6d7e';
    public const USER_MARIA = '3c4d5e6f-7a8b-9c0d-1e2f-3a4b5c6d7e8f';
    public const USER_MOHAMED = '4d5e6f7a-8b9c-0d1e-2f3a-4b5c6d7e8f9a';
    public const USER_AHMED = '5e6f7a8b-9c0d-1e2f-3a4b-5c6d7e8f9a0b';
    public const USER_EMMA = '6f7a8b9c-0d1e-2f3a-4b5c-6d7e8f9a0b1c';
    public const USER_DAVID = '7a8b9c0d-1e2f-3a4b-5c6d-7e8f9a0b1c2d';

    /**
     * Get all role IDs indexed by role_code
     */
    public static function getRoleIds(): array
    {
        return [
            'owner' => self::ROLE_OWNER,
            'admin' => self::ROLE_ADMIN,
            'marketing_manager' => self::ROLE_MARKETING_MANAGER,
            'content_creator' => self::ROLE_CONTENT_CREATOR,
            'social_manager' => self::ROLE_SOCIAL_MANAGER,
            'analyst' => self::ROLE_ANALYST,
            'viewer' => self::ROLE_VIEWER,
        ];
    }

    /**
     * Get all org IDs indexed by org name
     */
    public static function getOrgIds(): array
    {
        return [
            'CMIS' => self::ORG_CMIS,
            'TechVision Solutions' => self::ORG_TECHVISION,
            'الشركة العربية للتسويق' => self::ORG_ARABIC_MARKETING,
            'FashionHub Retail' => self::ORG_FASHIONHUB,
            'HealthWell Clinic' => self::ORG_HEALTHWELL,
        ];
    }

    /**
     * Get all user IDs indexed by email
     */
    public static function getUserIds(): array
    {
        return [
            'admin@cmis.test' => self::USER_ADMIN,
            'sarah@techvision.com' => self::USER_SARAH,
            'maria@techvision.com' => self::USER_MARIA,
            'mohamed@arabic-marketing.com' => self::USER_MOHAMED,
            'ahmed@arabic-marketing.com' => self::USER_AHMED,
            'emma@fashionhub.com' => self::USER_EMMA,
            'david@healthwell.com' => self::USER_DAVID,
        ];
    }
}
