<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CsvDataTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->assertTrue(true);
    }

    /**
     * create test case to to cache data.
     *
     * @return void
     */
    public function testGettingCsvData()
    {
        $response = $this->json( 'GET','api/csv-data');
        $response->assertStatus(200);
    }
}
