<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealRequest extends Model
{
    use HasFactory;

    protected $table = 'deal_requests';

    protected $fillable = ['id', 'end_user_id', 'kol_user_id', 'updated_at'];
}
