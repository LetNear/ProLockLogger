<?php

namespace App\Filament\Resources;

use App\Exports\StudentAttExporter;
use App\Filament\Exports\StudentAttendanceExporter;
use App\Filament\Resources\StudentAttendanceResource\Pages;
use App\Models\StudentAttendance;
use App\Models\UserInformation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Table;
use Filament\Tables\Filters\TextFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\DateFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

class StudentAttendanceResource extends Resource
{
    protected static ?string $model = StudentAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Laboratory Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Student Information')
                    ->schema([
                        Forms\Components\Select::make('user_information_id')
                            ->relationship('userInformation', 'id')
                            ->required()
                            ->preload()
                            ->label('Student')
                            ->searchable(),
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

    // Override the query to filter by instructor's logs
    public static function getEloquentQuery(): Builder
    {
        $instructor = auth()->user();
        $courses = $instructor->courses->pluck('id');

        if (!$courses) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()
            ->whereIn('course_id', $courses);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->poll('2s')
            ->headerActions([
                Action::make('export')
                    ->label('Export to Excel')
                    ->action(function () {
                        // Trigger the export using Maatwebsite Excel and your custom exporter
                        return Excel::download(new StudentAttExporter, 'student-attendance.xlsx');
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('userInformation.user.name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('associatedCourse')
                    ->label('Course')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        // Fetch the course associated with this attendance log
                        return $record->associatedCourse ?? 'N/A'; // Assuming 'associatedCourse' is a computed attribute
                    })
                    ->html(), // Enable HTML rendering
                Tables\Columns\TextColumn::make('userInformation.year')
                    ->label('Year Level')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('userInformation.block.block')
                    ->label('Block')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('userInformation.user_number')
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
            ->filters([])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([]);
           
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
