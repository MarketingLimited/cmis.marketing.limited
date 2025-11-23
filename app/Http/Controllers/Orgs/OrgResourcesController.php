<?php

namespace App\Http\Controllers\Orgs;

use App\Http\Controllers\Controller;
use App\Models\Core\Org;
use Illuminate\View\View;

/**
 * Organization Resources Controller
 *
 * Handles organization resource views (campaigns, services, products)
 */
class OrgResourcesController extends Controller
{
    /**
     * Display organization campaigns
     */
    public function campaigns($id): View
    {
        $org = Org::findOrFail($id);

        $campaigns = $org->campaigns()
            ->select('campaign_id', 'name', 'objective', 'status', 'start_date', 'end_date', 'budget', 'currency')
            ->orderByDesc('created_at')
            ->get();

        return view('orgs.campaigns', [
            'id' => $id,
            'org' => $org,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Display organization services
     */
    public function services($id): View
    {
        $org = Org::findOrFail($id);

        $services = $org->offerings()
            ->select('offering_id', 'name', 'kind')
            ->where('kind', 'service')
            ->orderBy('name')
            ->get();

        return view('orgs.services', [
            'org' => $org,
            'services' => $services,
        ]);
    }

    /**
     * Display organization products
     */
    public function products($id): View
    {
        $org = Org::findOrFail($id);

        $products = $org->offerings()
            ->select('offering_id', 'name', 'kind')
            ->where('kind', 'product')
            ->orderBy('name')
            ->get();

        return view('orgs.products', [
            'org' => $org,
            'products' => $products,
        ]);
    }
}
