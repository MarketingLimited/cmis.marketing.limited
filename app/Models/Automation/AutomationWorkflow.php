<?php

namespace App\Models\Automation;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationWorkflow extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.automation_workflows';
    protected $primaryKey = 'workflow_id';
    protected $fillable = [
        'org_id', 'created_by', 'name', 'description', 'category',
        'is_template', 'rules', 'config', 'status', 'usage_count'
    ];

    protected $casts = [
        'is_template' => 'boolean',
        'rules' => 'array',
        'config' => 'array',
        'usage_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // ===== Relationships =====

    

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');

    // ===== Workflow Management =====

    public function activate(): void
    {
        $this->update(['status' => 'active']);

    public function archive(): void
    {
        $this->update(['status' => 'archived']);

    public function incrementUsage(): void
    {
        $this->increment('usage_count');

    public function isGlobalTemplate(): bool
    {
        return $this->is_template && $this->org_id === null;

    public function isOrgTemplate(): bool
    {
        return $this->is_template && $this->org_id !== null;

    // ===== Rule Helpers =====

    public function addRule(array $rule): void
    {
        $rules = $this->rules ?? [];
        $rules[] = $rule;
        $this->update(['rules' => $rules]);

    public function removeRule(int $index): void
    {
        $rules = $this->rules ?? [];
        if (isset($rules[$index])) {
            unset($rules[$index]);
            $this->update(['rules' => array_values($rules)]);

    public function getRuleCount(): int
    {
        return count($this->rules ?? []);

    // ===== Scopes =====

    public function scopeActive($query)
    {
        return $query->where('status', 'active');

    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);

    public function scopeGlobalTemplates($query)
    {
        return $query->where('is_template', true)->whereNull('org_id');

    public function scopeOrgTemplates($query, string $orgId)
    {
        return $query->where('is_template', true)->where('org_id', $orgId);

    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
}
