<?php

namespace App\Filament\Resources\Categories;

use App\Exceptions\CategoryHasBooksException;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use App\Services\CategoryService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('books_count')->label('Libros')->counts('books'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->using(function (Category $record, DeleteAction $action): void {
                        try {
                            app(CategoryService::class)->delete($record);
                        } catch (CategoryHasBooksException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                            $action->halt();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function (Collection $records): void {
                            $service = app(CategoryService::class);

                            foreach ($records as $record) {
                                try {
                                    $service->delete($record);
                                } catch (CategoryHasBooksException) {
                                    Notification::make()
                                        ->danger()
                                        ->title("No se puede eliminar la categoría «{$record->name}» porque tiene libros asociados.")
                                        ->send();
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
