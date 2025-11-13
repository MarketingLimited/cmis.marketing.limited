<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreativeBriefController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display briefs list
     */
    public function index()
    {
        try {
            $briefs = DB::select("
                SELECT b.*, c.campaign_name
                FROM cmis.creative_briefs b
                LEFT JOIN cmis.campaigns c ON c.campaign_id = b.campaign_id
                WHERE b.org_id = ?
                ORDER BY b.created_at DESC
                LIMIT 50
            ", [Auth::user()->current_org_id]);

            return view('briefs.index', compact('briefs'));
        } catch (\Exception $e) {
            Log::error('Briefs index error: ' . $e->getMessage());
            return view('briefs.index', ['briefs' => []]);
        }
    }

    /**
     * Show create form
     */
    public function create()
    {
        $campaigns = DB::select("
            SELECT campaign_id, campaign_name
            FROM cmis.campaigns
            WHERE org_id = ? AND deleted_at IS NULL
            ORDER BY created_at DESC
        ", [Auth::user()->current_org_id]);

        return view('briefs.create', compact('campaigns'));
    }

    /**
     * Store new brief
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|uuid',
            'brief_name' => 'required|string|max:255',
            'objective' => 'required|string',
            'target_audience' => 'required|array',
            'key_message' => 'required|string',
            'tone' => 'required|string|max:100',
            'deliverables' => 'required|array',
            'constraints' => 'nullable|array',
            'references' => 'nullable|array',
        ]);

        try {
            // Validate brief structure using DB function
            $isValid = DB::select("
                SELECT cmis.validate_brief_structure(?) as is_valid
            ", [json_encode($validated)])[0]->is_valid ?? false;

            if (!$isValid) {
                return back()->with('error', 'هيكل البريف غير صحيح')->withInput();
            }

            $briefId = DB::table('cmis.creative_briefs')->insertGetId([
                'org_id' => Auth::user()->current_org_id,
                'campaign_id' => $validated['campaign_id'],
                'brief_name' => $validated['brief_name'],
                'objective' => $validated['objective'],
                'target_audience' => json_encode($validated['target_audience']),
                'key_message' => $validated['key_message'],
                'tone' => $validated['tone'],
                'deliverables' => json_encode($validated['deliverables']),
                'constraints' => json_encode($validated['constraints'] ?? []),
                'references' => json_encode($validated['references'] ?? []),
                'status' => 'draft',
                'created_at' => now(),
            ], 'brief_id');

            // Generate summary
            try {
                DB::select("SELECT cmis.generate_brief_summary(?)", [$briefId]);
            } catch (\Exception $e) {
                Log::warning('Failed to generate brief summary: ' . $e->getMessage());
            }

            return redirect()->route('briefs.show', $briefId)->with('success', 'تم إنشاء البريف بنجاح');
        } catch (\Exception $e) {
            Log::error('Brief store error: ' . $e->getMessage());
            return back()->with('error', 'فشل إنشاء البريف')->withInput();
        }
    }

    /**
     * Show brief details
     */
    public function show($briefId)
    {
        try {
            $brief = DB::selectOne("
                SELECT b.*, c.campaign_name,
                       u.name as creator_name
                FROM cmis.creative_briefs b
                LEFT JOIN cmis.campaigns c ON c.campaign_id = b.campaign_id
                LEFT JOIN cmis.users u ON u.user_id = b.created_by
                WHERE b.brief_id = ? AND b.org_id = ?
            ", [$briefId, Auth::user()->current_org_id]);

            if (!$brief) {
                return redirect()->route('briefs.index')->with('error', 'البريف غير موجود');
            }

            // Decode JSON fields
            $brief->target_audience = json_decode($brief->target_audience ?? '[]', true);
            $brief->deliverables = json_decode($brief->deliverables ?? '[]', true);
            $brief->constraints = json_decode($brief->constraints ?? '[]', true);
            $brief->references = json_decode($brief->references ?? '[]', true);

            return view('briefs.show', compact('brief'));
        } catch (\Exception $e) {
            Log::error('Brief show error: ' . $e->getMessage());
            return redirect()->route('briefs.index')->with('error', 'فشل تحميل البريف');
        }
    }

    /**
     * Approve brief
     */
    public function approve($briefId)
    {
        try {
            DB::table('cmis.creative_briefs')
                ->where('brief_id', $briefId)
                ->where('org_id', Auth::user()->current_org_id)
                ->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                    'updated_at' => now(),
                ]);

            return redirect()->route('briefs.show', $briefId)->with('success', 'تمت الموافقة على البريف');
        } catch (\Exception $e) {
            Log::error('Brief approve error: ' . $e->getMessage());
            return back()->with('error', 'فشلت الموافقة على البريف');
        }
    }
}
