<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('username')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->nullable()
                    ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn($state) => filled($state)) // only send to model if not empty
                    ->label('Password'),
                Select::make('role')
                    ->options(['admin' => 'Admin', 'user' => 'User'])
                    ->default('user')
                    ->required(),
                Select::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'banned' => 'Banned'])
                    ->default('active')
                    ->required(),
                Select::make('gender')
                    ->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other']),
                Select::make('sexual_orientation')
                    ->options([
                        'heterosexual' => 'Heterosexual',
                        'bisexual' => 'Bisexual',
                        'omosexual' => 'Omosexual',
                        'asexual' => 'Asexual',
                        'other' => 'Other',
                    ]),
                TextInput::make('profile_views')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('videos_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('albums_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('country_id')
                    ->label('Country')
                    ->relationship('country', 'name') // usa la relazione se hai un `belongsTo(Country::class)`
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('city'),
                DatePicker::make('birthdate'),
                Select::make('relationship_status')
                    ->options([
                        'single' => 'Single',
                        'in_a_relationship' => 'In a relationship',
                        'married' => 'Married',
                        'complicated' => 'Complicated',
                        'open' => 'Open',
                    ]),
                Toggle::make('has_avatar')
                    ->required(),
            ]);
    }
}
