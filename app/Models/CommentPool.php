<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentPool extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function order(){
        return $this->belongsTo('App\Models\Order');
    }
    public function user(){
        return $this->belongsTo('App\Models\User');
    }
    public function status(){
        return $this->belongsTo('App\Models\Status');
     }
}
