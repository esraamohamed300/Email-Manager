<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $fillable = ['user1_id', 'user2_id'];

    // The user who started the chat
    public function user1()
    {
        return $this->belongsTo(User::class, 'user1_id');
    }

    // The user who received the chat
    public function user2()
    {
        return $this->belongsTo(User::class, 'user2_id');
    }

    // All emails in this chat, oldest first
    public function emails()
    {
        return $this->hasMany(Email::class, 'chat_id')->orderBy('created_at', 'asc');
    }

    // Latest email in this chat (for inbox preview)
    public function latestEmail()
    {
        return $this->hasOne(Email::class, 'chat_id')->latestOfMany();
    }

    // Get the other participant given a user id
    public function otherUser(int $userId): User
    {
        return $this->user1_id === $userId ? $this->user2 : $this->user1;
    }
}