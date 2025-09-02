<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $categories = [
            ['name' => 'Generic', 'slug' => 'gay-generic', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay'],
            ['name' => '3D & Toons', 'slug' => 'gay-3d-toons', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay 3D & toons'],
            ['name' => 'Asian', 'slug' => 'gay-asian', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay asian'],
            ['name' => 'Bareback', 'slug' => 'gay-bareback', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay bareback'],
            ['name' => 'BDSM', 'slug' => 'gay-bdsm', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay BDSM'],
            ['name' => 'Bear', 'slug' => 'gay-bear', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay Bear'],
            ['name' => 'Big Cock', 'slug' => 'gay-big-cock', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay big cock'],
            ['name' => 'Bizarre', 'slug' => 'gay-bizare', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay bizarre'],
            ['name' => 'Black Men', 'slug' => 'gay-black-men', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay black men'],
            ['name' => 'Blowjob', 'slug' => 'gay-blowjob', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay blowjob'],
            ['name' => 'Feet', 'slug' => 'gay-feet', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay feet'],
            ['name' => 'Fetish', 'slug' => 'gay-fetish', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay fetish'],
            ['name' => 'Fisting', 'slug' => 'gay-fisting', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay fisting'],
            ['name' => 'Glory Hole', 'slug' => 'gay-glory-hole', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'hay hlory hole'],
            ['name' => 'Handjob', 'slug' => 'gay-handjob', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay handjob'],
            ['name' => 'Indian', 'slug' => 'gay-indian', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay indian'],
            ['name' => 'Interracial', 'slug' => 'gay-interracial', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay interracial'],
            ['name' => 'Massage', 'slug' => 'gay-massage', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay massage'],
            ['name' => 'Mature', 'slug' => 'gay-mature', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay mature'],
            ['name' => 'Muscle Men', 'slug' => 'gay-muscle-men', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay muscle men'],
            ['name' => 'Orgy', 'slug' => 'gay-orgy', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay orgy'],
            ['name' => 'Pissing', 'slug' => 'gay-pissing', 'target_id' => 1, 'is_extreme' => true, 'legacy' => 'gay pissing'],
            ['name' => 'Scat', 'slug' => 'gay-scat', 'target_id' => 1, 'is_extreme' => true, 'legacy' => 'gay scat'],
            ['name' => 'Smoking', 'slug' => 'gay-smoking', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay smoking'],
            ['name' => 'Spanking', 'slug' => 'gay-spanking', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay spanking'],
            ['name' => 'Twinks', 'slug' => 'gay-twinks', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'gay twinks'],
            ['name' => 'Male Butts', 'slug' => 'gay-male-butts', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'male butts'],
            ['name' => 'Male Farting', 'slug' => 'gay-male-farting', 'target_id' => 1, 'is_extreme' => true, 'legacy' => 'male farting'],
            ['name' => 'Male Humping', 'slug' => 'gay-male-humping', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'male humping'],
            ['name' => 'Male Verbal', 'slug' => 'gay-male-verbal', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'male verbal'],
            ['name' => 'Male Voyeur', 'slug' => 'gay-male-voyeur', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'male voyeur'],
            ['name' => 'Men Flashing', 'slug' => 'gay-men-flashing', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'men flashing'],
            ['name' => 'Penis Pumping', 'slug' => 'gay-penis-pumping', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'penis pumping'],
            ['name' => 'Sissy Crossdresser', 'slug' => 'gay-sissy-crossdresser', 'target_id' => 1, 'is_extreme' => false, 'legacy' => 'sissy vrossdresser'],
            ['name' => 'Straight Guys', 'slug' => 'gay-straight-guys', 'target_id' => 1, 'is_extreme' => false, 'legacy' => ' str8 guys'],
            ['name' => '3D', 'slug' => '3d', 'target_id' => 2, 'is_extreme' => false, 'legacy' => '3D'],
            ['name' => 'Amateur', 'slug' => 'amateur', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'amateur'],
            ['name' => 'Amputee', 'slug' => 'amputee', 'target_id' => 2, 'is_extreme' => true, 'legacy' => 'amputee'],
            ['name' => 'Anal', 'slug' => 'anal', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'anal'],
            ['name' => 'Asian', 'slug' => 'asian', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'asian'],
            ['name' => 'BDSM', 'slug' => 'bdsm', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'BDSM'],
            ['name' => 'Big Women', 'slug' => 'big-women', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'big women'],
            ['name' => 'Bisexual', 'slug' => 'bisexual', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'bisexual'],
            ['name' => 'Bizarre', 'slug' => 'bizarre', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'bizarre'],
            ['name' => 'Black And Ebony', 'slug' => 'black-and-ebony', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'black and ebony'],
            ['name' => 'Blowjob', 'slug' => 'blowjob', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'blowjob'],
            ['name' => 'Celebrity', 'slug' => 'celebrity', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'celebrity'],
            ['name' => 'Creampie', 'slug' => 'creampie', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'creampie'],
            ['name' => 'Deepthroat', 'slug' => 'deepthroat', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'deepthroat'],
            ['name' => 'Farting', 'slug' => 'farting', 'target_id' => 2, 'is_extreme' => true, 'legacy' => 'farting'],
            ['name' => 'Femdom', 'slug' => 'femdom', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'femdom'],
            ['name' => 'Fetish', 'slug' => 'fetish', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'fetish'],
            ['name' => 'Fisting', 'slug' => 'fisting', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'fisting'],
            ['name' => 'Fun', 'slug' => 'fun', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'fun'],
            ['name' => 'Gangbang', 'slug' => 'gangbang', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'gangbang'],
            ['name' => 'Girlfriend', 'slug' => 'girlfriend', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'girlfriend'],
            ['name' => 'Group Sex', 'slug' => 'groupsex', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'group sex'],
            ['name' => 'Handjob', 'slug' => 'handjob', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'handjob'],
            ['name' => 'Hentai', 'slug' => 'hentai', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'hentai'],
            ['name' => 'Interracial', 'slug' => 'interracial', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'interracial'],
            ['name' => 'Japanese', 'slug' => 'japanese', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'Japanese'],
            ['name' => 'Latina', 'slug' => 'latina', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'latina'],
            ['name' => 'Lesbian', 'slug' => 'lesbian', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'lesbian'],
            ['name' => 'Male', 'slug' => 'male', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'male'],
            ['name' => 'Mature', 'slug' => 'mature', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'mature'],
            ['name' => 'Nudism', 'slug' => 'nudism', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'nudism'],
            ['name' => 'Other', 'slug' => 'other', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'other'],
            ['name' => 'Paraplegic', 'slug' => 'paraplegic', 'target_id' => 2, 'is_extreme' => true, 'legacy' => 'paraplegic'],
            ['name' => 'Pissing', 'slug' => 'pissing', 'target_id' => 2, 'is_extreme' => true, 'legacy' => 'pissing'],
            ['name' => 'Preggo Sex', 'slug' => 'preggo-sex', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'preggo sex'],
            ['name' => 'Public', 'slug' => 'public', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'public'],
            ['name' => 'Scat', 'slug' => 'scat', 'target_id' => 2, 'is_extreme' => true, 'legacy' => 'scat'],
            ['name' => 'Scat Men', 'slug' => 'scat-men', 'target_id' => 2, 'is_extreme' => true, 'legacy' => 'scat men'],
            ['name' => 'Shemale', 'slug' => 'shemale', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'shemale'],
            ['name' => 'Shemale Scat', 'slug' => 'shemale-scat', 'target_id' => 2, 'is_extreme' => true, 'legacy' => 'shemale scat'],
            ['name' => 'Squirting', 'slug' => 'squirting', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'squirting'],
            ['name' => 'Teens', 'slug' => 'teens', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'teens'],
            ['name' => 'Toilet', 'slug' => 'toilet', 'target_id' => 2, 'is_extreme' => true, 'legacy' => 'toilet'],
            ['name' => 'Upskirt', 'slug' => 'upskirt', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'upskirt'],
            ['name' => 'Vomiting', 'slug' => 'vomiting', 'target_id' => 2, 'is_extreme' => true, 'legacy' => 'vomiting'],
            ['name' => 'Voyeur', 'slug' => 'voyeur', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'voyeur'],
            ['name' => 'Wetting', 'slug' => 'wetting', 'target_id' => 2, 'is_extreme' => false, 'legacy' => 'wetting'],
        ];

        DB::table('categories')->insert($categories);
    }
}
