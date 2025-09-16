<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Topic;

class Question extends Model
{
    protected $fillable = [
        'question',
<<<<<<< HEAD
        'images',
        'question_image_path',
        'answer_image_path',
        'answer',
        'latex_question',
        'latex_answer',
=======
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
>>>>>>> fdaa374b8c473690086850ea4e7af998f74c278c
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
    protected $casts = [
        'images' => 'array',
    ];

=======
<<<<<<< HEAD
=======
    protected $casts = [
        'options_images' => 'array',
        'text_options' => 'array',
    ];

>>>>>>> a0c595f5a6fd462401a4dc2125a6b45408cc7c90
>>>>>>> fdaa374b8c473690086850ea4e7af998f74c278c
    public function topic(){
        return $this->belongsTo(Topic::class);
    }

}

