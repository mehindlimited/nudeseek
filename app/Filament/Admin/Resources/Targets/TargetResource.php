<?php

namespace App\Filament\Admin\Resources\Targets;

use App\Filament\Admin\Resources\Targets\Pages\CreateTarget;
use App\Filament\Admin\Resources\Targets\Pages\EditTarget;
use App\Filament\Admin\Resources\Targets\Pages\ListTargets;
use App\Filament\Admin\Resources\Targets\Schemas\TargetForm;
use App\Filament\Admin\Resources\Targets\Tables\TargetsTable;
use App\Models\Target;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TargetResource extends Resource
{
    protected static ?string $model = Target::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'target';

    public static function form(Schema $schema): Schema
    {
        return TargetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TargetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTargets::route('/'),
            'create' => CreateTarget::route('/create'),
            'edit' => EditTarget::route('/{record}/edit'),
        ];
    }
}
