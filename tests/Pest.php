<?php

pest()->extend(Tests\TestCase::class)->in('Feature')
	->use(\Illuminate\Foundation\Testing\RefreshDatabase::class);
