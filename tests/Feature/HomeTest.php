<?php

test('home page responds successfully', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});
