<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Response extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'user_id',
        'date'
    ];

    public function casts()
    {
        return ['date' => 'datetime'];
    }
    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function answers()
    {
        return $this->hasMany(Answer::class, 'response_id');
    }
}
