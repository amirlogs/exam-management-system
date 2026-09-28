<?php

use App\Jobs\QuestionGuidanceJob;
use App\Models\PracticeExam;
use App\Models\PracticeQuestion;
use App\Models\PracticeQuestionHistory;
use App\Models\PracticeQuestionOption;
use App\Models\QuestionGuidances;
use App\Models\User;
use App\Services\QuestionGuidanceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;

uses(RefreshDatabase::class);

test('question guidance generator includes all options without student answer in prompt', function () {
    $user = User::create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@test.edu',
        'password' => 'secret123',
    ]);
    $exam = PracticeExam::create([
        'owned_by' => $user->id,
        'title' => 'Biology Practice',
        'status' => 'active',
        'duration_minutes' => 30,
        'total_marks' => 10,
    ]);

    $hist = PracticeQuestionHistory::create([
        'owned_by' => $user->id,
        'input' => 'Biology prompt',
        'count' => 1,
        'type' => 'mcq',
        'difficulty' => 'easy',
        'status' => 'completed',
        'practice_exam_id' => $exam->id,
    ]);

    $question = PracticeQuestion::create([
        'owned_by' => $user->id,
        'practice_exam_id' => $exam->id,
        'practice_question_histories_id' => $hist->id,
        'type' => 'mcq',
        'content' => 'What is the powerhouse of the cell?',
        'difficulty' => 'easy',
        'status' => 'draft',
    ]);

    $optA = PracticeQuestionOption::create([
        'practice_question_id' => $question->id,
        'option_text' => 'Ribosome',
        'is_correct' => false,
    ]);
    $optB = PracticeQuestionOption::create([
        'practice_question_id' => $question->id,
        'option_text' => 'Mitochondria',
        'is_correct' => true,
    ]);
    $optC = PracticeQuestionOption::create([
        'practice_question_id' => $question->id,
        'option_text' => 'Nucleus',
        'is_correct' => false,
    ]);
    $optD = PracticeQuestionOption::create([
        'practice_question_id' => $question->id,
        'option_text' => 'Chloroplast',
        'is_correct' => false,
    ]);

    $fake = Prism::fake([
        TextResponseFake::make()->withText('Option D (Chloroplast) is found in plant cells for photosynthesis, whereas mitochondria is the powerhouse of the cell.'),
    ]);

    $generator = new QuestionGuidanceGenerator;
    $response = $generator->generate(
        prompt: 'why not the answer is not d',
        question: $question,
        history: collect(),
    );

    expect($response)->toContain('Chloroplast');

    $fake->assertRequest(function (array $requests) {
        expect($requests)->toHaveCount(1);
        $prompt = $requests[0]->prompt();
        expect($prompt)
            ->toContain('What is the powerhouse of the cell?')
            ->toContain('Option A: Ribosome')
            ->toContain('Option B: Mitochondria [Official Correct Answer]')
            ->toContain('Option D: Chloroplast')
            ->toContain('why not the answer is not d')
            ->not->toContain('Student\'s Submitted Attempt')
            ->not->toContain('Selected:');
    });
});

test('question guidance job executes and updates guidance record with response', function () {
    $user = User::create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@test.edu',
        'password' => 'secret123',
    ]);
    $exam = PracticeExam::create([
        'owned_by' => $user->id,
        'title' => 'Math Practice',
        'status' => 'active',
        'duration_minutes' => 20,
        'total_marks' => 5,
    ]);

    $hist = PracticeQuestionHistory::create([
        'owned_by' => $user->id,
        'input' => 'Math prompt',
        'count' => 1,
        'type' => 'mcq',
        'difficulty' => 'easy',
        'status' => 'completed',
        'practice_exam_id' => $exam->id,
    ]);

    $question = PracticeQuestion::create([
        'owned_by' => $user->id,
        'practice_exam_id' => $exam->id,
        'practice_question_histories_id' => $hist->id,
        'type' => 'mcq',
        'content' => 'What is 2 + 2?',
        'difficulty' => 'easy',
        'status' => 'draft',
    ]);

    PracticeQuestionOption::create([
        'practice_question_id' => $question->id,
        'option_text' => '3',
        'is_correct' => false,
    ]);
    PracticeQuestionOption::create([
        'practice_question_id' => $question->id,
        'option_text' => '4',
        'is_correct' => true,
    ]);

    $guidance = QuestionGuidances::create([
        'user_id' => $user->id,
        'student_id' => 1,
        'practice_exam_id' => $exam->id,
        'practice_question_id' => $question->id,
        'prompt' => 'why is 4 the answer?',
    ]);

    Prism::fake([
        TextResponseFake::make()->withText('Because adding 2 units to 2 units equals 4.'),
    ]);

    $job = new QuestionGuidanceJob($guidance);
    $job->handle(new QuestionGuidanceGenerator);

    $guidance->refresh();
    expect($guidance->response)->toBe('Because adding 2 units to 2 units equals 4.');
});

test('question guidance includes previous history turns in chronological order', function () {
    $user = User::create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane.doe@test.edu',
        'password' => 'secret123',
    ]);
    $exam = PracticeExam::create([
        'owned_by' => $user->id,
        'title' => 'History Practice',
        'status' => 'active',
        'duration_minutes' => 15,
        'total_marks' => 5,
    ]);

    $hist = PracticeQuestionHistory::create([
        'owned_by' => $user->id,
        'input' => 'History prompt',
        'count' => 1,
        'type' => 'mcq',
        'difficulty' => 'medium',
        'status' => 'completed',
        'practice_exam_id' => $exam->id,
    ]);

    $question = PracticeQuestion::create([
        'owned_by' => $user->id,
        'practice_exam_id' => $exam->id,
        'practice_question_histories_id' => $hist->id,
        'type' => 'mcq',
        'content' => 'In which year did WW2 end?',
        'difficulty' => 'medium',
        'status' => 'draft',
    ]);

    PracticeQuestionOption::create([
        'practice_question_id' => $question->id,
        'option_text' => '1943',
        'is_correct' => false,
    ]);
    PracticeQuestionOption::create([
        'practice_question_id' => $question->id,
        'option_text' => '1945',
        'is_correct' => true,
    ]);

    // Create prior completed guidance
    $prevGuidance = QuestionGuidances::create([
        'user_id' => $user->id,
        'student_id' => 1,
        'practice_exam_id' => $exam->id,
        'practice_question_id' => $question->id,
        'prompt' => 'Can you give me a hint?',
        'response' => 'Think about the mid 1940s after the Pacific campaign concluded.',
    ]);

    // Create follow-up guidance
    $followUpGuidance = QuestionGuidances::create([
        'user_id' => $user->id,
        'student_id' => 1,
        'practice_exam_id' => $exam->id,
        'practice_question_id' => $question->id,
        'prompt' => 'So is it 1945?',
    ]);

    $fake = Prism::fake([
        TextResponseFake::make()->withText('Yes, exactly! WW2 concluded in 1945.'),
    ]);

    $job = new QuestionGuidanceJob($followUpGuidance);
    $job->handle(new QuestionGuidanceGenerator);

    $fake->assertRequest(function (array $requests) {
        $prompt = $requests[0]->prompt();
        expect($prompt)
            ->toContain('Student: Can you give me a hint?')
            ->toContain('Tutor: Think about the mid 1940s after the Pacific campaign concluded.')
            ->toContain('Student\'s Inquiry:')
            ->toContain('So is it 1945?');
    });

    $followUpGuidance->refresh();
    expect($followUpGuidance->response)->toBe('Yes, exactly! WW2 concluded in 1945.');
});
