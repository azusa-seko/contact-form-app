<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ContactSeeder;
use Database\Seeders\TagSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contacts_index_returns_json(): void
    {
        $this->seed([
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);

        $response = $this->getJson('/api/v1/contacts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'category',
                    'first_name',
                    'last_name',
                    'gender',
                    'email',
                    'tel',
                    'address',
                    'building',
                    'detail',
                    'tags',
                    'created_at',
                    'updated_at',
                ],
            ],
            'links',
            'meta',
        ]);
    }

    public function test_contacts_index_can_search_by_keyword(): void
    {
        $this->seed([
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);

        $contact = Contact::first();

        $response = $this->getJson(
            '/api/v1/contacts?keyword='.urlencode($contact->email)
        );

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'email' => $contact->email,
        ]);
    }

    public function test_contacts_index_can_paginate(): void
    {
        $this->seed([
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);

        $response = $this->getJson('/api/v1/contacts?per_page=5');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.per_page', 5);
    }

    public function test_contacts_index_rejects_invalid_gender(): void
    {
        $response = $this->getJson('/api/v1/contacts?gender=4');

        $response->assertStatus(422);
    }

    public function test_contacts_show_returns_contact(): void
    {
        $this->seed([
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);

        $contact = Contact::first();

        $response = $this->getJson('/api/v1/contacts/'.$contact->id);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath('data.email', $contact->email);
    }

    public function test_contacts_show_returns_404_for_missing_contact(): void
    {
        $response = $this->getJson('/api/v1/contacts/99999');

        $response->assertStatus(404);
    }

    public function test_contacts_can_be_created_via_api(): void
    {
        $this->seed([
            CategorySeeder::class,
            TagSeeder::class,
        ]);

        $category = Category::first();
        $tag = Tag::first();

        $data = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'api-test@example.com',
            'tel' => '09012345678',
            'address' => '東京都新宿区1-1-1',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => 'APIからのお問い合わせです。',
            'tag_ids' => [$tag->id],
        ];

        $response = $this->postJson('/api/v1/contacts', $data);

        $response->assertStatus(201);

        $response->assertJsonPath('data.email', 'api-test@example.com');

        $this->assertDatabaseHas('contacts', [
            'email' => 'api-test@example.com',
        ]);

        $contact = Contact::where('email', 'api-test@example.com')->first();

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_contacts_creation_rejects_invalid_data(): void
    {
        $response = $this->postJson('/api/v1/contacts', [
            'first_name' => '',
            'last_name' => '',
            'gender' => 4,
            'email' => 'invalid-email',
            'tel' => '12345',
            'address' => '',
            'category_id' => 99999,
            'detail' => '',
            'tag_ids' => [99999],
        ]);

        $response->assertStatus(422);
    }

    public function test_contacts_can_be_updated_via_api(): void
    {
        $this->seed([
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);

        $contact = Contact::first();
        $category = Category::first();
        $tag = Tag::first();

        $data = [
            'first_name' => '次郎',
            'last_name' => '佐藤',
            'gender' => 2,
            'email' => 'updated@example.com',
            'tel' => '08012345678',
            'address' => '大阪府大阪市1-2-3',
            'building' => '更新ビル202',
            'category_id' => $category->id,
            'detail' => '更新されたお問い合わせ内容です。',
            'tag_ids' => [$tag->id],
        ];

        $response = $this->putJson(
            '/api/v1/contacts/'.$contact->id,
            $data
        );

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.email',
            'updated@example.com'
        );

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'email' => 'updated@example.com',
            'first_name' => '次郎',
            'last_name' => '佐藤',
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_contacts_update_returns_404_for_missing_contact(): void
    {
        $this->seed([
            CategorySeeder::class,
            TagSeeder::class,
        ]);

        $category = Category::first();
        $tag = Tag::first();

        $data = [
            'first_name' => '次郎',
            'last_name' => '佐藤',
            'gender' => 2,
            'email' => 'updated@example.com',
            'tel' => '08012345678',
            'address' => '大阪府大阪市1-2-3',
            'building' => '更新ビル202',
            'category_id' => $category->id,
            'detail' => '更新されたお問い合わせ内容です。',
            'tag_ids' => [$tag->id],
        ];

        $response = $this->putJson(
            '/api/v1/contacts/99999',
            $data
        );

        $response->assertStatus(404);
    }

    public function test_contacts_update_rejects_invalid_data(): void
    {
        $this->seed([
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);

        $contact = Contact::first();
        $category = Category::first();
        $tag = Tag::first();

        $data = [
            'first_name' => '次郎',
            'last_name' => '佐藤',
            'gender' => 4,
            'email' => 'updated@example.com',
            'tel' => '08012345678',
            'address' => '大阪府大阪市1-2-3',
            'building' => '更新ビル202',
            'category_id' => $category->id,
            'detail' => '更新されたお問い合わせ内容です。',
            'tag_ids' => [$tag->id],
        ];

        $response = $this->putJson(
            '/api/v1/contacts/'.$contact->id,
            $data
        );

        $response->assertStatus(422);
    }

    public function test_contacts_can_be_deleted_via_api(): void
    {
        $this->seed([
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);

        $contact = Contact::first();

        $response = $this->deleteJson(
            '/api/v1/contacts/'.$contact->id
        );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    public function test_contacts_delete_returns_404_for_missing_contact(): void
    {
        $response = $this->deleteJson('/api/v1/contacts/99999');

        $response->assertStatus(404);
    }
}
