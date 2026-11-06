<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_is_available(): void
    {
        $this->withoutVite()->get('/')->assertOk()->assertSee('tautan Anda');
    }
}
