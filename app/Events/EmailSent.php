<?php

namespace App\Events;

use App\Models\Email;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Email $email)
    {
        //
    }

    public function broadcastOn(): array
    {
        $chat = $this->email->chat;
        $recipientId = $chat->user1_id === $this->email->sender_id
            ? $chat->user2_id
            : $chat->user1_id;

        return [
            new PrivateChannel('App.Models.User.' . $recipientId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id'          => $this->email->id,
            'chat_id'     => $this->email->chat_id,
            'sender_id'   => $this->email->sender_id,
            'message'     => $this->email->message,
            'created_at'  => $this->email->created_at->format('h:i A'),
            'sender_name' => $this->email->sender->name,
        ];
    }
}