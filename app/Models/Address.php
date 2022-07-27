<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'address';

    protected $fillable = ['id', 'user_id', 'address', 'landmark',  'city', 'state', 'zip', 'country', 'updated_at'];
}
