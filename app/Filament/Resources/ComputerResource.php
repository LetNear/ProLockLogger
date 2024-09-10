<?php

namespace App\Filament\Resources;

use App\Filament\Imports\ComputerImporter;
use App\Filament\Resources\ComputerResource\Pages;
use App\Filament\Resources\ComputerResource\RelationManagers;
use App\Models\Computer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\ImportAction;

class ComputerResource extends Resource
{
    protected static ?string $model = Computer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $title = 'Computer';

    protected static ?string $label = 'Computer';

    protected static ?string $navigationGroup = 'Laboratory Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Computer Details')
                    ->schema([
                        Forms\Components\Grid::make(2) // Setting the number of columns to 2
                            ->schema([
                                Forms\Components\TextInput::make('computer_number')
                                    ->label('Computer Number')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('Enter the computer number')
                                    ->helperText('The unique number assigned to the computer.')
                                    ->unique(ignoreRecord: true) // Ignore uniqueness validation when editing
                                    ->disabled(fn($record) => $record !== null), // Disable the field in edit mode
                                Forms\Components\TextInput::make('brand')
                                    ->label('Brand')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter the brand name')
                                    ->helperText('The brand or manufacturer of the computer.'),
                                Forms\Components\TextInput::make('model')
                                    ->label('Model')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter the model name')
                                    ->helperText('The model number or name of the computer.'),
                                Forms\Components\TextInput::make('serial_number')
                                    ->label('Serial Number')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter the serial number')
                                    ->helperText('The unique serial number of the computer.')
                                    ->rules([
                                        'required',
                                        'max:255',
                                        'unique:computers,serial_number', // Ensure uniqueness in the 'user_informations' table for the 'serial_number' column
                                    ])

                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->headerActions([
                ImportAction::make()
                    ->importer(ComputerImporter::class)
                    ->label('Import Computers')
            ])
            ->columns([
                Tables\Columns\TextColumn::make('computer_number')
                    ->label('Computer Number')
                    ->numeric()
                    ->sortable()
                    ->tooltip('The unique number assigned to the computer.'),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Brand')
                    ->searchable()
                    ->tooltip('The brand or manufacturer of the computer.'),
                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->searchable()
                    ->tooltip('The model number or name of the computer.'),
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Serial Number')
                    ->searchable()
                    ->tooltip('The unique serial number of the computer.'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('The date and time when the computer record was created.'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('The date and time when the computer record was last updated.'),
            ])
            ->filters([
                Tables\Filters\Filter::make('brand')
                    ->form([
                        Forms\Components\TextInput::make('brand')
                            ->label('Brand')
                            ->placeholder('Filter by brand'),
                    ])
                    ->query(fn(Builder $query, array $data) => $query->where('brand', 'like', "%{$data['brand']}%")),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-s-pencil')
                    ->tooltip('Edit this computer'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-s-trash')
                    ->tooltip('Delete this computer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-s-trash')
                        ->tooltip('Delete selected computers'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComputers::route('/'),
            'create' => Pages\CreateComputer::route('/create'),
            'edit' => Pages\EditComputer::route('/{record}/edit'),
        ];
    }
}
