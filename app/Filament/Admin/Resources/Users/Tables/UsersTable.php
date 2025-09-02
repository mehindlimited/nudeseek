<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('role'),
                TextColumn::make('status'),
                TextColumn::make('gender'),
                TextColumn::make('sexual_orientation'),
                TextColumn::make('profile_views')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('videos_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('albums_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('country_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('birthdate')
                    ->date()
                    ->sortable(),
                TextColumn::make('relationship_status'),
                IconColumn::make('has_avatar')
                    ->boolean(),
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
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
