<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $words1 = [
            'dark',
            'crazy',
            'cool',
            'fast',
            'hot',
            'red',
            'blue',
            'green',
            'wild',
            'sweet',
            'silent',
            'magic',
            'golden',
            'iron',
            'shadow',
            'storm',
            'super',
            'mega',
            'ultra',
            'toxic',
            'fire',
            'ice',
            'night',
            'moon',
            'sun',
            'sky',
            'wolf',
            'cat',
            'dog',
            'fox',
            'bloody',
            'cyber',
            'pixel',
            'retro',
            'fuzzy',
            'stormy',
            'cold',
            'happy',
            'sad',
            'angry',
            'lazy',
            'brave',
            'tiny',
            'giant',
            'alpha',
            'beta',
            'omega',
            'turbo',
            'hyper',
            'electro',
            'neo',
            'proto',
            'prime',
            'darkest',
            'lucky',
            'evil',
            'holy',
            'galactic',
            'cosmic',
            'void',
            'stellar',
            'silver',
            'bronze',
            'rapid',
            'mad',
            'ghostly',
            'phantom',
            'wicked',
            'crafter',
            'builder',
            'sneaky',
            'noble',
            'royal',
            'rusty',
            'frosty',
            'burning',
            'cosmo',
            'nuclear',
            'radioactive'
        ];

        $words2 = [
            'wolf',
            'cat',
            'dog',
            'dragon',
            'ninja',
            'hero',
            'queen',
            'king',
            'master',
            'lord',
            'hunter',
            'killer',
            'gamer',
            'rider',
            'dancer',
            'boy',
            'girl',
            'man',
            'woman',
            'lion',
            'tiger',
            'bear',
            'fox',
            'shark',
            'eagle',
            'wizard',
            'ghost',
            'vampire',
            'angel',
            'demon',
            'knight',
            'samurai',
            'pirate',
            'sniper',
            'warrior',
            'archer',
            'thief',
            'monk',
            'giant',
            'beast',
            'robot',
            'alien',
            'cyborg',
            'druid',
            'witch',
            'mage',
            'warlock',
            'paladin',
            'assassin',
            'guardian',
            'overlord',
            'champion',
            'reaper',
            'soldier',
            'ranger',
            'rogue',
            'zealot',
            'templar',
            'warden',
            'jester',
            'oracle',
            'seer',
            'seeress',
            'phantom',
            'ghoul',
            'orc',
            'elf',
            'dwarf',
            'giant',
            'serpent',
            'phoenix',
            'gryphon',
            'hydra',
            'kraken',
            'cyclops',
            'pegasus'
        ];

        $countryIds = [60, 187, 186, 64, 123, 165];

        $usernames = [];

        // Create 50 homosexual + 50 heterosexual
        for ($i = 0; $i < 100; $i++) {
            do {
                $username = strtolower(
                    $words1[array_rand($words1)] .
                        $words2[array_rand($words2)] .
                        rand(1, 999)
                );
            } while (in_array($username, $usernames)); // prevent duplicates

            $usernames[] = $username;

            User::create([
                'username'            => $username,
                'email'               => $username . '@gmail.com',
                'password'            => Hash::make('Paris2025'),
                'role'                => 'user',
                'status'              => 'active',
                'is_real'             => false,
                'country_id'          => $countryIds[array_rand($countryIds)],
                'sexual_orientation'  => $i < 50 ? 'omosexual' : 'heterosexual',
            ]);
        }
    }
}
