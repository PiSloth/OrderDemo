<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportTextBlock;
use App\Models\MasterTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

use App\Models\TodoDueTime;
use App\Models\TodoCategory;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;

class ReportController extends Controller
{
    private function getTodoOptions()
    {
        $dueTimes = TodoDueTime::with(['category', 'priority'])->get();
        $branches = Branch::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $categories = TodoCategory::all();
        $users = User::query()
            ->where('suspended', false)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'department_id', 'branch_id']);

        $itAdminDepartments = $departments->filter(function ($dept) {
            $name = strtolower($dept->name);
            return str_contains($name, 'it') || str_contains($name, 'admin');
        })->values();

        if ($itAdminDepartments->isEmpty()) {
            $itAdminDepartments = $departments;
        }

        return [
            'dueTimes' => $dueTimes,
            'branches' => $branches,
            'departments' => $departments,
            'categories' => $categories,
            'users' => $users,
            'itAdminDepartments' => $itAdminDepartments,
        ];
    }

    public function create()
    {
        $taxonomies = MasterTaxonomy::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group_key');

        return Inertia::render('Reports/Create', [
            'taxonomies' => $taxonomies,
            'todoOptions' => $this->getTodoOptions(),
        ]);
    }

    public function analyticBoard()
    {
        $taxonomies = MasterTaxonomy::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group_key');

        return Inertia::render('Reports/AnalyticBoard', [
            'taxonomies' => $taxonomies,
            'todoOptions' => $this->getTodoOptions(),
        ]);
    }

    public function edit(Report $report)
    {
        $report->load('textBlocks');

        $taxonomies = MasterTaxonomy::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group_key');

        return Inertia::render('Reports/Edit', [
            'report' => $report,
            'taxonomies' => $taxonomies,
            'todoOptions' => $this->getTodoOptions(),
        ]);
    }

    public function history(Request $request)
    {
        $query = ReportTextBlock::with(['report.author'])
            ->whereNotNull('plain_text')
            ->where('plain_text', '!=', '');

        if ($request->filled('category_type')) {
            $query->where('category_type', $request->query('category_type'));
        }

        if ($request->filled('branch_code')) {
            $query->where('branch_code', $request->query('branch_code'));
        }

        if ($request->filled('process_code')) {
            $query->where('process_code', $request->query('process_code'));
        }

        if ($request->filled('risk_level')) {
            $query->where('risk_level', $request->query('risk_level'));
        }

        $history = $query->orderBy('created_at', 'desc')->paginate(5);

        return response()->json($history);
    }

    public function imageboardThreads()
    {
        $reports = Report::has('textBlocks')
            ->with(['author', 'textBlocks'])
            ->orderBy('id', 'desc')
            ->get();

        $formattedThreads = $reports->map(function ($report) {
            $opBlock = $report->textBlocks->where('block_type', '!=', 'reply')->first() ?? $report->textBlocks->first();
            $replyBlocks = $report->textBlocks->where('block_type', 'reply');
            $json = is_array($opBlock->json_content ?? null) ? $opBlock->json_content : [];
            $opImages = $json['images'] ?? [];

            return [
                'id' => $report->id,
                'title' => $report->title,
                'author' => $report->author->name ?? 'Auditor_Anon',
                'timestamp' => $report->created_at ? $report->created_at->format('m/d/y(D)H:i:s') : now()->format('m/d/y(D)H:i:s'),
                'category' => $opBlock->category_type ?? 'Audit Finding',
                'branch' => $opBlock->branch_code ?? 'Branch 1',
                'process' => $opBlock->process_code ?? '',
                'risk' => $opBlock->risk_level ?? 'High Risk',
                'status' => 'Master Template: Active',
                'images' => $opImages,
                'content' => $opBlock->plain_text ?? strip_tags($opBlock->html_content ?? '') ?: $report->title,
                'html_content' => $opBlock->html_content ?? '',
                'require_todo_task' => $json['require_todo_task'] ?? false,
                'require_promote_action' => $json['require_promote_action'] ?? false,
                'is_liked' => $json['is_liked'] ?? false,
                'likes_count' => $json['likes_count'] ?? 0,
                'liked_by_users' => $json['liked_by_users'] ?? [],
                'is_saved' => $json['is_saved'] ?? false,
                'saved_by_users' => $json['saved_by_users'] ?? [],
                'json_content' => $json,
                'replies' => $replyBlocks->map(function ($reply) {
                    $replyJson = is_array($reply->json_content ?? null) ? $reply->json_content : [];
                    return [
                        'id' => $reply->id,
                        'author' => 'Staff_User',
                        'timestamp' => $reply->created_at ? $reply->created_at->format('m/d/y(D)H:i:s') : now()->format('m/d/y(D)H:i:s'),
                        'content' => $reply->plain_text ?? strip_tags($reply->html_content),
                        'html_content' => $reply->html_content,
                        'images' => $replyJson['images'] ?? []
                    ];
                })->values()->toArray()
            ];
        });

        return response()->json($formattedThreads);
    }

    public function updateMetadata(Request $request, $reportId)
    {
        $validated = $request->validate([
            'require_todo_task' => 'nullable|boolean',
            'require_promote_action' => 'nullable|boolean',
            'is_liked' => 'nullable|boolean',
            'likes_count' => 'nullable|integer',
            'liked_by_users' => 'nullable|array',
            'is_saved' => 'nullable|boolean',
            'saved_by_users' => 'nullable|array',
        ]);

        $report = Report::find($reportId);
        if ($report) {
            $opBlock = $report->textBlocks()->where('block_type', '!=', 'reply')->first() ?? $report->textBlocks()->first();
            if ($opBlock) {
                $json = is_array($opBlock->json_content) ? $opBlock->json_content : [];
                foreach ($validated as $key => $val) {
                    if ($val !== null) {
                        $json[$key] = $val;
                    }
                }
                $opBlock->json_content = $json;
                $opBlock->save();
            }
        }

        return response()->json([
            'message' => 'Metadata updated in database successfully',
            'metadata' => $validated
        ]);
    }

    public function reply(Request $request, $reportId)
    {
        $validated = $request->validate([
            'category_type' => 'nullable|string',
            'branch_code' => 'nullable|string',
            'process_code' => 'nullable|string',
            'risk_level' => 'nullable|string',
            'plain_text' => 'nullable|string',
            'html_content' => 'required|string',
        ]);

        $report = Report::find($reportId);

        if (!$report) {
            return response()->json(['message' => 'Target report not found.'], 404);
        }

        $maxOrder = $report->textBlocks()->max('sequence_order') ?? 0;

        $replyBlock = $report->textBlocks()->create([
            'sequence_order' => $maxOrder + 1,
            'block_type' => 'reply',
            'category_type' => $validated['category_type'] ?? null,
            'branch_code' => $validated['branch_code'] ?? null,
            'process_code' => $validated['process_code'] ?? null,
            'risk_level' => $validated['risk_level'] ?? null,
            'plain_text' => $validated['plain_text'] ?? strip_tags($validated['html_content']),
            'html_content' => $validated['html_content'],
        ]);

        return response()->json([
            'message' => 'Reply posted successfully',
            'reply' => [
                'id' => $replyBlock->id,
                'author' => auth()->user()->name ?? 'Current User',
                'timestamp' => now()->format('m/d/y(D)H:i:s'),
                'content' => $validated['plain_text'] ?? strip_tags($validated['html_content']),
                'html_content' => $validated['html_content'],
                'images' => []
            ]
        ]);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:10240',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('report_images', 'public');
            $url = asset('storage/' . $path);

            return response()->json([
                'url' => $url,
                'path' => $path,
                'success' => true,
            ]);
        }

        return response()->json(['error' => 'No image file uploaded'], 400);
    }

    public function store(Request $request)
    {
        $validated = $this->validateReportRequest($request);

        $report = null;
        DB::transaction(function () use ($validated, &$report) {
            $report = Report::create([
                'report_number' => 'RPT-' . strtoupper(Str::random(8)),
                'title' => $validated['title'],
                'author_id' => auth()->id() ?? 1,
                'status' => 'submitted',
                'last_autosaved_at' => now()
            ]);

            $this->syncReportBlocks($report, $validated['blocks']);
        });

        return redirect()->back()->with('success', 'Report created with block containers.');
    }

    public function update(Request $request, Report $report)
    {
        $validated = $this->validateReportRequest($request);

        DB::transaction(function () use ($report, $validated) {
            $report->update([
                'title' => $validated['title'],
                'last_autosaved_at' => now()
            ]);

            $this->syncReportBlocks($report, $validated['blocks']);
        });

        return redirect()->back()->with('success', 'Report blocks updated successfully.');
    }

    private function validateReportRequest(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'blocks' => 'required|array|min:1',
            'blocks.*.id' => 'nullable',
            'blocks.*.sequence_order' => 'required|integer',
            'blocks.*.category_type' => 'nullable|string|exists:master_taxonomies,code',
            'blocks.*.branch_code' => 'nullable|string|exists:master_taxonomies,code',
            'blocks.*.process_code' => 'nullable|string|exists:master_taxonomies,code',
            'blocks.*.risk_level' => 'nullable|string|in:LOW,MEDIUM,HIGH,CRITICAL',
            'blocks.*.plain_text' => 'nullable|string',
            'blocks.*.html_content' => 'nullable|string',
            'blocks.*.json_content' => 'nullable|array',
            'blocks.*.images' => 'nullable|array',
        ]);
    }

    private function syncReportBlocks(Report $report, array $blocksData): void
    {
        $incomingIds = array_filter(array_column($blocksData, 'id'), fn($id) => is_numeric($id));

        $report->textBlocks()->whereNotIn('id', $incomingIds)->delete();

        foreach ($blocksData as $blockData) {
            $jsonContent = $blockData['json_content'] ?? [];

            if (isset($blockData['images']) && is_array($blockData['images'])) {
                $jsonContent['images'] = array_values(array_unique(array_merge(
                    $jsonContent['images'] ?? [],
                    $blockData['images']
                )));
            }

            $report->textBlocks()->updateOrCreate(
                ['id' => is_numeric($blockData['id'] ?? null) ? $blockData['id'] : null],
                [
                    'sequence_order' => $blockData['sequence_order'],
                    'block_type' => 'paragraph',
                    'category_type' => $blockData['category_type'] ?? null,
                    'branch_code' => $blockData['branch_code'] ?? null,
                    'process_code' => $blockData['process_code'] ?? null,
                    'risk_level' => $blockData['risk_level'] ?? null,
                    'plain_text' => $blockData['plain_text'] ?? '',
                    'html_content' => $blockData['html_content'] ?? '',
                    'json_content' => $jsonContent,
                ]
            );
        }
    }
}
