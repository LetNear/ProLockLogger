<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LabScheduleResource\Pages;
use App\Models\LabSchedule;
use App\Models\User;
use App\Models\Block;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Events\Auditing;
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
                                Forms\Components\TextInput::make('subject_code')
                                    ->label('Subject Code')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('subject_name')
                                    ->label('Subject Name')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('instructor_id')
                                    ->label('Instructor')
                                    ->options(User::where('role_number', 2)->pluck('name', 'id')->toArray())
                                    ->required()
                                    ->placeholder('Select an instructor'),
                                Select::make('block_id')
                                    ->relationship('block', 'block')
                                    ->required(),
                                Select::make('year')
                                    ->options([
                                        '1' => '1st Year',
                                        '2' => '2nd Year',
                                        '3' => '3rd Year',
                                        '4' => '4th Year',
                                    ])
                                    ->required(),
                            ]),
                    ]),
                Forms\Components\Section::make('Schedule Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Select::make('day_of_the_week')
                                    ->options([
                                        'Monday' => 'Monday',
                                        'Tuesday' => 'Tuesday',
                                        'Wednesday' => 'Wednesday',
                                        'Thursday' => 'Thursday',
                                        'Friday' => 'Friday',
                                        'Saturday' => 'Saturday',
                                        'Sunday' => 'Sunday',
                                    ])
                                    ->required(),
                                    TimePicker::make('class_start')
                                    ->label('Class Start Time')
                                    ->required()
                                    ->format('g:i A') // 12-hour format with AM/PM
                                    ->seconds(false),
                                
                                TimePicker::make('class_end')
                                    ->label('Class End Time')
                                    ->required()
                                    ->format('g:i A') // 12-hour format with AM/PM
                                    ->seconds(false),
                            ])
                           
                    ]),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject_code')
                    ->label('Subject Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_name')
                    ->label('Subject Name')
                    ->searchable(),
                TextColumn::make('instructor.name')
                    ->label('Instructor')
                    ->searchable(),
                TextColumn::make('block.block')
                    ->label('Block')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('year')
                    ->label('Year')
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
