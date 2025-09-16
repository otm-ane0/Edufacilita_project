<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Question;

// Get a few recent questions to check their image data
$questions = Question::latest()->take(3)->get();

echo "Checking question image data:\n";
echo "============================\n";

foreach ($questions as $question) {
    echo "Question ID: {$question->id}\n";
    echo "Images field: " . json_encode($question->images) . "\n";
    echo "Images field type: " . gettype($question->images) . "\n";
    
    // Check if images is empty or null
    if (empty($question->images)) {
        echo "Images field is empty/null\n";
    } elseif (is_array($question->images) && count($question->images) > 0) {
        echo "Number of images: " . count($question->images) . "\n";
        foreach ($question->images as $index => $imagePath) {
            echo "  Image {$index}: {$imagePath}\n";
        }
    }
    
    echo "Raw attributes: " . json_encode($question->getAttributes()) . "\n";
    echo "---\n";
}
