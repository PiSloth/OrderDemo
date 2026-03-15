# Whiteboard Module

This module adds a collaborative whiteboard for distributing issues, proposals, reminders, and announcements to selected users or department-owned email list targets.

The main board now uses an Outlook-style master-detail layout: a filterable content list on the left and the selected content detail on the right, all within one Livewire page.

## Tables

- `whiteboard_contents`: main posts with soft deletes, reporter, author, type, flag, and proposed decision due date.
- `whiteboard_content_types`: configurable grouping categories used on the board, including whether a decision is required.
- `whiteboard_flags`: configurable urgency or status labels.
- `whiteboard_reports`: recipient assignments and read tracking.
- `whiteboard_decisions`: rich text decisions and optional appointments recorded against a post.

## Core Relationships

- `WhiteboardContent` belongs to `WhiteboardContentType`, `WhiteboardFlag`, reporter `EmailList`, and creator `User`.
- `WhiteboardContent` has many `WhiteboardReport` and `WhiteboardDecision` records.
- `WhiteboardReport` belongs to a target `EmailList` and optionally the `User` who acknowledged it.
- `WhiteboardDecision` belongs to a `WhiteboardContent` and optionally the `User` who created it.

## Visibility Rule

Users can see content when either of these is true:

- they created the content themselves
- the content was assigned to an `email_lists.email` that matches their login email
- the content was assigned to an `email_lists.department_id` that matches their department

That logic is implemented in `App\Models\WhiteboardContent::scopeVisibleTo()`.

## Example Queries

### Group board contents by content type for the current user

```php
$grouped = \App\Models\WhiteboardContent::query()
    ->boardFeed(auth()->user())
    ->get()
    ->groupBy(fn ($content) => $content->contentType?->name ?? 'Uncategorized');
```

### Get only unread whiteboard items assigned to the current user or department

```php
$unread = \App\Models\WhiteboardContent::query()
    ->visibleTo(auth()->user())
    ->whereHas('reports', function ($query) {
        $query->where('is_read', false);
    })
    ->with(['contentType', 'flag', 'reports.emailList'])
    ->latest()
    ->get();
```

### Show counts per content type for dashboard cards

```php
$counts = \App\Models\WhiteboardContent::query()
    ->visibleTo(auth()->user())
    ->selectRaw('content_type_id, count(*) as total')
    ->groupBy('content_type_id')
    ->with('contentType:id,name,color')
    ->get();
```

### Pull decisions with scheduled appointments

```php
$appointments = \App\Models\WhiteboardDecision::query()
    ->whereNotNull('appointment_at')
    ->with(['content.contentType', 'creator'])
    ->orderBy('appointment_at')
    ->get();
```

## Notes

- Read tracking lives in `whiteboard_reports` with `is_read`, `read_at`, and `read_by_user_id` so the board can support both recipient lists and actual user acknowledgement.
- The configuration screen reuses the existing `email_lists` table instead of introducing a second target directory.
- When a content type requires a decision, board creation requires a decision due date.
- Decision authoring now lives on the whiteboard detail page, where decisions are stored and rendered as rich text paragraphs.
- Board filters and sorting are URL-backed through Livewire so selected content, search, filter, and sort state can survive refreshes and deep links.