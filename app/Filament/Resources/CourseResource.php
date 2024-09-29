<?php

namespace App\Filament\Resources;

use App\Filament\Imports\CourseImporter;
use App\Filament\Resources\CourseResource\Pages;
use App\Models\Course;
use App\Models\YearAndSemester;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Courses';
    protected static ?string $pluralLabel = 'Courses';
    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        // Get the current authenticated user
        $user = Auth::user();
    
        // Check if the user's role_number is 2 (Faculty)
        $isFaculty = $user && $user->role_number === 2;
    
        // Fetch the active 'on-going' Year and Semester
        $onGoingYearAndSemester = YearAndSemester::where('status', 'on-going')->first();
    
        return $form
            ->schema([
                Section::make('Course Information')
                    ->description('Please provide the details for the course below.')
                    ->schema([
    
                        // Filter instructors by the ongoing year and semester
                        Select::make('instructor_id')
                            ->relationship('instructor', 'name', function ($query) use ($onGoingYearAndSemester) {
                                // Ensure there is an ongoing year and semester
                                if ($onGoingYearAndSemester) {
                                    $query->where('role_number', 2)
                                          ->where('year_and_semester_id', $onGoingYearAndSemester->id); // Filter by year and semester
                                }
                            })
                            ->preload()
                            ->searchable()
                            ->label('Instructor')
                            ->required()
                            ->disabled($isFaculty),  // Disable field for faculty
                        
                        Forms\Components\TextInput::make('course_name')
                            ->label('Course Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter the name of the course.')
                            ->disabled($isFaculty),  // Disable field for faculty
                        
                        Forms\Components\TextInput::make('course_code')
                            ->label('Course Code')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter the code for the course.')
                            ->disabled($isFaculty),  // Disable field for faculty
    
                        RichEditor::make('course_description')
                            ->label('Course Description')
                            ->helperText('Provide a brief description of the course.')
                            ->disabled(false),  // Always enabled
                    ]),
            ]);
    }
    

    public static function table(Table $table): Table
    {
        return $table
            ->poll('2s')
            ->headerActions([
                ImportAction::make()
                    ->importer(CourseImporter::class)
                    ->label('Import Course')
                    ->visible(fn() => Auth::user()->hasRole('Administrator')),
            ])
            ->columns([
                TextColumn::make('instructor.name')
                    ->label('Instructor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('course_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('course_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('course_description')
                    ->label('Description')
                    ->wrap()
                    ->limit(50),

                TextColumn::make('yearAndSemester.school_year')
                    ->label('School Year')
                    ->sortable()
                    ->tooltip('The school year of the course.')
                    ->searchable(),

                TextColumn::make('yearAndSemester.semester')
                    ->label('Semester')
                    ->sortable()
                    ->tooltip('The semester of the course.')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user.year_and_semester_id')
                ->label('Year and Semester')
                ->options(YearAndSemester::all()->mapWithKeys(function ($item) {
                    return [$item->id => $item->school_year . ' - ' . $item->semester];
                })->toArray())
                ->query(function (Builder $query, $data) {
                    if (isset($data['value'])) {
                        $query->where('year_and_semester_id', $data['value']);
                    }
                })
                ->placeholder('Select Year and Semester')
                ->searchable(),
            ])
            ->actions([
                EditAction::make()
                    ->tooltip('Edit Course'),
                
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->tooltip('Delete Course')
                    ->requiresConfirmation()
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->icon('heroicon-o-trash')
                        ->label('Delete Selected')
                        ->color('danger')
                        ->visible(fn() => Auth::user()->hasRole('Administrator')),
                ]),
            ]);
    }

    /**
     * Customize the query to show only the logged-in user's courses if they're an instructor (role_number 2),
     * and show all courses if the user is an admin (role_number 1).
     */
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        // Admin (role_number 1) can see all courses
        if ($user->role_number == 1) {
            return parent::getEloquentQuery();
        }

        // Instructors (role_number 2) can only see their own courses
        return parent::getEloquentQuery()->where('instructor_id', $user->id);
    }

    public static function getRelations(): array
    {
        return [
            // Define any related models or managers here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
