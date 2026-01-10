<?php

namespace App\Http\Controllers\Document;

use App\Models\EmailList;
use Illuminate\Http\Request;
use Spatie\SimpleExcel\SimpleExcelWriter;

class EmailListExportController
{
    public function __invoke(Request $request): void
    {
        $format = strtolower((string) $request->query('format', 'csv'));
        $type = $format === 'xlsx' ? 'xlsx' : 'csv';
        $downloadName = $type === 'xlsx' ? 'email-list.xlsx' : 'email-list.csv';

        $query = EmailList::query()->with(['department', 'tags']);

        $archived = filter_var($request->query('archived', false), FILTER_VALIDATE_BOOL);
        if ($archived) {
            $query->onlyTrashed();
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('department', fn($dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        $departmentId = (string) $request->query('department_id', '');
        if ($departmentId !== '') {
            $query->where('department_id', $departmentId);
        }

        $tagId = (string) $request->query('tag_id', '');
        if ($tagId !== '') {
            $query->whereHas('tags', fn($q) => $q->where('email_tags.id', $tagId));
        }

        $writer = SimpleExcelWriter::streamDownload($downloadName, $type);

        foreach ($query->orderBy('user_name')->cursor() as $row) {
            $writer->addRow([
                'user_name' => $row->user_name,
                'email' => $row->email,
                'department' => $row->department?->name,
                'department_id' => $row->department_id,
                'tags' => $row->tags?->pluck('name')->implode(', '),
                'archived' => $row->trashed() ? 1 : 0,
            ]);
        }

        $writer->toBrowser();
    }
}
