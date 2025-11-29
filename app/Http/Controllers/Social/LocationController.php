<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class LocationController extends Controller
{
    use ApiResponse;

    /**
     * Search for locations using Google Places API (or mock data)
     */
    public function search(Request $request, $orgId)
    {
        $query = $request->get('query');

        if (!$query || strlen($query) < 3) {
            return $this->success([], 'Query too short');
        }

        try {
            // Cache results for 1 hour
            $cacheKey = 'location_search_' . md5($query);

            $results = Cache::remember($cacheKey, 3600, function () use ($query) {
                // Check if Google Places API key is configured
                $apiKey = config('services.google.places_api_key');

                if ($apiKey) {
                    return $this->searchGooglePlaces($query, $apiKey);
                } else {
                    // Return mock data for testing
                    return $this->getMockLocations($query);
                }
            });

            return $this->success($results, 'Locations retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to search locations: ' . $e->getMessage());
        }
    }

    /**
     * Search using Google Places Autocomplete API
     */
    private function searchGooglePlaces($query, $apiKey)
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/place/autocomplete/json', [
            'input' => $query,
            'key' => $apiKey,
            'types' => 'establishment|geocode',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Google Places API request failed');
        }

        $data = $response->json();

        if (!isset($data['predictions'])) {
            return [];
        }

        return collect($data['predictions'])->map(function ($prediction) {
            return [
                'place_id' => $prediction['place_id'],
                'name' => $prediction['structured_formatting']['main_text'] ?? $prediction['description'],
                'address' => $prediction['description'],
                'types' => $prediction['types'] ?? [],
            ];
        })->toArray();
    }

    /**
     * Get mock locations for testing (when Google Places API is not configured)
     */
    private function getMockLocations($query)
    {
        $mockLocations = [
            [
                'place_id' => 'mock_1',
                'name' => 'Riyadh, Saudi Arabia',
                'address' => 'Riyadh, Saudi Arabia',
                'types' => ['locality', 'political'],
            ],
            [
                'place_id' => 'mock_2',
                'name' => 'Dubai Mall',
                'address' => 'Financial Center Road, Dubai, United Arab Emirates',
                'types' => ['shopping_mall', 'establishment'],
            ],
            [
                'place_id' => 'mock_3',
                'name' => 'Burj Khalifa',
                'address' => '1 Sheikh Mohammed bin Rashid Blvd, Dubai, United Arab Emirates',
                'types' => ['point_of_interest', 'establishment'],
            ],
            [
                'place_id' => 'mock_4',
                'name' => 'Cairo, Egypt',
                'address' => 'Cairo, Egypt',
                'types' => ['locality', 'political'],
            ],
            [
                'place_id' => 'mock_5',
                'name' => 'London, UK',
                'address' => 'London, United Kingdom',
                'types' => ['locality', 'political'],
            ],
            [
                'place_id' => 'mock_6',
                'name' => 'New York, NY',
                'address' => 'New York, NY, USA',
                'types' => ['locality', 'political'],
            ],
        ];

        // Filter based on query
        $query = strtolower($query);
        return collect($mockLocations)
            ->filter(function ($location) use ($query) {
                return str_contains(strtolower($location['name']), $query) ||
                       str_contains(strtolower($location['address']), $query);
            })
            ->values()
            ->toArray();
    }
}
