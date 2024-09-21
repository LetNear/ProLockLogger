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

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Courses';
    protected static ?string $pluralLabel = 'Courses';
    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Course Information') // Add section for better organization
                    ->description('Please provide the details for the course below.')
                    ->schema([
                        Select::make('instructor_id')
                            ->relationship('instructor', 'name', function($query){
                                return $query->where('role_number', 2);
                            })
                            ->preload()
                            ->searchable()
                            ->label('Instructor')
                            ->required(),
                        Forms\Components\TextInput::make('course_name')
                            ->label('Course Name')
                            ->required()
                            ->maxLength(255)
                          
                            ->helperText('Enter the name of the course.'),

                        Forms\Components\TextInput::make('course_code')
                            ->label('Course Code')
                            ->required()
                            ->maxLength(255)
                          
                            ->helperText('Enter the code for the course.'),

                        RichEditor::make('course_description')
                            ->label('Course Description')
                           

                            ->helperText('Provide a brief description of the course.'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->headerActions([
                ImportAction::make()
                    ->importer(CourseImporter::class)
                    ->label('Import Course')
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

                    ->wrap() // Wrap text for long descriptions
                    ->limit(50), // Limit displayed text for better readability

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
                })->toArray()) // Fetch year and semester options from the model
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

                    ->tooltip('Edit Course'), // Tooltip for clarity

                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->tooltip('Delete Course')
                    ->requiresConfirmation() // Confirmation dialog for deletes
                    ->color('danger'), // Highlight delete actions with color
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->icon('heroicon-o-trash')
                        ->label('Delete Selected') // Label for clarity
                        ->color('danger'),
                ]),
            ]);
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
