<?php

namespace App\Filament\Admin\Resources\Countries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('code3')
                    ->required(),
                TextInput::make('numeric_code'),
                TextInput::make('capital'),
                TextInput::make('flag_emoji'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
