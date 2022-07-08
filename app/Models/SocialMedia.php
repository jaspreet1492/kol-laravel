<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialMedia extends Model
{
    use HasFactory;

    protected $table = 'social_media';
    
    protected $fillable = ['id', 'user_id', 'profile_id', 'name', 'social_user_id', 'followers', 'status', 'created_at', 'updated_at'];
}
