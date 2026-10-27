<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'name',
        'choice_type',
        'choices',
        'is_required'
    ];

    public function casts()
    {
        return [
            'is_required' => 'boolean'
        ];
    }
    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }
    public function answers()
    {
        return $this->hasMany(Answer::class, 'question_id');
    }
}
