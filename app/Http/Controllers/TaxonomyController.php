<?php

namespace App\Http\Controllers;

use App\Models\MasterTaxonomy;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaxonomyController extends Controller
{
    public function index()
    {
        $taxonomies = MasterTaxonomy::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group_key');

        return Inertia::render('Admin/Taxonomies/Index', [
            'taxonomies' => $taxonomies
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_key' => 'required|string',
            'code' => 'required|string|unique:master_taxonomies,code',
            'title' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'color_hex' => 'nullable|string|max:7',
            'default_template' => 'nullable|array',
            'metadata' => 'nullable|array',
            'sort_order' => 'integer'
        ]);

        $validated['group_key'] = strtolower(trim(str_replace(' ', '_', $validated['group_key'])));

        MasterTaxonomy::create($validated);

        return redirect()->back()->with('success', 'Taxonomy entry created successfully.');
    }

    public function update(Request $request, MasterTaxonomy $taxonomy)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'color_hex' => 'nullable|string|max:7',
            'default_template' => 'nullable|array',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer'
        ]);

        $taxonomy->update($validated);

        return redirect()->back()->with('success', 'Taxonomy entry updated successfully.');
    }

    public function renameGroup(Request $request)
    {
        $validated = $request->validate([
            'old_group_key' => 'required|string',
            'new_group_key' => 'required|string|max:50',
        ]);

        $newKey = strtolower(trim(str_replace(' ', '_', $validated['new_group_key'])));

        MasterTaxonomy::where('group_key', $validated['old_group_key'])
            ->update(['group_key' => $newKey]);

        return redirect()->back()->with('success', 'Group renamed successfully.');
    }

    public function destroy(MasterTaxonomy $taxonomy)
    {
        $taxonomy->delete();
        return redirect()->back()->with('success', 'Taxonomy entry deleted.');
    }

    public function destroyGroup(string $groupKey)
    {
        MasterTaxonomy::where('group_key', $groupKey)->delete();
        return redirect()->back()->with('success', 'Group deleted.');
    }
}
