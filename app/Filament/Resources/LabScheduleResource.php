<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LabScheduleResource\Pages;
use App\Filament\Resources\LabScheduleResource\RelationManagers;
use App\Models\LabSchedule;
use App\Models\UserInformation;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Type\Time;
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
                                // Select::make('instructor_name')
                                //     ->label('Instructor')
                                //     ->options(function ($record) {
                                //         $assignedInstructors = LabSchedule::pluck('instructor_name')->toArray();
                                //         $query = UserInformation::where('role_id', 2);

                                //         // Include the current record's instructor_name in the options
                                //         if ($record && $record->instructor_name) {
                                //             $currentInstructorName = $record->instructor_name;
                                //             $query->orWhere(DB::raw('concat(first_name, " ", last_name)'), $currentInstructorName);
                                //         }

                                //         return $query->whereNotIn(DB::raw('concat(first_name, " ", last_name)'), $assignedInstructors)
                                //             ->get()
                                //             ->mapWithKeys(function ($user) {
                                //                 return [$user->first_name . ' ' . $user->last_name => $user->first_name . ' ' . $user->last_name];
                                //             });
                                //     })
                                //     ->required()
                                //     ->placeholder('Select an Instructor')
                                //     ->default(function ($record) {
                                //         // Ensure the instructor name is pre-populated when editing
                                //         return $record ? $record->instructor_name : null;
                                //     }),
                                Select::make('instructor_name')
                                    ->label('Instructor')
                                    ->options(function () {
                                        return UserInformation::where('role_id', 2)
                                            ->get()
                                            ->mapWithKeys(function ($user) {
                                                return [$user->first_name . ' ' . $user->last_name => $user->first_name . ' ' . $user->last_name];
                                            });
                                    })
                                    ->required()
                                    ->placeholder('Select an Instructor')
                                    ->default(function ($record) {
                                        // Ensure the instructor name is pre-populated when editing
                                        return $record ? $record->instructor_name : null;
                                    }),

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
                                    ->seconds(false),
                                TimePicker::make('class_end')
                                    ->label('Class End Time')
                                    ->required()
                                    ->seconds(false),
                            ]),
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
                // TODO: Make it SELECT
                Tables\Columns\TextColumn::make('subject_name')
                    ->label('Subject Name')
                    ->searchable(),
                // TODO: Make it SELECT
                TextColumn::make('instructor_name')
                    ->label('Instructor')
                    ->searchable(),
                TextColumn::make('block.block')
                    ->label('Block')
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
