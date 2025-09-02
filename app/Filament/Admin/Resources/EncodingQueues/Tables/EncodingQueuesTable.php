<?php

namespace App\Filament\Admin\Resources\EncodingQueues\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EncodingQueuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('video_code')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('input_file_path')
                    ->searchable(),
                TextColumn::make('output_file_path')
                    ->searchable(),
                TextColumn::make('retry_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_retries')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_retry_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
