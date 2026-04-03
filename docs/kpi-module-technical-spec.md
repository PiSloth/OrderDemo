# KPI Task Tracking Module

## Objective

Build a dedicated KPI task-tracking module that is separate from the existing Todo system.
The module manages pre-assigned recurring employee tasks, evidence submission, sequential approval,
exclusions, KPI scoring, department/company leaderboards, and Google Calendar reminders.

## Confirmed Business Rules

- One task belongs to one employee.
- KPI groups contain one or more task templates.
- One task template has exactly one active rule.
- Phase 1 rule types:
  - `pass_percentage`
  - `fail_count`
  - `spend_cost_lte`
- Task-level percentage formula:
  - `passed_count / must_do_count * 100`
- KPI-level percentage formula:
  - `total_kpi_passed_count / total_kpi_must_do_count * 100`
- A task instance becomes `passed` only after final approval.
- Late but approved means `completed but failed`.
- Rejected submissions can be resubmitted within the allowed time.
- Daily on-time depends on employee submission time, not approver action time.
- Daily reminders run hourly from `08:45 AM` until the cutoff time.
- Weekly and monthly tasks fail at the end of the last eligible day in the period.
- Daily exclusions are allowed by day or by single task.
- Weekly/monthly exclusions are out of scope for phase 1.
- Dependency flow:
  - Employee A submits task A.
  - The linked task B is auto-created/submitted for employee B.
  - Task B stays pending until employee B confirms.
  - Once B confirms, both tasks move through approval together.
  - Both tasks share the same final result.

## Users And Permissions

Primary roles for phase 1:

- `Supervisor`
- `Assistant Manager`
- `Manager`
- `Assistant General Manager`
- `CEO`

Phase 1 permission intent:

- `Supervisor`
  - can approve exclusion requests
  - can act as first or final approver when manually configured
- `Assistant Manager`
  - can create KPI groups/templates
  - can manage assignments
  - is the default final approver
  - can view company leaderboard
- `Manager`
  - can create KPI groups/templates
  - can manage assignments
  - can view company leaderboard
- `Assistant General Manager`
  - can view company leaderboard
- `CEO`
  - can view company leaderboard

Department leaderboard visibility:

- users can view employees in the same department

## Domain Model

### KPI Group

Logical container for related tasks, such as `KPI 1`.

### Task Template

Reusable recurring task definition with KPI group, frequency, cutoff, evidence settings,
guideline text, reminder configuration, and active status.

### Task Rule

Exactly one rule per task template.

- `pass_percentage`: target percentage
- `fail_count`: maximum fail count
- `spend_cost_lte`: maximum allowed cost amount

### Role Assignment

Maps a task template to one or more positions, optionally scoped by branch/department.

### Employee Task Assignment

Concrete assignment of a template to a single employee, with manual approvers and calendar control.

### Task Instance

One scoreable unit for a daily/weekly/monthly period.

### Submission

Evidence package for a task instance. A task instance may have multiple submissions due to resubmission.

### Submission Evidence

- image collection with title and remark
- configurable custom table rows/cells based on template field definitions

### Approval Step

Sequential review chain attached to a submission.

### Exclusion Request

Daily-only exclusion request for either a full day or a single assigned task on a given day.

### Dependency

Links two employee task assignments so they move together for approval and share the same final result.

### Calendar Control

Per-assignment reminder rules for hourly daily reminders and daily refresh for weekly/monthly tasks.

### Period Score

Precomputed employee KPI metrics per week/month.

## Status Model

Recommended task instance statuses:

- `pending`
- `submitted`
- `waiting_partner_confirmation`
- `waiting_first_approval`
- `waiting_final_approval`
- `passed`
- `failed_late`
- `failed_missed`
- `rejected`
- `excluded`

Submission statuses:

- `submitted`
- `rejected`
- `approved`

Approval step statuses:

- `pending`
- `approved`
- `rejected`
- `skipped`

## Generation And Scoring

### Daily Tasks

- generate one instance per eligible workday
- exclude holidays and approved exclusions from denominator
- reminders begin at `08:45 AM`
- if not submitted before cutoff, mark as `failed_missed`
- if submitted after cutoff and later approved, final result is `failed_late`

### Weekly Tasks

- one instance per eligible week
- denominator uses only eligible weeks
- fail at the end of the last eligible day of the week
- daily reminder refresh at `09:15 AM` while still open

### Monthly Tasks

- one or more instances based on the monthly required count
- denominator equals the required monthly count for that employee and month
- fail at the end of the last eligible day of the month
- daily reminder refresh at `09:15 AM` while still open

### Score Rollup

- task-level percentage is computed from task instances for the period
- KPI-level percentage aggregates all task instances under the KPI group
- late approved items count as completed operationally but failed for KPI score

## Evidence Rules

Each task template can define:

- images only
- custom table only
- both images and table

Image settings:

- minimum image count
- maximum image count
- per-image remark required or optional

Custom table settings:

- label
- field key
- field type
- required flag
- options payload for select/uom-driven fields
- sort order

## UX Structure

### Mobile Employee UX

- `Today Tasks`
- cutoff time and on-time warning
- one-tap submit flow
- evidence form
- guideline section
- pending/rejected/approved state
- exclusion request action

### Desktop UX

- monthly matrix view
- rows are assigned tasks
- columns are dates
- row-end completion summary
- approvals queue
- template management
- assignment management
- leaderboard views

### Sidebar Navigation

- Dashboard
- My Tasks
- Approvals
- Templates
- Assignments
- Leaderboard

Future additions:

- Exclusions
- Reports
- Calendar Control

## Implementation Plan

### Phase 1

- data schema
- Livewire route skeleton
- dedicated KPI layout/sidebar
- task templates and assignments
- task instance generation jobs
- evidence submission
- sequential approval
- exclusion request handling for daily tasks
- scoring service
- leaderboard service
- Google Calendar push integration

### Phase 2

- weekly/monthly exclusion support
- reporting exports
- richer permission policies
- calendar management screen
- bulk assignment tools
