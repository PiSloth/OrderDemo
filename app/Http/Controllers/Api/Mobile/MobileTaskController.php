<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Kpi\KpiTaskInstance;
use App\Models\Kpi\KpiTaskSubmission;
use App\Models\Kpi\KpiTaskSubmissionImage;
use App\Models\TaskNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MobileTaskController extends Controller
{
    /**
     * List assigned task instances for the authenticated submitter.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->query('status', 'all');

        $query = KpiTaskInstance::with(['template', 'group', 'latestSubmission'])
            ->where('user_id', $user->id)
            ->orderBy('due_at', 'asc');

        if ($status !== 'all') {
            if ($status === 'pending') {
                $query->whereNull('submitted_at')->where('status', '!=', 'cancelled');
            } elseif ($status === 'submitted') {
                $query->whereNotNull('submitted_at')->where('status', 'pending_approval');
            } elseif ($status === 'approved') {
                $query->where('status', 'approved');
            } elseif ($status === 'rejected') {
                $query->where('status', 'rejected');
            }
        }

        $tasks = $query->paginate(20);

        return response()->json([
            'data' => $tasks->items(),
            'current_page' => $tasks->currentPage(),
            'last_page' => $tasks->lastPage(),
            'total' => $tasks->total(),
        ]);
    }

    /**
     * Display details for a single task instance.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $task = KpiTaskInstance::with([
            'template.evidenceFields',
            'group',
            'submissions.images',
        ])
        ->where('user_id', $user->id)
        ->findOrFail($id);

        return response()->json([
            'task' => $task,
        ]);
    }

    /**
     * Submit a task instance with form data & compressed evidence photo(s).
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $task = KpiTaskInstance::with(['template.evidenceFields'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $request->validate([
            'remark' => 'nullable|string|max:1000',
            'evidence_data' => 'nullable|array',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240', // Max 10MB per file (client auto-resizes to ~500KB)
        ]);

        // Check if template requires images
        if ($task->template && $task->template->requires_images) {
            $existingCount = $task->submissions()->withCount('images')->get()->sum('images_count');
            $newCount = $request->hasFile('images') ? count($request->file('images')) : 0;
            if (($existingCount + $newCount) < ($task->required_image_count ?? 1)) {
                throw ValidationException::withMessages([
                    'images' => ['At least ' . ($task->required_image_count ?? 1) . ' evidence photo(s) are required.'],
                ]);
            }
        }

        DB::beginTransaction();

        try {
            $now = now();
            $isLate = $task->due_at && $now->isAfter($task->due_at);

            $submission = KpiTaskSubmission::create([
                'task_instance_id' => $task->id,
                'submitted_by_user_id' => $user->id,
                'submitted_at' => $now,
                'status' => 'submitted',
                'employee_remark' => $request->input('remark'),
                'is_late' => $isLate,
            ]);

            // Save uploaded evidence images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $idx => $file) {
                    $path = $file->store('kpi_submissions/' . date('Y/m'), 'public');

                    KpiTaskSubmissionImage::create([
                        'task_submission_id' => $submission->id,
                        'image_path' => $path,
                        'title' => $file->getClientOriginalName(),
                        'sort_order' => $idx + 1,
                    ]);
                }
            }

            // Update task instance state
            $task->update([
                'submitted_at' => $now,
                'status' => 'pending_approval',
                'is_on_time' => !$isLate,
            ]);

            $taskTitle = $task->template?->title ?? 'Assigned Task';

            // Create In-App Notification for submitter
            TaskNotification::create([
                'user_id' => $user->id,
                'type' => 'task_submitted',
                'title' => 'Task Submitted Successfully',
                'message' => "Your task '{$taskTitle}' has been submitted and is pending review.",
                'data' => [
                    'task_instance_id' => $task->id,
                    'submission_id' => $submission->id,
                ],
            ]);


            DB::commit();

            return response()->json([
                'message' => 'Task submitted successfully.',
                'task' => $task->fresh(['latestSubmission.images']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to submit task: ' . $e->getMessage(),
            ], 500);
        }
    }
}
