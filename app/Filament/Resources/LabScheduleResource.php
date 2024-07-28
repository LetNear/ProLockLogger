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

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $title = 'Laboratory Schedule';

    protected static ?string $label = 'Laboratory Schedule';

    protected static ?string $navigationGroup = 'Laboratory Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('year_and_program_id')
                                    ->label('Year and Program ID')
                                    ->numeric()
                                    ->default(null),
                                Forms\Components\TextInput::make('subject_code')
                                    ->label('Subject Code')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('subject_name')
                                    ->label('Subject Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('instructor')
                                    ->label('Instructor')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ]),
                Forms\Components\Section::make('Schedule Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('day_of_the_week')
                                    ->label('Day of the Week')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('class_start')
                                    ->label('Class Start Time')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('class_end')
                                    ->label('Class End Time')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('year_and_program_id')
                    ->label('Year and Program ID')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject_code')
                    ->label('Subject Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_name')
                    ->label('Subject Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('instructor')
                    ->label('Instructor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('day_of_the_week')
                    ->label('Day of the Week')
                    ->searchable(),
                Tables\Columns\TextColumn::make('class_start')
                    ->label('Class Start Time')
                    ->searchable(),
                Tables\Columns\TextColumn::make('class_end')
                    ->label('Class End Time')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Define any filters here if needed
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
