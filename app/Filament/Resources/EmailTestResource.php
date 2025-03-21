<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTestResource\Pages;
use App\Filament\Resources\EmailTestResource\RelationManagers;
use App\Models\EmailTest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmailTestResource extends Resource
{
    protected static ?string $model = EmailTest::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Email Management';

    protected static ?string $navigationLabel = 'Email Test';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListEmailTests::route('/'),
            'create' => Pages\CreateEmailTest::route('/create'),
            'edit' => Pages\EditEmailTest::route('/{record}/edit'),
        ];
    }
}
