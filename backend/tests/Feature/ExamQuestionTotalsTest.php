<?php

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('exam total_questions and total_marks automatically sync when questions are added or removed', function () {
    $user = User::create([
        'first_name' => 'Prof',
        'last_name' => 'Smith',
        'email' => 'prof.smith@university.edu',
        'password' => 'secret123',
    ]);

    $exam = Exam::create([
        'title' => 'Sample Test',
        'course_offering_id' => 1,
        'type' => 'MIDTERM',
        'duration_minutes' => 60,
        'status' => 'draft',
        'created_by' => $user->id,
        'total_questions' => 0,
        'total_marks' => 0,
    ]);

    $q1 = Question::create([
        'course_id' => 1,
        'created_by' => $user->id,
        'type' => 'mcq',
        'content' => 'Question 1',
        'difficulty' => 'easy',
        'status' => 'active',
    ]);

    $q2 = Question::create([
        'course_id' => 1,
        'created_by' => $user->id,
        'type' => 'mcq',
        'content' => 'Question 2',
        'difficulty' => 'easy',
        'status' => 'active',
    ]);

    // Adding questions via ExamQuestion model event
    $eq1 = ExamQuestion::create([
        'exam_id' => $exam->id,
        'question_id' => $q1->id,
        'marks' => 2,
    ]);

    $exam->refresh();
    expect($exam->total_questions)->toBe(1)
        ->and($exam->total_marks)->toBe(2);

    $eq2 = ExamQuestion::create([
        'exam_id' => $exam->id,
        'question_id' => $q2->id,
        'marks' => 3,
    ]);

    $exam->refresh();
    expect($exam->total_questions)->toBe(2)
        ->and($exam->total_marks)->toBe(5);

    // Deleting a question
    $eq1->delete();
    $exam->refresh();
    expect($exam->total_questions)->toBe(1)
        ->and($exam->total_marks)->toBe(3);
});

test('exam accessors self-heal stale total_questions and total_marks database columns', function () {
    $user = User::create([
        'first_name' => 'Prof',
        'last_name' => 'Smith',
        'email' => 'prof2.smith@university.edu',
        'password' => 'secret123',
    ]);

    $exam = Exam::create([
        'title' => 'Stale Test',
        'course_offering_id' => 1,
        'type' => 'FINAL',
        'duration_minutes' => 90,
        'status' => 'draft',
        'created_by' => $user->id,
        'total_questions' => 4, // Stale value in DB!
        'total_marks' => 6,      // Stale value in DB!
    ]);

    // Create 3 questions
    for ($i = 1; $i <= 3; $i++) {
        $q = Question::create([
            'course_id' => 1,
            'created_by' => $user->id,
            'type' => 'mcq',
            'content' => "Question {$i}",
            'difficulty' => 'medium',
            'status' => 'active',
        ]);

        // Insert directly to bypass model events and simulate stale data
        DB::table('exam_questions')->insert([
            'exam_id' => $exam->id,
            'question_id' => $q->id,
            'marks' => 5,
        ]);
    }

    // Exam in DB currently has total_questions = 4, but 3 rows in exam_questions
    $staleExam = Exam::find($exam->id);
    expect($staleExam->getRawOriginal('total_questions'))->toBe(4);

    // Accessing total_questions triggers self-healing
    expect($staleExam->total_questions)->toBe(3)
        ->and($staleExam->total_marks)->toBe(15);

    // Verify DB was healed
    $healed = Exam::find($exam->id);
    expect($healed->getRawOriginal('total_questions'))->toBe(3)
        ->and($healed->getRawOriginal('total_marks'))->toBe(15);
});
