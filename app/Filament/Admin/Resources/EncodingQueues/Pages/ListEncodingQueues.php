<?php

namespace App\Filament\Admin\Resources\EncodingQueues\Pages;

use App\Filament\Admin\Resources\EncodingQueues\EncodingQueueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEncodingQueues extends ListRecords
{
    protected static string $resource = EncodingQueueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
