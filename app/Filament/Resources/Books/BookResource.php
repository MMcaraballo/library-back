<?php

namespace App\Filament\Resources\Books;

use App\Filament\Resources\Books\Pages\ManageBooks;
use App\Models\Book;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookResource extends Resource
{
    protected static ?string $model = Book::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->label('Título')->required()->minLength(3)->maxLength(255),
                TextInput::make('author')->label('Autor')->required()->maxLength(255),
                Textarea::make('description')->label('Descripción')->columnSpanFull(),
                TextInput::make('published_year')
                    ->label('Año de publicación')
                    ->required()
                    ->integer()
                    ->minValue(1000)
                    ->maxValue(now()->year),
                Select::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Título')->searchable()->sortable(),
                TextColumn::make('author')->label('Autor')->searchable()->sortable(),
                TextColumn::make('published_year')->label('Año')->sortable(),
                TextColumn::make('category.name')->label('Categoría')->sortable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBooks::route('/'),
        ];
    }
}
