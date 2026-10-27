<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'limit_one_response',
        'creator_id'
    ];

    public function casts()
    {
        return [
            'limit_one_response' => 'boolean'
        ];
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
    public function question()
    {
        return $this->hasMany(Question::class, 'form_id');
    }
    public function responses()
    {
        return $this->hasMany(Response::class, 'form_id');
    }
    public function allowedDomains()
    {
        return $this->hasMany(AllowedDomain::class, 'form_id');
    }
}
