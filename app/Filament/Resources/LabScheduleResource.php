<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LabScheduleResource\Pages;
use App\Filament\Resources\LabScheduleResource\RelationManagers;
use App\Models\LabSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class LabScheduleResource extends Resource
{
    protected static ?string $model = LabSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('year_and_program_id')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('subject_code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('subject_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('instructor')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('day_of_the_week')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('class_start')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('class_end')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('year_and_program_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('instructor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('day_of_the_week')
                    ->searchable(),
                Tables\Columns\TextColumn::make('class_start')
                    ->searchable(),
                Tables\Columns\TextColumn::make('class_end')
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
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLabSchedules::route('/'),
            'create' => Pages\CreateLabSchedule::route('/create'),
            'edit' => Pages\EditLabSchedule::route('/{record}/edit'),
        ];
    }
}
