<?php

use Tests\TestCase;

/*
| Bind every Feature test to the Laravel TestCase so they can use the
| application helpers ($this->get(), $this->actingAs(), ...).
*/
pest()->extend(TestCase::class)->in('Feature');
