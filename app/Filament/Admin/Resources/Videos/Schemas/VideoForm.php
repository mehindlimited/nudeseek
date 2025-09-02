<?php

namespace App\Filament\Admin\Resources\Videos\Schemas;

use App\Models\Tag;
use App\Models\Target;
use Faker\Core\File;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Models\Video;
use App\Models\User;
use App\Models\Category;

class VideoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('slug', Str::slug($state));
                    }),
                TextInput::make('slug')
                    ->disabled()
                    ->dehydrated()
                    ->maxLength(255)
                    ->unique(table: 'videos', column: 'slug', ignoreRecord: true),
                Hidden::make('code')
                    ->default(fn() => Video::generateUniqueCode()),
                Textarea::make('description')
                    ->columnSpanFull()
                    ->maxLength(65535),
                FileUpload::make('video_file')
                    ->dehydrated(false)
                    ->required()
                    ->label('Video File')
                    ->disk('s3')
                    ->directory('temp')
                    ->acceptedFileTypes([
                        'video/mp4',
                        'video/quicktime',
                        'video/x-msvideo',
                        'video/x-matroska',
                        'video/x-ms-wmv',
                    ])
                    ->maxSize(2097152)
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get) {
                        $code = $get('code');
                        $extension = $file->getClientOriginalExtension();
                        return $code . '.' . $extension;
                    }),

                FileUpload::make('thumbnail')
                    ->dehydrated(false)
                    ->label('Thumbnail')
                    ->disk('s3')
                    ->directory('temp')
                    ->acceptedFileTypes([
                        'image/jpeg',
                        'image/png',
                    ])
                    ->maxSize(2048)
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get) {
                        $code = $get('code');
                        $extension = $file->getClientOriginalExtension();
                        return $code . '_thumb.' . $extension;
                    }),
                DateTimePicker::make('published_at')
                    ->required(),
                Hidden::make('status')
                    ->default('draft'),
                Select::make('access_type')
                    ->options(['public' => 'Public', 'private' => 'Private', 'unlisted' => 'Unlisted'])
                    ->default('public')
                    ->required(),
                Select::make('user_id')
                    ->label('User')
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(
                        fn(string $search): array =>
                        User::where('username', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('username', 'id')
                            ->toArray()
                    )
                    ->getOptionLabelUsing(
                        fn($value): ?string =>
                        User::find($value)?->name
                    ),
                Radio::make('target_id')
                    ->label('Target')
                    ->options(fn() => Target::pluck('name', 'id')->toArray())
                    ->default(fn() => Target::first()?->id)
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set) {
                        $set('category_id', null); // Clear category when target changes
                    }),
                Select::make('category_id')
                    ->label('Category')
                    ->searchable()
                    ->options(function (callable $get) {
                        $targetId = $get('target_id');
                        if (!$targetId) {
                            return [];
                        }
                        return Category::where('target_id', $targetId)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->reactive()
                    ->placeholder('Select a category'),
                TagsInput::make('tags')
                    ->label('Tags')
                    ->suggestions(
                        fn() => Tag::pluck('name')->toArray()
                    )
                    ->separator(',')
                    ->splitKeys(['Tab', 'Enter', ','])
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('tags', array_map('trim', $state));
                    })
                    ->saveRelationshipsUsing(function ($record, $state) {
                        $tags = collect($state)->map(function ($tagName) {
                            $tagName = trim($tagName);
                            if (empty($tagName)) {
                                return null;
                            }
                            return Tag::firstOrCreate(
                                ['name' => $tagName],
                                ['name' => $tagName, 'slug' => (new Tag)->generateUniqueSlug($tagName)]
                            )->id;
                        })->filter();
                        $record->tags()->sync($tags);
                    })
                    ->nestedRecursiveRules([
                        'min:2',
                        'max:50',
                    ]),
            ]);
    }
}
