<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CompanyDocument;
use App\Models\CompanyDocumentType;
use App\Models\Department;
use App\Models\Position;
use App\Models\Training\Test;
use App\Models\Training\TestOption;
use App\Models\Training\TestQuestion;
use App\Models\Training\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DocumentTestQuestionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $trainer;
    protected User $regularUser;
    protected Department $department;
    protected CompanyDocumentType $documentType;
    protected CompanyDocument $document;
    protected Training $trainingA;
    protected Training $trainingB;

    protected function setUp(): void
    {
        parent::setUp();

        config(['scout.driver' => null]);

        // Ensure permissions exist
        Permission::firstOrCreate(['name' => 'test-question.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'test-question.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'test-question.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'test-question.delete', 'guard_name' => 'web']);

        $branch = Branch::firstOrCreate(['name' => 'HQ Branch']);
        $position = Position::firstOrCreate(['name' => 'Trainer']);
        $this->department = Department::firstOrCreate(['name' => 'Operations']);
        $this->documentType = CompanyDocumentType::firstOrCreate(['name' => 'SOP']);

        $this->trainer = User::create([
            'name' => 'Test Trainer',
            'email' => 'trainer.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'position_id' => $position->id,
            'branch_id' => $branch->id,
            'department_id' => $this->department->id,
        ]);
        $this->trainer->givePermissionTo([
            'test-question.view',
            'test-question.create',
            'test-question.update',
            'test-question.delete',
        ]);

        $this->regularUser = User::create([
            'name' => 'Regular User',
            'email' => 'regular.' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'position_id' => $position->id,
            'branch_id' => $branch->id,
            'department_id' => $this->department->id,
        ]);

        $this->document = CompanyDocument::create([
            'title' => 'SOP-001 Warehouse Safety Protocol',
            'body' => '<p>Section 1: Always wear helmets. Section 2: Report hazards.</p>',
            'company_document_type_id' => $this->documentType->id,
            'department_id' => $this->department->id,
            'created_by' => $this->trainer->id,
        ]);

        $this->trainingA = Training::create([
            'code' => 'TR-A-' . uniqid(),
            'title' => 'New Hire Onboarding Course',
            'retrain_interval' => 1,
            'retrain_unit' => 'year',
            'passing_score' => 80,
            'status' => 'active',
            'created_by' => $this->trainer->id,
        ]);
        $this->trainingA->companyDocuments()->attach($this->document->id);

        $this->trainingB = Training::create([
            'code' => 'TR-B-' . uniqid(),
            'title' => 'Annual Safety Refresher',
            'retrain_interval' => 1,
            'retrain_unit' => 'year',
            'passing_score' => 80,
            'status' => 'active',
            'created_by' => $this->trainer->id,
        ]);
        $this->trainingB->companyDocuments()->attach($this->document->id);
    }

    public function test_permission_gates_for_document_questions(): void
    {
        // Regular user without test-question.view gets 403
        $response = $this->actingAs($this->regularUser)
            ->getJson(route('document.library.questions.index', $this->document));
        $response->assertStatus(403);

        // Trainer with test-question.view gets 200
        $response = $this->actingAs($this->trainer)
            ->getJson(route('document.library.questions.index', $this->document));
        $response->assertStatus(200);
        $response->assertJson(['document_id' => $this->document->id]);
    }

    public function test_trainer_can_create_and_sync_document_questions(): void
    {
        $payload = [
            'questions' => [
                [
                    'id' => null,
                    'question' => 'What is required in Section 1 of the Warehouse Safety SOP?',
                    'question_type' => 'MULTIPLE_CHOICE',
                    'marks' => 2.0,
                    'document_section_reference' => 'Section 1',
                    'options' => [
                        ['id' => null, 'answer' => 'Wear helmets', 'is_correct' => true],
                        ['id' => null, 'answer' => 'Wear sandals', 'is_correct' => false],
                    ],
                ],
                [
                    'id' => null,
                    'question' => 'Hazard reporting is optional.',
                    'question_type' => 'TRUE_FALSE',
                    'marks' => 1.0,
                    'document_section_reference' => 'Section 2',
                    'options' => [
                        ['id' => null, 'answer' => 'True', 'is_correct' => false],
                        ['id' => null, 'answer' => 'False', 'is_correct' => true],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->trainer)
            ->putJson(route('document.library.questions.sync', $this->document), $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('test_questions', [
            'company_document_id' => $this->document->id,
            'scope' => 'document',
            'question' => 'What is required in Section 1 of the Warehouse Safety SOP?',
        ]);

        $this->assertEquals(2, $this->document->questions()->count());
    }

    public function test_document_questions_propagate_to_referencing_training_catalogs(): void
    {
        // 1. Create a question on the document
        $q = TestQuestion::create([
            'company_document_id' => $this->document->id,
            'scope' => 'document',
            'question' => 'What color helmet is required in warehouse?',
            'question_type' => 'MULTIPLE_CHOICE',
            'marks' => 1.5,
            'document_section_reference' => 'Section 1.1',
            'created_by' => $this->trainer->id,
        ]);
        TestOption::create(['test_question_id' => $q->id, 'answer' => 'Yellow', 'is_correct' => true]);
        TestOption::create(['test_question_id' => $q->id, 'answer' => 'None', 'is_correct' => false]);

        // 2. Both Training A and Training B load this document and its questions automatically
        $loadedTrainingA = Training::with('companyDocuments.questions.options')->find($this->trainingA->id);
        $loadedTrainingB = Training::with('companyDocuments.questions.options')->find($this->trainingB->id);

        $this->assertEquals(1, $loadedTrainingA->companyDocuments->first()->questions->count());
        $this->assertEquals(1, $loadedTrainingB->companyDocuments->first()->questions->count());
        $this->assertEquals('What color helmet is required in warehouse?', $loadedTrainingA->companyDocuments->first()->questions->first()->question);

        // 3. Attach this question to Test A
        $testA = Test::create([
            'training_id' => $this->trainingA->id,
            'title' => 'Test A',
            'passing_score' => 80,
            'attempt_limit' => 3,
            'status' => 'active',
        ]);
        $testA->questions()->attach($q->id, ['marks' => 1.5, 'sort_order' => 1]);

        // 4. Trainer refactors the document question in Document Studio
        $q->update(['question' => 'Updated: Which certified PPE helmet is mandatory?']);

        // 5. Test A immediately reflects the updated question without manual re-editing!
        $testQuestions = $testA->fresh()->questions;
        $this->assertEquals(1, $testQuestions->count());
        $this->assertEquals('Updated: Which certified PPE helmet is mandatory?', $testQuestions->first()->question);
    }

    public function test_detaching_document_question_from_test_preserves_document_question(): void
    {
        $q = TestQuestion::create([
            'company_document_id' => $this->document->id,
            'scope' => 'document',
            'question' => 'Shared SOP question',
            'question_type' => 'TRUE_FALSE',
            'marks' => 1.0,
        ]);
        TestOption::create(['test_question_id' => $q->id, 'answer' => 'True', 'is_correct' => true]);
        TestOption::create(['test_question_id' => $q->id, 'answer' => 'False', 'is_correct' => false]);

        $test = Test::create([
            'training_id' => $this->trainingA->id,
            'title' => 'Test Assessment',
            'passing_score' => 80,
            'attempt_limit' => 3,
            'status' => 'active',
        ]);
        $test->questions()->attach($q->id, ['marks' => 1.0, 'sort_order' => 1]);

        // Save builder with an empty questions array or only catalog questions (detaching $q)
        $catalogQ = TestQuestion::create([
            'test_id' => $test->id,
            'training_id' => $this->trainingA->id,
            'scope' => 'catalog',
            'question' => 'Catalog specific practical test question',
            'question_type' => 'TRUE_FALSE',
            'marks' => 1.0,
        ]);
        TestOption::create(['test_question_id' => $catalogQ->id, 'answer' => 'True', 'is_correct' => true]);
        TestOption::create(['test_question_id' => $catalogQ->id, 'answer' => 'False', 'is_correct' => false]);

        $savePayload = [
            'title' => 'Updated Test Title',
            'description' => 'A test description',
            'passing_score' => 80,
            'attempt_limit' => 3,
            'status' => 'active',
            'questions' => [
                [
                    'id' => $catalogQ->id,
                    'scope' => 'catalog',
                    'question' => $catalogQ->question,
                    'question_type' => 'TRUE_FALSE',
                    'marks' => 1.0,
                    'options' => [
                        ['id' => null, 'answer' => 'True', 'is_correct' => true],
                        ['id' => null, 'answer' => 'False', 'is_correct' => false],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->trainer)
            ->put(route('training.tests.save-builder', $test), $savePayload);

        $response->assertSessionHasNoErrors();

        // The document question still exists in the library!
        $this->assertDatabaseHas('test_questions', ['id' => $q->id]);

        // But is detached from Test A
        $this->assertDatabaseMissing('test_has_questions', [
            'test_id' => $test->id,
            'test_question_id' => $q->id,
        ]);
    }

    public function test_can_convert_catalog_question_to_belong_to_specific_document(): void
    {
        $test = Test::create([
            'training_id' => $this->trainingA->id,
            'title' => 'Initial Assessment',
            'passing_score' => 80,
            'attempt_limit' => 3,
            'status' => 'active',
        ]);

        // Question starts with catalog scope
        $catalogQ = TestQuestion::create([
            'test_id' => $test->id,
            'training_id' => $this->trainingA->id,
            'scope' => 'catalog',
            'question' => 'How to log cash transactions?',
            'question_type' => 'MULTIPLE_CHOICE',
            'marks' => 2.0,
        ]);
        TestOption::create(['test_question_id' => $catalogQ->id, 'answer' => 'In Cashbook', 'is_correct' => true]);
        TestOption::create(['test_question_id' => $catalogQ->id, 'answer' => 'Ignore', 'is_correct' => false]);

        // Trainer in test-builder changes scope to 'document' and selects $this->document->id
        $payload = [
            'title' => 'Updated Assessment',
            'description' => 'Converted question',
            'passing_score' => 80,
            'attempt_limit' => 3,
            'status' => 'active',
            'questions' => [
                [
                    'id' => $catalogQ->id,
                    'scope' => 'document',
                    'company_document_id' => $this->document->id,
                    'document_section_reference' => 'Section 3.4',
                    'question' => 'How to log cash transactions according to SOP-001?',
                    'question_type' => 'MULTIPLE_CHOICE',
                    'marks' => 2.0,
                    'options' => [
                        ['id' => null, 'answer' => 'In Cashbook', 'is_correct' => true],
                        ['id' => null, 'answer' => 'Ignore', 'is_correct' => false],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->trainer)
            ->put(route('training.tests.save-builder', $test), $payload);

        $response->assertSessionHasNoErrors();

        // Verify question in database now belongs to the document
        $catalogQ->refresh();
        $this->assertEquals('document', $catalogQ->scope);
        $this->assertEquals($this->document->id, $catalogQ->company_document_id);
        $this->assertEquals('Section 3.4', $catalogQ->document_section_reference);
        $this->assertEquals('How to log cash transactions according to SOP-001?', $catalogQ->question);

        // Verify it appears in the document's question bank
        $this->assertTrue($this->document->questions->contains('id', $catalogQ->id));

        // Verify it now propagates to Training B which also references this document
        $trainingBDocs = Training::with('companyDocuments.questions')->find($this->trainingB->id);
        $docInTrainingB = $trainingBDocs->companyDocuments->firstWhere('id', $this->document->id);
        $this->assertNotNull($docInTrainingB);
        $this->assertTrue($docInTrainingB->questions->contains('id', $catalogQ->id));
    }
}
