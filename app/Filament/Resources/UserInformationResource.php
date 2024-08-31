<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserInformationResource\Pages;
use App\Filament\Resources\UserInformationResource\RelationManagers;
use App\Models\Nfc;
use App\Models\Role;
use App\Models\Seat;
use App\Models\UserInformation;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;
use App\Models\User;

class UserInformationResource extends Resource
{
    protected static ?string $model = UserInformation::class;
    protected static ?string $navigationIcon = 'heroicon-o-information-circle';
    protected static ?string $title = 'User Information';
    protected static ?string $label = 'User Information';
    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        $existingUserIds = UserInformation::pluck('user_id')->toArray();
        $existingIdCardIds = UserInformation::pluck('id_card_id')->toArray();
        $existingSeatIds = UserInformation::pluck('seat_id')->toArray();

        return $form
            ->schema([
                Section::make('User Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->label('User')
                                    ->placeholder('Select a user')
                                    ->helperText('Choose the user for this information.')
                                    ->searchable()
                                    ->preload(10)
                                    ->options(fn() => User::whereNotIn('id', $existingUserIds)->pluck('name', 'id')->toArray())
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $user = User::find($state);
                                        $roleNumber = $user ? $user->role_number : null;

                                        $set('isRestricted', $roleNumber == 1 || $roleNumber == 2);
                                    }),

                                Select::make('id_card_id')
                                    ->relationship('idCard', 'rfid_number')
                                    ->label('RFID Number')
                                    ->placeholder('Select an RFID number')
                                    ->helperText('Choose the RFID number for this user.')
                                    ->searchable()
                                    ->preload(10)
                                    ->options(function () {
                                        // Get all RFID numbers already assigned to a user
                                        $assignedIds = UserInformation::whereNotNull('id_card_id')->pluck('id_card_id');

                                        // Fetch RFID numbers that are not assigned yet
                                        return Nfc::whereNotIn('id', $assignedIds)->pluck('rfid_number', 'id')->toArray();
                                    })
                                    ->disabled(fn($get) => $get('isRestricted')),


                                Select::make('seat_id')
                                    ->relationship('seat', 'computer_id')
                                    ->label('Computer Number')
                                    ->placeholder('Select a computer number')
                                    ->helperText('Choose the computer number assigned to this user.')
                                    ->searchable()
                                    ->preload(10)
                                    ->options(fn() => Seat::whereNotIn('id', $existingSeatIds)->pluck('computer_id', 'id')->toArray())
                                    ->disabled(fn($get) => $get('isRestricted')),

                                TextInput::make('user_number')
                                    ->label('User ID Card Number')
                                    ->placeholder('Enter the user ID card number')
                                    ->helperText('The user\'s ID card number.')
                                    ->disabled(fn($get) => $get('isRestricted')),

                                Select::make('block_id')
                                    ->relationship('block', 'block')
                                    ->label('Block')
                                    ->placeholder('Select a block')
                                    ->helperText('Choose the block assigned to this user.')
                                    ->searchable()
                                    ->preload(10)
                                    ->disabled(fn($get) => $get('isRestricted')),

                                Select::make('year')
                                    ->options([
                                        '1' => '1st Year',
                                        '2' => '2nd Year',
                                        '3' => '3rd Year',
                                        '4' => '4th Year',
                                    ])
                                    ->label('Year')
                                    ->placeholder('Select the year')
                                    ->helperText('Choose the year level of the user.')
                                    ->disabled(fn($get) => $get('isRestricted')),
                                Select::make('courses')
                                    ->relationship('courses', 'course_name') // Uses the many-to-many relationship
                                    ->label('Courses')
                                    ->placeholder('Select courses')
                                    ->helperText('Choose one or more courses for this user.')
                                    ->searchable()
                                    ->multiple() // Allows multiple selections
                                    ->preload(10)
                                    ->options(fn() => \App\Models\Course::whereHas('labSchedules')->pluck('course_name', 'id')->toArray())
                                    ->disabled(fn($get) => $get('isRestricted')),
                            ]),
                    ]),
                Section::make('Personal Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('First Name')
                                    ->placeholder('Enter the first name')
                                    ->helperText('The user\'s first name.')
                                    ->maxLength(255),

                                TextInput::make('middle_name')
                                    ->label('Middle Name')
                                    ->placeholder('Enter the middle name')
                                    ->helperText('The user\'s middle name.')
                                    ->maxLength(255)
                                    ->default(null),

                                TextInput::make('last_name')
                                    ->label('Last Name')
                                    ->placeholder('Enter the last name')
                                    ->helperText('The user\'s last name.')
                                    ->maxLength(255),

                                TextInput::make('suffix')
                                    ->label('Suffix')
                                    ->placeholder('Enter the suffix')
                                    ->helperText('The user\'s suffix, if any.')
                                    ->maxLength(255)
                                    ->default(null),

                                DatePicker::make('date_of_birth')
                                    ->label('Date of Birth')
                                    ->placeholder('Select the date of birth')
                                    ->helperText('The user\'s date of birth.'),

                                Select::make('gender')
                                    ->options([
                                        'Male' => 'Male',
                                        'Female' => 'Female',
                                        'Other' => 'Other',
                                    ])
                                    ->label('Gender')
                                    ->placeholder('Select the gender')
                                    ->helperText('The user\'s gender.'),
                            ]),
                    ]),
                Section::make('Contact Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('contact_number')
                                    ->label('Contact Number')
                                    ->placeholder('Enter the contact number')
                                    ->helperText('The user\'s contact number. E.g., 09123456789')
                                    ->numeric()
                                    ->minLength(11)
                                    ->maxLength(11),

                                TextInput::make('complete_address')
                                    ->label('Complete Address')
                                    ->placeholder('Enter the complete address')
                                    ->helperText('The user\'s complete address.')
                                    ->maxLength(255),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('2s')
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s name.')
                    ->alignLeft(),
    
                TextColumn::make('user.role.name')
                    ->label('Role')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s role.')
                    ->alignLeft(),
    
                TextColumn::make('idCard.rfid_number')
                    ->label('RFID Number')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s RFID number.')
                    ->alignLeft()
                    ->getStateUsing(fn($record) => $record->id_card_id ? $record->idCard->rfid_number : 'None'),
    
                TextColumn::make('user_number')
                    ->label('User ID Card Number')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s ID card number.')
                    ->alignLeft(),
    
                TextColumn::make('seat.computer_number')
                    ->label('Computer Number')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s assigned computer number.')
                    ->alignLeft(),
    
                TextColumn::make('block.block')
                    ->label('Block')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s block.')
                    ->alignLeft(),
    
                TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s year level.')
                    ->alignLeft(),
    
                // Add this column to display courses
                TextColumn::make('courses')
                    ->label('Courses')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The courses assigned to the user.')
                    ->alignLeft()
                    ->getStateUsing(fn($record) => $record->courses->pluck('course_name')->join(', ')), // Joins course names into a string
    
                TextColumn::make('first_name')
                    ->label('First Name')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s first name.')
                    ->alignLeft(),
    
                TextColumn::make('middle_name')
                    ->label('Middle Name')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s middle name.')
                    ->alignLeft(),
    
                TextColumn::make('last_name')
                    ->label('Last Name')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s last name.')
                    ->alignLeft(),
    
                TextColumn::make('suffix')
                    ->label('Suffix')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s suffix.')
                    ->alignLeft(),
    
                TextColumn::make('date_of_birth')
                    ->label('Date of Birth')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s date of birth.')
                    ->alignCenter(),
    
                TextColumn::make('gender')
                    ->label('Gender')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s gender.')
                    ->alignLeft(),
    
                TextColumn::make('contact_number')
                    ->label('Contact Number')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s contact number.')
                    ->alignLeft(),
    
                TextColumn::make('complete_address')
                    ->label('Complete Address')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s complete address.')
                    ->alignLeft(),
    
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
    
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Year')
                    ->options([
                        '1' => '1st Year',
                        '2' => '2nd Year',
                        '3' => '3rd Year',
                        '4' => '4th Year',
                    ]),
    
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Gender')
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                        'Other' => 'Other',
                    ]),
    
                Tables\Filters\SelectFilter::make('block_id')
                    ->label('Block')
                    ->relationship('block', 'block'),
    
                // Tables\Filters\TrashedFilter::make('trashed'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserInformation::route('/'),
            'create' => Pages\CreateUserInformation::route('/create'),
            'edit' => Pages\EditUserInformation::route('/{record}/edit'),
        ];
    }

    // protected static function getNavigationIcon(): string
    // {
    //     return 'heroicon-o-information-circle';
    // }
}
