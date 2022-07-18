<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $table = 'chat';
    
    protected $fillable = ['id', 'sender_id', 'receiver_id', 'message', 'status', 'created_at', 'updated_at'];

    public function getSender(){
        return $this->belongsTo(User::class,'sender_id');
    }
    public function getReceiver(){
        return $this->belongsTo(User::class,'receiver_id');
    }
    public function kolProfile(){
        return $this->belongsTo(KolProfile::class, 'receiver_id','user_id');
        
    }
}
