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
                                    Forms\Components\Select::make('user_id')
                                        ->relationship('user', 'name')
                                        ->label('User')
                                        ->required(),
                                    Forms\Components\Select::make('role_id')
                                        ->relationship('role', 'name')
                                        ->label('Role')
                                        ->required(),
                                    Forms\Components\Select::make('block_id')
                                        ->relationship('block', 'block')
                                        ->label('Block')
                                        ->required(),
                                    Forms\Components\TextInput::make('year')
                                        ->label('Year')
                                        ->required(),
                                ]),
                        ]),
                    Forms\Components\Section::make('Timestamps')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('time_in')
                                        ->label('Time In')
                                        ->required(),
                                    Forms\Components\TextInput::make('time_out')
                                        ->label('Time Out')
                                        ->required(),
                                ]),
                        ]),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
            ->poll('2s')
                ->columns([
                    Tables\Columns\TextColumn::make('user_number')
                        ->label('User')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('role_id')
                        ->label('Role')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('block.block')
                        ->label('Block')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('year')
                        ->label('Year')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('time_in')
                        ->label('Time In')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('time_out')
                        ->label('Time Out')
                        ->sortable(),
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
                    Tables\Actions\DeleteBulkAction::make(),
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
