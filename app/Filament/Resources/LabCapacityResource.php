<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LabCapacityResource\Pages;
use App\Filament\Resources\LabCapacityResource\RelationManagers;
use App\Models\LabCapacity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LabCapacityResource extends Resource
{
    protected static ?string $model = LabCapacity::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('lab_attendance_id')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('max_cap')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lab_attendance_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_cap')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListLabCapacities::route('/'),
            'create' => Pages\CreateLabCapacity::route('/create'),
            'edit' => Pages\EditLabCapacity::route('/{record}/edit'),
        ];
    }
}
