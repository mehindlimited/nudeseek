<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code'),
                TextEntry::make('username'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('email_verified_at')
                    ->dateTime(),
                TextEntry::make('role'),
                TextEntry::make('status'),
                TextEntry::make('gender'),
                TextEntry::make('sexual_orientation'),
                TextEntry::make('profile_views')
                    ->numeric(),
                TextEntry::make('videos_count')
                    ->numeric(),
                TextEntry::make('albums_count')
                    ->numeric(),
                TextEntry::make('country_id')
                    ->numeric(),
                TextEntry::make('city'),
                TextEntry::make('birthdate')
                    ->date(),
                TextEntry::make('relationship_status'),
                IconEntry::make('has_avatar')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
