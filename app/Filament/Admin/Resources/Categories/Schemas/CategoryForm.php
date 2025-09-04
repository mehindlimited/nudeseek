<?php

namespace App\Filament\Admin\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use App\Models\Target;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->image(),
                Radio::make('target_id')
                    ->label('Target')
                    ->options(fn() => Target::pluck('name', 'id')->toArray())
                    ->required(),
                TextInput::make('legacy')
                    ->required(),
                Toggle::make('is_extreme')
                    ->label('Is Extreme')
                    ->inline(false) // shows label on the left
                    ->default(false),
            ]);
    }
}
