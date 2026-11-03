<?php

declare(strict_types=1);

test('home page responds successfully', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});
