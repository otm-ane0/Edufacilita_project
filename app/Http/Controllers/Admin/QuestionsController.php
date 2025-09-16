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
<<<<<<< HEAD
            'questions' => Question::with(['topic.subject'])->orderBy('created_at', 'desc')->paginate(10),
=======
            'questions' => Question::with(['topic.subject'])->paginate(10)->sortByDesc('created_at'),
>>>>>>> fdaa374b8c473690086850ea4e7af998f74c278c
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
<<<<<<< HEAD
            'image_files.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max per image
            'answer' => 'required|string',
            'latex_question' => 'nullable|string',
            'latex_answer' => 'nullable|string',
=======
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'options' => 'required|string',
<<<<<<< HEAD
            'answer' => 'required|string',
=======
            'option_images' => 'nullable|array',
            'option_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'text_options' => 'nullable|array',
            'text_options.*' => 'nullable|string',
            'answer' => 'required|string',
            'answer_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
>>>>>>> a0c595f5a6fd462401a4dc2125a6b45408cc7c90
>>>>>>> fdaa374b8c473690086850ea4e7af998f74c278c
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

<<<<<<< HEAD
        // Handle multiple image uploads (stored in public storage)
        $imagePaths = [];
        if ($request->hasFile('image_files')) {
            foreach ($request->file('image_files') as $image) {
                $imagePaths[] = $image->store('questions/images', 'public');
            }
        }

=======
<<<<<<< HEAD
        // Handle image upload (stored in public storage)
=======
        // Handle main image upload (stored in public storage)
>>>>>>> a0c595f5a6fd462401a4dc2125a6b45408cc7c90
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('questions/images', 'public');
        }

<<<<<<< HEAD
=======
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

>>>>>>> a0c595f5a6fd462401a4dc2125a6b45408cc7c90
>>>>>>> fdaa374b8c473690086850ea4e7af998f74c278c
        // Handle document upload (stored in private storage for security) - Document is required
        $docPath = $request->file('doc')->store('questions/documents', 'local');

        // Handle answer document upload (stored in private storage for security) - Answer Document is required
        $answerDocPath = $request->file('answer_doc')->store('questions/answer_documents', 'local');

        // Create the question
        $question = Question::create([
            'question' => $validated['question'],
<<<<<<< HEAD
            'images' => $imagePaths,
            'answer' => $validated['answer'],
            'latex_question' => $validated['latex_question'] ?? null,
            'latex_answer' => $validated['latex_answer'] ?? null,
=======
            'image' => $imagePath,
            'options' => $validated['options'],
<<<<<<< HEAD
            'answer' => $validated['answer'],
=======
            'options_images' => !empty($optionImagePaths) ? $optionImagePaths : null,
            'text_options' => !empty($textOptions) ? $textOptions : null,
            'answer' => $validated['answer'],
            'answer_image' => $answerImagePath,
>>>>>>> a0c595f5a6fd462401a4dc2125a6b45408cc7c90
>>>>>>> fdaa374b8c473690086850ea4e7af998f74c278c
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
<<<<<<< HEAD
            'image_files.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max per image
            'answer' => 'required|string',
            'latex_question' => 'nullable|string',
            'latex_answer' => 'nullable|string',
=======
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'options' => 'required|string',
<<<<<<< HEAD
            'answer' => 'required|string',
=======
            'option_images' => 'nullable|array',
            'option_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'text_options' => 'nullable|array',
            'text_options.*' => 'nullable|string',
            'answer' => 'required|string',
            'answer_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
>>>>>>> a0c595f5a6fd462401a4dc2125a6b45408cc7c90
>>>>>>> fdaa374b8c473690086850ea4e7af998f74c278c
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

<<<<<<< HEAD
        // Handle multiple image uploads - add to existing images
        $currentImages = $question->images ?? [];
        if ($request->hasFile('image_files')) {
            foreach ($request->file('image_files') as $image) {
                $currentImages[] = $image->store('questions/images', 'public');
            }
        }

=======
<<<<<<< HEAD
        // Handle image upload
=======
        // Handle main image upload
>>>>>>> a0c595f5a6fd462401a4dc2125a6b45408cc7c90
        $imagePath = $question->image;
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($question->image) {
                Storage::disk('public')->delete($question->image);
            }
            $imagePath = $request->file('image')->store('questions/images', 'public');
        }

<<<<<<< HEAD
=======
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

>>>>>>> a0c595f5a6fd462401a4dc2125a6b45408cc7c90
>>>>>>> fdaa374b8c473690086850ea4e7af998f74c278c
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
<<<<<<< HEAD
            'images' => $currentImages,
            'answer' => $validated['answer'],
            'latex_question' => $validated['latex_question'] ?? null,
            'latex_answer' => $validated['latex_answer'] ?? null,
=======
            'image' => $imagePath,
            'options' => $validated['options'],
<<<<<<< HEAD
            'answer' => $validated['answer'],
=======
            'options_images' => !empty($optionImagePaths) ? $optionImagePaths : null,
            'text_options' => !empty($textOptions) ? $textOptions : null,
            'answer' => $validated['answer'],
            'answer_image' => $answerImagePath,
>>>>>>> a0c595f5a6fd462401a4dc2125a6b45408cc7c90
>>>>>>> fdaa374b8c473690086850ea4e7af998f74c278c
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
<<<<<<< HEAD
        if ($question->images && count($question->images) > 0) {
            foreach ($question->images as $imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
        }

=======
        if ($question->image) {
            Storage::disk('public')->delete($question->image);
        }

<<<<<<< HEAD
=======
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

>>>>>>> a0c595f5a6fd462401a4dc2125a6b45408cc7c90
>>>>>>> fdaa374b8c473690086850ea4e7af998f74c278c
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
<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> fdaa374b8c473690086850ea4e7af998f74c278c
     * Handle CKEditor image upload for rich text content
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'upload' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            if ($request->hasFile('upload')) {
                $file = $request->file('upload');
                
                // Generate unique filename
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                // Create the directory if it doesn't exist
                $directory = storage_path('app/public/questions/editor_images');
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }
                
                // Save the file
                $path = $file->storeAs('questions/editor_images', $filename, 'public');
                $url = asset('storage/' . $path);

                return response()->json([
                    'success' => true,
                    'url' => $url
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'No file uploaded'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
<<<<<<< HEAD
=======
=======
>>>>>>> a0c595f5a6fd462401a4dc2125a6b45408cc7c90
>>>>>>> fdaa374b8c473690086850ea4e7af998f74c278c
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
