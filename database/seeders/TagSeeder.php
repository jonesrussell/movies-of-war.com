<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            // Genres
            ['name' => 'Drama', 'type' => 'genre'],
            ['name' => 'Action', 'type' => 'genre'],
            ['name' => 'Documentary', 'type' => 'genre'],
            ['name' => 'Thriller', 'type' => 'genre'],
            ['name' => 'Historical', 'type' => 'genre'],
            ['name' => 'Biography', 'type' => 'genre'],

            // Eras
            ['name' => 'WWI', 'type' => 'era'],
            ['name' => 'WWII', 'type' => 'era'],
            ['name' => 'Vietnam War', 'type' => 'era'],
            ['name' => 'Korean War', 'type' => 'era'],
            ['name' => 'Gulf War', 'type' => 'era'],
            ['name' => 'Iraq War', 'type' => 'era'],
            ['name' => 'Afghanistan War', 'type' => 'era'],
            ['name' => 'Cold War', 'type' => 'era'],

            // Themes
            ['name' => 'Heroism', 'type' => 'theme'],
            ['name' => 'Sacrifice', 'type' => 'theme'],
            ['name' => 'Survival', 'type' => 'theme'],
            ['name' => 'Combat', 'type' => 'theme'],
            ['name' => 'Leadership', 'type' => 'theme'],
            ['name' => 'Anti-war', 'type' => 'theme'],
            ['name' => 'Prisoner of War', 'type' => 'theme'],
            ['name' => 'Resistance', 'type' => 'theme'],
        ];

        foreach ($tags as $tag) {
            Tag::create($tag);
        }
    }
}
