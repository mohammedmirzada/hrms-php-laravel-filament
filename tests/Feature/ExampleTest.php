<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

it('returns a successful response', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->get('/admin');
    $response->assertRedirect('/admin/login');
});