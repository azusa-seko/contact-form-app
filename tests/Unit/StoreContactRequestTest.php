<?php

namespace Tests\Unit;

use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Database\Seeders\CategorySeeder;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_contact_data_is_accepted(): void
    {
        $this->seed([
            CategorySeeder::class,
            TagSeeder::class,
        ]);

        $category = Category::first();
        $tag = Tag::first();

        $request = new StoreContactRequest;

        $data = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都新宿区1-1-1',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => [$tag->id],
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_required_fields_are_rejected(): void
    {
        $request = new StoreContactRequest;

        $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->fails());

        $this->assertTrue($validator->errors()->has('first_name'));
        $this->assertTrue($validator->errors()->has('last_name'));
        $this->assertTrue($validator->errors()->has('gender'));
        $this->assertTrue($validator->errors()->has('email'));
        $this->assertTrue($validator->errors()->has('tel'));
        $this->assertTrue($validator->errors()->has('address'));
        $this->assertTrue($validator->errors()->has('category_id'));
        $this->assertTrue($validator->errors()->has('detail'));
    }

    public function test_invalid_tel_is_rejected(): void
    {
        $request = new StoreContactRequest;

        $validator = Validator::make(
            ['tel' => '12345'],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
    }

    public function test_invalid_category_is_rejected(): void
    {
        $request = new StoreContactRequest;

        $validator = Validator::make(
            ['category_id' => 99999],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
    }

    public function test_invalid_tag_is_rejected(): void
    {
        $request = new StoreContactRequest;

        $validator = Validator::make(
            ['tag_ids' => [99999]],
            $request->rules()
        );

        $this->assertTrue($validator->fails());
    }
}
