<?php

namespace Tests\Unit;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_search_parameters_are_accepted(): void
    {
        $this->seed(CategorySeeder::class);

        $category = Category::first();

        $request = new IndexContactRequest;

        $data = [
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-09-30',
            'per_page' => 20,
            'page' => 1,
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_invalid_gender_is_rejected(): void
    {
        $request = new IndexContactRequest;

        $validator = Validator::make(
            ['gender' => 4],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
    }

    public function test_invalid_category_is_rejected(): void
    {
        $request = new IndexContactRequest;

        $validator = Validator::make(
            ['category_id' => 99999],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
    }

    public function test_invalid_date_is_rejected(): void
    {
        $request = new IndexContactRequest;

        $validator = Validator::make(
            ['date' => 'not-a-date'],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
    }

    public function test_invalid_per_page_is_rejected(): void
    {
        $request = new IndexContactRequest;

        $validator = Validator::make(
            ['per_page' => 101],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
    }

    public function test_invalid_page_is_rejected(): void
    {
        $request = new IndexContactRequest;

        $validator = Validator::make(
            ['page' => 0],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
    }
}
