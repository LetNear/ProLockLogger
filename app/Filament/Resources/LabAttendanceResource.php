<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LabAttendanceResource\Pages;
use App\Models\LabAttendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\DateFilter;
use Filament\Tables\Filters\TextFilter;
use Filament\Tables\Filters\Filter;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class LabAttendanceResource extends Resource
{
    protected static ?string $model = LabAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-s-academic-cap';

    protected static ?string $title = 'Laboratory Attendance';

    protected static ?string $label = 'Laboratory Attendance';

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
                                    ->required()
                                    ->label('User'),
                                Forms\Components\TextInput::make('seat_id')
                                    ->numeric()
                                    ->nullable()
                                    ->label('Seat ID'),
                                Forms\Components\TextInput::make('lab_schedule_id')
                                    ->numeric()
                                    ->nullable()
                                    ->label('Lab Schedule ID'),
                            ]),
                    ]),
                Forms\Components\Section::make('Attendance Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TimePicker::make('time_in')
                                    ->required()
                                    ->label('Time In'),
                                Forms\Components\TimePicker::make('time_out')
                                    ->required()
                                    ->label('Time Out'),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'present' => 'Present',
                                        'absent' => 'Absent',
                                        'late' => 'Late',
                                    ])
                                    ->required()
                                    ->label('Status'),
                                Forms\Components\DatePicker::make('logdate')
                                    ->required()
                                    ->label('Log Date'),
                                Forms\Components\TextInput::make('instructor')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Instructor'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('2s')
            ->columns([
                Tables\Columns\TextColumn::make('instructor')
                    ->label('Instructor')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('time_in')
                    ->label('Time In')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('time_out')
                    ->label('Time Out')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('logdate')
                    ->label('Log Date')
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
                Filter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable(),

                Filter::make('status')
                    ->label('Status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'late' => 'Late',
                    ]),

                Filter::make('instructor')
                    ->label('Instructor')
                    ->placeholder('Search by instructor'),

                Filter::make('logdate')
                    ->label('Log Date')
                    ->placeholder('Select a log date'),

                Filter::make('time_in')
                    ->form([
                        Forms\Components\TimePicker::make('time_in')
                            ->label('Time In')
                            ->nullable(),
                    ])
                    ->query(function ($query, $data) {
                        if ($data['time_in']) {
                            $query->where('time_in', '>=', $data['time_in']);
                        }
                    }),

                Filter::make('time_out')
                    ->form([
                        Forms\Components\TimePicker::make('time_out')
                            ->label('Time Out')
                            ->nullable(),
                    ])
                    ->query(function ($query, $data) {
                        if ($data['time_out']) {
                            $query->where('time_out', '<=', $data['time_out']);
                        }
                    }),
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
            'index' => Pages\ListLabAttendances::route('/'),
            'create' => Pages\CreateLabAttendance::route('/create'),
            'edit' => Pages\EditLabAttendance::route('/{record}/edit'),
        ];
    }
}
