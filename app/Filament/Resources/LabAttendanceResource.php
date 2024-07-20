<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LabAttendanceResource\Pages;
use App\Filament\Resources\LabAttendanceResource\RelationManagers;
use App\Models\LabAttendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LabAttendanceResource extends Resource
{
    protected static ?string $model = LabAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('seat_id')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('lab_schedule_id')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('time_in')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('time_out')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('logdate')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('instructor')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('seat_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lab_schedule_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_in')
                    ->searchable(),
                Tables\Columns\TextColumn::make('time_out')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('logdate')
                    ->searchable(),
                Tables\Columns\TextColumn::make('instructor')
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
            'index' => Pages\ListLabAttendances::route('/'),
            'create' => Pages\CreateLabAttendance::route('/create'),
            'edit' => Pages\EditLabAttendance::route('/{record}/edit'),
        ];
    }
}
