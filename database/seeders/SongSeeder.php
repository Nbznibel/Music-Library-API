<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Song;

class SongSeeder extends Seeder
{
    public function run(): void
    {
        Song::insert([
            [
                'title' => 'Blinding Lights',
                'artist' => 'The Weeknd',
                'duration' => 200,
                'release_date' => '2020-11-29',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Shape of You',
                'artist' => 'Ed Sheeran',
                'duration' => 240,
                'release_date' => '2017-01-06',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Someone Like You',
                'artist' => 'Adele',
                'duration' => 285,
                'release_date' => '2011-01-24',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
