<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Topic;

class Question extends Model
{
    protected $fillable = [
        'question',
        'images',
        'question_image_path',
        'answer_image_path',
        'image',
        'options',
        'options_images',
        'text_options',
        'answer',
        'answer_image',
        'latex_question',
        'latex_answer',
        'difficulty',
        'question_type',
        'education_level',
        'topic_id',
        'institution',
        'source',
        'year',
        'region',
        'uf',
        'doc',
        'answer_doc',
    ];

    protected $casts = [
        'images' => 'array',
        'options_images' => 'array',
        'text_options' => 'array',
    ];

    public function topic(){
        return $this->belongsTo(Topic::class);
    }

}

