<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $fillable = ['chat_id', 'sender_id', 'subject', 'message', 'attachment'];

    // The chat this email belongs to
    public function chat()
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    // The user who sent this email
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}