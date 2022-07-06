<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KolProfile extends Model
{
    use HasFactory;

    protected $table = 'kol_profiles';
    
    protected $fillable = ['id', 'user_id', 'languages', 'bio', 'avatar', 'personal_email', 'kol_type', 'state', 'zip_code', 'city', 'total_viewer', 'banner', 'social_active', 'video_links', 'tags', 'status', 'created_at', 'updated_at'];


    public static function makeImageUrl($file)
    {
        
        $uploadFolder = 'profile';
        $name = preg_replace("/[^a-z0-9\._]+/", "-", strtolower(time() . rand(1, 9999) . '.' . $file->getClientOriginalName()));
        if ($file->move(public_path() . '/uploads/'.$uploadFolder, str_replace(" ", "", $name))) {
            return url('/') . '/uploads/'.$uploadFolder.'/' .$name;
        }
    }

    public function getSocialMedia(){
        return $this->hasMany(SocialMedia::class,'profile_id','id');
    }

    public function getUser(){
        return $this->hasOne(User::class,'id','user_id');
    }

}


