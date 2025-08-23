<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Topic;

class Question extends Model
{
    protected $fillable = [
        'question',
        'image',
        'options',
        'options_images',
        'text_options',
        'answer',
        'answer_image',
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
        'options_images' => 'array',
        'text_options' => 'array',
    ];

    public function topic(){
        return $this->belongsTo(Topic::class);
    }

}

