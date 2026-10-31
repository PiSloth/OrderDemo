<?php

namespace App\Http\Controllers\DigitalAsset;

use App\Http\Controllers\Controller;
use App\Models\DigitalAsset\SoftwarePlatform;
use App\Models\DigitalAsset\SoftwarePropertyDefinition;
use App\Services\DigitalAsset\SoftwareAccountAuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SoftwarePropertyDefinitionController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', SoftwarePropertyDefinition::class);

        $definitions = SoftwarePropertyDefinition::with('platform')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        $platforms = SoftwarePlatform::where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']);

        $supportedDataTypes = [
            'boolean',
            'text',
            'integer',
            'decimal',
            'date',
            'datetime',
            'email_reference',
            'single_choice',
            'multi_choice',
            'secret_reference',
        ];

        return Inertia::render('DigitalAsset/PropertyDefinitions/Index', [
            'definitions' => $definitions,
            'platforms' => $platforms,
            'supportedDataTypes' => $supportedDataTypes,
            'canCreate' => auth()->user()?->can('create', SoftwarePropertyDefinition::class) ?? false,
            'canUpdate' => auth()->user()?->can('update', new SoftwarePropertyDefinition()) ?? false,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', SoftwarePropertyDefinition::class);

        $validated = $request->validate([
            'software_platform_id' => 'nullable|exists:software_platforms,id',
            'code' => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
            'label' => 'required|string|max:150',
            'data_type' => 'required|string|in:boolean,text,integer,decimal,date,datetime,email_reference,single_choice,multi_choice,secret_reference',
            'description' => 'nullable|string',
            'is_required' => 'boolean',
            'is_sensitive' => 'boolean',
            'evidence_required' => 'boolean',
            'evidence_types' => 'nullable|array',
            'evidence_max_age_days' => 'nullable|integer|min:1',
            'review_interval_days' => 'nullable|integer|min:1',
            'review_on_change' => 'boolean',
            'requires_approval' => 'boolean',
            'validation_rules' => 'nullable|array',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $platformScope = $validated['software_platform_id'] ?? 0;
        $exists = SoftwarePropertyDefinition::where('platform_scope_id', $platformScope)
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'Property code already exists in this platform scope.']);
        }

        $definition = SoftwarePropertyDefinition::create($validated);

        SoftwareAccountAuditService::log(
            action: 'property_definition.created',
            entityType: 'SoftwarePropertyDefinition',
            entityId: $definition->id,
            outcome: 'success',
            metadata: ['code' => $definition->code, 'data_type' => $definition->data_type],
            actorUserId: auth()->id()
        );

        return back()->with('success', 'Security property definition created successfully.');
    }

    public function update(Request $request, SoftwarePropertyDefinition $definition)
    {
        $this->authorize('update', $definition);

        $validated = $request->validate([
            'label' => 'required|string|max:150',
            'description' => 'nullable|string',
            'is_required' => 'boolean',
            'is_sensitive' => 'boolean',
            'evidence_required' => 'boolean',
            'evidence_types' => 'nullable|array',
            'evidence_max_age_days' => 'nullable|integer|min:1',
            'review_interval_days' => 'nullable|integer|min:1',
            'review_on_change' => 'boolean',
            'requires_approval' => 'boolean',
            'validation_rules' => 'nullable|array',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $definition->update($validated);

        SoftwareAccountAuditService::log(
            action: 'property_definition.updated',
            entityType: 'SoftwarePropertyDefinition',
            entityId: $definition->id,
            outcome: 'success',
            metadata: ['code' => $definition->code],
            actorUserId: auth()->id()
        );

        return back()->with('success', 'Security property definition updated successfully.');
    }
}
