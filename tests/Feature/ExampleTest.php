
<?php

test('home page redirects to login', function () {
    // Make sure we're a guest (not logged in)
    auth()->logout();
    
    $response = $this->get('/');

    $response->assertRedirect('/login');
});