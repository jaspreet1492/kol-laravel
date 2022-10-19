<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable ;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['id','name','last_name','email','role_id','phone','gender','profile_image','date_of_birth','role_id','description','kol_type','language','category_id','whatsapp_chat_link','telegram_id','tiktok_link','livestream_link','other_link','instagram_followers','livestream_followers','tiktok_followers','email_verified_at','email_verification_code','is_varified','status','created_at','updated_at'];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public static function makeImageUrl($file)
    {
        $uploadFolder = 'user';
        $foldername = public_path() . '/uploads/'.$uploadFolder;
        if (!file_exists($foldername)) {
            mkdir($foldername, 0777);
        }        
        $name = preg_replace("/[^a-z0-9\._]+/", "-", strtolower(time() . rand(1, 9999) . '.' . $file->getClientOriginalName()));
        if ($file->move($foldername, str_replace(" ", "", $name))) {
            return '/uploads/'.$uploadFolder.'/' .$name;
        }
    }

    public function getJWTIdentifier() {
        return $this->getKey();
    }
    public function getJWTCustomClaims() {
        return [];
    } 

    public function getAddress(){
        return $this->hasOne(Address::class,'user_id','id');
    
    }
}
