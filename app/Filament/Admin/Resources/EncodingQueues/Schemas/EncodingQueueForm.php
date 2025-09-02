<?php

namespace App\Filament\Admin\Resources\EncodingQueues\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class EncodingQueueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('video_code')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('input_file_path')
                    ->required(),
                TextInput::make('output_file_path'),
                TextInput::make('thumbnail_paths'),
                TextInput::make('encoding_options'),
                Textarea::make('error_message')
                    ->columnSpanFull(),
                TextInput::make('retry_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('max_retries')
                    ->required()
                    ->numeric()
                    ->default(3),
                DateTimePicker::make('last_retry_at'),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
