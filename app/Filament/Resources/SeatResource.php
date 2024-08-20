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
                                Forms\Components\Select::make('computer_id')
                                    ->relationship('computer', 'computer_number')
                                    ->options(function ($record) {
                                        $assignedComputers = Seat::pluck('computer_id')->toArray();
                                        $query = \App\Models\Computer::query();

                                        if ($record) {
                                            $currentComputerId = $record->computer_id;
                                            $query->orWhere('id', $currentComputerId);
                                        }

                                        return $query->whereNotIn('id', $assignedComputers)
                                            ->get()
                                            ->mapWithKeys(function ($computer) {
                                                return [$computer->id => $computer->computer_number];
                                            });
                                    })
                                    ->required()
                                    ->label('Computer Number')
                                    ->placeholder('Select Computer Number'),

                                    Forms\Components\Select::make('instructor_id')
                                     // Use instructor_id
                                    ->options(function ($record) {
                                        if (auth()->check() && auth()->user()->role_number == 2) {
                                          
                                            // If the user is an instructor, provide only their ID and name
                                            return [
                                                auth()->user()->id => auth()->user()->name,
                                            ];
                                        }
                                        
                                        // Otherwise, provide all instructors
                                        return User::where('role_number', 2) // Fetch users with role_number 2
                                            ->pluck('name', 'id'); // Pluck name and ID
                                    })
                                    ->required()
                                    ->label('Instructor')
                                    ->placeholder('Select an Instructor')
                                    ->default(function ($record) {
                                        
                                        // Set the default value to the logged-in user if they are an instructor
                                        return auth()->check() && auth()->user()->role_number == 2
                                            ? auth()->user()->id
                                            : null;
                                    }),
                                
                                    
                                
                                

                                Select::make('year')
                                    ->options([
                                        '1' => '1st Year',
                                        '2' => '2nd Year',
                                        '3' => '3rd Year',
                                        '4' => '4th Year',
                                    ])
                                    ->required()
                                    ->label('Year')
                                    ->placeholder('Select the year')
                                    ->reactive()
                                    ->afterStateUpdated(fn($state, callable $set) => $set('student_id', null)),

                                Forms\Components\Select::make('block_id')
                                    ->required()
                                    ->relationship('block', 'block')
                                    ->label('Block')
                                    ->placeholder('Enter the block')
                                    ->reactive()
                                    ->afterStateUpdated(fn($state, callable $set) => $set('student_id', null)),

                                    Forms\Components\Select::make('student_id')
                                    ->label('Student')
                                    ->options(function ($get) {
                                        $year = $get('year');
                                        $block = $get('block_id');
                                
                                        if ($year && $block) {
                                            // Get the IDs of students already assigned to a seat
                                            $assignedStudents = \App\Models\Seat::pluck('student_id')->toArray();
                                
                                            return \App\Models\UserInformation::where('year', $year)
                                                ->where('block_id', $block)
                                                ->whereHas('user', function ($query) {
                                                    $query->where('role_number', 3);
                                                })
                                                ->whereNotIn('user_id', $assignedStudents) // Exclude students already assigned to a seat
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
                                    ->placeholder('Select Student')
                                    ->reactive()
                                    ->default(fn($record) => $record->student_id ?? null)
                                
                                
                                
                            ]),
                    ])
                    ->collapsible(),
            ])
            ->columns(1);
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
