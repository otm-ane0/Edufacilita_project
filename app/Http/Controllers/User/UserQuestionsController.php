<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CreditHistory;
use App\Models\DownloadSession;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;

class UserQuestionsController extends Controller
{
    public function index()
    {
        // Get questions with relationships - load topic and subject relationships
        $questions = Question::with(['topic.subject'])->get();
        $subjects = Subject::all();
        
        return view('user.questions.index', [
            'questions' => $questions,
            'subjects' => $subjects
        ]);
    }

    /**
     * Handle the download request for selected questions.
     */
    public function download(Request $request)
    {
        $selectedQuestions = $request->input('selected_questions', []);
        $format = $request->input('format', 'word'); // Default to Word format

        if (is_string($selectedQuestions)) {
            $selectedQuestions = json_decode($selectedQuestions, true);
        }

        // Validate the selected questions
        if (empty($selectedQuestions)) {
            return redirect()->back()->with('error', 'No questions selected for download.');
        }

        // Sort question IDs to ensure consistent hash generation
        sort($selectedQuestions);
        
        // Generate consistent selection hash
        $selectionHash = md5(json_encode($selectedQuestions));
        
        // Check if credits have already been deducted for this selection
        $existingDownload = $this->checkExistingDownload($selectionHash, Auth::id());
        
        if (!$existingDownload && Auth::user()->credit < count($selectedQuestions)) {
            return redirect()->back()->with('error', 'Insufficient credits for download.');
        }

        if (count($selectedQuestions) === 1) {
            // Handle single question download
            $question = Question::find($selectedQuestions[0]);
            if (!$question) {
                return redirect()->back()->with('error', 'Question not found.');
            }
            
            if ($format === 'latex') {
                return $this->downloadSingleLatex($question, $existingDownload, $selectionHash);
            } else {
                return $this->downloadSingleDocument($question, $existingDownload, $selectionHash);
            }
        }

        // Handle multiple questions download
        if ($format === 'latex') {
            return $this->downloadMultipleLatex($selectedQuestions, $existingDownload, $selectionHash);
        } else {
            return $this->downloadMultipleDocuments($selectedQuestions, $existingDownload, $selectionHash);
        }
    }

    /**
     * Download LaTeX format as separate folders with individual docx files
     */
    public function downloadLatexZip(Request $request)
    {
        $selectedQuestions = $request->input('selected_questions', []);

        if (is_string($selectedQuestions)) {
            $selectedQuestions = json_decode($selectedQuestions, true);
        }

        // Validate the selected questions
        if (empty($selectedQuestions)) {
            return redirect()->back()->with('error', 'No questions selected for download.');
        }

        // Sort question IDs to ensure consistent hash generation
        sort($selectedQuestions);
        
        // Generate consistent selection hash
        $selectionHash = md5(json_encode($selectedQuestions));
        
        // Check if credits have already been deducted for this selection
        $existingDownload = $this->checkExistingDownload($selectionHash, Auth::id());
        
        if (!$existingDownload && Auth::user()->credit < count($selectedQuestions)) {
            return redirect()->back()->with('error', 'Insufficient credits for download.');
        }

        $questions = Question::whereIn('id', $selectedQuestions)->get();
        if ($questions->isEmpty()) {
            return redirect()->back()->with('error', 'No valid questions found for download.');
        }

        try {
            // Create temporary ZIP file for all questions
            $masterZipFileName = 'latex_questions_' . count($questions) . '_items_' . time() . '.zip';
            $tempMasterZipPath = storage_path('app/temp/' . $masterZipFileName);

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Create master ZIP archive
            $masterZip = new \ZipArchive();
            if ($masterZip->open($tempMasterZipPath, \ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Cannot create master ZIP file');
            }

            // Add each question as a separate folder
            $questionNumber = 1;
            $tempFiles = []; // Track temp files for cleanup
            foreach ($questions as $question) {
                $folderName = 'question_' . $questionNumber;

                // Generate LaTeX question document
                $questionDocPath = $this->generateLatexQuestionDocument($question);
                if ($questionDocPath) {
                    $masterZip->addFile($questionDocPath, $folderName . '/question_latex.docx');
                    $tempFiles[] = $questionDocPath;
                }

                // Generate LaTeX answer document
                $answerDocPath = $this->generateLatexAnswerDocument($question);
                if ($answerDocPath) {
                    $masterZip->addFile($answerDocPath, $folderName . '/answer_latex.docx');
                    $tempFiles[] = $answerDocPath;
                }

                // Add images if they exist
                $imageAdded = false;
                
                // Check for new image path fields first
                if ($question->question_image_path && Storage::disk('public')->exists($question->question_image_path)) {
                    $fullImagePath = Storage::disk('public')->path($question->question_image_path);
                    $imageExtension = pathinfo($question->question_image_path, PATHINFO_EXTENSION);
                    $masterZip->addFile($fullImagePath, $folderName . '/images/question_image.' . $imageExtension);
                    $imageAdded = true;
                    \Log::info("Added question image from question_image_path: " . $question->question_image_path);
                }

                if ($question->answer_image_path && Storage::disk('public')->exists($question->answer_image_path)) {
                    $fullImagePath = Storage::disk('public')->path($question->answer_image_path);
                    $imageExtension = pathinfo($question->answer_image_path, PATHINFO_EXTENSION);
                    $masterZip->addFile($fullImagePath, $folderName . '/images/answer_image.' . $imageExtension);
                    $imageAdded = true;
                    \Log::info("Added answer image from answer_image_path: " . $question->answer_image_path);
                }

                // Fallback to old images array if new fields are not populated
                if (!$imageAdded && $question->images && is_array($question->images) && count($question->images) > 0) {
                    foreach ($question->images as $index => $imagePath) {
                        if (Storage::disk('public')->exists($imagePath)) {
                            $fullImagePath = Storage::disk('public')->path($imagePath);
                            $imageExtension = pathinfo($imagePath, PATHINFO_EXTENSION);
                            $masterZip->addFile($fullImagePath, $folderName . '/images/image_' . ($index + 1) . '.' . $imageExtension);
                            $imageAdded = true;
                            \Log::info("Added image from images array: " . $imagePath);
                        }
                    }
                }

                \Log::info("Question {$question->id}: Images added = " . ($imageAdded ? 'yes' : 'no') . 
                          ", question_image_path = " . ($question->question_image_path ?? 'null') . 
                          ", answer_image_path = " . ($question->answer_image_path ?? 'null') . 
                          ", images array = " . json_encode($question->images));

                $questionNumber++;
            }

            // Close master ZIP file
            $masterZip->close();

            $downloadFileName = 'latex_questions_' . count($questions) . '_items.zip';

            // Handle credits only if not already downloaded
            if (!$existingDownload) {
                // Deduct credits and record transaction
                $this->deductCreditsAndRecord($selectedQuestions, $selectionHash, 'latex-folders');
            }

            // Clean up temporary files
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }

            // Return download response and delete temp file after download
            return response()->download($tempMasterZipPath, $downloadFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating LaTeX ZIP package: ' . $e->getMessage());
        }
    }

    /**
     * Download the question and answer documents as a ZIP file
     */
    public function downloadSingleDocument(Question $question, $existingDownload = false, $selectionHash = null)
    {
        // Check if question document exists
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

            // Handle credits only if not already downloaded
            if (!$existingDownload) {
                if (!$selectionHash) {
                    $selectionHash = md5(json_encode([$question->id]));
                }
                $this->deductCreditsAndRecord([$question->id], $selectionHash, 'word');
            }

            // Return download response and delete temp file after download
            return response()->download($tempZipPath, $downloadFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating ZIP file: ' . $e->getMessage());
        }
    }

    /**
     * Download multiple documents with simple Q/R file naming structure
     */
    public function downloadMultipleDocuments(array $questionIds, $existingDownload = false, $selectionHash = null)
    {
        $questions = Question::whereIn('id', $questionIds)->get();
        if ($questions->isEmpty()) {
            return redirect()->back()->with('error', 'No valid questions found for download.');
        }

        try {
            // Create temporary ZIP file for all questions
            $masterZipFileName = 'questions_package_' . count($questions) . '_items_' . time() . '.zip';
            $tempMasterZipPath = storage_path('app/temp/' . $masterZipFileName);

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Create master ZIP archive
            $masterZip = new \ZipArchive();
            if ($masterZip->open($tempMasterZipPath, \ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Cannot create master ZIP file');
            }

            // Add each question with simple Q/R naming structure
            $questionNumber = 1;
            foreach ($questions as $question) {
                if ($question->doc && Storage::disk('local')->exists($question->doc) &&
                    $question->answer_doc && Storage::disk('local')->exists($question->answer_doc)) {
                    
                    $questionDocPath = Storage::disk('local')->path($question->doc);
                    $answerDocPath = Storage::disk('local')->path($question->answer_doc);

                    // Add question document with Q naming
                    $questionFileName = 'Q' . $questionNumber . '.doc';
                    $masterZip->addFile($questionDocPath, $questionFileName);

                    // Add answer document with R naming
                    $answerFileName = 'R' . $questionNumber . '.doc';
                    $masterZip->addFile($answerDocPath, $answerFileName);

                    $questionNumber++;
                }
            }

            // Close master ZIP file
            $masterZip->close();

            $downloadFileName = 'questions_package_' . count($questions) . '_items.zip';

            // Handle credits only if not already downloaded
            if (!$existingDownload) {
                if (!$selectionHash) {
                    $selectionHash = md5(json_encode($questionIds));
                }
                $this->deductCreditsAndRecord($questionIds, $selectionHash, 'word');
            }

            // Return download response and delete temp file after download
            return response()->download($tempMasterZipPath, $downloadFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating ZIP package: ' . $e->getMessage());
        }
    }

    /**
     * Download single question as LaTeX-based Word document with organized structure
     */
    public function downloadSingleLatex(Question $question, $existingDownload = false, $selectionHash = null)
    {
        try {
            // Create temporary ZIP file
            $zipFileName = 'question_' . $question->id . '_latex_word_' . time() . '.zip';
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

            // Create Word document with LaTeX content
            $wordDocPath = $this->generateLatexWordDocument([$question]);
            
            // Add Word file to ZIP
            $zip->addFile($wordDocPath, 'questions_latex.docx');
            
            // Add images (using png format as default since image_format is removed)
            if ($question->images && count($question->images) > 0) {
                foreach ($question->images as $index => $imagePath) {
                    if (Storage::disk('public')->exists($imagePath)) {
                        $fullImagePath = Storage::disk('public')->path($imagePath);
                        $imageFormat = 'png'; // Default format since image_format field is removed
                        $convertedImagePath = $this->convertImageFormat($fullImagePath, $imageFormat, $question->id . '_' . $index);
                        if ($convertedImagePath) {
                            $zip->addFile($convertedImagePath, "images/question_1_image_" . ($index + 1) . ".{$imageFormat}");
                        }
                    }
                }
            }

            // Close ZIP file
            $zip->close();

            $downloadFileName = 'question_' . $question->id . '_latex_word.zip';

            // Handle credits only if not already downloaded
            if (!$existingDownload) {
                if (!$selectionHash) {
                    $selectionHash = md5(json_encode([$question->id]));
                }
                $this->deductCreditsAndRecord([$question->id], $selectionHash, 'latex');
            }

            // Clean up temporary files
            if (file_exists($wordDocPath)) {
                unlink($wordDocPath);
            }

            // Return download response and delete temp file after download
            return response()->download($tempZipPath, $downloadFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating LaTeX-Word package: ' . $e->getMessage());
        }
    }

    /**
     * Download multiple questions as LaTeX-based Word document with organized structure
     */
    public function downloadMultipleLatex(array $questionIds, $existingDownload = false, $selectionHash = null)
    {
        $questions = Question::whereIn('id', $questionIds)->get();
        if ($questions->isEmpty()) {
            return redirect()->back()->with('error', 'No valid questions found for download.');
        }

        try {
            // Create temporary ZIP file for all questions
            $masterZipFileName = 'questions_latex_word_package_' . count($questions) . '_items_' . time() . '.zip';
            $tempMasterZipPath = storage_path('app/temp/' . $masterZipFileName);

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Create master ZIP archive
            $masterZip = new \ZipArchive();
            if ($masterZip->open($tempMasterZipPath, \ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Cannot create master ZIP file');
            }

            // Generate Word document with LaTeX content for all questions
            $wordDocPath = $this->generateLatexWordDocument($questions);
            
            // Add Word file to ZIP
            $masterZip->addFile($wordDocPath, 'questions_latex.docx');

            // Add each question's images (using png format as default)
            $questionNumber = 1;
            foreach ($questions as $question) {
                if ($question->images && count($question->images) > 0) {
                    foreach ($question->images as $index => $imagePath) {
                        if (Storage::disk('public')->exists($imagePath)) {
                            $fullImagePath = Storage::disk('public')->path($imagePath);
                            $imageFormat = 'png'; // Default format since image_format field is removed
                            $convertedImagePath = $this->convertImageFormat($fullImagePath, $imageFormat, $question->id . '_' . $index);
                            if ($convertedImagePath) {
                                $masterZip->addFile($convertedImagePath, "images/question_{$questionNumber}_image_" . ($index + 1) . ".{$imageFormat}");
                            }
                        }
                    }
                }
                $questionNumber++;
            }

            // Close master ZIP file
            $masterZip->close();

            $downloadFileName = 'questions_latex_word_package_' . count($questions) . '_items.zip';

            // Handle credits only if not already downloaded
            if (!$existingDownload) {
                if (!$selectionHash) {
                    $selectionHash = md5(json_encode(sort($questionIds)));
                }
                $this->deductCreditsAndRecord($questionIds, $selectionHash, 'latex');
            }

            // Clean up temporary files
            if (file_exists($wordDocPath)) {
                unlink($wordDocPath);
            }

            // Return download response and delete temp file after download
            return response()->download($tempMasterZipPath, $downloadFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating LaTeX-Word package: ' . $e->getMessage());
        }
    }

    /**
     * Generate Word document with LaTeX content rendered as mathematical expressions
     */
    private function generateLatexWordDocument($questions)
    {
        $phpWord = new PhpWord();
        
        // Set document properties
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('EduFacilita');
        $properties->setTitle('Mathematics Questions - LaTeX Export');
        $properties->setDescription('Questions with LaTeX mathematical expressions');

        // Create a section
        $section = $phpWord->addSection([
            'marginLeft' => Converter::cmToTwip(2.5),
            'marginRight' => Converter::cmToTwip(2.5),
            'marginTop' => Converter::cmToTwip(2.5),
            'marginBottom' => Converter::cmToTwip(2.5),
        ]);

        // Title style
        $titleStyle = [
            'name' => 'Times New Roman',
            'size' => 18,
            'bold' => true,
            'color' => '1F497D'
        ];

        // Header style
        $headerStyle = [
            'name' => 'Times New Roman',
            'size' => 14,
            'bold' => true,
            'color' => '365F91'
        ];

        // Normal text style
        $normalStyle = [
            'name' => 'Times New Roman',
            'size' => 12
        ];

        // LaTeX/Math style
        $mathStyle = [
            'name' => 'Cambria Math',
            'size' => 12,
            'color' => '4472C4'
        ];

        // Metadata style
        $metaStyle = [
            'name' => 'Times New Roman',
            'size' => 10,
            'italic' => true,
            'color' => '7F7F7F'
        ];

        // Add title
        $section->addText('Mathematics Questions - LaTeX Export', $titleStyle, ['alignment' => 'center']);
        $section->addText('Generated on ' . date('F j, Y \a\t g:i A'), $metaStyle, ['alignment' => 'center']);
        $section->addTextBreak(2);

        // Questions section
        $section->addText('Questions', $headerStyle);
        $section->addTextBreak(1);

        $questionNumber = 1;
        foreach ($questions as $question) {
            // Question header
            $section->addText("Question {$questionNumber}", $headerStyle);
            $section->addTextBreak(1);

            // Question metadata
            $metadataTable = $section->addTable([
                'borderSize' => 6,
                'borderColor' => 'CCCCCC',
                'width' => 100 * 50,
                'unit' => 'pct'
            ]);
            
            $metadataTable->addRow();
            $metadataTable->addCell(2000)->addText('Difficulty:', $metaStyle);
            $metadataTable->addCell(2000)->addText(ucfirst($question->difficulty ?? 'N/A'), $normalStyle);
            $metadataTable->addCell(2000)->addText('Type:', $metaStyle);
            $metadataTable->addCell(2000)->addText($question->question_type ?? 'N/A', $normalStyle);

            $metadataTable->addRow();
            $metadataTable->addCell(2000)->addText('Level:', $metaStyle);
            $metadataTable->addCell(2000)->addText($question->education_level ?? 'N/A', $normalStyle);
            
            if ($question->topic && $question->topic->subject) {
                $metadataTable->addCell(2000)->addText('Subject:', $metaStyle);
                $metadataTable->addCell(2000)->addText($question->topic->subject->name ?? 'N/A', $normalStyle);
            }

            if ($question->topic) {
                $metadataTable->addRow();
                $metadataTable->addCell(2000)->addText('Topic:', $metaStyle);
                $metadataTable->addCell(2000)->addText($question->topic->name ?? 'N/A', $normalStyle);
                $metadataTable->addCell(2000)->addText('ID:', $metaStyle);
                $metadataTable->addCell(2000)->addText($question->id, $normalStyle);
            }

            $section->addTextBreak(1);

            // Question content
            $section->addText('Question:', ['bold' => true]);
            $section->addTextBreak(1);

            // Add LaTeX question if available, otherwise use regular question
            if (!empty($question->latex_question)) {
                // Convert LaTeX to a more readable format in Word
                $latexQuestionLines = $this->convertLatexToWordFormat($question->latex_question);
                foreach ($latexQuestionLines as $line) {
                    if (!empty(trim($line))) {
                        $section->addText($line, $mathStyle);
                    }
                    $section->addTextBreak(1);
                }
            } else {
                $questionText = strip_tags($question->question ?? 'No question text available');
                $section->addText($questionText, $normalStyle);
            }

            // Add image reference if exists
            if ($question->image) {
                $section->addTextBreak(1);
                $section->addText('Note: Image included in package - question_' . $questionNumber . '_image.' . ($question->image_format ?? 'png'), $metaStyle);
            }

            $section->addTextBreak(2);
            $questionNumber++;
        }

        // Answers section
        $section->addPageBreak();
        $section->addText('Answers', $headerStyle);
        $section->addTextBreak(1);

        $questionNumber = 1;
        foreach ($questions as $question) {
            $section->addText("Answer {$questionNumber}", $headerStyle);
            $section->addTextBreak(1);

            // Add LaTeX answer if available, otherwise use regular answer
            if (!empty($question->latex_answer)) {
                $latexAnswerLines = $this->convertLatexToWordFormat($question->latex_answer);
                foreach ($latexAnswerLines as $line) {
                    if (!empty(trim($line))) {
                        $section->addText($line, $mathStyle);
                    }
                    $section->addTextBreak(1);
                }
            } else {
                $answerText = strip_tags($question->answer ?? 'No answer available');
                $section->addText($answerText, $normalStyle);
            }

            $section->addTextBreak(2);
            $questionNumber++;
        }

        // Save the document
        $tempDocPath = storage_path('app/temp/questions_latex_' . time() . '.docx');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempDocPath);

        return $tempDocPath;
    }

    /**
     * Convert LaTeX expressions to a more readable format in Word
     * Returns an array of lines to preserve line breaks
     */
    private function convertLatexToWordFormat($latexText)
    {
        // Basic LaTeX to Unicode/Symbol conversion
        $conversions = [
            // Greek letters
            '\\alpha' => 'α',
            '\\beta' => 'β',
            '\\gamma' => 'γ',
            '\\delta' => 'δ',
            '\\epsilon' => 'ε',
            '\\theta' => 'θ',
            '\\lambda' => 'λ',
            '\\mu' => 'μ',
            '\\pi' => 'π',
            '\\sigma' => 'σ',
            '\\phi' => 'φ',
            '\\omega' => 'ω',

            // Mathematical symbols
            '\\infty' => '∞',
            '\\sum' => '∑',
            '\\prod' => '∏',
            '\\int' => '∫',
            '\\partial' => '∂',
            '\\nabla' => '∇',
            '\\sqrt' => '√',
            '\\pm' => '±',
            '\\times' => '×',
            '\\div' => '÷',
            '\\neq' => '≠',
            '\\leq' => '≤',
            '\\geq' => '≥',
            '\\approx' => '≈',
            '\\equiv' => '≡',

            // Fractions (basic conversion)
            '\\frac{1}{2}' => '½',
            '\\frac{1}{3}' => '⅓',
            '\\frac{2}{3}' => '⅔',
            '\\frac{1}{4}' => '¼',
            '\\frac{3}{4}' => '¾',
            '\\frac{1}{5}' => '⅕',
            '\\frac{1}{6}' => '⅙',
            '\\frac{1}{8}' => '⅛',

            // Superscripts (basic)
            '^2' => '²',
            '^3' => '³',
            '^{-1}' => '⁻¹',

            // Remove common LaTeX commands
            '\\left(' => '(',
            '\\right)' => ')',
            '\\left[' => '[',
            '\\right]' => ']',
            '\\left\\{' => '{',
            '\\right\\}' => '}',
            '\\,' => ' ',
            '\\;' => ' ',
            '\\!' => '',
        ];

        // Split by line breaks first to preserve formatting
        $lines = preg_split('/\r\n|\r|\n/', $latexText);
        $convertedLines = [];
        
        foreach ($lines as $line) {
            $converted = $line;
            
            // Apply conversions
            foreach ($conversions as $latex => $unicode) {
                $converted = str_replace($latex, $unicode, $converted);
            }

            // Handle fractions with pattern matching
            $converted = preg_replace('/\\\\frac\{([^}]+)\}\{([^}]+)\}/', '($1)/($2)', $converted);
            
            // Handle superscripts with pattern matching
            $converted = preg_replace('/\^\{([^}]+)\}/', '^($1)', $converted);
            
            // Handle subscripts with pattern matching
            $converted = preg_replace('/_\{([^}]+)\}/', '_($1)', $converted);
            
            // Remove remaining backslashes for simple commands
            $converted = preg_replace('/\\\\([a-zA-Z]+)/', '$1', $converted);
            
            // Clean up extra spaces
            $converted = preg_replace('/\s+/', ' ', $converted);
            $converted = trim($converted);

            $convertedLines[] = $converted;
        }

        return $convertedLines;
    }

    /**
     * Convert image to specified format
     */
    private function convertImageFormat($originalPath, $targetFormat, $questionId)
    {
        try {
            $imageInfo = getimagesize($originalPath);
            if (!$imageInfo) {
                return null;
            }

            $originalFormat = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));
            
            // If already in target format, just copy it
            if ($originalFormat === $targetFormat) {
                $tempPath = storage_path('app/temp/question_' . $questionId . '_image.' . $targetFormat);
                copy($originalPath, $tempPath);
                return $tempPath;
            }

            // Create image resource from original
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    $sourceImage = imagecreatefromjpeg($originalPath);
                    break;
                case IMAGETYPE_PNG:
                    $sourceImage = imagecreatefrompng($originalPath);
                    break;
                case IMAGETYPE_GIF:
                    $sourceImage = imagecreatefromgif($originalPath);
                    break;
                default:
                    return null;
            }

            if (!$sourceImage) {
                return null;
            }

            $tempPath = storage_path('app/temp/question_' . $questionId . '_image.' . $targetFormat);

            // Save in target format
            switch ($targetFormat) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($sourceImage, $tempPath, 90);
                    break;
                case 'png':
                    imagepng($sourceImage, $tempPath, 9);
                    break;
                case 'gif':
                    imagegif($sourceImage, $tempPath);
                    break;
                case 'webp':
                    if (function_exists('imagewebp')) {
                        imagewebp($sourceImage, $tempPath, 90);
                    } else {
                        // Fallback to PNG if WebP not supported
                        imagepng($sourceImage, $tempPath, 9);
                    }
                    break;
                default:
                    imagedestroy($sourceImage);
                    return null;
            }

            imagedestroy($sourceImage);
            return $tempPath;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get topics by subject ID for AJAX requests
     */
    public function getTopics($subjectId)
    {
        $topics = Topic::where('subject_id', $subjectId)->select('id', 'name')->get();
        return response()->json($topics);
    }

    /**
     * Check if credits have already been deducted for this selection
     */
    private function checkExistingDownload($selectionHash, $userId)
    {
        return DownloadSession::where('user_id', $userId)
            ->where('selection_hash', $selectionHash)
            ->whereDate('created_at', today())
            ->exists();
    }

    /**
     * Deduct credits and record the transaction with selection hash
     */
    private function deductCreditsAndRecord($questionIds, $selectionHash, $downloadType)
    {
        $user = Auth::user();
        $creditAmount = count($questionIds);
        
        // Deduct credits
        $user->credit = $user->credit - $creditAmount;
        $user->save();

        // Record transaction in credit history
        CreditHistory::create([
            'user_id' => $user->id,
            'action' => 'Download',
            'amount' => '- ' . $creditAmount,
            'description' => $downloadType . ' (' . $creditAmount . ' questions) - IDs: ' . implode(', ', $questionIds),
        ]);

        // Record download session for credit sharing
        DownloadSession::create([
            'user_id' => $user->id,
            'selection_hash' => $selectionHash,
            'question_ids' => $questionIds,
            'credit_amount' => $creditAmount,
            'download_type' => $downloadType,
        ]);
    }

    /**
     * Generate a Word document for a single question's LaTeX content
     */
    private function generateLatexQuestionDocument(Question $question)
    {
        try {
            $phpWord = new PhpWord();
            
            // Set document properties
            $properties = $phpWord->getDocInfo();
            $properties->setCreator('EduFacilita');
            $properties->setTitle('Question LaTeX - ID: ' . $question->id);

            // Create a section
            $section = $phpWord->addSection([
                'marginLeft' => Converter::cmToTwip(2.5),
                'marginRight' => Converter::cmToTwip(2.5),
                'marginTop' => Converter::cmToTwip(2.5),
                'marginBottom' => Converter::cmToTwip(2.5),
            ]);

            // Styles
            $titleStyle = [
                'name' => 'Times New Roman',
                'size' => 16,
                'bold' => true,
                'color' => '1F497D'
            ];

            $normalStyle = [
                'name' => 'Times New Roman',
                'size' => 12
            ];

            $mathStyle = [
                'name' => 'Cambria Math',
                'size' => 12,
                'color' => '4472C4'
            ];

            // Add title
            $section->addText('Question (LaTeX Format)', $titleStyle);
            $section->addTextBreak(1);

            // Add question metadata
            $section->addText('Question ID: ' . $question->id, $normalStyle);
            $section->addText('Difficulty: ' . ucfirst($question->difficulty ?? 'N/A'), $normalStyle);
            $section->addText('Type: ' . ($question->question_type ?? 'N/A'), $normalStyle);
            $section->addText('Level: ' . ($question->education_level ?? 'N/A'), $normalStyle);
            
            if ($question->topic) {
                $section->addText('Topic: ' . $question->topic->name, $normalStyle);
                if ($question->topic->subject) {
                    $section->addText('Subject: ' . $question->topic->subject->name, $normalStyle);
                }
            }
            
            $section->addTextBreak(2);

            // Add question content
            $section->addText('Question:', ['bold' => true]);
            $section->addTextBreak(1);

            if (!empty($question->latex_question)) {
                $latexQuestionLines = $this->convertLatexToWordFormat($question->latex_question);
                foreach ($latexQuestionLines as $line) {
                    if (!empty(trim($line))) {
                        $section->addText($line, $mathStyle);
                    }
                    $section->addTextBreak(1);
                }
            } else {
                $questionText = strip_tags($question->question ?? 'No question text available');
                $section->addText($questionText, $normalStyle);
            }

            // Save the document
            $tempDocPath = storage_path('app/temp/question_latex_' . $question->id . '_' . time() . '.docx');
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempDocPath);

            return $tempDocPath;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate a Word document for a single answer's LaTeX content
     */
    private function generateLatexAnswerDocument(Question $question)
    {
        try {
            $phpWord = new PhpWord();
            
            // Set document properties
            $properties = $phpWord->getDocInfo();
            $properties->setCreator('EduFacilita');
            $properties->setTitle('Answer LaTeX - ID: ' . $question->id);

            // Create a section
            $section = $phpWord->addSection([
                'marginLeft' => Converter::cmToTwip(2.5),
                'marginRight' => Converter::cmToTwip(2.5),
                'marginTop' => Converter::cmToTwip(2.5),
                'marginBottom' => Converter::cmToTwip(2.5),
            ]);

            // Styles
            $titleStyle = [
                'name' => 'Times New Roman',
                'size' => 16,
                'bold' => true,
                'color' => '1F497D'
            ];

            $normalStyle = [
                'name' => 'Times New Roman',
                'size' => 12
            ];

            $mathStyle = [
                'name' => 'Cambria Math',
                'size' => 12,
                'color' => '4472C4'
            ];

            // Add title
            $section->addText('Answer (LaTeX Format)', $titleStyle);
            $section->addTextBreak(1);

            // Add answer metadata
            $section->addText('Question ID: ' . $question->id, $normalStyle);
            $section->addTextBreak(1);

            // Add answer content
            $section->addText('Answer:', ['bold' => true]);
            $section->addTextBreak(1);

            if (!empty($question->latex_answer)) {
                $latexAnswerLines = $this->convertLatexToWordFormat($question->latex_answer);
                foreach ($latexAnswerLines as $line) {
                    if (!empty(trim($line))) {
                        $section->addText($line, $mathStyle);
                    }
                    $section->addTextBreak(1);
                }
            } else {
                $answerText = strip_tags($question->answer ?? 'No answer available');
                $section->addText($answerText, $normalStyle);
            }

            // Save the document
            $tempDocPath = storage_path('app/temp/answer_latex_' . $question->id . '_' . time() . '.docx');
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempDocPath);

            return $tempDocPath;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Send questions by email
     */
    public function sendEmail(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'selected_questions' => 'required|string',
                'recipients' => 'required|string',
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
                'format' => 'required|in:word,latex-folders'
            ]);

            $selectedQuestions = json_decode($request->input('selected_questions'), true);
            $recipients = $request->input('recipients');
            $subject = $request->input('subject');
            $message = $request->input('message');
            $format = $request->input('format');

            // Validate the selected questions
            if (empty($selectedQuestions)) {
                return response()->json(['success' => false, 'message' => 'No questions selected.']);
            }

            // Parse and validate email addresses
            $emailList = array_map('trim', explode(',', $recipients));
            $emailList = array_filter($emailList); // Remove empty emails

            foreach ($emailList as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return response()->json(['success' => false, 'message' => "Invalid email format: {$email}"]);
                }
            }

            if (empty($emailList)) {
                return response()->json(['success' => false, 'message' => 'No valid email addresses provided.']);
            }

            // Sort question IDs to ensure consistent hash generation
            sort($selectedQuestions);
            
            // Generate consistent selection hash
            $selectionHash = md5(json_encode($selectedQuestions));
            
            // Check if credits have already been deducted for this selection
            $existingDownload = $this->checkExistingDownload($selectionHash, Auth::id());
            
            // Only check credits if this is a new selection
            if (!$existingDownload && Auth::user()->credit < count($selectedQuestions)) {
                return response()->json(['success' => false, 'message' => 'Insufficient credits for email sending.']);
            }

            // Generate the file based on format
            $filePath = null;
            $fileName = null;

            if ($format === 'latex-folders') {
                // Generate LaTeX ZIP file
                $questions = Question::whereIn('id', $selectedQuestions)->get();
                if ($questions->isEmpty()) {
                    return response()->json(['success' => false, 'message' => 'No valid questions found.']);
                }

                $fileName = 'latex_questions_' . count($questions) . '_items_' . time() . '.zip';
                $filePath = $this->generateLatexZipForEmail($questions, $fileName);
            } else {
                // Generate Word ZIP file (same structure as normal download)
                $questions = Question::whereIn('id', $selectedQuestions)->get();
                if ($questions->isEmpty()) {
                    return response()->json(['success' => false, 'message' => 'No valid questions found.']);
                }

                $fileName = 'questions_package_' . count($questions) . '_items_' . time() . '.zip';
                $filePath = $this->generateWordZipForEmail($selectedQuestions, $fileName);
            }

            if (!$filePath || !file_exists($filePath)) {
                return response()->json(['success' => false, 'message' => 'Failed to generate file attachment.']);
            }

            // Send email to all recipients
            $emailsSent = 0;
            $emailErrors = [];
            
            foreach ($emailList as $email) {
                try {
                    Mail::raw($message, function ($mail) use ($email, $subject, $filePath, $fileName, $format) {
                        $mail->to($email)
                             ->subject($subject)
                             ->from(config('mail.from.address'), config('mail.from.name'))
                             ->attach($filePath, [
                                 'as' => $fileName,
                                 'mime' => 'application/zip'  // Both formats now generate ZIP files
                             ]);
                    });
                    $emailsSent++;
                } catch (\Exception $e) {
                    \Log::error("Failed to send email to {$email}: " . $e->getMessage());
                    $emailErrors[] = $email;
                }
            }

            if ($emailsSent === 0) {
                return response()->json(['success' => false, 'message' => 'Failed to send emails to any recipients.']);
            }

            // Only deduct credits if this is a new selection
            if (!$existingDownload) {
                $this->deductCreditsAndRecord($selectedQuestions, $selectionHash, 'Email: ' . $format);
            }

            // Clean up temporary file
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return response()->json([
                'success' => true, 
                'message' => "Email sent successfully to {$emailsSent} recipient(s)!" . 
                           (count($emailErrors) > 0 ? " (Failed to send to: " . implode(', ', $emailErrors) . ")" : "")
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())]);
        } catch (\Exception $e) {
            \Log::error('Email sending failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send email. Please try again.']);
        }
    }

    /**
     * Generate Word document for email attachment
     */
    private function generateWordDocumentForEmail($questions, $fileName)
    {
        try {
            $phpWord = new PhpWord();
            
            // Set default font
            $phpWord->setDefaultFontName('Calibri');
            $phpWord->setDefaultFontSize(11);

            foreach ($questions as $index => $question) {
                // Add a new section for each question
                $section = $phpWord->addSection();
                
                // Question header
                $section->addTitle("Question #" . $question->id, 1);
                
                // Question metadata table
                $metadataTable = $section->addTable([
                    'borderSize' => 6,
                    'borderColor' => '006699',
                    'cellMargin' => 80
                ]);
                
                $metadataTable->addRow();
                $metadataTable->addCell(2000)->addText('Education Level:', ['bold' => true]);
                $metadataTable->addCell(2000)->addText($question->education_level ?? 'Not specified');
                $metadataTable->addCell(2000)->addText('Question Type:', ['bold' => true]);
                $metadataTable->addCell(2000)->addText($question->question_type ?? 'Not specified');
                
                $metadataTable->addRow();
                $metadataTable->addCell(2000)->addText('Subject:', ['bold' => true]);
                $metadataTable->addCell(2000)->addText($question->topic && $question->topic->subject ? $question->topic->subject->name : 'Not specified');
                $metadataTable->addCell(2000)->addText('Topic:', ['bold' => true]);
                $metadataTable->addCell(2000)->addText($question->topic ? $question->topic->name : 'Not specified');
                
                $metadataTable->addRow();
                $metadataTable->addCell(4000)->addText('Difficulty:', ['bold' => true]);
                $metadataTable->addCell(4000)->addText($question->difficulty ? ucfirst($question->difficulty) : 'Not specified');
                
                $section->addTextBreak(1);
                
                // Question content
                $section->addText('Question:', ['size' => 14, 'bold' => true, 'color' => '1f4788']);
                $section->addTextBreak(1);
                
                if ($question->question && trim($question->question)) {
                    $section->addText($question->question, ['size' => 11]);
                } else {
                    $section->addText('Question text not available', ['italic' => true, 'color' => '999999']);
                }
                
                // Add question image if available
                if ($question->question_image_path && Storage::disk('public')->exists($question->question_image_path)) {
                    try {
                        $section->addTextBreak(1);
                        $imagePath = Storage::disk('public')->path($question->question_image_path);
                        if (file_exists($imagePath)) {
                            $section->addImage($imagePath, [
                                'width' => Converter::pixelToPoint(350),
                                'wrappingStyle' => 'inline'
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Failed to add question image: ' . $e->getMessage());
                        $section->addText('[Question image could not be loaded]', ['italic' => true, 'color' => 'red']);
                    }
                }
                
                $section->addTextBreak(2);
                
                // Answer content
                $section->addText('Answer:', ['size' => 14, 'bold' => true, 'color' => '228b22']);
                $section->addTextBreak(1);
                
                if ($question->answer && trim($question->answer)) {
                    $section->addText($question->answer, ['size' => 11]);
                } else {
                    $section->addText('Answer text not available', ['italic' => true, 'color' => '999999']);
                }
                
                // Add answer image if available
                if ($question->answer_image_path && Storage::disk('public')->exists($question->answer_image_path)) {
                    try {
                        $section->addTextBreak(1);
                        $imagePath = Storage::disk('public')->path($question->answer_image_path);
                        if (file_exists($imagePath)) {
                            $section->addImage($imagePath, [
                                'width' => Converter::pixelToPoint(350),
                                'wrappingStyle' => 'inline'
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Failed to add answer image: ' . $e->getMessage());
                        $section->addText('[Answer image could not be loaded]', ['italic' => true, 'color' => 'red']);
                    }
                }
                
                // Add page break if not the last question
                if ($index < count($questions) - 1) {
                    $section->addPageBreak();
                }
            }

            // Save to temporary file
            $tempPath = storage_path('app/temp/' . $fileName);
            
            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempPath);
            
            return $tempPath;

        } catch (\Exception $e) {
            \Log::error('Word document generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate LaTeX ZIP file for email attachment
     */
    private function generateLatexZipForEmail($questions, $fileName)
    {
        try {
            $tempZipPath = storage_path('app/temp/' . $fileName);

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Create ZIP archive
            $zip = new \ZipArchive();
            if ($zip->open($tempZipPath, \ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Cannot create ZIP file');
            }

            // Add each question as a separate folder
            $questionNumber = 1;
            $tempFiles = []; // Track temp files for cleanup
            
            foreach ($questions as $question) {
                $folderName = 'question_' . $questionNumber;

                // Generate LaTeX question document
                $questionDocPath = $this->generateLatexQuestionDocument($question);
                if ($questionDocPath) {
                    $zip->addFile($questionDocPath, $folderName . '/question_latex.docx');
                    $tempFiles[] = $questionDocPath;
                }

                // Generate LaTeX answer document  
                $answerDocPath = $this->generateLatexAnswerDocument($question);
                if ($answerDocPath) {
                    $zip->addFile($answerDocPath, $folderName . '/answer_latex.docx');
                    $tempFiles[] = $answerDocPath;
                }

                // Add images if they exist
                $imageAdded = false;
                
                // Check for new image path fields first
                if ($question->question_image_path && Storage::disk('public')->exists($question->question_image_path)) {
                    $imagePath = Storage::disk('public')->path($question->question_image_path);
                    $imageExtension = pathinfo($question->question_image_path, PATHINFO_EXTENSION);
                    $zip->addFile($imagePath, $folderName . '/images/question_image.' . $imageExtension);
                    $imageAdded = true;
                    \Log::info("Email ZIP: Added question image from question_image_path: " . $question->question_image_path);
                }

                if ($question->answer_image_path && Storage::disk('public')->exists($question->answer_image_path)) {
                    $imagePath = Storage::disk('public')->path($question->answer_image_path);
                    $imageExtension = pathinfo($question->answer_image_path, PATHINFO_EXTENSION);
                    $zip->addFile($imagePath, $folderName . '/images/answer_image.' . $imageExtension);
                    $imageAdded = true;
                    \Log::info("Email ZIP: Added answer image from answer_image_path: " . $question->answer_image_path);
                }

                // Fallback to old images array if new fields are not populated
                if (!$imageAdded && $question->images && is_array($question->images) && count($question->images) > 0) {
                    foreach ($question->images as $index => $imagePath) {
                        if (Storage::disk('public')->exists($imagePath)) {
                            $fullImagePath = Storage::disk('public')->path($imagePath);
                            $imageExtension = pathinfo($imagePath, PATHINFO_EXTENSION);
                            $zip->addFile($fullImagePath, $folderName . '/images/image_' . ($index + 1) . '.' . $imageExtension);
                            $imageAdded = true;
                            \Log::info("Email ZIP: Added image from images array: " . $imagePath);
                        }
                    }
                }

                \Log::info("Email ZIP Question {$question->id}: Images added = " . ($imageAdded ? 'yes' : 'no') . 
                          ", question_image_path = " . ($question->question_image_path ?? 'null') . 
                          ", answer_image_path = " . ($question->answer_image_path ?? 'null') . 
                          ", images array = " . json_encode($question->images));

                $questionNumber++;
            }

            $zip->close();

            // Clean up temporary files
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }

            return $tempZipPath;

        } catch (\Exception $e) {
            \Log::error('LaTeX ZIP generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate Word ZIP file for email attachment (Q1, Q2, Q3, R1, R2, R3 structure)
     */
    private function generateWordZipForEmail(array $questionIds, $fileName)
    {
        try {
            $questions = Question::whereIn('id', $questionIds)->get();
            if ($questions->isEmpty()) {
                throw new \Exception('No valid questions found for Word ZIP generation');
            }

            $tempZipPath = storage_path('app/temp/' . $fileName);

            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Create ZIP archive
            $zip = new \ZipArchive();
            if ($zip->open($tempZipPath, \ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Cannot create Word ZIP file');
            }

            // Add each question with Q/R naming structure (same as normal download)
            $questionNumber = 1;
            foreach ($questions as $question) {
                // Check if question has doc and answer_doc files
                if ($question->doc && Storage::disk('local')->exists($question->doc) &&
                    $question->answer_doc && Storage::disk('local')->exists($question->answer_doc)) {
                    
                    $questionDocPath = Storage::disk('local')->path($question->doc);
                    $answerDocPath = Storage::disk('local')->path($question->answer_doc);

                    // Add question document with Q naming
                    $questionFileName = 'Q' . $questionNumber . '.doc';
                    $zip->addFile($questionDocPath, $questionFileName);

                    // Add answer document with R naming
                    $answerFileName = 'R' . $questionNumber . '.doc';
                    $zip->addFile($answerDocPath, $answerFileName);

                    \Log::info("Email Word ZIP: Added Q{$questionNumber}.doc and R{$questionNumber}.doc for question {$question->id}");
                } else {
                    \Log::warning("Email Word ZIP: Question {$question->id} missing doc files - doc: " . ($question->doc ?? 'null') . ", answer_doc: " . ($question->answer_doc ?? 'null'));
                }

                $questionNumber++;
            }

            $zip->close();
            \Log::info("Email Word ZIP generated successfully: {$tempZipPath}");

            return $tempZipPath;

        } catch (\Exception $e) {
            \Log::error('Email Word ZIP generation failed: ' . $e->getMessage());
            return null;
        }
    }
}
