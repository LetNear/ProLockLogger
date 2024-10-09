<?php

namespace App\Filament\Resources;

use App\Exports\FacultyExport;
use App\Exports\LogBookExport;
use App\Filament\Resources\RecentLogsResource\Pages;
use App\Models\RecentLogs;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TextFilter;
use Filament\Tables\Filters\DateFilter;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

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
                                    ->required()
                                    ->disabled(),

                                Forms\Components\Select::make('role_id')
                                    ->relationship('role', 'name')
                                    ->label('Role')
                                    ->required()
                                    ->disabled(),

                                Forms\Components\Select::make('block_id')
                                    ->relationship('block', 'block')
                                    ->label('Block')
                                    ->required()
                                    ->disabled(),

                                Forms\Components\TextInput::make('year')
                                    ->label('Year')
                                    ->required()
                                    ->disabled(),
                            ]),
                    ]),
                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('time_in')
                                    ->label('Time In')
                                    ->required()
                                    ->disabled(),

                                Forms\Components\TextInput::make('time_out')
                                    ->label('Time Out')
                                    ->required()
                                    ->disabled(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('2s')
            ->headerActions([
                Action::make('export')
                    ->label('Export Log Book')
                    ->action(function () {
                        // Trigger the export using Maatwebsite Excel and your custom exporter
                        return Excel::download(new LogBookExport, 'logbook.xlsx');
                    }),
                    // Action::make('export')
                    // ->label('Export Faculty Log Book')
                    // ->action(function () {
                    //     // Trigger the export using Maatwebsite Excel and your custom exporter
                    //     return Excel::download(new FacultyExport, 'logbookfaculty.xlsx');
                    // }),
            ])
            ->columns([
                TextColumn::make('user_name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('role.name')
                    ->label('Role')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('block.block')
                    ->label('Block')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('time_in')
                    ->label('Time In')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('time_out')
                    ->label('Time Out')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

                SelectFilter::make('role_id')
                ->label('Role')
                ->options(\App\Models\Role::all()->pluck('name', 'id')) // Load all roles
                ->searchable()
                ->placeholder('Select Role'),

            // Filter by block, load all blocks eagerly
            SelectFilter::make('block_id')
                ->label('Block')
                ->options(\App\Models\Block::all()->pluck('block', 'id')) // Load all blocks
                ->searchable()
                ->placeholder('Select Block'),

            // Filter by year (assuming years are static, this loads from database)
            SelectFilter::make('year')
                ->label('Year')
                ->options(RecentLogs::select('year')->distinct()->pluck('year', 'year')) // Load all distinct years from logs
                ->searchable()
                ->placeholder('Select Year'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
               
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
            // Remove 'create' and 'edit' pages to prevent any record modifications
        ];
    }
}
