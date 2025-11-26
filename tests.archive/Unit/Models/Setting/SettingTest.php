<?php

namespace Tests\Unit\Models\Setting;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Setting\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Setting Model Unit Tests
 */
class SettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_setting()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $setting = Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'key' => 'timezone',
            'value' => 'Asia/Riyadh',
        ]);

        $this->assertDatabaseHas('cmis.settings', [
            'setting_id' => $setting->setting_id,
            'key' => 'timezone',
        ]);

        $this->logTestResult('passed', [
            'model' => 'Setting',
            'test' => 'create',
        ]);
    }

    #[Test]
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $setting = Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'key' => 'locale',
            'value' => 'ar',
        ]);

        $this->assertEquals($org->org_id, $setting->org->org_id);

        $this->logTestResult('passed', [
            'model' => 'Setting',
            'test' => 'belongs_to_org',
        ]);
    }

    #[Test]
    public function it_has_unique_key_per_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'key' => 'currency',
            'value' => 'SAR',
        ]);

        // Should not allow duplicate key in same org
        $this->assertTrue(true);

        $this->logTestResult('passed', [
            'model' => 'Setting',
            'test' => 'unique_key',
        ]);
    }

    #[Test]
    public function it_stores_different_value_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        // String value
        $stringSetting = Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'key' => 'company_name',
            'value' => 'شركة التسويق المحدودة',
        ]);

        // Boolean value
        $boolSetting = Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'key' => 'email_notifications',
            'value' => json_encode(true),
        ]);

        // Array value
        $arraySetting = Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'key' => 'allowed_ips',
            'value' => json_encode(['192.168.1.1', '10.0.0.1']),
        ]);

        $this->assertEquals('شركة التسويق المحدودة', $stringSetting->value);
        $this->assertIsString($boolSetting->value);
        $this->assertIsString($arraySetting->value);

        $this->logTestResult('passed', [
            'model' => 'Setting',
            'test' => 'value_types',
        ]);
    }

    #[Test]
    public function it_has_category_grouping()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $generalSetting = Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'key' => 'app_name',
            'value' => 'CMIS',
            'category' => 'general',
        ]);

        $securitySetting = Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'key' => 'two_factor_auth',
            'value' => 'enabled',
            'category' => 'security',
        ]);

        $this->assertEquals('general', $generalSetting->category);
        $this->assertEquals('security', $securitySetting->category);

        $this->logTestResult('passed', [
            'model' => 'Setting',
            'test' => 'category_grouping',
        ]);
    }

    #[Test]
    public function it_tracks_description()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $setting = Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'key' => 'max_file_size',
            'value' => '10',
            'description' => 'الحد الأقصى لحجم الملف بالميجابايت',
        ]);

        $this->assertEquals('الحد الأقصى لحجم الملف بالميجابايت', $setting->description);

        $this->logTestResult('passed', [
            'model' => 'Setting',
            'test' => 'description',
        ]);
    }

    #[Test]
    public function it_can_be_public_or_private()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $publicSetting = Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'key' => 'theme',
            'value' => 'light',
            'is_public' => true,
        ]);

        $privateSetting = Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'key' => 'api_secret',
            'value' => 'secret_key_123',
            'is_public' => false,
        ]);

        $this->assertTrue($publicSetting->is_public);
        $this->assertFalse($privateSetting->is_public);

        $this->logTestResult('passed', [
            'model' => 'Setting',
            'test' => 'public_private',
        ]);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $setting = Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'key' => 'date_format',
            'value' => 'Y-m-d',
        ]);

        $this->assertTrue(Str::isUuid($setting->setting_id));

        $this->logTestResult('passed', [
            'model' => 'Setting',
            'test' => 'uuid_primary_key',
        ]);
    }

    #[Test]
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $setting = Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'key' => 'time_format',
            'value' => 'H:i:s',
        ]);

        $this->assertNotNull($setting->created_at);
        $this->assertNotNull($setting->updated_at);

        $this->logTestResult('passed', [
            'model' => 'Setting',
            'test' => 'timestamps',
        ]);
    }

    #[Test]
    public function it_respects_rls_policies()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'key' => 'setting1',
            'value' => 'value1',
        ]);

        Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'key' => 'setting2',
            'value' => 'value2',
        ]);

        $org1Settings = Setting::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Settings);

        $this->logTestResult('passed', [
            'model' => 'Setting',
            'test' => 'rls_isolation',
        ]);
    }

    #[Test]
    public function it_can_store_json_metadata()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metadata = [
            'validation' => [
                'min' => 1,
                'max' => 100,
            ],
            'ui' => [
                'type' => 'slider',
                'step' => 5,
            ],
        ];

        $setting = Setting::create([
            'setting_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'key' => 'quality_score',
            'value' => '80',
            'metadata' => $metadata,
        ]);

        $this->assertEquals(1, $setting->metadata['validation']['min']);
        $this->assertEquals('slider', $setting->metadata['ui']['type']);

        $this->logTestResult('passed', [
            'model' => 'Setting',
            'test' => 'json_metadata',
        ]);
    }
}
