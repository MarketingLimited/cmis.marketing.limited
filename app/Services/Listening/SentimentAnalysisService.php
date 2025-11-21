<?php

namespace App\Services\Listening;

use App\Models\Listening\SocialMention;
use App\Models\Listening\SentimentAnalysis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SentimentAnalysisService
{
    protected string $apiKey;
    protected string $apiEndpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.google.gemini_api_key', env('GOOGLE_GEMINI_API_KEY'));
    }

    /**
     * Analyze sentiment for a mention
     */
    public function analyzeMention(SocialMention $mention): SentimentAnalysis
    {
        // Check cache first
        $cacheKey = "sentiment_analysis_{$mention->mention_id}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $analysisData = $this->performAnalysis($mention->content);

            $analysis = SentimentAnalysis::create([
                'org_id' => $mention->org_id,
                'mention_id' => $mention->mention_id,
                'overall_sentiment' => $analysisData['overall_sentiment'],
                'sentiment_score' => $analysisData['sentiment_score'],
                'confidence' => $analysisData['confidence'],
                'positive_score' => $analysisData['positive_score'],
                'negative_score' => $analysisData['negative_score'],
                'neutral_score' => $analysisData['neutral_score'],
                'mixed_score' => $analysisData['mixed_score'],
                'emotions' => $analysisData['emotions'],
                'primary_emotion' => $analysisData['primary_emotion'],
                'key_phrases' => $analysisData['key_phrases'],
                'entities' => $analysisData['entities'],
                'topics' => $analysisData['topics'],
                'model_used' => 'gemini-pro',
                'model_response' => $analysisData['raw_response'] ?? [],
            ]);

            // Update mention with sentiment
            $mention->updateSentiment(
                $analysis->overall_sentiment,
                $analysis->sentiment_score,
                $analysis->confidence
            );

            // Add detected topics and entities to mention
            foreach ($analysis->topics as $topic) {
                $mention->addTopic($topic);
            }

            foreach ($analysis->entities as $entity) {
                if (isset($entity['type']) && isset($entity['text'])) {
                    $mention->addEntity($entity['type'], $entity['text']);
                }
            }

            Cache::put($cacheKey, $analysis, 3600); // Cache for 1 hour

            Log::info('Sentiment analysis completed', [
                'mention_id' => $mention->mention_id,
                'sentiment' => $analysis->overall_sentiment,
                'score' => $analysis->sentiment_score,
            ]);

            return $analysis;

        } catch (\Exception $e) {
            Log::error('Sentiment analysis failed', [
                'mention_id' => $mention->mention_id,
                'error' => $e->getMessage(),
            ]);

            // Create basic analysis as fallback
            return $this->createFallbackAnalysis($mention);
        }
    }

    /**
     * Perform AI-powered sentiment analysis
     */
    protected function performAnalysis(string $text): array
    {
        $prompt = $this->buildAnalysisPrompt($text);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiEndpoint . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'topK' => 1,
                    'topP' => 1,
                    'maxOutputTokens' => 1024,
                ],
            ]);

            if (!$response->successful()) {
                throw new \Exception('API request failed: ' . $response->status());
            }

            $result = $response->json();
            return $this->parseAnalysisResponse($result, $text);

        } catch (\Exception $e) {
            Log::error('AI analysis API call failed', ['error' => $e->getMessage()]);
            return $this->performBasicAnalysis($text);
        }
    }

    /**
     * Build analysis prompt for AI
     */
    protected function buildAnalysisPrompt(string $text): string
    {
        return <<<PROMPT
Analyze the sentiment and extract information from the following social media text. Return a JSON response with the following structure:

{
  "overall_sentiment": "positive|negative|neutral|mixed",
  "sentiment_score": -1.0 to 1.0,
  "confidence": 0-100,
  "positive_score": 0.0-1.0,
  "negative_score": 0.0-1.0,
  "neutral_score": 0.0-1.0,
  "mixed_score": 0.0-1.0,
  "emotions": {"joy": 0.8, "sadness": 0.1, ...},
  "primary_emotion": "joy|sadness|anger|fear|surprise",
  "key_phrases": ["phrase1", "phrase2", ...],
  "entities": [{"type": "PERSON|ORGANIZATION|LOCATION|PRODUCT", "text": "entity name"}, ...],
  "topics": ["topic1", "topic2", ...]
}

Text to analyze:
{$text}

Provide ONLY the JSON response, no additional text.
PROMPT;
    }

    /**
     * Parse AI response
     */
    protected function parseAnalysisResponse(array $response, string $originalText): array
    {
        try {
            $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Remove markdown code blocks if present
            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            $content = trim($content);

            $data = json_decode($content, true);

            if (!$data) {
                throw new \Exception('Failed to parse JSON response');
            }

            return [
                'overall_sentiment' => $data['overall_sentiment'] ?? 'neutral',
                'sentiment_score' => $data['sentiment_score'] ?? 0.0,
                'confidence' => $data['confidence'] ?? 50,
                'positive_score' => $data['positive_score'] ?? 0.0,
                'negative_score' => $data['negative_score'] ?? 0.0,
                'neutral_score' => $data['neutral_score'] ?? 1.0,
                'mixed_score' => $data['mixed_score'] ?? 0.0,
                'emotions' => $data['emotions'] ?? [],
                'primary_emotion' => $data['primary_emotion'] ?? null,
                'key_phrases' => $data['key_phrases'] ?? [],
                'entities' => $data['entities'] ?? [],
                'topics' => $data['topics'] ?? [],
                'raw_response' => $response,
            ];

        } catch (\Exception $e) {
            Log::warning('Failed to parse AI response, using basic analysis', [
                'error' => $e->getMessage()
            ]);
            return $this->performBasicAnalysis($originalText);
        }
    }

    /**
     * Perform basic rule-based sentiment analysis
     */
    protected function performBasicAnalysis(string $text): array
    {
        $text = strtolower($text);

        $positiveWords = ['great', 'excellent', 'amazing', 'love', 'wonderful', 'fantastic', 'awesome', 'best', 'good', 'happy', 'perfect', 'brilliant'];
        $negativeWords = ['bad', 'terrible', 'awful', 'hate', 'horrible', 'worst', 'poor', 'disappointing', 'sad', 'angry', 'frustrated'];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($text, $word);
        }

        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($text, $word);
        }

        $totalSentimentWords = $positiveCount + $negativeCount;

        if ($totalSentimentWords == 0) {
            $sentiment = 'neutral';
            $score = 0.0;
            $positiveScore = 0.33;
            $negativeScore = 0.33;
            $neutralScore = 0.34;
        } else {
            $positiveScore = $positiveCount / max($totalSentimentWords, 1);
            $negativeScore = $negativeCount / max($totalSentimentWords, 1);
            $neutralScore = 1 - ($positiveScore + $negativeScore);

            if ($positiveCount > $negativeCount) {
                $sentiment = 'positive';
                $score = $positiveScore;
            } elseif ($negativeCount > $positiveCount) {
                $sentiment = 'negative';
                $score = -$negativeScore;
            } else {
                $sentiment = 'neutral';
                $score = 0.0;
            }
        }

        return [
            'overall_sentiment' => $sentiment,
            'sentiment_score' => round($score, 4),
            'confidence' => 50, // Lower confidence for basic analysis
            'positive_score' => round($positiveScore, 4),
            'negative_score' => round($negativeScore, 4),
            'neutral_score' => round($neutralScore, 4),
            'mixed_score' => 0.0,
            'emotions' => [],
            'primary_emotion' => null,
            'key_phrases' => [],
            'entities' => [],
            'topics' => [],
            'raw_response' => ['method' => 'basic_analysis'],
        ];
    }

    /**
     * Create fallback analysis when AI fails
     */
    protected function createFallbackAnalysis(SocialMention $mention): SentimentAnalysis
    {
        $basicAnalysis = $this->performBasicAnalysis($mention->content);

        return SentimentAnalysis::create([
            'org_id' => $mention->org_id,
            'mention_id' => $mention->mention_id,
            'overall_sentiment' => $basicAnalysis['overall_sentiment'],
            'sentiment_score' => $basicAnalysis['sentiment_score'],
            'confidence' => $basicAnalysis['confidence'],
            'positive_score' => $basicAnalysis['positive_score'],
            'negative_score' => $basicAnalysis['negative_score'],
            'neutral_score' => $basicAnalysis['neutral_score'],
            'mixed_score' => $basicAnalysis['mixed_score'],
            'model_used' => 'fallback-basic',
        ]);
    }

    /**
     * Batch analyze mentions
     */
    public function batchAnalyze(array $mentions): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
        ];

        foreach ($mentions as $mention) {
            try {
                $this->analyzeMention($mention);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('Batch analysis failed for mention', [
                    'mention_id' => $mention->mention_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}
