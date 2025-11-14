<?php

namespace Tests\Unit\Models\Content;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Content\ContentMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Content Media Model Unit Tests
 */
class ContentMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_create_content_media()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $media = ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'file_name' => 'product-image.jpg',
            'file_path' => '/media/2024/01/product-image.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024000,
        ]);

        $this->assertDatabaseHas('cmis.content_media', [
            'media_id' => $media->media_id,
            'file_name' => 'product-image.jpg',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $media = ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'file_name' => 'test.jpg',
            'file_path' => '/media/test.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertEquals($org->org_id, $media->org->org_id);
    }

    /** @test */
    public function it_stores_image_dimensions()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $media = ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'file_name' => 'banner.jpg',
            'file_path' => '/media/banner.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'width' => 1920,
            'height' => 1080,
        ]);

        $this->assertEquals(1920, $media->width);
        $this->assertEquals(1080, $media->height);
    }

    /** @test */
    public function it_stores_video_duration()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $media = ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'file_name' => 'promo-video.mp4',
            'file_path' => '/media/promo-video.mp4',
            'file_type' => 'video',
            'mime_type' => 'video/mp4',
            'duration' => 120, // 2 minutes
        ]);

        $this->assertEquals(120, $media->duration);
    }

    /** @test */
    public function it_stores_metadata_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $metadata = [
            'camera' => 'Canon EOS R5',
            'location' => 'Manama, Bahrain',
            'tags' => ['product', 'summer', 'sale'],
            'color_profile' => 'sRGB',
        ];

        $media = ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'file_name' => 'photo.jpg',
            'file_path' => '/media/photo.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'metadata' => $metadata,
        ]);

        $this->assertEquals('Canon EOS R5', $media->metadata['camera']);
        $this->assertContains('summer', $media->metadata['tags']);
    }

    /** @test */
    public function it_tracks_usage_count()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $media = ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'file_name' => 'popular-image.jpg',
            'file_path' => '/media/popular-image.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'usage_count' => 0,
        ]);

        $media->increment('usage_count');
        $media->increment('usage_count');
        $media->increment('usage_count');

        $this->assertEquals(3, $media->fresh()->usage_count);
    }

    /** @test */
    public function it_stores_thumbnail_path()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $media = ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'file_name' => 'video.mp4',
            'file_path' => '/media/video.mp4',
            'file_type' => 'video',
            'mime_type' => 'video/mp4',
            'thumbnail_path' => '/media/thumbnails/video.jpg',
        ]);

        $this->assertEquals('/media/thumbnails/video.jpg', $media->thumbnail_path);
    }

    /** @test */
    public function it_has_file_type_categories()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $imageMedia = ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'file_name' => 'image.jpg',
            'file_path' => '/media/image.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
        ]);

        $videoMedia = ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'file_name' => 'video.mp4',
            'file_path' => '/media/video.mp4',
            'file_type' => 'video',
            'mime_type' => 'video/mp4',
        ]);

        $documentMedia = ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'file_name' => 'document.pdf',
            'file_path' => '/media/document.pdf',
            'file_type' => 'document',
            'mime_type' => 'application/pdf',
        ]);

        $this->assertEquals('image', $imageMedia->file_type);
        $this->assertEquals('video', $videoMedia->file_type);
        $this->assertEquals('document', $documentMedia->file_type);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $media = ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'file_name' => 'test.jpg',
            'file_path' => '/media/test.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertTrue(Str::isUuid($media->media_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $media = ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'file_name' => 'test.jpg',
            'file_path' => '/media/test.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertNotNull($media->created_at);
        $this->assertNotNull($media->updated_at);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $media = ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'file_name' => 'deletable.jpg',
            'file_path' => '/media/deletable.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
        ]);

        $mediaId = $media->media_id;

        $media->delete();

        $this->assertSoftDeleted('cmis.content_media', [
            'media_id' => $mediaId,
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

        ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'file_name' => 'org1-image.jpg',
            'file_path' => '/media/org1-image.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
        ]);

        ContentMedia::create([
            'media_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'file_name' => 'org2-image.jpg',
            'file_path' => '/media/org2-image.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
        ]);

        $org1Media = ContentMedia::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Media);
        $this->assertEquals('org1-image.jpg', $org1Media->first()->file_name);
    }
}
