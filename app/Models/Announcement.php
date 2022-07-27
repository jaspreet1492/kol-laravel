<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $table = 'announcements';
    
    protected $fillable = ['id', 'profile_id', 'user_id', 'title', 'description', 'start_date', 'end_date', 'image', 'social_platform', 'status', 'created_at', 'updated_at'];

    public static function makeImageUrl($file)
    {
        
        $uploadFolder = 'announcement';
        $name = preg_replace("/[^a-z0-9\._]+/", "-", strtolower(time() . rand(1, 9999) . '.' . $file->getClientOriginalName()));
        if ($file->move(public_path() . '/uploads/'.$uploadFolder, str_replace(" ", "", $name))) {
            return '/uploads/'.$uploadFolder.'/' .$name;
        }
    }

    public function getUser(){
        return $this->hasOne(User::class,'id','user_id');
    }
}
