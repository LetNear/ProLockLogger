<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LabCapacityResource\Pages;
use App\Filament\Resources\LabCapacityResource\RelationManagers;
use App\Models\LabCapacity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class LabCapacityResource extends Resource
{
    protected static ?string $model = LabCapacity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard';

    protected static ?string $title = 'Laboratory Capacity';

    protected static ?string $label = 'Laboratory Capacity';

    protected static ?string $navigationGroup = 'Laboratory Management';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\Section::make('General Information')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('lab_attendance_id')
                                ->label('Lab Attendance ID')
                                ->numeric()
                                ->default(null),
                            Forms\Components\TextInput::make('max_cap')
                                ->label('Maximum Capacity')
                                ->required()
                                ->maxLength(255),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lab_attendance_id')
                    ->label('Lab Attendance ID')
                    ->sortable()
                    ->numeric(),
                Tables\Columns\TextColumn::make('max_cap')
                    ->label('Maximum Capacity')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Define any filters here if needed
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListLabCapacities::route('/'),
            'create' => Pages\CreateLabCapacity::route('/create'),
            'edit' => Pages\EditLabCapacity::route('/{record}/edit'),
        ];
    }
}
