<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecentLogsResource\Pages;
use App\Filament\Resources\RecentLogsResource\RelationManagers;
use App\Models\RecentLogs;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class RecentLogsResource extends Resource
{
    protected static ?string $model = RecentLogs::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $title = 'Recent Logs';

    protected static ?string $label = 'Recent Logs';

    protected static ?string $navigationGroup = 'Laboratory Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identification')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('role_id')
                                    ->label('Role ID')
                                    ->numeric()
                                    ->default(null),
                                Forms\Components\TextInput::make('lab_attendance_id')
                                    ->label('Lab Attendance ID')
                                    ->numeric()
                                    ->default(null),
                            ]),
                    ]),
                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('created_at')
                                    ->label('Created At')
                                    ->default(now())
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('updated_at')
                                    ->label('Updated At')
                                    ->default(now())
                                    ->disabled(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('role_id')
                    ->label('Role ID')
                    ->sortable()
                    ->numeric(),
                Tables\Columns\TextColumn::make('lab_attendance_id')
                    ->label('Lab Attendance ID')
                    ->sortable()
                    ->numeric(),
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
            'index' => Pages\ListRecentLogs::route('/'),
            'create' => Pages\CreateRecentLogs::route('/create'),
            'edit' => Pages\EditRecentLogs::route('/{record}/edit'),
        ];
    }
}
