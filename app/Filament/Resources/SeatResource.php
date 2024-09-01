<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeatResource\Pages;
use App\Filament\Resources\SeatResource\RelationManagers;
use App\Models\Block;
use App\Models\Computer;
use App\Models\Course;
use App\Models\Seat;
use App\Models\User;
use App\Models\UserInformation;
use App\Models\LabSchedule; // Import LabSchedule model
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
                                    ->default(Auth::id())
                                    ->required()
                                    ->reactive()
                                    ->disabled(),
    
                                Select::make('course_id')
                                    ->label('Course Name')
                                    ->options(function () {
                                        return Course::pluck('course_name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->placeholder('Select Course')
                                    ->reactive(), // Reactive to update dependent fields
    
                                Select::make('year')
                                    ->label('Year')
                                    ->options(function () {
                                        return LabSchedule::distinct()->pluck('year', 'year');
                                    })
                                    ->required()
                                    ->placeholder('Select Year'),
    
                                Select::make('block_id')
                                    ->label('Block')
                                    ->options(function () {
                                        $assignedBlockIds = LabSchedule::pluck('block_id')->unique();
                                        return Block::whereIn('id', $assignedBlockIds)->pluck('block', 'id');
                                    })
                                    ->required()
                                    ->placeholder('Select Block'),
    
                                    Select::make('student_id')
                                    ->label('Student')
                                    ->options(function ($get) {
                                        $courseId = $get('course_id'); // Retrieve the selected course ID
                                
                                        if ($courseId) {
                                            // Fetch students enrolled in the selected course using the pivot table 'course_user_information'
                                            return UserInformation::whereHas('courses', function ($query) use ($courseId) {
                                                    $query->where('course_id', $courseId); // Filter by selected course ID
                                                })
                                                ->with('user') // Eager load the related user model to get user names
                                                ->get()
                                                ->pluck('user.name', 'user_id'); // Pluck user names and their IDs
                                        }
                                
                                        return [];
                                    })
                                    ->relationship('student', 'name') // Use the correct relationship for the select
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Select Student'),
                                
    
                                    // Select::make('computer_id')
                                    // ->label('Computer')
                                    // ->options(function ($get) {
                                    //     $courseId = $get('course_id');
    
                                    //     if ($courseId) {
                                    //         $assignedComputerIds = Seat::where('course_id', $courseId)
                                    //             ->pluck('computer_id')
                                    //             ->toArray();
    
                                    //         return Computer::whereNotIn('id', $assignedComputerIds)
                                    //             ->pluck('computer_number', 'id');
                                    //     }
    
                                    //     return [];
                                    // })
                                    // ->searchable()
                                    // ->required()
                                    // ->placeholder('Select Computer'),
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
                ->label('Instructor')
                ->sortable()
                ->searchable(),

            TextColumn::make('course.course_name')
                ->label('Course')
                ->sortable()
                ->searchable(),

            TextColumn::make('block.block')
                ->label('Block')
                ->sortable()
                ->searchable(),

            TextColumn::make('student.name')
                ->label('Student')
                ->sortable()
                ->searchable(),

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
