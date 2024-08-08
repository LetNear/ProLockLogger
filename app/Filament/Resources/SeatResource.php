<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeatResource\Pages;
use App\Filament\Resources\SeatResource\RelationManagers;
use App\Models\Seat;
use App\Models\UserInformation;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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
                                        $assignedInstructors = Seat::pluck('instructor_name')->toArray();
                                        $query = UserInformation::where('role_id', 2);
                                        
                                        // Include the current record's instructor_name in the options
                                        if ($record) {
                                            $currentInstructorName = $record->instructor_name;
                                            $query->orWhere(DB::raw('concat(first_name, " ", last_name)'), $currentInstructorName);
                                        }

                                        return $query->whereNotIn(DB::raw('concat(first_name, " ", last_name)'), $assignedInstructors)
                                            ->get()
                                            ->mapWithKeys(function ($user) {
                                                return [$user->id => $user->first_name . ' ' . $user->last_name];
                                            });
                                    })
                                    ->required()
                                    ->label('Instructor')
                                    ->placeholder('Select an Instructor')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $instructor = UserInformation::find($state);
                                        if ($instructor) {
                                            $set('instructor_name', $instructor->first_name . ' ' . $instructor->last_name); // Save the name
                                        }
                                    }),

                                Forms\Components\TextInput::make('year_section')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Year Section')
                                    ->placeholder('Enter the year and section'),
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
