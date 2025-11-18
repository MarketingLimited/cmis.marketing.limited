<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChannelsSeeder extends Seeder
{
    /**
     * Seed marketing channels with their constraints and specifications.
     */
    public function run(): void
    {
        $channels = [
            [
                'code' => 'facebook',
                'name' => 'Facebook',
                'constraints' => json_encode([
                    'max_text_length' => 63206,
                    'max_link_caption' => 30,
                    'max_link_description' => 90,
                    'supported_formats' => ['image', 'video', 'carousel', 'collection'],
                    'video_max_size_mb' => 4096,
                    'image_max_size_mb' => 30,
                ])
            ],
            [
                'code' => 'instagram',
                'name' => 'Instagram',
                'constraints' => json_encode([
                    'max_caption_length' => 2200,
                    'max_hashtags' => 30,
                    'supported_formats' => ['feed', 'story', 'reel', 'carousel'],
                    'video_max_duration_seconds' => 90,
                    'story_duration_seconds' => 15,
                    'reel_max_duration_seconds' => 90,
                ])
            ],
            [
                'code' => 'twitter',
                'name' => 'Twitter/X',
                'constraints' => json_encode([
                    'max_text_length' => 280,
                    'max_video_duration_seconds' => 140,
                    'image_max_size_mb' => 5,
                    'video_max_size_mb' => 512,
                    'supported_formats' => ['text', 'image', 'video', 'poll'],
                ])
            ],
            [
                'code' => 'linkedin',
                'name' => 'LinkedIn',
                'constraints' => json_encode([
                    'max_text_length' => 3000,
                    'max_video_duration_seconds' => 600,
                    'supported_formats' => ['text', 'image', 'video', 'document', 'article'],
                    'video_max_size_mb' => 5120,
                ])
            ],
            [
                'code' => 'tiktok',
                'name' => 'TikTok',
                'constraints' => json_encode([
                    'max_caption_length' => 2200,
                    'max_video_duration_seconds' => 600,
                    'min_video_duration_seconds' => 3,
                    'supported_formats' => ['video'],
                    'video_max_size_mb' => 287,
                ])
            ],
            [
                'code' => 'youtube',
                'name' => 'YouTube',
                'constraints' => json_encode([
                    'max_title_length' => 100,
                    'max_description_length' => 5000,
                    'max_video_duration_seconds' => 43200, // 12 hours
                    'video_max_size_mb' => 256000,
                    'supported_formats' => ['video', 'shorts'],
                ])
            ],
            [
                'code' => 'snapchat',
                'name' => 'Snapchat',
                'constraints' => json_encode([
                    'video_duration_seconds' => 10,
                    'supported_formats' => ['image', 'video'],
                ])
            ],
            [
                'code' => 'pinterest',
                'name' => 'Pinterest',
                'constraints' => json_encode([
                    'max_description_length' => 500,
                    'max_title_length' => 100,
                    'supported_formats' => ['image', 'video', 'carousel'],
                    'video_max_duration_seconds' => 60,
                ])
            ],
            [
                'code' => 'google_ads',
                'name' => 'Google Ads',
                'constraints' => json_encode([
                    'headline_max_length' => 30,
                    'max_headlines' => 15,
                    'description_max_length' => 90,
                    'max_descriptions' => 4,
                    'supported_formats' => ['search', 'display', 'video', 'shopping'],
                ])
            ],
            [
                'code' => 'meta_ads',
                'name' => 'Meta Ads',
                'constraints' => json_encode([
                    'primary_text_max_length' => 125,
                    'headline_max_length' => 40,
                    'description_max_length' => 30,
                    'supported_formats' => ['image', 'video', 'carousel', 'collection'],
                ])
            ],
        ];

        // First, truncate the table to ensure clean state (use public schema explicitly)
        DB::statement('TRUNCATE TABLE public.channels RESTART IDENTITY CASCADE');

        foreach ($channels as $channel) {
            // Use raw SQL with nextval to get the next sequence value
            DB::statement(
                "INSERT INTO public.channels (channel_id, code, name, constraints) VALUES (nextval('channels_channel_id_seq'), ?, ?, ?::jsonb)",
                [$channel['code'], $channel['name'], $channel['constraints']]
            );
        }

        $this->command->info('Channels seeded successfully!');
    }
}