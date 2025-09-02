<?php

namespace App\Filament\Admin\Resources\Targets\Pages;

use App\Filament\Admin\Resources\Targets\TargetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTarget extends CreateRecord
{
    protected static string $resource = TargetResource::class;
}
