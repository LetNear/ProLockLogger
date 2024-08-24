<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeatResource\Pages;
use App\Filament\Resources\SeatResource\RelationManagers;
use App\Models\Seat;
use App\Models\User;
use App\Models\UserInformation;
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
                                // Computer Select with Dynamic Availability
                                Forms\Components\Select::make('computer_id')
                                    ->label('Computer Number')
                                    ->relationship('computer', 'computer_number')
                                    ->options(function ($get) {
                                        // Get the selected instructor, year, and block
                                        $instructorId = $get('instructor_id');
                                        $year = $get('year');
                                        $blockId = $get('block_id');
                                        
                                        // Get assigned computers in the same year/block/instructor combination
                                        $assignedComputers = Seat::where('instructor_id', $instructorId)
                                            ->where('year', $year)
                                            ->where('block_id', $blockId)
                                            ->pluck('computer_id')
                                            ->toArray();
                                        
                                        // Query computers not assigned
                                        return \App\Models\Computer::whereNotIn('id', $assignedComputers)
                                            ->get()
                                            ->mapWithKeys(function ($computer) {
                                                return [$computer->id => $computer->computer_number];
                                            });
                                    })
                                    ->required()
                                    ->placeholder('Select Computer Number')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('student_id', null); // Reset student select when computer changes
                                    }),
    
                                // Instructor Select
                                Forms\Components\Select::make('instructor_id')
                                    ->label('Instructor')
                                    ->options(function () {
                                        // If the user is an instructor, limit options to their own record
                                        if (auth()->user()->role_number == 2) {
                                            return [auth()->user()->id => auth()->user()->name];
                                        }
                                        // Otherwise, list all instructors
                                        return User::where('role_number', 2)->pluck('name', 'id');
                                    })
                                    ->default(auth()->user()->role_number == 2 ? auth()->user()->id : null)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('computer_id', null); // Reset computer select when instructor changes
                                    }),
    
                                // Year Select
                                Forms\Components\Select::make('year')
                                    ->label('Year')
                                    ->options([
                                        '1' => '1st Year',
                                        '2' => '2nd Year',
                                        '3' => '3rd Year',
                                        '4' => '4th Year',
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('computer_id', null); // Reset computer select when year changes
                                    }),
    
                                // Block Select
                                Forms\Components\Select::make('block_id')
                                    ->label('Block')
                                    ->relationship('block', 'block')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('computer_id', null); // Reset computer select when block changes
                                    }),
    
                                // Student Select
                                Forms\Components\Select::make('student_id')
                                    ->label('Student')
                                    ->options(function ($get) {
                                        $year = $get('year');
                                        $blockId = $get('block_id');
                                        
                                        if ($year && $blockId) {
                                            // Get students already assigned to a seat
                                            $assignedStudents = Seat::pluck('student_id')->toArray();
                                            
                                            return UserInformation::where('year', $year)
                                                ->where('block_id', $blockId)
                                                ->whereHas('user', function ($query) {
                                                    $query->where('role_number', 3);
                                                })
                                                ->whereNotIn('user_id', $assignedStudents)
                                                ->with('user')
                                                ->get()
                                                ->mapWithKeys(function ($userInformation) {
                                                    return [$userInformation->user->id => $userInformation->user->name];
                                                });
                                        }
    
                                        return [];
                                    })
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Select Student'),
                            ]),
                    ])
                    ->collapsible(),
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
