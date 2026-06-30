<?php

namespace Tests\Unit;

use App\Models\Chat;
use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailUnitTest extends TestCase
{
    use RefreshDatabase;

    // ── Unit Test 1 ──────────────────────────────────────────────────────────
    // Email model belongs to correct sender.
    public function test_email_belongs_to_sender(): void
    {
        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        $chat = Chat::create([
            'user1_id' => $sender->id,
            'user2_id' => $receiver->id,
        ]);

        $email = Email::create([
            'chat_id'   => $chat->id,
            'sender_id' => $sender->id,
            'subject'   => 'Unit Test Subject',
            'message'   => 'Unit test message body',
        ]);

        // The sender relationship should return the correct user
        $this->assertEquals($sender->id, $email->sender->id);
        $this->assertEquals($sender->email, $email->sender->email);
    }

    // ── Unit Test 2 ──────────────────────────────────────────────────────────
    // Chat model correctly identifies the other participant.
    public function test_chat_other_user_helper_returns_correct_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $chat = Chat::create([
            'user1_id' => $user1->id,
            'user2_id' => $user2->id,
        ]);

        // Load relationships
        $chat->load(['user1', 'user2']);

        // From user1's perspective, other user should be user2
        $this->assertEquals($user2->id, $chat->otherUser($user1->id)->id);

        // From user2's perspective, other user should be user1
        $this->assertEquals($user1->id, $chat->otherUser($user2->id)->id);
    }

    // ── Unit Test 3 ──────────────────────────────────────────────────────────
    // Chat emails relationship returns messages in correct order.
    public function test_chat_emails_are_ordered_oldest_first(): void
    {
        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        $chat = Chat::create([
            'user1_id' => $sender->id,
            'user2_id' => $receiver->id,
        ]);

        // Create emails with slight time difference
        $first = Email::create([
            'chat_id'    => $chat->id,
            'sender_id'  => $sender->id,
            'subject'    => 'First',
            'message'    => 'First message',
            'created_at' => now()->subMinutes(5),
        ]);

        $second = Email::create([
            'chat_id'    => $chat->id,
            'sender_id'  => $receiver->id,
            'subject'    => 'Reply',
            'message'    => 'Second message',
            'created_at' => now(),
        ]);

        $emails = $chat->emails()->get();

        // First email should be the oldest
        $this->assertEquals($first->id,  $emails->first()->id);
        $this->assertEquals($second->id, $emails->last()->id);
    }
}