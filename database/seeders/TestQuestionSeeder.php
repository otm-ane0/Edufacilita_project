<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Support\Facades\Storage;

class TestQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a dummy document file for testing
        $dummyDocContent = "This is a test document content for question.";
        Storage::disk('local')->put('questions/documents/test_question.docx', $dummyDocContent);
        Storage::disk('local')->put('questions/answer_documents/test_answer.docx', $dummyDocContent);

        // Check if we have subjects and topics, if not create them
        $subject = Subject::firstOrCreate(['name' => 'Mathematics']);

        $topic = Topic::firstOrCreate(
            ['name' => 'Algebra', 'subject_id' => $subject->id]
        );

        // Create test questions with LaTeX content
        $questions = [
            [
                'question' => 'Solve for x in the equation: 2x + 5 = 13',
                'latex_question' => '2x + 5 = 13',
                'answer' => 'To solve: 2x + 5 = 13, subtract 5 from both sides: 2x = 8, then divide by 2: x = 4',
                'latex_answer' => '2x + 5 = 13 \\\\ 2x = 13 - 5 \\\\ 2x = 8 \\\\ x = \\frac{8}{2} = 4',
                'difficulty' => 'easy',
                'question_type' => 'Open Ended',
                'education_level' => 'Middle School',
                'institution' => 'Test Institution',
                'source' => 'Test Source',
                'year' => 2024,
                'region' => 'Test Region',
                'uf' => 'SP'
            ],
            [
                'question' => 'Find the derivative of f(x) = x² + 3x + 2',
                'latex_question' => 'f(x) = x^2 + 3x + 2',
                'answer' => 'The derivative is f\'(x) = 2x + 3',
                'latex_answer' => 'f\'(x) = \\frac{d}{dx}(x^2 + 3x + 2) = 2x + 3',
                'difficulty' => 'medium',
                'question_type' => 'Open Ended',
                'education_level' => 'High School',
                'institution' => 'Test Institution',
                'source' => 'Test Source',
                'year' => 2024,
                'region' => 'Test Region',
                'uf' => 'SP'
            ],
            [
                'question' => 'What is the integral of sin(x)?',
                'latex_question' => '\\int \\sin(x) \\, dx = ?',
                'answer' => 'The integral of sin(x) is -cos(x) + C',
                'latex_answer' => '\\int \\sin(x) \\, dx = -\\cos(x) + C',
                'difficulty' => 'hard',
                'question_type' => 'Open Ended',
                'education_level' => 'University',
                'institution' => 'Test Institution',
                'source' => 'Test Source',
                'year' => 2024,
                'region' => 'Test Region',
                'uf' => 'SP'
            ]
        ];

        foreach ($questions as $questionData) {
            Question::create([
                'question' => $questionData['question'],
                'latex_question' => $questionData['latex_question'],
                'answer' => $questionData['answer'],
                'latex_answer' => $questionData['latex_answer'],
                'difficulty' => $questionData['difficulty'],
                'question_type' => $questionData['question_type'],
                'education_level' => $questionData['education_level'],
                'topic_id' => $topic->id,
                'institution' => $questionData['institution'],
                'source' => $questionData['source'],
                'year' => $questionData['year'],
                'region' => $questionData['region'],
                'uf' => $questionData['uf'],
                'doc' => 'questions/documents/test_question.docx',
                'answer_doc' => 'questions/answer_documents/test_answer.docx',
            ]);
        }

        $this->command->info('Created ' . count($questions) . ' test questions with LaTeX content.');
    }
}
