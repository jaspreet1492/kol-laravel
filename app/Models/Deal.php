<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use HasFactory;

    protected $table = 'deals';
    protected $fillable = ['id', 'kol_profile_id', 'title','description','type','total_days','price'];

    public function getKolProfile(){
        return $this->belongsTo(KolProfile::class,'kol_profile_id','id');
    }

    public function getUser(){
        return $this->belongsTo(KolProfile::class,'kol_profile_id','id');
    }

}
