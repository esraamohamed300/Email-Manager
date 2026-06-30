<?php

namespace App\Events;

use App\Models\Email;
use App\Models\Chat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewEmailReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $messageData;
    public int   $recipientId;

    public function __construct(Email $email, Chat $chat, int $recipientId)
    {
        $this->recipientId = $recipientId;

        $sender = $email->sender;

        $this->messageData = [
            'messageId'    => $email->id,
            'threadId'     => $chat->id,
            'type'         => 'received',
            'from'         => $sender->name,
            'fromEmail'    => $sender->email,
            'to'           => '',
            'toEmail'      => '',
            'subject'      => $email->subject,
            'body'         => $email->message,
            'time'         => $email->created_at->format('M d, h:i A'),
            'attachment'   => $email->attachment,
            'thread'       => [[
                'from'       => $sender->name,
                'body'       => $email->message,
                'time'       => $email->created_at->format('M d, h:i A'),
                'attachment' => $email->attachment,
            ]],
        ];
    }

    // Broadcast on a private channel per user
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->recipientId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'new.email';
    }
}