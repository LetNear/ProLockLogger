<?php

namespace App\Filament\Resources;

use App\Filament\Resources\YearAndSemesterResource\Pages;
use App\Models\YearAndSemester;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class YearAndSemesterResource extends Resource
{
    protected static ?string $model = YearAndSemester::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('school_year')
                    ->label('School Year')
                    ->required()
                    ->placeholder('e.g., 2024-2025')
                    ->rules(['required', 'regex:/^\d{4}-\d{4}$/'])
                    ->helperText('Format: YYYY-YYYY')
                    ->maxLength(9),

                Select::make('semester')
                    ->label('Semester')
                    ->required()
                    ->options([
                        '1st semester' => '1st Semester',
                        '2nd semester' => '2nd Semester',
                    ])
                    ->placeholder('Select a semester'),

                Select::make('status')
                    ->label('Status')
                    ->required()
                    ->options([
                        'on-going' => 'On-going',
                        'pending' => 'Pending',
                        'closed' => 'Closed',
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('school_year')
                    ->label('School Year')
                    ->sortable(),

                Tables\Columns\TextColumn::make('semester')
                    ->label('Semester')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'on-going',
                        'warning' => 'pending',
                        'danger' => 'closed',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListYearAndSemesters::route('/'),
            'create' => Pages\CreateYearAndSemester::route('/create'),
            'edit' => Pages\EditYearAndSemester::route('/{record}/edit'),
        ];
    }
}
