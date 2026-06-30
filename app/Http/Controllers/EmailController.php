<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Email;
use App\Models\User;
use App\Events\NewEmailReceived;                        
use App\Http\Requests\StoreEmailRequest;
use App\Http\Requests\StoreReplyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailController extends Controller
{
    // ── INDEX — main inbox page ───────────────────────────────────────────────
    public function index()
    {
        $user    = Auth::user();
        $user_id = $user->id;

        $chats = Chat::with(['user1', 'user2', 'emails.sender'])
            ->where('user1_id', $user_id)
            ->orWhere('user2_id', $user_id)
            ->get();

        $emails = [];

        foreach ($chats as $chat) {
            $allEmails  = $chat->emails;
            if ($allEmails->isEmpty()) continue;

            $latestEmail = $allEmails->last();
            $isSent      = $latestEmail->sender_id === $user_id;
            $other       = $chat->otherUser($user_id);

            $thread = $allEmails->map(function ($msg) use ($user_id) {
                return [
                    'from'       => $msg->sender_id === $user_id ? 'You' : $msg->sender->name,
                    'body'       => $msg->message,
                    'time'       => $msg->created_at->format('M d, h:i A'),
                    'attachment' => $msg->attachment,
                ];
            })->values()->toArray();

            $emails[] = [
                'messageId'    => $latestEmail->id,
                'threadId'     => $chat->id,
                'type'         => $isSent ? 'sent' : 'received',
                'from'         => $isSent ? $user->name  : $other->name,
                'fromEmail'    => $isSent ? $user->email : $other->email,
                'fromInitials' => $this->initials($isSent ? $user->name : $other->name),
                'to'           => $isSent ? $other->name  : $user->name,
                'toEmail'      => $isSent ? $other->email : $user->email,
                'toInitials'   => $this->initials($isSent ? $other->name : $user->name),
                'subject'      => $latestEmail->subject,
                'body'         => $latestEmail->message,
                'time'         => $latestEmail->created_at->format('M d, h:i A'),
                'status'       => 'New',
                'thread'       => $thread,
            ];
        }

        usort($emails, fn($a, $b) => strcmp($b['time'], $a['time']));

        return view('inbox', compact('emails', 'user'));
    }

    // ── READ — return all emails as JSON (used by AJAX) ───────────────────────
    public function read()
    {
        $user      = Auth::user();
        $user_id   = $user->id;

        $chats = Chat::with(['user1', 'user2', 'emails.sender'])
            ->where('user1_id', $user_id)
            ->orWhere('user2_id', $user_id)
            ->get();

        $emails = [];

        foreach ($chats as $chat) {
            $allEmails = $chat->emails;
            if ($allEmails->isEmpty()) continue;

            $latestEmail = $allEmails->last();
            $isSent      = $latestEmail->sender_id === $user_id;
            $other       = $chat->otherUser($user_id);

            $thread = $allEmails->map(function ($msg) use ($user_id) {
                return [
                    'from'       => $msg->sender_id === $user_id ? 'You' : $msg->sender->name,
                    'body'       => $msg->message,
                    'time'       => $msg->created_at->format('M d, h:i A'),
                    'attachment' => $msg->attachment,
                ];
            })->values()->toArray();

            $emails[] = [
                'messageId'    => $latestEmail->id,
                'threadId'     => $chat->id,
                'type'         => $isSent ? 'sent' : 'received',
                'from'         => $isSent ? $user->name  : $other->name,
                'fromEmail'    => $isSent ? $user->email : $other->email,
                'fromInitials' => $this->initials($isSent ? $user->name : $other->name),
                'to'           => $isSent ? $other->name  : $user->name,
                'toEmail'      => $isSent ? $other->email : $user->email,
                'toInitials'   => $this->initials($isSent ? $other->name : $user->name),
                'subject'      => $latestEmail->subject,
                'body'         => $latestEmail->message,
                'time'         => $latestEmail->created_at->format('M d, h:i A'),
                'status'       => 'New',
                'thread'       => $thread,
            ];
        }

        usort($emails, fn($a, $b) => strcmp($b['time'], $a['time']));

        return response()->json($emails);
    }

    // ── STORE — compose and send a new email ─────────────────────────────────
    public function store(StoreEmailRequest $request)
    {
        $user       = Auth::user();
        $receiver   = User::where('email', $request->composeEmail)->first();
        $attachment = null;

        if ($request->hasFile('attachment')) {
            $file       = $request->file('attachment');
            $filename   = bin2hex(random_bytes(16)) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $attachment = $filename;
        } elseif (is_string($request->attachment) && $request->attachment !== '') {
            $attachment = $request->attachment;
        }

        $chat = Chat::create([
            'user1_id' => $user->id,
            'user2_id' => $receiver->id,
        ]);

        $email = Email::create([                   
            'chat_id'    => $chat->id,
            'sender_id'  => $user->id,
            'subject'    => $request->composeSubject,
            'message'    => $request->composeBody,
            'attachment' => $attachment,
        ]);

        // ── Fire real-time event to recipient ─────────────────────────────────
        $email->load('sender');
        broadcast(new NewEmailReceived($email, $chat, $receiver->id))->toOthers();
 
        return response()->json(['message' => 'Email added', 'chat_id' => $chat->id]);
    }

    // ── REPLY — add a message to an existing chat ─────────────────────────────
    public function reply(StoreReplyRequest $request, Chat $chat)
    {
        $user = Auth::user();

        if ($chat->user1_id !== $user->id && $chat->user2_id !== $user->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $attachment = null;

        if ($request->hasFile('attachment')) {
            $file       = $request->file('attachment');
            $filename   = bin2hex(random_bytes(16)) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $attachment = $filename;
        } elseif (is_string($request->attachment) && $request->attachment !== '') {
            $attachment = $request->attachment;
        }

        $subject = $chat->emails()->first()->subject ?? '';

        $email = Email::create([                 
            'chat_id'    => $chat->id,
            'sender_id'  => $user->id,
            'subject'    => $subject,
            'message'    => $request->message ?? '',
            'attachment' => $attachment,
        ]);

        // ── Fire real-time event to the other person in the chat ──────────────
        $email->load('sender');
        $recipientId = $chat->user1_id === $user->id ? $chat->user2_id : $chat->user1_id;
        broadcast(new NewEmailReceived($email, $chat, $recipientId))->toOthers();
 
        return response()->json(['message' => 'Reply added', 'attachment' => $attachment]);
    }

    // ── UPLOAD ────────────────────────────────────────────────────────────────
    public function upload(Request $request)
    {
        $request->validate([
            'attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,pdf', 'max:5120'],
        ]);

        try {
            $file     = $request->file('attachment');
            $filename = bin2hex(random_bytes(16)) . '.' . $file->getClientOriginalExtension();

            $uploadPath = public_path('uploads');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $file->move($uploadPath, $filename);

            return response()->json([
                'success'  => true,
                'filename' => $filename,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ── DESTROY — delete a chat and all its emails ────────────────────────────
    public function destroy(Chat $chat)
    {
        $user = Auth::user();

        if ($chat->user1_id !== $user->id && $chat->user2_id !== $user->id) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $count = $chat->emails()->count();
        $chat->delete();

        return response()->json(['message' => "Deleted $count messages and associated chats"]);
    }

    // ── Helper: generate initials from a name ────────────────────────────────
    private function initials(string $name): string
    {
        return strtoupper(
            implode('', array_map(
                fn($w) => mb_substr($w, 0, 1),
                array_filter(explode(' ', trim($name)))
            ))
        );
    }
}