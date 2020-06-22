<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'message','conversation_id','sender_id', 'receiver_id','read_at'
    ];

    //Get conversation for the message
    public function conversation()
    {
        return $this->belongsTo('App\Models\Conversation');
    }
}
