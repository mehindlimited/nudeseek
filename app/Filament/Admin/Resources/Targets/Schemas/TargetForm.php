<?php

namespace App\Filament\Admin\Resources\Targets\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TargetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
            ]);
    }
}
