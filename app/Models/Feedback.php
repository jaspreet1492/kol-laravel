<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks';

    protected $fillable = ['id', 'end_user_id', 'kol_user_id', 'kol_profile_id', 'comment', 'rating', 'updated_at'];

    public function getKolProfile(){
        return $this->hasMany(KolProfile::class,'id','kol_profile_id');
    }

    public function getUser(){
        return $this->hasMany(User::class,'id','kol_user_id');
    }
}
