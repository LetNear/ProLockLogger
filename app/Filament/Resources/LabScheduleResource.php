<?php

namespace App\Filament\Resources;

use App\Filament\Imports\LabScheduleImporter;
use App\Filament\Resources\LabScheduleResource\Pages;
use App\Models\LabSchedule;
use App\Models\Course;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
                                Select::make('course_id')
                                    ->label('Course')
                                    ->relationship('course', 'course_name')
                                    ->required()
                                    ->placeholder('Select a course')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($course = Course::find($state)) {
                                            $set('course_code', $course->course_code);
                                            $set('course_name', $course->course_name);
                                        } else {
                                            $set('course_code', null);
                                            $set('course_name', null);
                                        }
                                    }),
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
                                Forms\Components\Toggle::make('is_makeup_class')
                                    ->label('Makeup Class')
                                    ->inline(false)
                                    ->reactive()
                                    ->helperText('Toggle on for makeup classes.'),
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
                                    ->required()
                                    ->visible(fn($get) => !$get('is_makeup_class')), // Show only for regular classes
                                DatePicker::make('specific_date')
                                    ->label('Specific Date')
                                    ->required()
                                    ->visible(fn($get) => $get('is_makeup_class')), // Show only for makeup classes
                                TimePicker::make('class_start')
                                    ->label('Class Start Time')
                                    ->required()
                                    ->seconds(false),
                                TimePicker::make('class_end')
                                    ->label('Class End Time')
                                    ->required()
                                    ->seconds(false),
                                TextInput::make('password')
                                    ->label('Password')
                                    ->required()
                                    ->placeholder('Enter the password for the laboratory schedule'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(LabScheduleImporter::class)
                    ->label('Import Schedule'),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('course_name')
                    ->label('Course Name')
                    ->searchable(),
                TextColumn::make('course_code') // Display course code
                    ->label('Course Code')
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
                TextColumn::make('day_of_the_week')
                    ->label('Day of the Week')
                    ->getStateUsing(fn($record) => $record->day_of_the_week ?? '-----')
                    ->searchable()
                    ->sortable(), // Check if record exists
                TextColumn::make('specific_date')
                    ->label('Makeup Class Date')
                    ->getStateUsing(fn($record) => $record->specific_date ?? '-----')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('class_start')
                    ->label('Class Start Time')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('class_end')
                    ->label('Class End Time')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('class_type') // Display class type
                    ->label('Class Type')
                    ->getStateUsing(fn($record) => $record && $record->is_makeup_class ? 'Makeup' : 'Regular')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('yearAndSemester.school_year')
                    ->label('School Year')
                    ->sortable()
                    ->tooltip('The school year of the schedule.')
                    ->searchable(),
                TextColumn::make('yearAndSemester.semester')
                    ->label('Semester')
                    ->sortable()
                    ->tooltip('The semester of the schedule.')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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
                Action::make('Crete Makeup Class')
                    ->label('Create Makeup Class')
                    ->form([
                        DatePicker::make('specific_date')
                            ->label('Specific Date')
                            ->required(),
                        TimePicker::make('class_start')
                            ->label('Class Start Time')
                            ->required()
                            ->seconds(false),
                        TimePicker::make('class_end')
                            ->label('Class End Time')
                            ->required()
                            ->seconds(false),
                    ])
                    ->action(function (array $data, $record) {
                        $newRecord = $record->replicate(['day_of_the_week'])->fill([...$data, 'is_makeup_class' => true,]);
                        $newRecord = LabSchedule::create($newRecord->toArray());
                        $newRecord->update(['course_name' => $record->course->course_name . ' (Makeup)']);
                        $students = $record->course->students->each(function ($student) use ($newRecord) {
                            $newRecord->students()->attach($student->id, ['course_id' => $newRecord->course->id]);
                        });

                        return redirect(LabScheduleResource::getUrl('edit', ['record' => $newRecord]));
                    })
                    ->disabled(fn($record) => $record->is_makeup_class),

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
