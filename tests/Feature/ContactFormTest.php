<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Database\Seeders\CategorySeeder;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_is_displayed(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(TagSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('商品のお届けについて');
        $response->assertSee('質問');
    }

    public function test_contact_confirmation_page_is_displayed(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(TagSeeder::class);

        $category = Category::first();
        $tags = Tag::take(2)->pluck('id')->toArray();

        $data = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => 'テストマンション101',
            'category_id' => $category->id,
            'detail' => 'テストのお問い合わせです。',
            'tag_ids' => $tags,
        ];

        $response = $this->post('/contacts/confirm', $data);
        $response->assertStatus(200);
        $response->assertSee('山田');
    }

    public function test_contact_can_be_stored(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(TagSeeder::class);

        $category = Category::first();
        $tags = Tag::take(2)->pluck('id')->toArray();

        $data = [
            'first_name' => '佐藤',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'hanako@example.com',
            'tel' => '08012345678',
            'address' => '大阪府大阪市',
            'building' => 'テストビル202',
            'category_id' => $category->id,
            'detail' => '保存テストです。',
            'tag_ids' => $tags,
        ];

        $response = $this->post('/contacts', $data);
        $response->assertRedirect('/thanks');

        $this->assertDatabaseHas('contacts', [
            'email' => 'hanako@example.com',
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => Contact::where('email', 'hanako@example.com')->first()->id,
            'tag_id' => $tags[0],
        ]);
    }

    public function test_contact_validation_rejects_invalid_tel(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(TagSeeder::class);

        $category = Category::first();
        $tags = Tag::take(2)->pluck('id')->toArray();

        $data = [
            'first_name' => '佐藤',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'hanako@example.com',
            'tel' => '12345',
            'address' => '大阪府大阪市',
            'building' => 'テストビル202',
            'category_id' => $category->id,
            'detail' => 'バリデーションテストです。',
            'tag_ids' => $tags,
        ];

        $response = $this->post('/contacts', $data);
        $response->assertRedirect('/');
        $response->assertSessionHasErrors('tel');
    }

    public function test_contact_validation_rejects_missing_required_fields(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(TagSeeder::class);

        $category = Category::first();
        $tags = Tag::take(2)->pluck('id')->toArray();
        $data = [];

        $response = $this->post('/contacts', $data);
        $response->assertRedirect('/');

        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ]);
    }

    public function test_contact_validation_rejects_invalid_tag(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(TagSeeder::class);

        $category = Category::first();

        $data = [
            'first_name' => '佐藤',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'hanako@example.com',
            'tel' => '08012345678',
            'address' => '大阪府大阪市',
            'building' => 'テストビル202',
            'category_id' => $category->id,
            'detail' => 'タグバリデーションテストです。',
            'tag_ids' => [99999],
        ];

        $response = $this->post('/contacts', $data);
        $response->assertRedirect('/');
        $response->assertSessionHasErrors('tag_ids.0');
    }
}
