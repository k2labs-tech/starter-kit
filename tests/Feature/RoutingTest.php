<?php

declare(strict_types=1);

test('the root sends guests to the login page', function () {
    $this->get('/')->assertRedirect('/login');
});

test('the login page renders', function () {
    $this->get('/login')->assertOk();
});
