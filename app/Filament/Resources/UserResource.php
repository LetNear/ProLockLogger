<?php

namespace App\Filament\Resources;

use App\Filament\Imports\UserImporter;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\YearAndSemester;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule; // Import the Rule class
use Filament\Notifications\Notification;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $title = 'Faculty User';

    protected static ?string $label = 'Faculty User';

    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {

        $ongoingYearAndSemester = User::getOngoingYearAndSemester();

    if (!$ongoingYearAndSemester) {
        // Trigger a notification and prevent submission if no ongoing year and semester
        Notification::make()
            ->title('Cannot Save User')
            ->danger()
            ->body('There is no ongoing year and semester. Please set an ongoing year and semester before saving a user.')
            ->send();

        return $form->schema([]); // Return an empty schema to prevent submission
    }
        return $form
            ->schema([
                Section::make('User Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter the user\'s name')
                                    ->helperText('The full name of the user.'),
                                    TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter the user\'s email address')
                                    ->helperText('The email address of the user.')
                                    ->rules(function () {
                                        $recordId = request()->route('record');
    
                                        // Check if we're editing a record
                                        if ($recordId) {
                                            $user = User::find($recordId);
    
                                            if ($user) {
                                                return [
                                                    Rule::unique('users', 'email')
                                                        ->where(function ($query) use ($user) {
                                                            if ($user->year_and_semester_id) {
                                                                $query->where('year_and_semester_id', $user->year_and_semester_id);
                                                            }
                                                        })
                                                        ->ignore($user->id), // Ignore this user's ID when checking uniqueness
                                                ];
                                            }
                                        }
    
                                        // If creating a new user
                                        return [
                                            Rule::unique('users', 'email')
                                                ->where(function ($query) {
                                                    $ongoingYearAndSemester = User::getOngoingYearAndSemester();
                                                    if ($ongoingYearAndSemester) {
                                                        $query->where('year_and_semester_id', $ongoingYearAndSemester->id);
                                                    }
                                                }),
                                        ];
                                    }),
                                Select::make('role_number')
                                    ->label('Roles')
                                    ->relationship('roles', 'name')
                                    ->preload()
                                    ->required(),

                                Repeater::make('fingerprint_id')
                                    ->label('Fingerprint IDs')
                                    ->schema([
                                        TextInput::make('fingerprint_id')
                                            ->label('Fingerprint ID')
                                            ->placeholder('Enter a fingerprint ID')
                                    ])
                                    ->minItems(0)
                                    ->maxItems(2)
                                    ->helperText('Add exactly two fingerprint IDs.')
                                    ->disableItemDeletion()
                                    ->disabled(),

                            ]),
                    ]),
            ]);
    }

    // This can be added in the boot method or inside the model observer to automatically assign year and semester
    protected static function boot()
    {
        parent::boot();
    
        static::creating(function ($user) {
            $ongoingYearAndSemester = User::getOngoingYearAndSemester();
    
            if (!$ongoingYearAndSemester) {
                // Prevent saving if no ongoing year and semester
                Notification::make()
                    ->title('Cannot Save User')
                    ->danger()
                    ->body('There is no ongoing year and semester. Please set an ongoing year and semester before saving a user.')
                    ->send();
    
                return false; // Prevent saving the record
            }
    
            $user->year_and_semester_id = $ongoingYearAndSemester->id;
        });
    }
    

    public static function table(Table $table): Table
    {
        return $table
            ->poll('2s')
            ->headerActions([
                ImportAction::make()
                    ->importer(UserImporter::class)
                    ->label('Import Instructors')
                    ->visible(fn() => Auth::user()->hasRole('Administrator')), // Only visible to Administrators
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->tooltip('The full name of the user.'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->tooltip('The email address of the user.'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('The date and time when the user was created.'),
                Tables\Columns\TextColumn::make('role_number')
                    ->label('Roles')
                    ->getStateUsing(function ($record) {
                        $roles = [
                            1 => 'Administrator',
                            2 => 'Faculty',
                            3 => 'Student',
                        ];

                        return $roles[$record->role_number] ?? 'Unknown';
                    })
                    ->sortable()
                    ->tooltip('The roles assigned to the user.'),
                TextColumn::make('fingerprint_id')
                    ->label('Fingerprint IDs')
                    ->getStateUsing(function ($record) {
                        $fingerprintData = $record->fingerprint_id;

                        if (is_string($fingerprintData)) {
                            $fingerprintData = json_decode($fingerprintData, true);
                        }

                        if (!is_array($fingerprintData)) {
                            $fingerprintData = [];
                        }

                        $fingerprintIds = array_map(function ($item) {
                            return $item['fingerprint_id'] ?? null;
                        }, $fingerprintData);

                        $fingerprintIds = array_filter($fingerprintIds);

                        return empty($fingerprintIds) ? 'No fingerprints' : implode(', ', $fingerprintIds);
                    })
                    ->searchable()
                    ->sortable()
                    ->tooltip('The fingerprint IDs of the user.'),
                TextColumn::make('yearAndSemester.school_year')
                    ->label('School Year')
                    ->sortable()
                    ->tooltip('The school year of the user.')
                    ->searchable(),
                TextColumn::make('yearAndSemester.semester')
                    ->label('Semester')
                    ->sortable()
                    ->tooltip('The semester of the user.')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-s-pencil')
                    ->tooltip('Edit this user'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-s-trash')
                    ->tooltip('Delete this user'),
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
                // Tables\Filters\TrashedFilter::make('trashed'),
            ])

            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->icon('heroicon-s-trash')
                    ->tooltip('Delete selected users'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
