<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CollectionItem;
use App\Models\User;

class CollectionItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('username', 'user')->first();
        
        if (!$user) {
            $this->command->warn('User not found, skipping collection items seeding.');
            return;
        }

        $items = [
            [
                'user_id' => $user->id,
                'title' => 'The Legend of Zelda: Breath of the Wild',
                'description' => 'Een open-world actie-avontuur spel voor Nintendo Switch',
                'type' => 'game',
                'platform' => 'Nintendo Switch',
                'category' => 'Action Adventure',
                'condition_rating' => 5,
                'purchase_date' => '2017-03-03',
                'purchase_price' => 59.99,
                'current_value' => 45.00,
                'location' => 'Gaming Shelf',
                'notes' => 'Favoriete spel van alle tijden',
                'cover_image' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=400&h=600&fit=crop',
                'barcode' => '045496590561',
            ],
            [
                'user_id' => $user->id,
                'title' => 'The Lord of the Rings: The Fellowship of the Ring',
                'description' => 'De eerste film in de Lord of the Rings trilogie',
                'type' => 'film',
                'platform' => 'Blu-ray',
                'category' => 'Fantasy',
                'condition_rating' => 4,
                'purchase_date' => '2012-06-15',
                'purchase_price' => 24.99,
                'current_value' => 15.00,
                'location' => 'Movie Collection',
                'notes' => 'Extended Edition',
                'cover_image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=600&fit=crop',
                'barcode' => '5051889000000',
            ],
            [
                'user_id' => $user->id,
                'title' => 'Breaking Bad - Complete Series',
                'description' => 'De complete serie van Breaking Bad op Blu-ray',
                'type' => 'serie',
                'platform' => 'Blu-ray',
                'category' => 'Drama',
                'condition_rating' => 5,
                'purchase_date' => '2014-11-10',
                'purchase_price' => 89.99,
                'current_value' => 75.00,
                'location' => 'TV Series Shelf',
                'notes' => 'Collector\'s Edition met extra features',
                'cover_image' => 'https://images.unsplash.com/photo-1594909122845-11baa439b7bf?w=400&h=600&fit=crop',
                'barcode' => '5039036060000',
            ],
            [
                'user_id' => $user->id,
                'title' => 'The Hobbit',
                'description' => 'Fantasy roman van J.R.R. Tolkien',
                'type' => 'book',
                'platform' => 'Hardcover',
                'category' => 'Fantasy',
                'condition_rating' => 3,
                'purchase_date' => '2010-05-20',
                'purchase_price' => 19.99,
                'current_value' => 25.00,
                'location' => 'Bookshelf',
                'notes' => 'Eerste druk, goede conditie',
                'cover_image' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=400&h=600&fit=crop',
                'barcode' => '9780261103283',
            ],
            [
                'user_id' => $user->id,
                'title' => 'Dark Side of the Moon',
                'description' => 'Iconisch album van Pink Floyd',
                'type' => 'music',
                'platform' => 'Vinyl',
                'category' => 'Rock',
                'condition_rating' => 4,
                'purchase_date' => '2015-08-12',
                'purchase_price' => 35.00,
                'current_value' => 50.00,
                'location' => 'Vinyl Collection',
                'notes' => '180g vinyl reissue',
                'cover_image' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=400&h=600&fit=crop',
                'barcode' => '5099902980014',
            ],
            [
                'user_id' => $user->id,
                'title' => 'Red Dead Redemption 2',
                'description' => 'Western actie-avontuur spel van Rockstar Games',
                'type' => 'game',
                'platform' => 'PlayStation 4',
                'category' => 'Action Adventure',
                'condition_rating' => 5,
                'purchase_date' => '2018-10-26',
                'purchase_price' => 59.99,
                'current_value' => 30.00,
                'location' => 'Gaming Shelf',
                'notes' => 'Special Edition',
                'cover_image' => 'https://images.unsplash.com/photo-1552820728-8b83bb6b773f?w=400&h=600&fit=crop',
                'barcode' => '7104254630000',
            ],
        ];

        foreach ($items as $item) {
            CollectionItem::firstOrCreate(
                [
                    'user_id' => $item['user_id'],
                    'title' => $item['title'],
                    'barcode' => $item['barcode']
                ],
                $item
            );
        }

        $this->command->info('Collection items seeded successfully!');
        $this->command->info('Created ' . count($items) . ' sample items for user: ' . $user->username);
    }
} 