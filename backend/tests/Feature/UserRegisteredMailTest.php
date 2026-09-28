<?php

use App\Mail\UserRegistered;
use App\Models\User;

test('user registered mailable renders successfully with recipient details and temporary password', function () {
    $user = new User([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane.doe@university.edu',
    ]);

    $tempPassword = 'SecretP@ssw0rd!';
    $mailable = new UserRegistered($user, $tempPassword);

    $mailable->assertHasSubject('Your University Exam System Account Credentials');

    $html = $mailable->render();

    expect($html)
        ->toContain('Jane Doe')
        ->toContain('jane.doe@university.edu')
        ->toContain('SecretP@ssw0rd!')
        ->toContain('First-Time Login Security Requirement')
        ->toContain('/login');
});
