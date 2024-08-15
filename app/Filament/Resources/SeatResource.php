<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeatResource\Pages;
use App\Filament\Resources\SeatResource\RelationManagers;
use App\Models\Seat;
use App\Models\User;
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
                        Forms\Components\Grid::make(2) // 2-column layout
                            ->schema([
                                Forms\Components\Select::make('computer_id') // Correct column name
                                    ->relationship('computer', 'computer_number')
                                    ->options(function ($record) {
                                        $assignedComputers = Seat::pluck('computer_id')->toArray();
                                        $query = \App\Models\Computer::query();
                                        
                                        // Include the current record's computer_id in the options
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
    
                                Forms\Components\Select::make('instructor_name') // Correct column name
                                    ->options(function ($record) {
                                        // Only show the current logged-in instructor
                                        if (auth()->check() && auth()->user()->role_number == 2) {
                                            return [
                                                auth()->user()->id => auth()->user()->name,
                                            ];
                                        }
    
                                        return [];
                                    })
                                    ->required()
                                    ->label('Instructor')
                                    ->placeholder('Select an Instructor')
                                    ->default(function ($record) {
                                        // Set the default instructor to the currently logged-in user if creating
                                        return auth()->check() && auth()->user()->role_number == 2
                                            ? auth()->user()->id
                                            : null;
                                    })
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $instructor = User::find($state);
                                        if ($instructor) {
                                            $set('instructor_name', $instructor->name); // Save the name
                                        }
                                    }),
    
                                    Forms\Components\Select::make('year')
                                    ->required()
                                    ->options([
                                        '1' => '1st Year',
                                        '2' => '2nd Year',
                                        '3' => '3rd Year',
                                        '4' => '4th Year',
                                    ])
                                    ->label('Year')
                                    ->placeholder('Select the year'),
                                
                                    Forms\Components\Select::make('block_id')
                                    ->required()
                                    ->relationship('block', 'block')
                                    
                                    ->label('block ')
                                    ->placeholder('Enter the block '),

                                    // make a select for the student to be assigned to the seat
                                    Forms\Components\Select::make('student_id')
                                    ->label('Student')
                                    ->options(function ($get) {
                                        $year = $get('year');
                                        $block = $get('block');
                                
                                        if ($year && $block) {
                                            return \App\Models\UserInformation::where('year', $year)
                                                ->where('block', $block)
                                                ->whereHas('user', function ($query) {
                                                    $query->where('role_number', 3);
                                                })
                                                ->pluck('first_name', 'id');
                                        }
                                
                                        return [];
                                    })
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Select Student')
                                    ->reactive(), // This makes the select react to changes in other fields.
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
                Tables\Columns\TextColumn::make('computer_id') // Correct column name
                    ->searchable()
                    ->label('Computer Number')
                    ->tooltip('The unique number assigned to the computer.'),
                Tables\Columns\TextColumn::make('instructor_name') // Display instructor's name
                    ->searchable()
                    ->label('Instructor')
                    ->tooltip('The instructor assigned to the seat plan.'),
                Tables\Columns\TextColumn::make('year_section')
                    ->searchable()
                    ->label('Year Section')
                    ->tooltip('The year and section assigned to the seat plan.'),
                TextColumn::make('year')
                    ->label('Year')
                    ->tooltip('The year assigned to the seat plan.'),
                TextColumn::make('block')
                    ->label('Block')
                    ->tooltip('The block assigned to the seat plan.'),
                TextColumn::make('student_id')
                    ->label('Student')
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
                    ->tooltip('Edit this seat') // Tooltip for the Edit action
                    ->icon('heroicon-s-pencil') // Optional: Add an icon for visual appeal
                    ->color('primary'), // Optional: Set color
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete this seat') // Tooltip for the Delete action
                    ->icon('heroicon-s-trash') // Optional: Add an icon for visual appeal
                    ->color('danger'), // Optional: Set color
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->tooltip('Delete selected seats') // Tooltip for bulk delete
                        ->color('danger'), // Optional: Set color
                ]),
            ])
            ->searchable()
            ->defaultSort('created_at', 'desc'); // Default sorting
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
