<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LikeDislike extends Model
{
    use HasFactory;

    protected $table='likes_dislikes';

    protected $fillable=['sender_id', 'receiver_id', 'type'];
}
