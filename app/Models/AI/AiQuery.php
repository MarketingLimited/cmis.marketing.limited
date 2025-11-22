<?php

namespace App\Models\AI;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\User;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiQuery extends BaseModel
{
    
    

    /**
     * Get the user that made the query.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');

    /**
     * Log an AI query.
     *
     * @param string $queryType
     * @param string $queryText
     * @param string|null $responseText
     * @param string $modelUsed
     * @param int|null $tokensUsed
     * @param int|null $executionTimeMs
     * @param string $status
     * @param string|null $errorMessage
     * @param array|null $metadata
     * @return static
     */
    public static function log(
        string $queryType,
        string $queryText,
        ?string $responseText,
        string $modelUsed,
        ?int $tokensUsed = null,
        ?int $executionTimeMs = null,
        string $status = 'success',
        ?string $errorMessage = null,
        ?array $metadata = null
    ): static {
        return static::create([
            'query_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => session('current_org_id'),
            'user_id' => auth()->id(),
            'query_type' => $queryType,
            'query_text' => $queryText,
            'response_text' => $responseText,
            'model_used' => $modelUsed,
            'tokens_used' => $tokensUsed,
            'execution_time_ms' => $executionTimeMs,
            'status' => $status,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
        ]);

    /**
     * Scope successful queries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');

    /**
     * Scope failed queries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');

    /**
     * Scope queries by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('query_type', $type);

    /**
     * Scope queries by model.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByModel($query, string $model)
    {
        return $query->where('model_used', $model);

    /**
     * Get total tokens used for an organization in a time period.
     *
     * @param string $orgId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return int
     */
    public static function totalTokensUsed(string $orgId, ?string $startDate = null, ?string $endDate = null): int
    {
        $query = static::where('org_id', $orgId)
            ->where('status', 'success');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);

        return $query->sum('tokens_used') ?? 0;
}
