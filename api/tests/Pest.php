<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Bind every Feature test to the Laravel TestCase so they can use the
| application helpers ($this->get(), $this->actingAs(), ...), and reset
| the database between tests.
*/
pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
