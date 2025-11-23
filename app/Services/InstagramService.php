<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InstagramService
{
    public function fetchMedia($integration, array $options = []): array
    {
        $debug = $options['debug'] ?? false;
        $debugFull = $options['debug_full'] ?? false;

        $from   = $options['from'] ? Carbon::parse($options['from'])->utc() : null;
        $to     = $options['to'] ? Carbon::parse($options['to'])->utc() : null;
        $metric = $options['metric'] ?? null;
        $sort   = strtolower($options['sort'] ?? 'desc');

        // إذا تم تحديد فترة زمنية، نرفع limit إلى 100
        $limit  = $options['limit'] ?? (($from || $to) ? 100 : 25);

        $endpoint = "https://graph.facebook.com/v22.0/{$integration->account_id}/media";
        $params = [
            'fields' => 'id,caption,media_type,media_url,permalink,timestamp,like_count,comments_count',
            'access_token' => $integration->access_token,
            'limit' => $limit,
        ];

        // دعم since/until إذا تم تمريرها
        if ($from) $params['since'] = $from->timestamp;
        if ($to) $params['until'] = $to->timestamp;

        $posts = collect();
        $pageCount = 0;

        do {
            $pageCount++;
            $response = Http::get($endpoint, $params);

            if (!$response->successful()) {
                if ($debug) Log::warning("[IG DEBUG] Failed to fetch page {$pageCount}: " . json_encode($response->json()));
                break;
            }

            $data = collect($response->json('data', []));

            if ($debug) {
                $typesCount = $data->groupBy('media_type')->map->count();
                $dates = $data->pluck('timestamp')->map(fn($t) => Carbon::parse($t)->utc()->toDateTimeString());
                $earliest = $dates->min();
                $latest = $dates->max();
                Log::info("[IG DEBUG] Page {$pageCount}: total={$data->count()} types=" . json_encode($typesCount) . " | range={$earliest} → {$latest}");
                if ($debugFull) {
                    Log::info("[IG DEBUG-FULL] Response for page {$pageCount}: " . json_encode($response->json()));
                }
            }

            foreach ($data as $post) {
                $time = Carbon::parse($post['timestamp'])->utc();

                if ($from && $time->lt($from)) {
                    if ($debug) Log::info("[IG DEBUG] Stopping fetch at post {$post['id']} ({$time}) — reached before --from date.");
                    break 2;
                }

                if ((!$from || $time->gte($from)) && (!$to || $time->lte($to))) {
                    $posts->push($post);
                }
            }

            $paging = $response->json('paging.next') ?? null;
            $endpoint = $paging;
            $params = [];

        } while ($endpoint && $pageCount < 20 && $posts->count() < $limit * 3);

        if ($debug) Log::info("[IG DEBUG] Finished pagination. Collected {$posts->count()} posts.");

        $posts = $posts->take($limit);

        $posts = $posts->map(function ($post) use ($integration, $debug, $debugFull) {
            $mediaId = $post['id'];

            $metaResponse = Http::get("https://graph.facebook.com/v22.0/{$mediaId}", [
                'fields' => 'media_type,media_product_type',
                'access_token' => $integration->access_token,
            ]);

            if ($metaResponse->successful()) {
                $meta = $metaResponse->json();
                $post['media_type'] = $meta['media_type'] ?? $post['media_type'];
                $post['media_product_type'] = $meta['media_product_type'] ?? null;
            }

            if ($post['media_type'] === 'CAROUSEL_ALBUM') {
                $childrenResponse = Http::get("https://graph.facebook.com/v22.0/{$mediaId}/children", [
                    'fields' => 'id,media_type,media_url',
                    'access_token' => $integration->access_token,
                ]);
                $post['children'] = $childrenResponse->json('data', []);
            }

            $metrics = match ($post['media_type']) {
                'REEL' => 'reach,plays,likes,comments,shares,saved,ig_reels_avg_watch_time,ig_reels_video_view_total_time',
                default => 'reach,likes,comments,saved,shares,total_interactions',
            };

            try {
                $insightsResponse = Http::get("https://graph.facebook.com/v22.0/{$mediaId}/insights", [
                    'metric' => $metrics,
                    'access_token' => $integration->access_token,
                ]);

                if ($insightsResponse->successful()) {
                    $insights = collect($insightsResponse->json('data', []))
                        ->mapWithKeys(fn($i) => [$i['name'] => $i['values'][0]['value'] ?? 0]);
                    $post = array_merge($post, $insights->toArray());
                }

                if ($debug && $debugFull) {
                    Log::info("[IG DEBUG-FULL] Insights for {$mediaId}: " . json_encode($insightsResponse->json()));
                }
            } catch (\Exception $e) {
                if ($debug) Log::error("[IG DEBUG] Exception fetching insights for {$mediaId}: {$e->getMessage()}");
            }

            return $post;
        });

        if ($debug) Log::info("[IG DEBUG] Final post count: {$posts->count()}");

        if ($metric && isset($posts->first()[$metric])) {
            $posts = $posts->sortBy($metric, SORT_NATURAL, $sort === 'desc');
        }

        return $posts->values()->take($limit)->toArray();
    }
}
