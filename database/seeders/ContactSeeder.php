<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('ja_JP');

        $categories = Category::pluck('id')->toArray();
        $tags = Tag::pluck('id')->toArray();

        // 20件データ作る
        for ($i = 0; $i < 20; $i++) {
            $contact = Contact::create([
                'category_id' => $faker->randomElement($categories),
                'first_name' => $faker->lastName(),
                'last_name' => $faker->firstName(),
                'gender' => $faker->numberBetween(1, 3),
                'email' => $faker->safeEmail(),
                'tel' => $faker->numerify('090########'),
                'address' => $faker->address(),
                'building' => $faker->optional()->secondaryAddress(),
                'detail' => $faker->realText(120),
            ]);

            $contact->tags()->attach(
                $faker->randomElements($tags, rand(1, 3))
            );
        }
    }
}
