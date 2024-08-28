<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeatResource\Pages;
use App\Filament\Resources\SeatResource\RelationManagers;
use App\Models\Block;
use App\Models\Computer;
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

                                Select::make('block_id')
                                    ->label('Block')
                                    ->options(function ($get) {
                                        $instructorId = $get('instructor_id');

                                        if ($instructorId) {
                                            // Fetch blocks based on the selected instructor
                                            return Block::whereIn('id', function ($query) use ($instructorId) {
                                                $query->select('block_id')
                                                    ->from('lab_schedules')
                                                    ->where('instructor_id', $instructorId);
                                            })->pluck('block', 'id')->toArray();
                                        }

                                        return [];
                                    })
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('year', null); // Reset year when block changes
                                    }),

                                Select::make('year')
                                    ->label('Year')
                                    ->options(function ($get) {
                                        $instructorId = $get('instructor_id');
                                        $blockId = $get('block_id');

                                        if ($instructorId && $blockId) {
                                            // Fetch years based on the selected instructor and block
                                            return LabSchedule::where('instructor_id', $instructorId)
                                                ->where('block_id', $blockId)
                                                ->distinct()
                                                ->pluck('year', 'year')
                                                ->mapWithKeys(function ($year) {
                                                    return [$year => "{$year} Year"];
                                                })
                                                ->toArray();
                                        }

                                        return [];
                                    })
                                    ->required(),


                                Select::make('computer_id')
                                    ->label('Computer')
                                    ->options(function ($get) {
                                        $year = $get('year');
                                        $blockId = $get('block_id');

                                        if ($year && $blockId) {
                                            // Filter computers that are not already assigned
                                            $assignedComputerIds = Seat::where('year', $year)
                                                ->where('block_id', $blockId)
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
                                        $year = $get('year');
                                        $blockId = $get('block_id');

                                        if ($year && $blockId) {
                                            // Filter students who are not already assigned
                                            $assignedStudentIds = Seat::where('year', $year)
                                                ->where('block_id', $blockId)
                                                ->pluck('student_id')
                                                ->toArray();

                                            return UserInformation::where('year', $year)
                                                ->where('block_id', $blockId)
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

                Tables\Columns\TextColumn::make('instructor.name') // Show instructor's name
                    ->label('Instructor')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The instructor assigned to the seat plan.'),

                Tables\Columns\TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The year assigned to the seat plan.'),

                Tables\Columns\TextColumn::make('block.block') // Show block name
                    ->label('Block')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The block assigned to the seat plan.'),

                Tables\Columns\TextColumn::make('student.name') // Show student's name
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
