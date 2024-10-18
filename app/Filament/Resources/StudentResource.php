<?php

namespace App\Filament\Resources;

use App\Filament\Imports\StudentImporter;
use App\Filament\Resources\StudentResource\Pages;
use App\Models\User;
use App\Models\YearAndSemester;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // Import Rule class
use Filament\Notifications\Notification;

class StudentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $title = 'Student';

    protected static ?string $label = 'Student';

    protected static ?string $navigationGroup = 'User Management';

    // Year and Semester check similar to User logic
    // protected function getActiveYearAndSemester()
    // {
    //     return YearAndSemester::where('status', 'on-going')->first(); // Fetches the first active year and semester
    // }

    public static function form(Form $form): Form
    {
        $ongoingYearAndSemester = User::getOngoingYearAndSemester();

        if(!$ongoingYearAndSemester){
            Notification::make()
            ->title('Cannot Save Student')
            ->danger()
            ->body('There is no ongoing year and semester. Please sePt an on-going year and semester')
            ->send();
            return $form->schema([]);
        }

        return $form
            ->schema([
                Section::make('Student Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter the student\'s name')
                                    ->helperText('The full name of the student.'),
                                
                                // Email field with validation for uniqueness per year and semester
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter the student\'s email address')
                                    ->helperText('The email address of the student.')
                                    ->rules([
                                        Rule::unique('users', 'email')
                                            ->where(function ($query) use ($ongoingYearAndSemester) {
                                                return $query->where('year_and_semester_id', $ongoingYearAndSemester->id);
                                            })
                                            ->ignore(request()->route('record')), // Ignore current record when editing
                                    ]),
                                
                                Select::make('role_number')
                                    ->relationship('role', 'name')
                                    ->label('Roles')
                                    ->default(3)
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
                ImportAction::make()
                    ->importer(StudentImporter::class)
                    ->label('Import Students')
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->tooltip('The full name of the student.'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->tooltip('The email address of the student.'),
                Tables\Columns\TextColumn::make('role_number')
                    ->label('Roles')
                    ->getStateUsing(fn($record) => 'Student')
                    ->sortable()
                    ->tooltip('The roles assigned to the student.'),
                TextColumn::make('yearAndSemester.school_year')
                    ->label('School Year')
                    ->sortable()
                    ->tooltip('The school year of the user.'),
                TextColumn::make('yearAndSemester.semester')
                    ->label('Semester')
                    ->sortable()
                    ->tooltip('The semester of the user.'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year_and_semester_id')
                    ->label('Year and Semester')
                    ->options(YearAndSemester::all()->mapWithKeys(function ($item) {
                        return [$item->id => $item->school_year . ' - ' . $item->semester];
                    })->toArray())
                    ->query(function (Builder $query, $data) {
                        if (isset($data['value'])) {
                            $query->where('year_and_semester_id', $data['value']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-s-pencil')
                    ->tooltip('Edit this student'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-s-trash')
                    ->tooltip('Delete this student'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->icon('heroicon-s-trash')
                    ->tooltip('Delete selected students'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add any relations here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role_number', 3); // Students only
    }

   
   
}
