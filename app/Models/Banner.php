<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $table = 'banners';

    protected $fillable = ['id', 'title', 'description', 'banner', 'updated_at'];

    public static function makeImageUrl($file)
    {
        
        $uploadFolder = 'banner';
        $name = preg_replace("/[^a-z0-9\._]+/", "-", strtolower(time() . rand(1, 9999) . '.' . $file->getClientOriginalName()));
        if ($file->move(public_path() . '/uploads/'.$uploadFolder, str_replace(" ", "", $name))) {
            return '/uploads/'.$uploadFolder.'/' .$name;
        }
    }

    public function getUser(){
        return $this->hasOne(User::class,'id','user_id');
    }

}
