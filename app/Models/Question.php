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
        'answer',
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
    ];

    public function topic(){
        return $this->belongsTo(Topic::class);
    }

}

