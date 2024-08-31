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
                                    ->default(Auth::id()) // Automatically set to the logged-in user
                                    ->required()
                                    ->reactive()
                                    ->disabled(), // Make it read-only if you don't want it to be editable
    
                                Select::make('course_id')
                                    ->label('Course Name')
                                    ->options(function () {
                                        return Course::pluck('course_name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->placeholder('Select Course'),
    
                                Select::make('computer_id')
                                    ->label('Computer')
                                    ->options(function ($get) {
                                        $courseId = $get('course_id');
    
                                        if ($courseId) {
                                            // Filter computers that are not already assigned
                                            $assignedComputerIds = Seat::where('course_id', $courseId)
                                                ->pluck('computer_id')
                                                ->toArray();
    
                                            return Computer::whereNotIn('id', $assignedComputerIds) // Exclude already assigned computers
                                                ->pluck('computer_number', 'id');
                                        }
    
                                        return [];
                                    })
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Select Computer'),
    
                                Select::make('student_id')
                                    ->label('Student')
                                    ->options(function ($get) {
                                        $courseId = $get('course_id');
    
                                        if ($courseId) {
                                            // Filter students who are not already assigned
                                            $assignedStudentIds = Seat::where('course_id', $courseId)
                                                ->pluck('student_id')
                                                ->toArray();
    
                                            return UserInformation::whereHas('labSchedule', function ($query) use ($courseId) {
                                                $query->where('course_id', $courseId);
                                            })
                                                ->whereNotIn('user_id', $assignedStudentIds) // Exclude already assigned students
                                                ->with('user')
                                                ->get()
                                                ->pluck('user.name', 'user_id');
                                        }
    
                                        return [];
                                    })
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Select Student'),
    
                                Select::make('year')
                                    ->label('Year')
                                    ->options(function () {
                                        // Load all available years
                                        return LabSchedule::distinct()->pluck('year', 'year');
                                    })
                                    ->required()
                                    ->placeholder('Select Year'),
    
                                    Select::make('block_id')
                                    ->label('Block')
                                    ->options(function () {
                                        // Get distinct blocks from the LabSchedule that are assigned
                                        $assignedBlockIds = LabSchedule::pluck('block_id')->unique();
                                
                                        return Block::whereIn('id', $assignedBlockIds)
                                            ->pluck('block', 'id');
                                    })
                                    ->required()
                                    ->placeholder('Select Block'),
                            ]),
                    ]),
            ]);
    }
    

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('computer.computer_number')
                    ->label('Computer Number')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The unique number assigned to the computer.'),

                Tables\Columns\TextColumn::make('instructor.name')
                    ->label('Instructor')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The instructor assigned to the seat plan.'),

                Tables\Columns\TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The year assigned to the seat plan.'),

                Tables\Columns\TextColumn::make('block.block')
                    ->label('Block')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The block assigned to the seat plan.'),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Student')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The student assigned to the seat plan.'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created At')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Updated At')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit this seat')
                    ->icon('heroicon-s-pencil')
                    ->color('primary'),

                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete this seat')
                    ->icon('heroicon-s-trash')
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->tooltip('Delete selected seats')
                        ->color('danger'),
                ]),
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
