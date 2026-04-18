# KPI Dependency Group Phase 1

## Objective

Add shared daily tasks for 3 or more employees where one member initiates with shared evidence,
all required members confirm before cutoff, and one shared approval result is copied back to each
member's KPI task instance.

Phase 1 scope is intentionally narrow:

- daily tasks only
- all members required
- one shared image-based submission
- one shared approval record
- shared final result for all linked members

## Confirmed Rules

- any member can initiate the group task
- shared evidence is uploaded once by the current initiator
- evidence stays editable until the first confirmation
- after the first confirmation, evidence is locked
- confirmer meaning:
  - "I reviewed the uploaded evidence and accept it as correct for this task instance."
- confirm comment is optional
- reject-back comment is required
- any member can reject back to the group before approval
- after reject-back, any member can reopen and become the new initiator if still before cutoff
- reminders run hourly from `08:45 AM`
- reminders stop per-user as soon as that member confirms
- when all required members confirm, create one shared approval request
- the approval chain comes from dependency-group/template configuration, not from the initiator
- final approval result is shared across all members
- each member gets one KPI task result on their own linked task instance
- if confirmations complete after cutoff, all members become `failed_late`
- if not all required confirmations happen before cutoff, all members become `failed_missed`
- approved holiday/exclusion before cutoff reduces the required count
- audit markers:
  - `I` for initiator
  - `C` for confirmer
  - `X` for failed/missed
  - gray for holiday/exclusion

## Domain Model

### Dependency Group

Configuration for one shared task team.

- belongs to one task template
- stores fixed approver chain
- stores reminder start time and active flag

### Dependency Group Member

Links one employee task assignment into the dependency group.

- belongs to one dependency group
- points to one employee task assignment
- stores the expected user for that slot

### Dependency Group Run

One generated shared task run for one day.

- belongs to one dependency group
- one run per day
- tracks lifecycle from pending through approval/finalization

### Dependency Group Run Member

Runtime member status for one employee inside one run.

- links the member's individual task instance to the shared run
- tracks:
  - pending
  - initiated
  - confirmed
  - rejected_back
  - excluded
  - missed

### Dependency Group Submission

One shared evidence package for the run.

- initiated by one user
- contains one shared remark
- locks after the first confirmation

### Dependency Group Submission Image

One shared image belonging to the run submission.

- title
- remark
- sort order

### Dependency Group Approval Step

Shared sequential approval chain for the dependency-group run.

## Recommended Status Flow

### Group Run Status

- `pending`
- `in_progress`
- `waiting_group_confirmation`
- `waiting_first_approval`
- `waiting_final_approval`
- `passed`
- `failed_late`
- `failed_missed`
- `rejected`

### Group Member Status

- `pending`
- `initiated`
- `confirmed`
- `rejected_back`
- `excluded`
- `missed`

### Shared Submission Status

- `submitted`
- `reopened`
- `approved`
- `rejected`

### Shared Approval Status

- `pending`
- `approved`
- `rejected`
- `skipped`

## Workflow

1. Generator creates one dependency-group run for the day.
2. Generator also creates the member task instances for that day.
3. All members receive reminders from `08:45 AM` until they confirm or the cutoff passes.
4. Any member can initiate the shared run by uploading the required images.
5. The run moves to `waiting_group_confirmation`.
6. Other members open the shared evidence and either confirm or reject-back.
7. First confirmation locks the evidence payload.
8. A reject-back returns the run to `in_progress` with required comment.
9. Any member can reopen while still before cutoff.
10. Once all required members confirm, the run moves into shared approval.
11. Shared approval uses the fixed approver chain from the dependency-group configuration.
12. Final result is written back to every linked task instance for KPI scoring, dashboard, leaderboard, and audit.

## Failure Rules

- if all confirmations finish after cutoff:
  - all linked task instances become `failed_late`
- if the cutoff passes before enough confirmations:
  - all linked task instances become `failed_missed`
- if a member has approved holiday/exclusion before cutoff:
  - that member is marked `excluded`
  - required confirmation count is reduced

## Integration Notes

- keep normal per-user task instances for scoring and reporting
- do not duplicate the shared evidence into each member submission table
- use the shared run tables as the evidence and approval source of truth
- linked task instances should mirror the final shared result

## Phase 2 Later

- weekly/monthly dependency groups
- minimum-required confirmation mode
- shared custom table evidence
- richer rejection and reassignment rules
- admin UI for dependency-group configuration
