<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ContactSeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_admin(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_admin(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->get('/admin');
        $response->assertStatus(200);
    }

    public function test_admin_contacts_are_paginated_by_7(): void
    {
        $this->seed([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);
        $user = User::where('email', 'test@example.com')->first();
        $this->actingAs($user);

        $response = $this->get('/admin');
        $response->assertStatus(200);
        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->count() === 7;
        });
    }

    public function test_admin_can_search_contacts_by_keyword(): void
    {
        $this->seed([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->actingAs($user);

        $response = $this->get('/admin?keyword=@example.com');
        $response->assertStatus(200);
        $response->assertSee('@example.com');
    }

    public function test_admin_can_filter_contacts_by_gender(): void
    {
        $this->seed([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->actingAs($user);
        $response = $this->get('/admin?gender=1');
        $response->assertStatus(200);

        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->every(fn ($contact) => $contact->gender === 1);
        });
    }

    public function test_admin_can_filter_contacts_by_category(): void
    {
        $this->seed([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);
        $category = Category::first();
        $user = User::where('email', 'test@example.com')->first();
        $this->actingAs($user);
        $response = $this->get('/admin?category_id='.$category->id);
        $response->assertStatus(200);
        $response->assertViewHas('contacts', function ($contacts) use ($category) {
            return $contacts->every(fn ($contact) => $contact->category_id === $category->id);
        });
    }

    public function test_admin_can_filter_contacts_by_date(): void
    {
        $this->seed([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);
        $contact = Contact::first();
        $date = $contact->created_at->format('Y-m-d');
        $user = User::where('email', 'test@example.com')->first();
        $this->actingAs($user);

        $response = $this->get('/admin?date='.$date);
        $response->assertStatus(200);

        $response->assertViewHas('contacts', function ($contacts) use ($date) {
            return $contacts->every(fn ($contact) => $contact->created_at->format('Y-m-d') === $date);
        });
    }

    public function test_admin_can_view_contact_detail(): void
    {
        $this->seed([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);

        $contact = Contact::first();
        $user = User::where('email', 'test@example.com')->first();
        $this->actingAs($user);
        $response = $this->get('/admin/contacts/'.$contact->id);
        $response->assertStatus(200);
        $response->assertSee($contact->first_name);
    }

    public function test_admin_can_delete_contact(): void
    {
        $this->seed([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);

        $contact = Contact::first();
        $user = User::where('email', 'test@example.com')->first();
        $this->actingAs($user);
        $response = $this->delete('/admin/contacts/'.$contact->id);
        $response->assertRedirect('/admin');

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    public function test_admin_can_create_tag(): void
    {
        $this->seed(UserSeeder::class);

        $user = User::where('email', 'test@example.com')->first();
        $this->actingAs($user);

        $response = $this->post('/admin/tags', [
            'name' => '新しいタグ',
        ]);
        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', [
            'name' => '新しいタグ',
        ]);
    }

    public function test_admin_can_update_tag(): void
    {
        $this->seed(TagSeeder::class);
        $tag = Tag::first();

        $this->seed(UserSeeder::class);

        $user = User::where('email', 'test@example.com')->first();
        $this->actingAs($user);

        $response = $this->put('/admin/tags/'.$tag->id, [
            'name' => '更新したタグ',
        ]);

        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '更新したタグ',
        ]);
    }

    public function test_admin_can_delete_tag(): void
    {
        $this->seed(TagSeeder::class);
        $tag = Tag::first();

        $this->seed(UserSeeder::class);

        $user = User::where('email', 'test@example.com')->first();
        $this->actingAs($user);
        $response = $this->delete('/admin/tags/'.$tag->id);
        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_admin_can_export_contacts_as_csv(): void
    {
        $this->seed([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->actingAs($user);
        $response = $this->get('/admin/contacts/csv');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_export_filtered_contacts_as_csv(): void
    {
        $this->seed([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            ContactSeeder::class,
        ]);

        $user = User::where(
            'email',
            'test@example.com'
        )->first();

        $this->actingAs($user);

        $contact = Contact::where('gender', 1)->first();

        $response = $this->get(
            '/admin/contacts/csv?gender=1'
        );

        $response->assertStatus(200);

        $response->assertHeader(
            'Content-Type',
            'text/csv; charset=UTF-8'
        );

        $csv = $response->getContent();

        $this->assertStringContainsString(
            $contact->email,
            $csv
        );

        $gender2Contact = Contact::where(
            'gender',
            '!=',
            1
        )->first();

        $this->assertStringNotContainsString(
            $gender2Contact->email,
            $csv
        );
    }
}
