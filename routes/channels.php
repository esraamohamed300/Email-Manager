<?php

use Illuminate\Support\Facades\Broadcast;

// Private channel — only the authenticated user can listen to their own channel
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});