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
<<<<<<< HEAD
        'answer',
=======
        'options_images',
        'text_options',
        'answer',
        'answer_image',
>>>>>>> a0c595f5a6fd462401a4dc2125a6b45408cc7c90
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

<<<<<<< HEAD
=======
    protected $casts = [
        'options_images' => 'array',
        'text_options' => 'array',
    ];

>>>>>>> a0c595f5a6fd462401a4dc2125a6b45408cc7c90
    public function topic(){
        return $this->belongsTo(Topic::class);
    }

}

