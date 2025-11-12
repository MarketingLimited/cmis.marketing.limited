<?php

namespace App\Services;

use App\Models\Context\ContextBase;
use App\Models\Context\CreativeContext;
use App\Models\Context\ValueContext;
use App\Models\Context\OfferingContext;
use App\Models\Context\CampaignContextLink;
use App\Models\Context\FieldDefinition;
use App\Models\Context\FieldValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContextService
{
    /**
     * Create context base
     */
    public function createContext(array $data): ContextBase
    {
        return ContextBase::create([
            'context_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $data['org_id'] ?? session('current_org_id'),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'context_type' => $data['context_type'],
            'metadata' => $data['metadata'] ?? [],
            'tags' => $data['tags'] ?? [],
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $data['created_by'] ?? auth()->id(),
        ]);
    }

    /**
     * Create creative context
     */
    public function createCreativeContext(array $data): CreativeContext
    {
        return CreativeContext::create([
            'context_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $data['org_id'] ?? session('current_org_id'),
            'brand_voice' => $data['brand_voice'] ?? null,
            'tone' => $data['tone'] ?? null,
            'style_guidelines' => $data['style_guidelines'] ?? [],
            'visual_guidelines' => $data['visual_guidelines'] ?? [],
            'messaging_themes' => $data['messaging_themes'] ?? [],
            'do_not_use' => $data['do_not_use'] ?? [],
            'preferred_vocabulary' => $data['preferred_vocabulary'] ?? [],
            'color_palette' => $data['color_palette'] ?? [],
            'typography' => $data['typography'] ?? [],
            'imagery_style' => $data['imagery_style'] ?? null,
        ]);
    }

    /**
     * Create value context
     */
    public function createValueContext(array $data): ValueContext
    {
        return ValueContext::create([
            'context_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $data['org_id'] ?? session('current_org_id'),
            'offering_id' => $data['offering_id'] ?? null,
            'campaign_id' => $data['campaign_id'] ?? null,
            'framework' => $data['framework'] ?? null,
            'tone' => $data['tone'] ?? null,
            'value_proposition' => $data['value_proposition'] ?? null,
            'pain_points' => $data['pain_points'] ?? [],
            'benefits' => $data['benefits'] ?? [],
            'proof_points' => $data['proof_points'] ?? [],
            'unique_selling_points' => $data['unique_selling_points'] ?? [],
            'target_audience' => $data['target_audience'] ?? [],
        ]);
    }

    /**
     * Create offering context
     */
    public function createOfferingContext(array $data): OfferingContext
    {
        return OfferingContext::create([
            'context_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $data['org_id'] ?? session('current_org_id'),
            'offering_id' => $data['offering_id'],
            'product_details' => $data['product_details'] ?? [],
            'features' => $data['features'] ?? [],
            'benefits' => $data['benefits'] ?? [],
            'pricing_info' => $data['pricing_info'] ?? [],
            'availability' => $data['availability'] ?? null,
            'target_segments' => $data['target_segments'] ?? [],
            'competitive_positioning' => $data['competitive_positioning'] ?? [],
            'use_cases' => $data['use_cases'] ?? [],
        ]);
    }

    /**
     * Link context to campaign
     */
    public function linkContextToCampaign(
        string $campaignId,
        string $contextId,
        string $contextType,
        string $linkType = 'direct',
        float $linkStrength = 1.0
    ): CampaignContextLink {
        return CampaignContextLink::create([
            'link_id' => \Illuminate\Support\Str::uuid(),
            'campaign_id' => $campaignId,
            'context_id' => $contextId,
            'context_type' => $contextType,
            'link_type' => $linkType,
            'link_strength' => $linkStrength,
            'is_active' => true,
            'effective_from' => now(),
        ]);
    }

    /**
     * Get campaign contexts
     */
    public function getCampaignContexts(string $campaignId): array
    {
        $links = CampaignContextLink::where('campaign_id', $campaignId)
            ->active()
            ->get();

        $contexts = [];

        foreach ($links as $link) {
            $contextModel = $this->getContextModel($link->context_type);

            if ($contextModel) {
                $context = $contextModel::find($link->context_id);

                if ($context) {
                    $contexts[$link->context_type][] = [
                        'context' => $context,
                        'link' => $link,
                    ];
                }
            }
        }

        return $contexts;
    }

    /**
     * Enrich campaign with contexts
     */
    public function enrichCampaign(string $campaignId): array
    {
        $contexts = $this->getCampaignContexts($campaignId);

        return [
            'campaign_id' => $campaignId,
            'contexts' => $contexts,
            'enriched_at' => now()->toIso8601String(),
            'context_summary' => $this->summarizeContexts($contexts),
        ];
    }

    /**
     * Define custom field
     */
    public function defineField(array $data): FieldDefinition
    {
        return FieldDefinition::create([
            'field_id' => \Illuminate\Support\Str::uuid(),
            'module_id' => $data['module_id'],
            'field_name' => $data['field_name'],
            'field_type' => $data['field_type'],
            'is_required' => $data['is_required'] ?? false,
            'validation_rules' => $data['validation_rules'] ?? [],
            'options' => $data['options'] ?? [],
            'default_value' => $data['default_value'] ?? null,
            'help_text' => $data['help_text'] ?? null,
            'display_order' => $data['display_order'] ?? 0,
        ]);
    }

    /**
     * Set field value
     */
    public function setFieldValue(string $entityType, string $entityId, string $fieldId, $value): FieldValue
    {
        return FieldValue::updateOrCreate(
            [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'field_id' => $fieldId,
            ],
            [
                'value_id' => \Illuminate\Support\Str::uuid(),
                'field_value' => $value,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Get field values for entity
     */
    public function getFieldValues(string $entityType, string $entityId): array
    {
        $values = FieldValue::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->with('fieldDefinition')
            ->get();

        $result = [];

        foreach ($values as $value) {
            if ($value->fieldDefinition) {
                $result[$value->fieldDefinition->field_name] = [
                    'value' => $value->field_value,
                    'field_type' => $value->fieldDefinition->field_type,
                    'updated_at' => $value->updated_at,
                ];
            }
        }

        return $result;
    }

    /**
     * Get context model class
     */
    protected function getContextModel(string $contextType): ?string
    {
        $models = [
            'creative' => CreativeContext::class,
            'value' => ValueContext::class,
            'offering' => OfferingContext::class,
            'base' => ContextBase::class,
        ];

        return $models[$contextType] ?? null;
    }

    /**
     * Summarize contexts
     */
    protected function summarizeContexts(array $contexts): array
    {
        $summary = [
            'total_contexts' => 0,
            'by_type' => [],
            'has_creative' => false,
            'has_value' => false,
            'has_offering' => false,
        ];

        foreach ($contexts as $type => $contextList) {
            $summary['total_contexts'] += count($contextList);
            $summary['by_type'][$type] = count($contextList);
            $summary["has_{$type}"] = count($contextList) > 0;
        }

        return $summary;
    }

    /**
     * Merge contexts for AI processing
     */
    public function mergeContextsForAI(string $campaignId): array
    {
        $contexts = $this->getCampaignContexts($campaignId);

        $merged = [
            'campaign_id' => $campaignId,
            'brand_voice' => null,
            'tone' => null,
            'value_proposition' => null,
            'target_audience' => [],
            'features' => [],
            'benefits' => [],
            'style_guidelines' => [],
            'messaging_themes' => [],
        ];

        // Merge creative contexts
        if (isset($contexts['creative'])) {
            foreach ($contexts['creative'] as $item) {
                $context = $item['context'];
                $merged['brand_voice'] = $merged['brand_voice'] ?? $context->brand_voice;
                $merged['tone'] = $merged['tone'] ?? $context->tone;
                $merged['style_guidelines'] = array_merge($merged['style_guidelines'], $context->style_guidelines ?? []);
                $merged['messaging_themes'] = array_merge($merged['messaging_themes'], $context->messaging_themes ?? []);
            }
        }

        // Merge value contexts
        if (isset($contexts['value'])) {
            foreach ($contexts['value'] as $item) {
                $context = $item['context'];
                $merged['value_proposition'] = $merged['value_proposition'] ?? $context->value_proposition;
                $merged['target_audience'] = array_merge($merged['target_audience'], $context->target_audience ?? []);
                $merged['benefits'] = array_merge($merged['benefits'], $context->benefits ?? []);
            }
        }

        // Merge offering contexts
        if (isset($contexts['offering'])) {
            foreach ($contexts['offering'] as $item) {
                $context = $item['context'];
                $merged['features'] = array_merge($merged['features'], $context->features ?? []);
                $merged['benefits'] = array_merge($merged['benefits'], $context->benefits ?? []);
            }
        }

        return $merged;
    }
}
