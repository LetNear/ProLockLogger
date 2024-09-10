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
                    ->maxLength(9)
                    ->rule(function ($get, $set, $record) {
                        return function ($attribute, $value, $fail) use ($get, $record) {
                            // Validate the school year format and ensure it's one year apart
                            if (preg_match('/^(\d{4})-(\d{4})$/', $value, $matches)) {
                                $startYear = (int)$matches[1];
                                $endYear = (int)$matches[2];

                                // Check that the school year starts from 2024 onwards
                                if ($startYear < 2024) {
                                    $fail('The school year must start from 2024 onwards.');
                                }

                                // Check that the school years are exactly one year apart
                                if ($endYear !== $startYear + 1) {
                                    $fail('The school year must be exactly one year apart (e.g., 2024-2025).');
                                }
                            } else {
                                $fail('The school year format is invalid. Please use YYYY-YYYY.');
                            }

                            // Validation logic for checking duplicates and count of semesters
                            $semester = $get('semester');

                            // Only check for duplicates if $record is not null (i.e., not during creation)
                            $existingRecord = YearAndSemester::where('school_year', $value)
                                ->where('semester', $semester)
                                ->when($record, function ($query) use ($record) {
                                    return $query->where('id', '!=', $record->id);
                                })
                                ->exists();

                            if ($existingRecord) {
                                $fail('The combination of this school year and semester already exists.');
                            }

                            // Check if there are already two records with the same school year but different semesters
                            $sameYearCount = YearAndSemester::where('school_year', $value)
                                ->when($record, function ($query) use ($record) {
                                    return $query->where('id', '!=', $record->id);
                                })
                                ->distinct('semester')
                                ->count();

                            if ($sameYearCount >= 2) {
                                $fail('There can only be two records with the same school year, each with a different semester.');
                            }
                        };
                    }),

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
                    ->placeholder('Select status')
                    ->rule(function () {
                        return function ($attribute, $value, $fail) {
                            if ($value === 'on-going') {
                                $existingOnGoing = YearAndSemester::where('status', 'on-going')
                                    ->where('id', '!=', request()->route('record'))
                                    ->exists();

                                if ($existingOnGoing) {
                                    $fail('There can only be one record with "On-going" status.');
                                }
                            }
                        };
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('school_year')
                    ->label('School Year')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('semester')
                    ->label('Semester')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'on-going',
                        'warning' => 'pending',
                        'danger' => 'closed',
                    ])
                    ->formatStateUsing(function ($state) {
                        return ucfirst(str_replace('-', ' ', $state)); // Format state to readable text
                    })
                    ->searchable()
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
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'on-going' => 'On-going',
                        'pending' => 'Pending',
                        'closed' => 'Closed',
                    ]),
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
            // Define any relations if needed
        ];
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
