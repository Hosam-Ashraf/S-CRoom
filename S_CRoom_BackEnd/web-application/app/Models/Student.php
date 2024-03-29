<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
//    protected $primaryKey = 'student_id';
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_subject', 'student_id',
            'subject_id');
    }
}
