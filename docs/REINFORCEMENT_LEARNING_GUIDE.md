# Reinforcement Learning Module
## Full Feature Specification, Architecture & User Guide

---

## 1. Overview

The **Reinforcement Learning** module (implemented under the `/training` route group and `Training` namespace) is an end-to-end organizational learning, compliance tracking, and competency assessment system. It closes the loop between company Standard Operating Procedures (SOPs), employee onboarding, instructor-led sessions, knowledge verification tests, remedial reinforcement, and recurring retraining cycles.

### Key Capabilities
- **Executive Compliance Dashboard**: Real-time completion rates, department attendance metrics, test pass rates, and an interactive compliance matrix.
- **Skill Matrix & Strategic Analytics**: Multi-level competency views across Departments, Branches, Locations, and individual Employees with scope-based access control.
- **Training Catalog & Target Scopes**: Course definitions with linked Company Documents, department/position audience scopes, retraining intervals, and PDF curriculum/matrix exports.
- **Office Positions & Auto-Onboarding**: Role-based employee grouping that automatically assigns matching active trainings when staff are hired or transferred.
- **Session Scheduling & Attendance Tracking**: Multi-day schedule slots, trainer approval gates that unlock tests, daily attendance logs, and PDF session announcements.
- **Test & Question Builder**: Reusable question bank supporting `catalog`, `document`, and `global` scopes across `MULTIPLE_CHOICE`, `TRUE_FALSE`, and `MULTI_SELECT` formats.
- **Employee Assessment Portal**: Anti-cheat randomized test delivery, instant automated grading, attempt history review, and automatic remedial session enrollment on failure.
- **Printable Scorecard & Certificate**: Print-ready training scorecard and competency certificate template.

---

## 2. Navigation & Routes

In the application sidebar (`AsideLayout.jsx` / `aside.blade.php`), the module appears under the **Reinforcement Learning** expandable group (graduation cap icon):

| Menu Item | Route | Controller | Purpose |
| :--- | :--- | :--- | :--- |
| **Compliance Dashboard** | `/training/dashboard` | `TrainingComplianceController@index` | Executive KPIs, department attendance, test performance, compliance matrix |
| **Skill Matrix** | `/training/skill-matrix` | `TrainingSkillMatrixController@index` | Department, branch, location, and employee competency analytics |
| **Training Catalog** | `/training/trainings` | `TrainingController@index` | Manage courses, scopes, linked SOPs, manual assignments, and PDF reports |
| **Office Positions** | `/training/office-positions` | `OfficePositionController@index` | Manage office positions and bulk-assign employees |
| **Sessions & Attendance** | `/training/sessions` | `TrainingSessionController@index` | Schedule sessions, manage slots, approve sessions, record attendance |
| **My Trainings & Tests** | `/training/my-trainings` | `TrainingEmployeeController@myTrainings` | Employee self-service portal for assigned trainings and assessments |

### Supporting Routes
- **Test Builder**: `GET /training/trainings/{training}/test-builder` & `PUT /training/tests/{test}/save-builder` (`TestController`)
- **Take & Submit Test**: `GET /training/assignments/{assignment}/tests/{test}/take` & `POST /training/assignments/{assignment}/tests/{test}/submit` (`TrainingEmployeeController`)
- **Printable Scorecard**: `GET /training/scorecard` & `GET /training/assignments/{assignment}/scorecard` (`TrainingScorecardController`)
- **Assignment Management**: `GET /training/assignments/{assignment}/attempt-history`, `DELETE /training/assignments/{assignment}`, `POST /training/assignments/cleanup-orphaned` (`TrainingComplianceController`)
- **Skill Matrix API**: `GET /training/skill-matrix/users` & `GET /training/skill-matrix/employees/{user}` (`TrainingComplianceController`)

---

## 3. Core Features & User Guide

### 3.1 Compliance & Analytics Dashboard (`/training/dashboard`)
Provides administrators and managers with a real-time overview of organizational training health:
- **Summary KPIs**: Total active users, total assignments, `COMPLETED`, `IN_PROGRESS`, `PENDING`, `OVERDUE`, `FAILED`, upcoming due assignments (within 14 days), and overall completion percentage.
- **Department Attendance Breakdown**: Calculates attendance completion rates across departments based on **approved** training sessions (`approved_at IS NOT NULL`), comparing attended days/schedule slots against total required days.
- **Test Performance Analytics**: Displays total graded attempts, passed attempts, and pass rate percentage per active training course.
- **Compliance Matrix & Filtering**: Filter assignments by Department, Office Position, Training, Status, Trigger Type (`MANUAL`, `NEW_USER`, `RETRAINING`), Due Date range, or keyword search.
- **Administrative Actions**:
  - Inspect full attempt history and question-by-question answers (`attemptHistory`).
  - Delete uncompleted or non-passed assignments (`deleteAssignment` — blocked if the assignment is `COMPLETED` or has a `PASSED` attempt).
  - Clean up orphaned assignments (`cleanupOrphaned` — removes assignments lacking `PASSED` attempts and lacking active session links).

---

### 3.2 Skill Matrix (`/training/skill-matrix`)
Accessible to users with global, department, or branch scope permissions (`global-scope.view`, `department-scope.view`, `branch-scope.view`, `training.catalog.view`, `training.attempt.view`, or Super Users).
- **Scoped Visibility**: Users without global scope only see themselves plus colleagues in their own department (with `department-scope.view`) and/or branch (with `branch-scope.view`).
- **Multi-Dimensional Views**: Aggregates employee training assignments and latest test attempt results across Departments, Branches, Locations, and individual Employees.
- **Inline & Modal Matrix**: Reusable `EmployeeSkillMatrixInline` and `EmployeeSkillMatrixModal` components allow viewing an employee's competency breakdown directly from other screens.

---

### 3.3 Training Catalog & Audience Scopes (`/training/trainings`)
The catalog defines every training program in the organization.

#### Course Configuration
- **Identity**: Unique `code` (max 50 chars), `title`, `description`, and `TrainingCategory` (select existing or create inline via `new_category_name`).
- **Assessment & Scheduling Rules**:
  - `passing_score`: Minimum percentage (0–100) required to pass the test.
  - `duration_days`: Default session duration in days (minimum 1).
  - `retrain_interval` & `retrain_unit`: Recurring cycle (`day`, `month`, or `year`; `0` disables automatic retraining).
  - `status`: `active`, `draft`, or `archived`.
- **Audience Scopes (`training_scopes`)**: Pair a `department_id` with an optional `office_position_id`. Leaving `office_position_id` empty targets the entire department.
- **Linked Company Documents**: Associate one or more `CompanyDocument` records (SOPs, policies, manuals) with the training.

#### Manual Assignment (`POST /training/trainings/{training}/assign`)
Administrators can manually trigger assignments with two modes and four target types:
- **Assignment Modes (`assignment_type`)**:
  - `FULL_TRAINING` (default): Automatically provisions one shared `PENDING` `TrainingSession` for all targeted users and registers them as participants.
  - `TEST_ONLY`: Assigns only the knowledge assessment without creating or requiring a training session.
- **Target Types (`target_type`)**:
  - `scopes`: All active users matching the training's configured `training_scopes`.
  - `departments`: All active users in selected `department_ids`.
  - `positions`: All active users in selected `office_position_ids`.
  - `employees`: Specific `user_ids` (forces generation of a fresh assignment even if an active one exists).

#### Client-Side PDF Exports (`resources/js/utils/trainingPdfExport.js`)
- **Catalog & Documents PDF (`exportTrainingCurriculumPdf`)**: Exports a single training or the full active catalog along with linked company documents.
- **Training Matrix PDF (`exportTrainingMatrixPdf`)**: Exports an organization-wide compliance matrix mapping active users against active trainings.

---

### 3.4 Office Positions (`/training/office-positions`)
- Create, edit, and delete `OfficePosition` records (`name`, `description`).
- **Bulk User Assignment**: Select active employees to assign to a position. Saving triggers `UserTrainingObserver::updated`, which automatically evaluates `training_scopes` and assigns any matching active trainings to newly assigned users.
- Deleting a position safely clears `office_position_id` on affected users before removing the position.

---

### 3.5 Training Sessions & Attendance (`/training/sessions`)
Manages instructor-led or virtual group training sessions.

#### Session Lifecycle & Approval Gate
- **Statuses**: `PENDING` → `OPEN` → `IN_PROGRESS` → `COMPLETED` (or `CANCELLED`).
- **Multi-Day Schedule Slots**: Sessions support structured `schedule_slots` JSON entries, each with an individual `PENDING` or `COMPLETED` status (`updateSlotStatus`).
- **Trainer Approval (`approveSession`)**: Records `approved_by`, `approved_at`, and `approval_notes`, and transitions `PENDING` sessions to `OPEN`. For `FULL_TRAINING` assignments, **the employee's test remains locked until the session is approved**.

#### Participant & Attendance Management
- **Participants**: Add or remove participants while the session is not locked/finished (`addParticipant`, `removeParticipant`). Removing a participant also cleans up draft unsubmitted test attempts and orphaned assignments with no submitted results.
- **Attendance Statuses**: `REGISTERED`, `ATTENDED`, `ABSENT`, `EXCUSED`, plus per-day `daily_attendance` tracking.
- **Completion Trigger**: Marking a participant as `ATTENDED` checks if they have already passed the associated test; if so, the `TrainingAssignment` immediately transitions to `COMPLETED`.
- **PDF Announcement (`exportSessionAnnouncementPdf`)**: Generates a printable session announcement sheet for departments/participants.

---

### 3.6 Test & Question Builder (`/training/trainings/{training}/test-builder`)
Each training has an associated `Test` record (auto-created with default settings if missing when opening the builder).

#### Test Settings
- `title`, `description`, `passing_score` (0–100%), `attempt_limit` (minimum 1), and `status` (`active`, `draft`, `inactive`).

#### Question Bank Scopes
Questions are stored in `test_questions` and linked to tests via the `test_has_questions` pivot table (storing `marks`, `sort_order`, and `is_mandatory`):
- **`catalog`**: Questions owned specifically by this training's test.
- **`document`**: Questions linked to a `CompanyDocument` (`company_document_id`) with an optional `document_section_reference`. Attaching a document question automatically links that document to the training.
- **`global`**: Reusable organization-wide questions shared across multiple tests.

#### Question Types & Rules
- `MULTIPLE_CHOICE`: Single correct option.
- `TRUE_FALSE`: Two options; option order is preserved during test delivery.
- `MULTI_SELECT`: Multiple correct options; graded with an **exact-match rule** (the trainee must select *all* correct options and *zero* incorrect options to earn marks).
- Every question requires at least 2 options and at least 1 option marked `is_correct = true`.

---

### 3.7 Employee Portal: My Trainings & Assessments (`/training/my-trainings`)
Employees view all their assigned trainings sorted by urgency (`PENDING`, `IN_PROGRESS`, `OVERDUE`, `FAILED`, `COMPLETED`, `EXPIRED`) and `due_date`.

#### Taking an Assessment (`/training/assignments/{assignment}/tests/{test}/take`)
- **Access & Lock Gates (`FULL_TRAINING`)**:
  - Blocked (`403`) if the employee was marked `ABSENT` in their latest session.
  - Blocked (`403`) until the training session is approved (`approved_at !== null` or status `APPROVED`/`COMPLETED`).
- **Anti-Cheating Measures**:
  - Question cards are shuffled on every page load.
  - Options for `MULTIPLE_CHOICE` and `MULTI_SELECT` are shuffled (`TRUE_FALSE` retains logical order).
  - The `is_correct` flag is stripped from option payloads while taking the test.
- **Evaluation (`TestEvaluationService`)**:
  - Enforces `attempt_limit`; exceeding the limit marks an uncompleted assignment as `FAILED`.
  - Calculates `score`, `max_score`, `percentage`, and `result` (`PASSED` or `FAILED`).
  - **3-Tier Completion Rule**:
    - `PASSED` + (`TEST_ONLY` or `ATTENDED` session) → Assignment marked `COMPLETED` (`completed_at` stamped).
    - `PASSED` + session attendance not yet confirmed → Assignment remains `IN_PROGRESS` until trainer marks `ATTENDED`.
    - `FAILED` → If attempts remain, status stays `IN_PROGRESS`; if `attempt_limit` is reached, status becomes `FAILED`. In both failure cases, `attachToRemedialSession()` automatically enrolls the employee into a shared `PENDING` remedial session (`{SESSION_CODE}-REM-01`).

---

### 3.8 Printable Scorecard & Certificate (`/training/assignments/{assignment}/scorecard`)
- Renders an official, print-optimized (`@media print`) training scorecard and certificate for the employee, including training details, linked document competency domains, session attendance, and assessment results.
- Includes a floating **Print / Save PDF** action (`window.print()`).
- Access is restricted to the assignment owner or users with `training.catalog.view`.

---

## 4. Automation & Lifecycle Rules

### 4.1 Automatic Onboarding (`UserTrainingObserver`)
Registered in `EventServiceProvider` on `App\Models\User`:
- **`created`**: If the new user has a `department_id`, `TrainingAssignmentService::assignNewUser()` finds all `active` trainings whose `training_scopes` match the user's `department_id` (either whole-department scopes where `office_position_id IS NULL`, or exact `office_position_id` matches).
- **`updated`**: Fires whenever `department_id` or `office_position_id` changes on an active user, assigning any newly matched trainings and linking them to an existing `PENDING`/`OPEN` session (or provisioning Session #1).

### 4.2 Recurring Retraining & Overdue Detection (`training:check-due`)
Run via Artisan command `php artisan training:check-due` (`CheckTrainingDueCommand`):
1. Scans all `COMPLETED` assignments whose training has `retrain_interval > 0`.
2. Computes the next due date using `retrain_unit` (`day`, `month`, or `year`) from `completed_at`.
3. If no newer assignment exists and the next due date is **within 30 days** (or already past), creates a `RETRAINING` trigger and generates a new `TrainingAssignment`.
4. Marks any `PENDING` or `IN_PROGRESS` assignments with `due_date < today` as `OVERDUE`.

### 4.3 Assignment Statuses
| Status | Meaning |
| :--- | :--- |
| `PENDING` | Assigned; no test attempt started and attendance not yet confirmed. |
| `IN_PROGRESS` | Test attempt started, or test passed while awaiting session attendance, or failed with attempts remaining. |
| `COMPLETED` | Test passed **and** session attended (or `TEST_ONLY` mode). |
| `FAILED` | Maximum `attempt_limit` reached without passing. |
| `OVERDUE` | Past `due_date` while still `PENDING` or `IN_PROGRESS`. |
| `EXPIRED` | Superseded by a newly generated manual/re-test assignment. |

---

## 5. Database Schema Reference

| Table | Purpose |
| :--- | :--- |
| `office_positions` | Job/office position definitions (`name`, `description`). |
| `training_categories` | Course categories (`name`). |
| `trainings` | Master training catalog (`code`, `title`, `retrain_interval`, `retrain_unit`, `duration_days`, `passing_score`, `status`). |
| `training_scopes` | Target audience mapping (`training_id`, `department_id`, nullable `office_position_id`). |
| `training_company_documents` | Pivot linking `trainings` to `company_documents`. |
| `training_triggers` | Audit record of why an assignment was created (`MANUAL`, `NEW_USER`, `RETRAINING`, `source_type`, `source_id`, `reason`). |
| `training_assignments` | Individual employee training obligations (`training_id`, `user_id`, `assignment_type`, `due_date`, `status`, `completed_at`). |
| `training_sessions` | Scheduled group/remedial sessions (`session_code`, `parent_session_id`, `trainer_id`, `schedule_slots`, `approved_by`, `approved_at`, `status`). |
| `training_session_participants` | Session roster & attendance (`training_session_id`, `training_assignment_id`, `user_id`, `attendance_status`, `daily_attendance`). |
| `tests` | Assessment configuration per training (`passing_score`, `attempt_limit`, `status`). |
| `test_questions` | Question bank (`scope`: `catalog`/`document`/`global`, `company_document_id`, `question_type`, `marks`). |
| `test_has_questions` | Pivot attaching questions to tests (`test_id`, `test_question_id`, `marks`, `sort_order`, `is_mandatory`). |
| `test_options` | Answer choices (`test_question_id`, `answer`, `is_correct`, `sort_order`). |
| `test_attempts` | Employee test submissions (`attempt_number`, `score`, `max_score`, `percentage`, `result`, `started_at`, `submitted_at`). |
| `test_answers` | Per-question responses (`selected_option_id`, `selected_option_ids` JSON, `is_correct`, `marks_obtained`). |

---

## 6. Permissions Reference

| Permission | Controls |
| :--- | :--- |
| `training.catalog.view` | View Training Catalog, view any attempt/scorecard, global Skill Matrix access. |
| `training.catalog.create` | Create new training catalog entries. |
| `training.catalog.update` | Edit trainings, sync scopes/documents, trigger manual assignments. |
| `training.catalog.delete` | Delete trainings and uncompleted/non-passed assignments. |
| `training.attempt.view` | View employee test attempt histories and global Skill Matrix access. |
| `training-session.read` | View Sessions & Attendance page. |
| `training-session.create` | Create training sessions. |
| `training-session.update` | Edit sessions, update slots/status, approve sessions, manage participants, record attendance. |
| `training-session.delete` | Delete training sessions and assignments. |
| `test-question.view` / `.create` / `.update` / `.delete` | Manage questions in the Test & Question Builder. |
| `global-scope.view` / `department-scope.view` / `branch-scope.view` | Controls employee visibility scope on the Skill Matrix page and endpoints. |

---

## 7. Codebase File Map

- **Controllers (`app/Http/Controllers/Training/`)**:
  - `TrainingComplianceController.php`
  - `TrainingSkillMatrixController.php`
  - `TrainingController.php`
  - `OfficePositionController.php`
  - `TrainingSessionController.php`
  - `TestController.php`
  - `TrainingEmployeeController.php`
  - `TrainingScorecardController.php`
- **Services (`app/Services/Training/`)**:
  - `TrainingAssignmentService.php`
  - `TestEvaluationService.php`
- **Observer & Command**:
  - `app/Observers/UserTrainingObserver.php`
  - `app/Console/Commands/CheckTrainingDueCommand.php`
- **Frontend Pages (`resources/js/Pages/Training/`)**:
  - `Dashboard/Compliance.jsx`
  - `SkillMatrix/Index.jsx`
  - `Trainings/Index.jsx`
  - `OfficePositions/Index.jsx`
  - `Sessions/Index.jsx`
  - `Tests/Builder.jsx`
  - `Employee/MyTrainings.jsx`
  - `Employee/TakeTest.jsx`
  - `Scorecard.jsx`
- **Frontend Components & Utilities**:
  - `resources/js/components/Training/EmployeeSkillMatrixInline.jsx`
  - `resources/js/components/Training/EmployeeSkillMatrixModal.jsx`
  - `resources/js/components/Training/TrainingScopeModal.jsx`
  - `resources/js/utils/trainingPdfExport.js`
