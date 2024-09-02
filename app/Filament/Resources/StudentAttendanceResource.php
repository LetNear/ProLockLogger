<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentAttendanceResource\Pages;
use App\Models\StudentAttendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TextFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\DateFilter;
use Filament\Tables\Filters\Filter;

class StudentAttendanceResource extends Resource
{
    protected static ?string $model = StudentAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Student Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Name'),
                        Forms\Components\TextInput::make('course')
                            ->required()
                            ->label('Course'),
                        Forms\Components\TextInput::make('year')
                            ->required()
                            ->label('Year Level'),
                        Forms\Components\TextInput::make('block')
                            ->required()
                            ->label('Block'),
                        Forms\Components\TextInput::make('student_number')
                            ->required()
                            ->unique(StudentAttendance::class, 'student_number')
                            ->label('Student Number'),
                    ]),
                Forms\Components\Section::make('Attendance Details')
                    ->schema([
                        Forms\Components\TimePicker::make('time_in')
                            ->label('Time In'),
                        Forms\Components\TimePicker::make('time_out')
                            ->label('Time Out'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'present' => 'Present',
                                'absent' => 'Absent',
                                'late' => 'Late',
                            ])
                            ->required()
                            ->label('Status'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('2s')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('course')
                    ->label('Course')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('year')
                    ->label('Year Level')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('block')
                    ->label('Block')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('student_number')
                    ->label('Student Number')
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([


               
            ])
            ->actions([
                
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentAttendances::route('/'),
            
        ];
    }
}
