<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory , Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    // Chats where this user is user1
    public function chatsAsUser1()
    {
        return $this->hasMany(Chat::class, 'user1_id');
    }

    // Chats where this user is user2
    public function chatsAsUser2()
    {
        return $this->hasMany(Chat::class, 'user2_id');
    }

    // All chats this user is part of (both sides combined)
    public function allChats()
    {
        return Chat::where('user1_id', $this->id)
                   ->orWhere('user2_id', $this->id);
    }

    // Emails this user has sent
    public function sentEmails()
    {
        return $this->hasMany(Email::class, 'sender_id');
    }
}