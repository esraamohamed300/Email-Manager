<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailFeatureTest extends TestCase
{
    use RefreshDatabase;

    // ── Feature Test 1 ───────────────────────────────────────────────────────
    // Authenticated user can compose and send an email.
    // Verifies HTTP response and that the record is saved in the DB.
    public function test_authenticated_user_can_send_email(): void
    {
        // Create two users
        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        // Act as the sender
        $response = $this->actingAs($sender)->postJson(route('emails.store'), [
            'composeEmail'   => $receiver->email,
            'composeSubject' => 'Test Subject',
            'composeBody'    => 'Hello from feature test!',
        ]);

        // Assert JSON response
        $response->assertStatus(200)
                 ->assertJson(['message' => 'Email added']);

        // Assert the email was actually saved in the database
        $this->assertDatabaseHas('emails', [
            'sender_id' => $sender->id,
            'subject'   => 'Test Subject',
            'message'   => 'Hello from feature test!',
        ]);

        // Assert a chat was created between the two users
        $this->assertDatabaseHas('chats', [
            'user1_id' => $sender->id,
            'user2_id' => $receiver->id,
        ]);
    }

    // ── Feature Test 2 ───────────────────────────────────────────────────────
    // Unauthenticated user is redirected away from the inbox.
    public function test_guest_cannot_access_inbox(): void
    {
        $response = $this->get(route('inbox'));

        // Should redirect to login, not show the inbox
        $response->assertRedirect(route('login'));
    }

    // ── Feature Test 3 ───────────────────────────────────────────────────────
    // Authenticated user can reply to an existing chat.
    public function test_authenticated_user_can_reply_to_chat(): void
    {
        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        // Create a chat and an initial email manually
        $chat = Chat::create([
            'user1_id' => $sender->id,
            'user2_id' => $receiver->id,
        ]);

        Email::create([
            'chat_id'   => $chat->id,
            'sender_id' => $sender->id,
            'subject'   => 'Original Subject',
            'message'   => 'Original message',
        ]);

        // Receiver replies
        $response = $this->actingAs($receiver)->postJson(
            route('emails.reply', $chat->id),
            ['message' => 'This is my reply']
        );

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Reply added']);

        $this->assertDatabaseHas('emails', [
            'chat_id'   => $chat->id,
            'sender_id' => $receiver->id,
            'message'   => 'This is my reply',
        ]);
    }

    // ── Feature Test 4 ───────────────────────────────────────────────────────
    // Sending to a non-existent email returns a validation error.
    public function test_sending_to_nonexistent_email_fails_validation(): void
    {
        $sender = User::factory()->create();

        $response = $this->actingAs($sender)->postJson(route('emails.store'), [
            'composeEmail'   => 'nobody@nowhere.com',
            'composeSubject' => 'Hello',
            'composeBody'    => 'Test',
        ]);

        // Laravel validation should return 422 Unprocessable Entity
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['composeEmail']);
    }

    // ── Feature Test 5 ───────────────────────────────────────────────────────
    // Authenticated user can delete a chat.
    public function test_authenticated_user_can_delete_chat(): void
    {
        $sender   = User::factory()->create();
        $receiver = User::factory()->create();

        $chat = Chat::create([
            'user1_id' => $sender->id,
            'user2_id' => $receiver->id,
        ]);

        Email::create([
            'chat_id'   => $chat->id,
            'sender_id' => $sender->id,
            'subject'   => 'To be deleted',
            'message'   => 'This will be deleted',
        ]);

        $response = $this->actingAs($sender)->deleteJson(
            route('emails.destroy', $chat->id)
        );

        $response->assertStatus(200);

        // Chat and its emails should be gone
        $this->assertDatabaseMissing('chats',  ['id' => $chat->id]);
        $this->assertDatabaseMissing('emails', ['chat_id' => $chat->id]);
    }
}