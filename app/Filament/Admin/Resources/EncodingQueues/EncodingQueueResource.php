<?php

namespace App\Filament\Admin\Resources\EncodingQueues;

use App\Filament\Admin\Resources\EncodingQueues\Pages\CreateEncodingQueue;
use App\Filament\Admin\Resources\EncodingQueues\Pages\EditEncodingQueue;
use App\Filament\Admin\Resources\EncodingQueues\Pages\ListEncodingQueues;
use App\Filament\Admin\Resources\EncodingQueues\Schemas\EncodingQueueForm;
use App\Filament\Admin\Resources\EncodingQueues\Tables\EncodingQueuesTable;
use App\Models\EncodingQueue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EncodingQueueResource extends Resource
{
    protected static ?string $model = EncodingQueue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'EncodingQueue';

    public static function form(Schema $schema): Schema
    {
        return EncodingQueueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EncodingQueuesTable::configure($table);
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
            'index' => ListEncodingQueues::route('/'),
            'create' => CreateEncodingQueue::route('/create'),
            'edit' => EditEncodingQueue::route('/{record}/edit'),
        ];
    }
}
