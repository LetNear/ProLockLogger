<?php

namespace App\Filament\Resources;

use App\Filament\Imports\LabScheduleImporter;
use App\Filament\Resources\LabScheduleResource\Pages;
use App\Models\Block;
use App\Models\LabSchedule;
use App\Models\Course;
use App\Models\User;
use App\Models\YearAndSemester;
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
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder;

class LabScheduleResource extends Resource
{
    protected static ?string $model = LabSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $title = 'Laboratory Schedule';

    protected static ?string $label = 'Laboratory Schedule';

    protected static ?string $navigationGroup = 'Laboratory Management';

    public static function form(Form $form): Form
    {
        // Fetch the active 'on-going' Year and Semester
        $onGoingYearAndSemester = YearAndSemester::where('status', 'on-going')->first();
    
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Select::make('course_id')
                                    ->label('Course')
                                    ->relationship('course', 'course_name', function (Builder $query) use ($onGoingYearAndSemester) {
                                        // Ensure there is an ongoing year and semester
                                        if ($onGoingYearAndSemester) {
                                            $query->where('year_and_semester_id', $onGoingYearAndSemester->id);
                                        }
                                    })
                                    ->required()
                                    ->placeholder('Select a course')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $course = Course::find($state);
    
                                        if ($course) {
                                            $set('course_code', $course->course_code);
                                            $set('course_name', $course->course_name);
                                            $set('instructor_name', $course->instructor?->name );
                                        } else {
                                            $set('course_code', null);
                                            $set('course_name', null);
                                            $set('instructor_id', null);
                                            $set('instructor_name', null);
                                        }
                                    }),
    
                                TextInput::make('instructor_name')
                                    ->disabled()
                                    ->formatStateUsing(fn($record) => $record?->course?->instructor?->name)
                                    ->label('Instructor'), // Set the instructor name in edit mode
    
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
                                    ->placeholder('Enter the password for the laboratory schedule'),
                            ]),
                    ]),
            ]);
    }
    


    public static function table(Table $table): Table
    {
        return $table
            ->poll('2s')
            ->headerActions([
                ImportAction::make()
                    ->importer(LabScheduleImporter::class)
                    ->label('Import Schedule'),
            ])
            ->columns([

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
                    ->sortable(),
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
                // Add filter for Day of the Week
                Tables\Filters\SelectFilter::make('day_of_the_week')
                    ->label('Day of the Week')
                    ->options([
                        'Monday' => 'Monday',
                        'Tuesday' => 'Tuesday',
                        'Wednesday' => 'Wednesday',
                        'Thursday' => 'Thursday',
                        'Friday' => 'Friday',
                        'Saturday' => 'Saturday',
                        'Sunday' => 'Sunday',
                    ]),
                // Add filter for Block
                Tables\Filters\SelectFilter::make('block_id')
                ->label('Block')
                ->options(
                    Block::all()->pluck('block', 'id')  // Assuming 'block' is the column name for block name
                )
                ->query(function (Builder $query, $data) {
                    if ($data['value']) {
                        $query->where('block_id', $data['value']);
                    }
                })
                ->placeholder('All Blocks')
                ->searchable(),
                // Add filter for Year
                Tables\Filters\SelectFilter::make('year')
                    ->label('Year')
                    ->options([
                        '1' => '1st Year',
                        '2' => '2nd Year',
                        '3' => '3rd Year',
                        '4' => '4th Year',
                    ]),
                // Replace ToggleFilter with SelectFilter for Makeup Class
                Tables\Filters\SelectFilter::make('is_makeup_class')
                    ->label('Makeup Class')
                    ->options([
                        '1' => 'Yes',
                        '0' => 'No',
                    ]),
                // Filter for Year and Semester
                Tables\Filters\SelectFilter::make('year_and_semester_id')
                    ->label('Year and Semester')
                    ->options(YearAndSemester::all()->mapWithKeys(function ($item) {
                        return [$item->id => $item->school_year . ' - ' . $item->semester];
                    })->toArray())
                    ->query(function (Builder $query, $data) {
                        if (isset($data['value'])) {
                            $query->where('year_and_semester_id', $data['value']);
                        }
                    })
                    ->placeholder('Select Year and Semester')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('Create Makeup Class')
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
                        // 1. Validate if the specific date is in the past
                        if (strtotime($data['specific_date']) < strtotime(now()->format('Y-m-d'))) {
                            Notification::make()
                                ->title('Invalid Makeup Class Date')
                                ->danger()
                                ->body('Makeup class date must be today or a future date.')
                                ->send();

                            throw ValidationException::withMessages([
                                'specific_date' => ['Makeup class date must be today or a future date.'],
                            ]);
                        }

                        // Validate if the makeup class conflicts with any other makeup class
                        $conflictingSchedule = LabSchedule::where('specific_date', $data['specific_date'])
                            ->where('is_makeup_class', true)
                            ->where(function ($query) use ($data) {
                                $query->whereTime('class_start', '<', $data['class_end'])
                                    ->whereTime('class_end', '>', $data['class_start']);
                            })
                            ->where('id', '!=', $record->id ?? null) // Exclude the current record if editing
                            ->exists();

                        if ($conflictingSchedule) {
                            Notification::make()
                                ->title('Makeup Class Conflict')
                                ->danger()
                                ->body('This makeup class conflicts with another makeup class.')
                                ->send();

                            throw ValidationException::withMessages([
                                'specific_date' => ['This makeup class conflicts with another makeup class.'],
                            ]);
                        }

                        // Create the makeup class if no conflicts are found
                        $newRecord = $record->replicate(['day_of_the_week'])->fill(array_merge($data, ['is_makeup_class' => true]));
                        $newRecord = LabSchedule::create($newRecord->toArray());
                        $newRecord->update(['course_name' => $record->course->course_name . ' (Makeup)']);

                        // Optionally link students to the new makeup class
                        $students = $record->course->students->each(function ($student) use ($newRecord) {
                            $newRecord->students()->attach($student->id, ['course_id' => $newRecord->course->id]);
                        });

                        // Redirect to the edit page for the new makeup class
                        return redirect(LabScheduleResource::getUrl('edit', ['record' => $newRecord]));
                    })
                    ->disabled(fn($record) => $record->is_makeup_class), // Disable action if the record is already a makeup class
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
