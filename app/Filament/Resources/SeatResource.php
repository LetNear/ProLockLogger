<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeatResource\Pages;
use App\Models\Block;
use App\Models\Computer;
use App\Models\Course;
use App\Models\Seat;
use App\Models\User;
use App\Models\UserInformation;
use App\Models\LabSchedule;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class SeatResource extends Resource
{
    protected static ?string $model = Seat::class;

    protected static ?string $navigationIcon = 'heroicon-s-archive-box';

    protected static ?string $title = 'Seat';

    protected static ?string $label = 'Seat';

    protected static ?string $navigationGroup = 'Laboratory Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Seat Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Select::make('instructor_id')
                                    ->label('Instructor')
                                    ->options(function () {
                                        return User::where('role_number', 2)->pluck('name', 'id');
                                    })
                                    ->default(Auth::id()) // Set the default to the current user's ID
                                    ->required()
                                    ->reactive(),

                                Select::make('course_id')
                                    ->label('Course')
                                    ->options(function () {
                                        $instructorId = Auth::id();
                                        return LabSchedule::with('course')
                                            ->where('instructor_id', $instructorId)
                                            ->get()
                                            ->mapWithKeys(function ($schedule) {
                                                $classStart = Carbon::parse($schedule->class_start)->format('H:i');
                                                $classEnd = Carbon::parse($schedule->class_end)->format('H:i');
                                                $displayText = "{$schedule->course->course_name} - {$schedule->day_of_the_week}, {$classStart} - {$classEnd}";
                                                return [$schedule->id => $displayText]; // Save schedule_id
                                            });
                                    })
                                    ->required()
                                    ->searchable()
                                    ->placeholder('Select Course')
                                    ->reactive(),

                                Select::make('student_id')
                                    ->label('Student')
                                    ->options(function ($get) {
                                        $scheduleId = $get('course_id');

                                        if ($scheduleId) {
                                            $assignedStudentIds = Seat::pluck('student_id')->toArray();

                                            return UserInformation::whereHas('courses', function ($query) use ($scheduleId) {
                                                $query->where('course_user_information.schedule_id', $scheduleId);
                                            })
                                                ->whereNotIn('id', $assignedStudentIds) // Exclude assigned students
                                                ->with('user')
                                                ->get()
                                                ->pluck('user.name', 'id');
                                        }

                                        return [];
                                    })
                                    ->default(fn($record) => $record ? $record->student_id : null)
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Select Student')
                                    ->formatStateUsing(function ($state) {
                                        if ($state) {
                                            $student = UserInformation::with('user')->find($state);
                                            return $student ? $student->user->name : $state; // Show the student's name if available
                                        }
                                        return null;
                                    }),

                                Select::make('computer_id')
                                    ->label('Computer')
                                    ->options(function ($get) {
                                        $courseId = $get('course_id');

                                        if ($courseId) {
                                            $assignedComputerIds = Seat::where('course_id', $courseId)
                                                ->pluck('computer_id')
                                                ->toArray();

                                            return Computer::whereNotIn('id', $assignedComputerIds)
                                                ->pluck('computer_number', 'id');
                                        }

                                        return [];
                                    })
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Select Computer'),
                            ]),
                    ]),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('computer.computer_number')
                    ->label('Computer Number')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('instructor.name')
                    ->label('Instructor Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('student.user.name')
                    ->label('Student Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('course.course_name')
                    ->label('Course Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('schedule.day_of_the_week')
                    ->label('Day of the Week')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('schedule.class_start')
                    ->label('Class Start Time')
                    ->sortable()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('H:i')),

                TextColumn::make('schedule.class_end')
                    ->label('Class End Time')
                    ->sortable()
                    ->formatStateUsing(fn($state) => Carbon::parse($state)->format('H:i')),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created At'),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Updated At'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->searchable()
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListSeats::route('/'),
            'create' => Pages\CreateSeat::route('/create'),
            'edit' => Pages\EditSeat::route('/{record}/edit'),
        ];
    }
}
