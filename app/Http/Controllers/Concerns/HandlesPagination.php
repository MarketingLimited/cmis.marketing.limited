<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

trait HandlesPagination
{
    /**
     * Default pagination limit.
     */
    protected int $defaultPerPage = 20;

    /**
     * Maximum pagination limit.
     */
    protected int $maxPerPage = 100;

    /**
     * Minimum pagination limit.
     */
    protected int $minPerPage = 1;

    /**
     * Paginate a query with standardized parameters.
     *
     * @param  Builder|mixed  $query  The query builder
     * @param  Request|null  $request  The request (if null, uses current request)
     * @return LengthAwarePaginator
     */
    protected function paginateQuery($query, ?Request $request = null): LengthAwarePaginator
    {
        $request = $request ?? request();

        $perPage = $this->getPerPageFromRequest($request);
        $page = $this->getPageFromRequest($request);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get per_page value from request with validation.
     */
    protected function getPerPageFromRequest(Request $request): int
    {
        $perPage = $request->input('per_page', $this->defaultPerPage);

        // Ensure it's an integer
        $perPage = (int) $perPage;

        // Clamp between min and max
        return max(
            $this->minPerPage,
            min($perPage, $this->maxPerPage)
        );
    }

    /**
     * Get page number from request with validation.
     */
    protected function getPageFromRequest(Request $request): int
    {
        $page = $request->input('page', 1);

        // Ensure it's a positive integer
        return max(1, (int) $page);
    }

    /**
     * Format paginated response with standardized structure.
     *
     * Returns: {
     *   success: true,
     *   data: [...],
     *   meta: {
     *     current_page: 1,
     *     per_page: 20,
     *     total: 100,
     *     last_page: 5,
     *     from: 1,
     *     to: 20
     *   },
     *   links: {
     *     first: "...",
     *     last: "...",
     *     prev: null,
     *     next: "..."
     *   }
     * }
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = 'Data retrieved successfully')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Set custom per_page limits for specific endpoints.
     *
     * Usage in controller:
     * $this->setPerPageLimits(default: 50, max: 200);
     */
    protected function setPerPageLimits(int $default = null, int $max = null, int $min = null): void
    {
        if ($default !== null) {
            $this->defaultPerPage = $default;
        }

        if ($max !== null) {
            $this->maxPerPage = $max;
        }

        if ($min !== null) {
            $this->minPerPage = $min;
        }
    }

    /**
     * Get pagination info for response headers.
     *
     * Useful for adding pagination metadata to response headers.
     */
    protected function getPaginationHeaders(LengthAwarePaginator $paginator): array
    {
        return [
            'X-Pagination-Total' => $paginator->total(),
            'X-Pagination-Count' => $paginator->count(),
            'X-Pagination-Per-Page' => $paginator->perPage(),
            'X-Pagination-Current-Page' => $paginator->currentPage(),
            'X-Pagination-Last-Page' => $paginator->lastPage(),
        ];
    }

    /**
     * Check if pagination is requested.
     *
     * Some endpoints might want to support both paginated and non-paginated responses.
     */
    protected function shouldPaginate(Request $request): bool
    {
        // If explicitly requesting no pagination
        if ($request->has('no_pagination') && $request->boolean('no_pagination')) {
            return false;
        }

        // If requesting all results
        if ($request->has('per_page') && $request->input('per_page') === 'all') {
            return false;
        }

        return true;
    }
}
