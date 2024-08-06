<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeatResource\Pages;
use App\Filament\Resources\SeatResource\RelationManagers;
use App\Models\Seat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class SeatResource extends Resource
{
    protected static ?string $model = Seat::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Seat Details')
                ->schema([
                    Forms\Components\Grid::make(2) // 2-column layout
                        ->schema([
                            Forms\Components\TextInput::make('computer_number')
                                ->required()
                                ->maxLength(255)
                                ->label('Computer Number')
                                ->placeholder('Enter the computer number'),
                            Forms\Components\TextInput::make('instructor')
                                ->required()
                                ->maxLength(255)
                                ->label('Instructor')
                                ->placeholder('Enter the instructor name'),
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
                Tables\Columns\TextColumn::make('computer_number')
                    ->searchable()
                    ->label('Computer Number'),
                Tables\Columns\TextColumn::make('instructor')
                    ->searchable()
                    ->label('Instructor'),
                Tables\Columns\TextColumn::make('year_section')
                    ->searchable()
                    ->label('Year Section'),
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
            ->filters([
                // Tables\Filters\Filter::make('computer_number')
                //     ->form([
                //         Forms\Components\TextInput::make('computer_number')
                //             ->placeholder('Filter by Computer Number'),
                //     ])
                //     ->query(fn (Builder $query, array $data) =>
                //         $query->where('computer_number', 'like', "%{$data['computer_number']}%")
                //     ),
                
                // Tables\Filters\Filter::make('instructor')
                //     ->form([
                //         Forms\Components\TextInput::make('instructor')
                //             ->placeholder('Filter by Instructor'),
                //     ])
                //     ->query(fn (Builder $query, array $data) =>
                //         $query->where('instructor', 'like', "%{$data['instructor']}%")
                //     ),
                
                // Tables\Filters\Filter::make('year_section')
                //     ->form([
                //         Forms\Components\TextInput::make('year_section')
                //             ->placeholder('Filter by Year Section'),
                //     ])
                //     ->query(fn (Builder $query, array $data) =>
                //         $query->where('year_section', 'like', "%{$data['year_section']}%")
                //     ),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit this seat'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->tooltip('Delete selected seats'),
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
