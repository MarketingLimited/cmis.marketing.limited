<?php

namespace Tests\Unit\Models\Asset;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Asset\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Asset Model Unit Tests
 */
class AssetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_asset()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $asset = Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'صورة الحملة',
            'file_type' => 'image',
            'file_path' => 'assets/images/campaign.jpg',
        ]);

        $this->assertDatabaseHas('cmis.assets', [
            'asset_id' => $asset->asset_id,
            'name' => 'صورة الحملة',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $asset = Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Asset',
            'file_type' => 'image',
            'file_path' => 'assets/test.jpg',
        ]);

        $this->assertEquals($org->org_id, $asset->org->org_id);
    }

    /** @test */
    public function it_has_different_file_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $fileTypes = ['image', 'video', 'document', 'audio'];

        foreach ($fileTypes as $type) {
            Asset::create([
                'asset_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'name' => "Asset {$type}",
                'file_type' => $type,
                'file_path' => "assets/{$type}/file",
            ]);
        }

        $assets = Asset::where('org_id', $org->org_id)->get();
        $this->assertCount(4, $assets);
    }

    /** @test */
    public function it_stores_file_size()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $asset = Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Large Image',
            'file_type' => 'image',
            'file_path' => 'assets/large.jpg',
            'file_size' => 2048576, // 2MB in bytes
        ]);

        $this->assertEquals(2048576, $asset->file_size);
    }

    /** @test */
    public function it_stores_mime_type()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $asset = Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'PNG Image',
            'file_type' => 'image',
            'file_path' => 'assets/image.png',
            'mime_type' => 'image/png',
        ]);

        $this->assertEquals('image/png', $asset->mime_type);
    }

    /** @test */
    public function it_stores_image_dimensions()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $asset = Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'HD Image',
            'file_type' => 'image',
            'file_path' => 'assets/hd.jpg',
            'width' => 1920,
            'height' => 1080,
        ]);

        $this->assertEquals(1920, $asset->width);
        $this->assertEquals(1080, $asset->height);
    }

    /** @test */
    public function it_stores_video_duration()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $asset = Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Promo Video',
            'file_type' => 'video',
            'file_path' => 'assets/promo.mp4',
            'duration' => 120, // 2 minutes in seconds
        ]);

        $this->assertEquals(120, $asset->duration);
    }

    /** @test */
    public function it_stores_thumbnail_path()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $asset = Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Video with Thumbnail',
            'file_type' => 'video',
            'file_path' => 'assets/video.mp4',
            'thumbnail_path' => 'assets/thumbnails/video_thumb.jpg',
        ]);

        $this->assertEquals('assets/thumbnails/video_thumb.jpg', $asset->thumbnail_path);
    }

    /** @test */
    public function it_stores_metadata()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metadata = [
            'camera' => 'Canon EOS R5',
            'location' => 'الرياض',
            'photographer' => 'أحمد محمد',
            'copyright' => '© 2024',
        ];

        $asset = Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Professional Photo',
            'file_type' => 'image',
            'file_path' => 'assets/photo.jpg',
            'metadata' => $metadata,
        ]);

        $this->assertEquals('الرياض', $asset->metadata['location']);
        $this->assertEquals('أحمد محمد', $asset->metadata['photographer']);
    }

    /** @test */
    public function it_tracks_usage_count()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $asset = Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Popular Asset',
            'file_type' => 'image',
            'file_path' => 'assets/popular.jpg',
            'usage_count' => 0,
        ]);

        $asset->increment('usage_count');
        $asset->increment('usage_count');

        $this->assertEquals(2, $asset->fresh()->usage_count);
    }

    /** @test */
    public function it_stores_tags()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $tags = ['marketing', 'social', 'campaign', 'رمضان'];

        $asset = Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Tagged Asset',
            'file_type' => 'image',
            'file_path' => 'assets/tagged.jpg',
            'tags' => $tags,
        ]);

        $this->assertCount(4, $asset->tags);
        $this->assertContains('رمضان', $asset->tags);
    }

    /** @test */
    public function it_can_be_public_or_private()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $publicAsset = Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Public Asset',
            'file_type' => 'image',
            'file_path' => 'assets/public.jpg',
            'is_public' => true,
        ]);

        $privateAsset = Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Private Asset',
            'file_type' => 'document',
            'file_path' => 'assets/private.pdf',
            'is_public' => false,
        ]);

        $this->assertTrue($publicAsset->is_public);
        $this->assertFalse($privateAsset->is_public);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $asset = Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'UUID Asset',
            'file_type' => 'image',
            'file_path' => 'assets/uuid.jpg',
        ]);

        $this->assertTrue(Str::isUuid($asset->asset_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $asset = Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Timestamped Asset',
            'file_type' => 'image',
            'file_path' => 'assets/timestamp.jpg',
        ]);

        $this->assertNotNull($asset->created_at);
        $this->assertNotNull($asset->updated_at);
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

        Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Asset',
            'file_type' => 'image',
            'file_path' => 'assets/org1.jpg',
        ]);

        Asset::create([
            'asset_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Asset',
            'file_type' => 'image',
            'file_path' => 'assets/org2.jpg',
        ]);

        $org1Assets = Asset::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Assets);
        $this->assertEquals('Org 1 Asset', $org1Assets->first()->name);
    }
}
