<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChannelFormatsSeeder extends Seeder
{
    /**
     * Seed channel formats with aspect ratios and specifications.
     */
    public function run(): void
    {
        $formats = [
            // Facebook formats
            ['channel_code' => 'facebook', 'code' => 'feed_image', 'ratio' => '1:1', 'length_hint' => null],
            ['channel_code' => 'facebook', 'code' => 'feed_video', 'ratio' => '16:9', 'length_hint' => '15-30s'],
            ['channel_code' => 'facebook', 'code' => 'story', 'ratio' => '9:16', 'length_hint' => '15s'],
            ['channel_code' => 'facebook', 'code' => 'carousel', 'ratio' => '1:1', 'length_hint' => null],

            // Instagram formats
            ['channel_code' => 'instagram', 'code' => 'feed_square', 'ratio' => '1:1', 'length_hint' => null],
            ['channel_code' => 'instagram', 'code' => 'feed_portrait', 'ratio' => '4:5', 'length_hint' => null],
            ['channel_code' => 'instagram', 'code' => 'story', 'ratio' => '9:16', 'length_hint' => '15s'],
            ['channel_code' => 'instagram', 'code' => 'reel', 'ratio' => '9:16', 'length_hint' => '15-90s'],
            ['channel_code' => 'instagram', 'code' => 'carousel', 'ratio' => '1:1', 'length_hint' => null],

            // Twitter/X formats
            ['channel_code' => 'twitter', 'code' => 'image', 'ratio' => '16:9', 'length_hint' => null],
            ['channel_code' => 'twitter', 'code' => 'video', 'ratio' => '16:9', 'length_hint' => '15-45s'],

            // LinkedIn formats
            ['channel_code' => 'linkedin', 'code' => 'image', 'ratio' => '1.91:1', 'length_hint' => null],
            ['channel_code' => 'linkedin', 'code' => 'video', 'ratio' => '16:9', 'length_hint' => '30-90s'],
            ['channel_code' => 'linkedin', 'code' => 'document', 'ratio' => null, 'length_hint' => null],

            // TikTok formats
            ['channel_code' => 'tiktok', 'code' => 'video', 'ratio' => '9:16', 'length_hint' => '15-60s'],

            // YouTube formats
            ['channel_code' => 'youtube', 'code' => 'video', 'ratio' => '16:9', 'length_hint' => '5-15min'],
            ['channel_code' => 'youtube', 'code' => 'shorts', 'ratio' => '9:16', 'length_hint' => '15-60s'],

            // Snapchat formats
            ['channel_code' => 'snapchat', 'code' => 'snap', 'ratio' => '9:16', 'length_hint' => '10s'],

            // Pinterest formats
            ['channel_code' => 'pinterest', 'code' => 'pin', 'ratio' => '2:3', 'length_hint' => null],
            ['channel_code' => 'pinterest', 'code' => 'video_pin', 'ratio' => '9:16', 'length_hint' => '6-15s'],

            // Google Ads formats
            ['channel_code' => 'google_ads', 'code' => 'search_text', 'ratio' => null, 'length_hint' => null],
            ['channel_code' => 'google_ads', 'code' => 'display_banner', 'ratio' => '16:9', 'length_hint' => null],
            ['channel_code' => 'google_ads', 'code' => 'display_square', 'ratio' => '1:1', 'length_hint' => null],
            ['channel_code' => 'google_ads', 'code' => 'video', 'ratio' => '16:9', 'length_hint' => '15-30s'],

            // Meta Ads formats
            ['channel_code' => 'meta_ads', 'code' => 'feed', 'ratio' => '1:1', 'length_hint' => null],
            ['channel_code' => 'meta_ads', 'code' => 'story', 'ratio' => '9:16', 'length_hint' => '15s'],
            ['channel_code' => 'meta_ads', 'code' => 'video', 'ratio' => '16:9', 'length_hint' => '15-30s'],
            ['channel_code' => 'meta_ads', 'code' => 'carousel', 'ratio' => '1:1', 'length_hint' => null],
        ];

        // First, truncate the table to ensure clean state
        DB::statement('TRUNCATE TABLE public.channel_formats RESTART IDENTITY CASCADE');

        // Get channel IDs
        $channels = DB::table('public.channels')->get()->keyBy('code');

        foreach ($formats as $format) {
            $channel = $channels[$format['channel_code']] ?? null;
            if (!$channel) continue;

            // Use raw SQL with nextval to get the next sequence value for format_id
            DB::statement(
                "INSERT INTO public.channel_formats (format_id, channel_id, code, ratio, length_hint)
                 VALUES (nextval('channel_formats_format_id_seq'), ?, ?, ?, ?)",
                [
                    $channel->channel_id,
                    $format['code'],
                    $format['ratio'],
                    $format['length_hint']
                ]
            );
        }

        $this->command->info('Channel formats seeded successfully!');
    }
}