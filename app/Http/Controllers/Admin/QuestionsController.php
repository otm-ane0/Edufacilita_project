<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class QuestionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.questions.index', [
            'questions' => Question::with(['topic.subject'])->paginate(10)->sortByDesc('created_at'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $subjects = Subject::all();
        return view('admin.questions.create', compact('subjects'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'options' => 'required|string',
            'option_images' => 'nullable|array',
            'option_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'text_options' => 'nullable|array',
            'text_options.*' => 'nullable|string',
            'answer' => 'required|string',
            'answer_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'question_type' => ['required', Rule::in(['Multiple Choice', 'True/False', 'Open Ended', 'Fill in the Blank'])],
            'education_level' => ['required', Rule::in(['Elementary', 'Middle School', 'High School', 'University'])],
            'topic_id' => 'required|exists:topics,id',
            'institution' => 'required|string|max:255',
            'source' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'region' => 'required|string|max:255',
            'uf' => 'required|string|size:2',
            'doc' => 'required|file|mimes:doc,docx|max:10240', // 10MB max for documents
            'answer_doc' => 'required|file|mimes:doc,docx|max:10240', // 10MB max for answer documents
        ]);

        // Handle main image upload (stored in public storage)
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('questions/images', 'public');
        }

        // Handle option images upload
        $optionImagePaths = [];
        if ($request->hasFile('option_images')) {
            foreach ($request->file('option_images') as $index => $optionImage) {
                if ($optionImage && $optionImage->isValid()) {
                    $optionImagePaths[$index] = $optionImage->store('questions/option_images', 'public');
                } else {
                    $optionImagePaths[$index] = null;
                }
            }
            // Remove null values but keep the indexing intact for the options that do have images
            $optionImagePaths = array_filter($optionImagePaths, function($value) {
                return $value !== null;
            });
        }

        // Handle text options processing
        $textOptions = [];
        if ($request->has('text_options')) {
            foreach ($request->input('text_options') as $index => $textOption) {
                if ($textOption && trim($textOption) !== '') {
                    $textOptions[$index] = trim($textOption);
                }
            }
        }

        // Handle answer image upload
        $answerImagePath = null;
        if ($request->hasFile('answer_image')) {
            $answerImagePath = $request->file('answer_image')->store('questions/answer_images', 'public');
        }

        // Handle document upload (stored in private storage for security) - Document is required
        $docPath = $request->file('doc')->store('questions/documents', 'local');

        // Handle answer document upload (stored in private storage for security) - Answer Document is required
        $answerDocPath = $request->file('answer_doc')->store('questions/answer_documents', 'local');

        // Create the question
        $question = Question::create([
            'question' => $validated['question'],
            'image' => $imagePath,
            'options' => $validated['options'],
            'options_images' => !empty($optionImagePaths) ? $optionImagePaths : null,
            'text_options' => !empty($textOptions) ? $textOptions : null,
            'answer' => $validated['answer'],
            'answer_image' => $answerImagePath,
            'difficulty' => $validated['difficulty'],
            'question_type' => $validated['question_type'],
            'education_level' => $validated['education_level'],
            'topic_id' => $validated['topic_id'],
            'institution' => $validated['institution'],
            'source' => $validated['source'],
            'year' => $validated['year'],
            'region' => $validated['region'],
            'uf' => strtoupper($validated['uf']),
            'doc' => $docPath,
            'answer_doc' => $answerDocPath,
        ]);

        return redirect()
            ->route('admin.questions.index', $question)
            ->with('success', 'Question created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Question $question)
    {
        $question->load(['topic.subject']);
        return view('admin.questions.show', [
            'question' => $question,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Question $question)
    {
        $subjects = Subject::all();
        $question->load(['topic.subject']);
        return view('admin.questions.edit', [
            'question' => $question,
            'subjects' => $subjects,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'options' => 'required|string',
            'option_images' => 'nullable|array',
            'option_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'text_options' => 'nullable|array',
            'text_options.*' => 'nullable|string',
            'answer' => 'required|string',
            'answer_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'question_type' => ['required', Rule::in(['Multiple Choice', 'True/False', 'Open Ended', 'Fill in the Blank'])],
            'education_level' => ['required', Rule::in(['Elementary', 'Middle School', 'High School', 'University'])],
            'topic_id' => 'required|exists:topics,id',
            'institution' => 'required|string|max:255',
            'source' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'region' => 'required|string|max:255',
            'uf' => 'required|string|size:2',
            'doc' => 'nullable|file|mimes:doc,docx|max:10240', // Optional on update
            'answer_doc' => 'nullable|file|mimes:doc,docx|max:10240', // Optional on update
        ]);

        // Handle main image upload
        $imagePath = $question->image;
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($question->image) {
                Storage::disk('public')->delete($question->image);
            }
            $imagePath = $request->file('image')->store('questions/images', 'public');
        }

        // Handle option images upload
        $optionImagePaths = $question->options_images ?? [];
        if ($request->hasFile('option_images')) {
            // Delete old option images if they exist
            if ($question->options_images) {
                foreach ($question->options_images as $oldOptionImage) {
                    if ($oldOptionImage) {
                        Storage::disk('public')->delete($oldOptionImage);
                    }
                }
            }
            
            $optionImagePaths = [];
            foreach ($request->file('option_images') as $index => $optionImage) {
                if ($optionImage && $optionImage->isValid()) {
                    $optionImagePaths[$index] = $optionImage->store('questions/option_images', 'public');
                } else {
                    $optionImagePaths[$index] = null;
                }
            }
            // Remove null values but keep the indexing intact for the options that do have images
            $optionImagePaths = array_filter($optionImagePaths, function($value) {
                return $value !== null;
            });
        }

        // Handle text options processing
        $textOptions = $question->text_options ?? [];
        if ($request->has('text_options')) {
            $textOptions = [];
            foreach ($request->input('text_options') as $index => $textOption) {
                if ($textOption && trim($textOption) !== '') {
                    $textOptions[$index] = trim($textOption);
                }
            }
        }

        // Handle answer image upload
        $answerImagePath = $question->answer_image;
        if ($request->hasFile('answer_image')) {
            // Delete old answer image if exists
            if ($question->answer_image) {
                Storage::disk('public')->delete($question->answer_image);
            }
            $answerImagePath = $request->file('answer_image')->store('questions/answer_images', 'public');
        }

        // Handle document upload
        $docPath = $question->doc;
        if ($request->hasFile('doc')) {
            // Delete old document if exists
            if ($question->doc) {
                Storage::disk('local')->delete($question->doc);
            }
            $docPath = $request->file('doc')->store('questions/documents', 'local');
        }

        // Handle answer document upload
        $answerDocPath = $question->answer_doc;
        if ($request->hasFile('answer_doc')) {
            // Delete old answer document if exists
            if ($question->answer_doc) {
                Storage::disk('local')->delete($question->answer_doc);
            }
            $answerDocPath = $request->file('answer_doc')->store('questions/answer_documents', 'local');
        }

        // Ensure document is always present (either existing or newly uploaded)
        if (!$docPath) {
            return redirect()->back()->withErrors(['doc' => 'A document is required for this question.'])->withInput();
        }

        // Ensure answer document is always present (either existing or newly uploaded)
        if (!$answerDocPath) {
            return redirect()->back()->withErrors(['answer_doc' => 'An answer document is required for this question.'])->withInput();
        }

        // Update the question
        $question->update([
            'question' => $validated['question'],
            'image' => $imagePath,
            'options' => $validated['options'],
            'options_images' => !empty($optionImagePaths) ? $optionImagePaths : null,
            'text_options' => !empty($textOptions) ? $textOptions : null,
            'answer' => $validated['answer'],
            'answer_image' => $answerImagePath,
            'difficulty' => $validated['difficulty'],
            'question_type' => $validated['question_type'],
            'education_level' => $validated['education_level'],
            'topic_id' => $validated['topic_id'],
            'institution' => $validated['institution'],
            'source' => $validated['source'],
            'year' => $validated['year'],
            'region' => $validated['region'],
            'uf' => strtoupper($validated['uf']),
            'doc' => $docPath,
            'answer_doc' => $answerDocPath,
        ]);

        return redirect()
            ->route('admin.questions.index', $question)
            ->with('success', 'Question updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question)
    {
        // Delete associated files
        if ($question->image) {
            Storage::disk('public')->delete($question->image);
        }

        // Delete option images
        if ($question->options_images) {
            foreach ($question->options_images as $optionImage) {
                if ($optionImage) {
                    Storage::disk('public')->delete($optionImage);
                }
            }
        }

        // Delete answer image
        if ($question->answer_image) {
            Storage::disk('public')->delete($question->answer_image);
        }

        if ($question->doc) {
            Storage::disk('local')->delete($question->doc);
        }

        if ($question->answer_doc) {
            Storage::disk('local')->delete($question->answer_doc);
        }

        // Delete the question
        $question->delete();

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question deleted successfully!');
    }

    /**
     * Download the question and answer documents as a ZIP file
     */
    public function downloadDocument(Question $question)
    {
        if (!$question->doc || !Storage::disk('local')->exists($question->doc)) {
            abort(404, 'Question document not found.');
        }

        if (!$question->answer_doc || !Storage::disk('local')->exists($question->answer_doc)) {
            abort(404, 'Answer document not found.');
        }

        try {
            $questionDocPath = Storage::disk('local')->path($question->doc);
            $answerDocPath = Storage::disk('local')->path($question->answer_doc);

            // Create temporary ZIP file
            $zipFileName = 'question_' . $question->id . '_documents_' . time() . '.zip';
            $tempZipPath = storage_path('app/temp/' . $zipFileName);

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Create ZIP archive
            $zip = new \ZipArchive();
            if ($zip->open($tempZipPath, \ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Cannot create ZIP file');
            }

            // Add question document to ZIP
            $questionFileName = 'Question.docx';
            $zip->addFile($questionDocPath, $questionFileName);

            // Add answer document to ZIP
            $answerFileName = 'Answer.docx';
            $zip->addFile($answerDocPath, $answerFileName);

            // Close ZIP file
            $zip->close();

            $downloadFileName = 'question_' . $question->id . '_documents.zip';

            // Return download response and delete temp file after download
            return response()->download($tempZipPath, $downloadFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Document ZIP creation error: ' . $e->getMessage());
            abort(500, 'Error creating ZIP file: ' . $e->getMessage());
        }
    }
}
