<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    protected $fillable = ['id', 'deal_id', 'order_id', 'kol_profile_id', 'end_user_id', 'start_date','end_date','video_link','image_link', 'tax', 'order_summary'];
}
