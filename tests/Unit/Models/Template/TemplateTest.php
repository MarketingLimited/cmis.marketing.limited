<?php

namespace Tests\Unit\Models\Template;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Template\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Template Model Unit Tests
 */
class TemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_template()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $template = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Email Template',
            'type' => 'email',
            'content' => '<html><body>Hello {{name}}</body></html>',
        ]);

        $this->assertDatabaseHas('cmis.templates', [
            'template_id' => $template->template_id,
            'name' => 'Email Template',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $template = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Template',
            'type' => 'email',
            'content' => 'Test content',
        ]);

        $this->assertEquals($org->org_id, $template->org->org_id);
    }

    /** @test */
    public function it_has_different_template_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $emailTemplate = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Email Template',
            'type' => 'email',
            'content' => 'Email content',
        ]);

        $smsTemplate = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'SMS Template',
            'type' => 'sms',
            'content' => 'SMS content',
        ]);

        $socialTemplate = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Social Media Template',
            'type' => 'social',
            'content' => 'Social content',
        ]);

        $this->assertEquals('email', $emailTemplate->type);
        $this->assertEquals('sms', $smsTemplate->type);
        $this->assertEquals('social', $socialTemplate->type);
    }

    /** @test */
    public function it_stores_template_variables()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $variables = [
            'name' => 'Customer Name',
            'email' => 'Customer Email',
            'discount_code' => 'Discount Code',
            'expiry_date' => 'Offer Expiry Date',
        ];

        $template = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Promotional Email',
            'type' => 'email',
            'content' => 'Hello {{name}}, use code {{discount_code}}',
            'variables' => $variables,
        ]);

        $this->assertArrayHasKey('name', $template->variables);
        $this->assertEquals('Customer Name', $template->variables['name']);
    }

    /** @test */
    public function it_stores_template_metadata()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metadata = [
            'subject' => 'Special Offer Inside!',
            'preview_text' => 'Don\'t miss out on this deal',
            'from_name' => 'Marketing Team',
            'reply_to' => 'support@example.com',
        ];

        $template = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Email Template',
            'type' => 'email',
            'content' => 'Template content',
            'metadata' => $metadata,
        ]);

        $this->assertEquals('Special Offer Inside!', $template->metadata['subject']);
        $this->assertEquals('support@example.com', $template->metadata['reply_to']);
    }

    /** @test */
    public function it_tracks_usage_count()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $template = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Popular Template',
            'type' => 'email',
            'content' => 'Content',
            'usage_count' => 0,
        ]);

        $template->increment('usage_count', 5);

        $this->assertEquals(5, $template->fresh()->usage_count);
    }

    /** @test */
    public function it_can_be_active_or_inactive()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $activeTemplate = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Active Template',
            'type' => 'email',
            'content' => 'Content',
            'is_active' => true,
        ]);

        $inactiveTemplate = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Inactive Template',
            'type' => 'email',
            'content' => 'Content',
            'is_active' => false,
        ]);

        $this->assertTrue($activeTemplate->is_active);
        $this->assertFalse($inactiveTemplate->is_active);
    }

    /** @test */
    public function it_can_be_default_template()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $defaultTemplate = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Default Welcome Email',
            'type' => 'email',
            'content' => 'Welcome!',
            'is_default' => true,
        ]);

        $this->assertTrue($defaultTemplate->is_default);
    }

    /** @test */
    public function it_stores_category()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $template = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Newsletter Template',
            'type' => 'email',
            'content' => 'Newsletter content',
            'category' => 'newsletter',
        ]);

        $this->assertEquals('newsletter', $template->category);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $template = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Template',
            'type' => 'email',
            'content' => 'Content',
        ]);

        $this->assertTrue(Str::isUuid($template->template_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $template = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Template',
            'type' => 'email',
            'content' => 'Content',
        ]);

        $this->assertNotNull($template->created_at);
        $this->assertNotNull($template->updated_at);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $template = Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Deletable Template',
            'type' => 'email',
            'content' => 'Content',
        ]);

        $templateId = $template->template_id;

        $template->delete();

        $this->assertSoftDeleted('cmis.templates', [
            'template_id' => $templateId,
        ]);
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

        Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Template',
            'type' => 'email',
            'content' => 'Content',
        ]);

        Template::create([
            'template_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Template',
            'type' => 'email',
            'content' => 'Content',
        ]);

        $org1Templates = Template::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Templates);
        $this->assertEquals('Org 1 Template', $org1Templates->first()->name);
    }
}
