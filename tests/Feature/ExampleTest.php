<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns a successful response', function () {
    /** @var TestCase $this */
    $response = $this->get('/');
    $response->assertOk();
});
