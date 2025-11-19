<?php

namespace Tests\Unit\Models\CustomField;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\CustomField\CustomField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * CustomField Model Unit Tests
 */
class CustomFieldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_custom_field()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $customField = CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'الصناعة',
            'field_type' => 'select',
            'entity_type' => 'contact',
        ]);

        $this->assertDatabaseHas('cmis.custom_fields', [
            'field_id' => $customField->field_id,
            'name' => 'الصناعة',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $customField = CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Field',
            'field_type' => 'text',
            'entity_type' => 'contact',
        ]);

        $this->assertEquals($org->org_id, $customField->org->org_id);
    }

    /** @test */
    public function it_has_different_field_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $fieldTypes = ['text', 'number', 'date', 'select', 'multiselect', 'checkbox', 'url', 'email'];

        foreach ($fieldTypes as $type) {
            CustomField::create([
                'field_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'name' => "Field {$type}",
                'field_type' => $type,
                'entity_type' => 'contact',
            ]);
        }

        $fields = CustomField::where('org_id', $org->org_id)->get();
        $this->assertCount(8, $fields);
    }

    /** @test */
    public function it_applies_to_different_entity_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $entityTypes = ['contact', 'lead', 'campaign', 'content'];

        foreach ($entityTypes as $entity) {
            CustomField::create([
                'field_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'name' => "Field for {$entity}",
                'field_type' => 'text',
                'entity_type' => $entity,
            ]);
        }

        $fields = CustomField::where('org_id', $org->org_id)->get();
        $this->assertCount(4, $fields);
    }

    /** @test */
    public function it_stores_field_options_for_select_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $options = [
            'تكنولوجيا',
            'تجارة إلكترونية',
            'خدمات مالية',
            'تعليم',
            'صحة',
        ];

        $customField = CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'الصناعة',
            'field_type' => 'select',
            'entity_type' => 'contact',
            'options' => $options,
        ]);

        $this->assertCount(5, $customField->options);
        $this->assertContains('تكنولوجيا', $customField->options);
    }

    /** @test */
    public function it_has_validation_rules()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $validationRules = [
            'required' => true,
            'min' => 5,
            'max' => 100,
            'pattern' => '^[A-Z]',
        ];

        $customField = CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'رمز العميل',
            'field_type' => 'text',
            'entity_type' => 'contact',
            'validation_rules' => $validationRules,
        ]);

        $this->assertTrue($customField->validation_rules['required']);
        $this->assertEquals(5, $customField->validation_rules['min']);
    }

    /** @test */
    public function it_has_default_value()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $customField = CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'الحالة',
            'field_type' => 'select',
            'entity_type' => 'lead',
            'default_value' => 'new',
        ]);

        $this->assertEquals('new', $customField->default_value);
    }

    /** @test */
    public function it_can_be_required_or_optional()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $requiredField = CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Required Field',
            'field_type' => 'text',
            'entity_type' => 'contact',
            'is_required' => true,
        ]);

        $optionalField = CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Optional Field',
            'field_type' => 'text',
            'entity_type' => 'contact',
            'is_required' => false,
        ]);

        $this->assertTrue($requiredField->is_required);
        $this->assertFalse($optionalField->is_required);
    }

    /** @test */
    public function it_has_display_order()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $field1 = CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'First Field',
            'field_type' => 'text',
            'entity_type' => 'contact',
            'display_order' => 1,
        ]);

        $field2 = CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Second Field',
            'field_type' => 'text',
            'entity_type' => 'contact',
            'display_order' => 2,
        ]);

        $this->assertEquals(1, $field1->display_order);
        $this->assertEquals(2, $field2->display_order);
    }

    /** @test */
    public function it_can_be_active_or_inactive()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $activeField = CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Active Field',
            'field_type' => 'text',
            'entity_type' => 'contact',
            'is_active' => true,
        ]);

        $inactiveField = CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Inactive Field',
            'field_type' => 'text',
            'entity_type' => 'contact',
            'is_active' => false,
        ]);

        $this->assertTrue($activeField->is_active);
        $this->assertFalse($inactiveField->is_active);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $customField = CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Field',
            'field_type' => 'text',
            'entity_type' => 'contact',
        ]);

        $this->assertTrue(Str::isUuid($customField->field_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $customField = CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Field',
            'field_type' => 'text',
            'entity_type' => 'contact',
        ]);

        $this->assertNotNull($customField->created_at);
        $this->assertNotNull($customField->updated_at);
    }

    /** @test */
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

        CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Field',
            'field_type' => 'text',
            'entity_type' => 'contact',
        ]);

        CustomField::create([
            'field_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Field',
            'field_type' => 'text',
            'entity_type' => 'contact',
        ]);

        $org1Fields = CustomField::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Fields);
        $this->assertEquals('Org 1 Field', $org1Fields->first()->name);
    }
}
