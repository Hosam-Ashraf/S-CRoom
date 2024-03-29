<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function students()
    {
        return $this->belongsToMany('Student', 'student_subject');
    }
    public function professor()
    {
        return $this->belongsTo('Professor');
    }
}
