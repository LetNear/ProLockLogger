<?php
namespace App\Filament\Resources;

use App\Filament\Imports\LabScheduleImporter;
use App\Filament\Resources\LabScheduleResource\Pages;
use App\Models\LabSchedule;
use App\Models\Course;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\DateFilter;
use Filament\Tables\Table;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class LabScheduleResource extends Resource
{
    protected static ?string $model = LabSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $title = 'Laboratory Schedule';
    protected static ?string $label = 'Laboratory Schedule';
    protected static ?string $navigationGroup = 'Laboratory Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Select::make('course_id')
                                    ->label('Course')
                                    ->relationship('course', 'course_name')
                                    ->required()
                                    ->placeholder('Select a course')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($course = Course::find($state)) {
                                            $set('course_code', $course->course_code);
                                            $set('course_name', $course->course_name);
                                        } else {
                                            $set('course_code', null);
                                            $set('course_name', null);
                                        }
                                    }),
                                Select::make('instructor_id')
                                    ->label('Instructor')
                                    ->options(User::where('role_number', 2)->pluck('name', 'id')->toArray())
                                    ->required()
                                    ->placeholder('Select an instructor'),
                                Select::make('block_id')
                                    ->relationship('block', 'block')
                                    ->required(),
                                Select::make('year')
                                    ->options([
                                        '1' => '1st Year',
                                        '2' => '2nd Year',
                                        '3' => '3rd Year',
                                        '4' => '4th Year',
                                    ])
                                    ->required(),
                            ]),
                    ]),
                Forms\Components\Section::make('Schedule Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Select::make('day_of_the_week')
                                    ->options([
                                        'Monday' => 'Monday',
                                        'Tuesday' => 'Tuesday',
                                        'Wednesday' => 'Wednesday',
                                        'Thursday' => 'Thursday',
                                        'Friday' => 'Friday',
                                        'Saturday' => 'Saturday',
                                        'Sunday' => 'Sunday',
                                    ])
                                    ->required(),
                                TimePicker::make('class_start')
                                    ->label('Class Start Time')
                                    ->required()
                                    ->seconds(false),
                                TimePicker::make('class_end')
                                    ->label('Class End Time')
                                    ->required()
                                    ->seconds(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ImportAction::make()
                    ->importer(LabScheduleImporter::class)
                    ->label('Import Schedule'),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('course_name')
                    ->label('Course Name')
                    ->searchable(),
                TextColumn::make('course_code')
                    ->label('Course Code')
                    ->searchable(),
                TextColumn::make('instructor.name')
                    ->label('Instructor')
                    ->searchable(),
                TextColumn::make('block.block')
                    ->label('Block')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('year')
                    ->label('Year')
                    ->searchable(),
                TextColumn::make('day_of_the_week')
                    ->label('Day of the Week')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('class_start')
                    ->label('Class Start Time')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('class_end')
                    ->label('Class End Time')
                    ->searchable()
                    ->sortable(),
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
                SelectFilter::make('course_id')
                    ->label('Course')
                    ->relationship('course', 'course_name')
                    ->searchable()
                    ->placeholder('All Courses'),

                SelectFilter::make('instructor_id')
                    ->label('Instructor')
                    ->relationship('instructor', 'name')
                    ->searchable()
                    ->placeholder('All Instructors'),

                SelectFilter::make('block_id')
                    ->label('Block')
                    ->relationship('block', 'block')
                    ->searchable()
                    ->placeholder('All Blocks'),

                SelectFilter::make('year')
                    ->label('Year')
                    ->options([
                        '1' => '1st Year',
                        '2' => '2nd Year',
                        '3' => '3rd Year',
                        '4' => '4th Year',
                    ])
                    ->placeholder('All Years'),

                SelectFilter::make('day_of_the_week')
                    ->label('Day of the Week')
                    ->options([
                        'Monday' => 'Monday',
                        'Tuesday' => 'Tuesday',
                        'Wednesday' => 'Wednesday',
                        'Thursday' => 'Thursday',
                        'Friday' => 'Friday',
                        'Saturday' => 'Saturday',
                        'Sunday' => 'Sunday',
                    ])
                    ->placeholder('All Days'),

                // You can also add custom filters for Class Start and End Times if needed
                Filter::make('class_start')
                    ->label('Class Start Time')
                    ->form([
                        Forms\Components\TimePicker::make('start')
                            ->label('Start Time')
                            ->withoutSeconds(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['start'], fn($q, $time) => $q->where('class_start', '>=', $time));
                    }),

                Filter::make('class_end')
                    ->label('Class End Time')
                    ->form([
                        Forms\Components\TimePicker::make('end')
                            ->label('End Time')
                            ->withoutSeconds(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['end'], fn($q, $time) => $q->where('class_end', '<=', $time));
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
            'index' => Pages\ListLabSchedules::route('/'),
            'create' => Pages\CreateLabSchedule::route('/create'),
            'edit' => Pages\EditLabSchedule::route('/{record}/edit'),
        ];
    }
}
