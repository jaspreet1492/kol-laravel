<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    use HasFactory;

    protected $table = 'bookmarks';
    
    protected $fillable = ['id', 'end_user_id', 'kol_user_id', 'kol_profile_id', 'updated_at'];

    public function getKolProfile(){
        return $this->belongsTo(KolProfile::class,'kol_profile_id','id');
    }

    public function getUser(){
        return $this->belongsTo(User::class,'kol_user_id','id');
    }
}
