<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Creative\BrandVoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BrandVoiceSettingsController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of brand voices.
     */
    public function index(Request $request, string $org)
    {
        $brandVoices = BrandVoice::where('org_id', $org)
            ->with(['profileGroup', 'creator'])
            ->withCount('profileGroups')
            ->orderBy('name')
            ->get();

        if ($request->wantsJson()) {
            return $this->success($brandVoices, 'Brand voices retrieved successfully');
        }

        return view('settings.brand-voices.index', [
            'brandVoices' => $brandVoices,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for creating a new brand voice.
     */
    public function create(Request $request, string $org)
    {
        return view('settings.brand-voices.create', [
            'currentOrg' => $org,
            'tones' => $this->getTones(),
            'emojiOptions' => $this->getEmojiOptions(),
            'hashtagStrategies' => $this->getHashtagStrategies(),
        ]);
    }

    /**
     * Store a newly created brand voice.
     */
    public function store(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'tone' => 'required|string|max:50',
            'personality_traits' => 'nullable|array',
            'inspired_by' => 'nullable|array',
            'target_audience' => 'nullable|string|max:500',
            'keywords_to_use' => 'nullable|array',
            'keywords_to_avoid' => 'nullable|array',
            'emojis_preference' => 'required|string|in:none,minimal,moderate,frequent',
            'hashtag_strategy' => 'required|string|in:none,minimal,moderate,extensive',
            'example_posts' => 'nullable|array',
            'primary_language' => 'required|string|max:10',
            'secondary_languages' => 'nullable|array',
            'dialect_preference' => 'nullable|string|max:50',
            'ai_system_prompt' => 'nullable|string|max:5000',
            'temperature' => 'nullable|numeric|min:0|max:2',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $brandVoice = BrandVoice::create([
                'org_id' => $org,
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'tone' => $request->input('tone'),
                'personality_traits' => $request->input('personality_traits', []),
                'inspired_by' => $request->input('inspired_by', []),
                'target_audience' => $request->input('target_audience'),
                'keywords_to_use' => $request->input('keywords_to_use', []),
                'keywords_to_avoid' => $request->input('keywords_to_avoid', []),
                'emojis_preference' => $request->input('emojis_preference'),
                'hashtag_strategy' => $request->input('hashtag_strategy'),
                'example_posts' => $request->input('example_posts', []),
                'primary_language' => $request->input('primary_language'),
                'secondary_languages' => $request->input('secondary_languages', []),
                'dialect_preference' => $request->input('dialect_preference'),
                'ai_system_prompt' => $request->input('ai_system_prompt'),
                'temperature' => $request->input('temperature', 0.7),
                'created_by' => Auth::id(),
            ]);

            if ($request->wantsJson()) {
                return $this->created($brandVoice, 'Brand voice created successfully');
            }

            return redirect()->route('orgs.settings.brand-voices.show', ['org' => $org, 'voice' => $brandVoice->voice_id])
                ->with('success', __('settings.created_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to create brand voice: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to create brand voice'])->withInput();
        }
    }

    /**
     * Display the specified brand voice.
     */
    public function show(Request $request, string $org, string $voice)
    {
        $brandVoice = BrandVoice::where('org_id', $org)
            ->where('voice_id', $voice)
            ->with(['profileGroup', 'creator', 'profileGroups'])
            ->firstOrFail();

        if ($request->wantsJson()) {
            return $this->success($brandVoice, 'Brand voice retrieved successfully');
        }

        return view('settings.brand-voices.show', [
            'brandVoice' => $brandVoice,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for editing the specified brand voice.
     */
    public function edit(Request $request, string $org, string $voice)
    {
        $brandVoice = BrandVoice::where('org_id', $org)
            ->where('voice_id', $voice)
            ->firstOrFail();

        return view('settings.brand-voices.edit', [
            'brandVoice' => $brandVoice,
            'currentOrg' => $org,
            'tones' => $this->getTones(),
            'emojiOptions' => $this->getEmojiOptions(),
            'hashtagStrategies' => $this->getHashtagStrategies(),
        ]);
    }

    /**
     * Update the specified brand voice.
     */
    public function update(Request $request, string $org, string $voice)
    {
        $brandVoice = BrandVoice::where('org_id', $org)
            ->where('voice_id', $voice)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'tone' => 'required|string|max:50',
            'personality_traits' => 'nullable|array',
            'inspired_by' => 'nullable|array',
            'target_audience' => 'nullable|string|max:500',
            'keywords_to_use' => 'nullable|array',
            'keywords_to_avoid' => 'nullable|array',
            'emojis_preference' => 'required|string|in:none,minimal,moderate,frequent',
            'hashtag_strategy' => 'required|string|in:none,minimal,moderate,extensive',
            'example_posts' => 'nullable|array',
            'primary_language' => 'required|string|max:10',
            'secondary_languages' => 'nullable|array',
            'dialect_preference' => 'nullable|string|max:50',
            'ai_system_prompt' => 'nullable|string|max:5000',
            'temperature' => 'nullable|numeric|min:0|max:2',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $brandVoice->update($request->only([
                'name',
                'description',
                'tone',
                'personality_traits',
                'inspired_by',
                'target_audience',
                'keywords_to_use',
                'keywords_to_avoid',
                'emojis_preference',
                'hashtag_strategy',
                'example_posts',
                'primary_language',
                'secondary_languages',
                'dialect_preference',
                'ai_system_prompt',
                'temperature',
            ]));

            if ($request->wantsJson()) {
                return $this->success($brandVoice, 'Brand voice updated successfully');
            }

            return redirect()->route('orgs.settings.brand-voices.show', ['org' => $org, 'voice' => $voice])
                ->with('success', __('settings.updated_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to update brand voice: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to update brand voice'])->withInput();
        }
    }

    /**
     * Remove the specified brand voice.
     */
    public function destroy(Request $request, string $org, string $voice)
    {
        $brandVoice = BrandVoice::where('org_id', $org)
            ->where('voice_id', $voice)
            ->firstOrFail();

        try {
            $brandVoice->delete();

            if ($request->wantsJson()) {
                return $this->deleted('Brand voice deleted successfully');
            }

            return redirect()->route('orgs.settings.brand-voices.index', ['org' => $org])
                ->with('success', __('settings.deleted_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to delete brand voice: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to delete brand voice']);
        }
    }

    /**
     * Get available tones.
     */
    private function getTones(): array
    {
        return [
            'professional' => 'Professional',
            'friendly' => 'Friendly',
            'casual' => 'Casual',
            'formal' => 'Formal',
            'humorous' => 'Humorous',
            'inspirational' => 'Inspirational',
            'authoritative' => 'Authoritative',
            'empathetic' => 'Empathetic',
            'playful' => 'Playful',
            'educational' => 'Educational',
        ];
    }

    /**
     * Get emoji preference options.
     */
    private function getEmojiOptions(): array
    {
        return [
            'none' => 'No emojis',
            'minimal' => 'Minimal (1-2 per post)',
            'moderate' => 'Moderate (3-5 per post)',
            'frequent' => 'Frequent (6+ per post)',
        ];
    }

    /**
     * Get hashtag strategy options.
     */
    private function getHashtagStrategies(): array
    {
        return [
            'none' => 'No hashtags',
            'minimal' => 'Minimal (1-3 hashtags)',
            'moderate' => 'Moderate (4-7 hashtags)',
            'extensive' => 'Extensive (8+ hashtags)',
        ];
    }
}
