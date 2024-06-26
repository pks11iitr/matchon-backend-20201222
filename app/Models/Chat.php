<?php

namespace App\Models;

use App\Models\Traits\DocumentUploadTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Chat extends Model
{
    use HasFactory, DocumentUploadTrait;

    protected $table='chats';

    protected $fillable=['user_1', 'user_2', 'direction', 'message', 'type', 'image'];


    public function user1(){
        return $this->belongsTo('App\Models\Customer', 'user_1');
    }

    public function user2(){
        return $this->belongsTo('App\Models\Customer', 'user_2');
    }


    public function getImageAttribute($value){
        if($value)
            return Storage::url($value);
        return '';
    }

    public function getCreatedAtAttribute($value){
        return date('d/m/Y h:ia', strtotime($value));
    }
}
