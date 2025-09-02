<?php

namespace App\Filament\Admin\Resources\EncodingQueues\Pages;

use App\Filament\Admin\Resources\EncodingQueues\EncodingQueueResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEncodingQueue extends EditRecord
{
    protected static string $resource = EncodingQueueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
