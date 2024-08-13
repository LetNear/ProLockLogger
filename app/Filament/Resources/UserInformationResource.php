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
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $user = User::find($state);
                                    $isInstructor = $user && $user->roles->contains('id', 2);
                                    $isAdmin = $user && $user->roles->contains('id', 1);

                                    $set('isInstructor', $isInstructor);
                                    $set('disableUserIdCard', $isAdmin);
                                }),

                            Select::make('id_card_id')
                                ->relationship('idCard', 'rfid_number')
                                ->label('RFID Number')
                                ->placeholder('Select an RFID number')
                                ->helperText('Choose the RFID number for this user.')
                                ->searchable(fn($get) => !$get('isInstructor'))
                                ->preload(10)
                                ->options(fn() => Nfc::whereNotIn('id', $existingIdCardIds)->pluck('rfid_number', 'id')->toArray())
                                ->disabled(fn($get) => $get('disableUserIdCard')),

                            Select::make('seat_id')
                                ->relationship('seat', 'computer_id')
                                ->label('Computer Number')
                                ->placeholder('Select a computer number')
                                ->helperText('Choose the computer number assigned to this user.')
                                ->searchable(fn($get) => !$get('isInstructor'))
                                ->preload(10)
                                ->options(fn() => Seat::whereNotIn('id', $existingSeatIds)->pluck('computer_id', 'id')->toArray())
                                ->createOptionForm([
                                    Section::make('New Seat Details')
                                        ->schema([
                                            Forms\Components\Grid::make(2)
                                                ->schema([
                                                    TextInput::make('computer_number')
                                                        ->required()
                                                        ->label('Computer Number')
                                                        ->placeholder('Enter the computer number'),
                                                    TextInput::make('instructor')
                                                        ->required()
                                                        ->label('Instructor')
                                                        ->placeholder('Enter the instructor name'),
                                                    TextInput::make('year_section')
                                                        ->required()
                                                        ->label('Year Section')
                                                        ->placeholder('Enter the year and section'),
                                                ]),
                                        ]),
                                ])
                                ->disabled(fn($get) => $get('disableUserIdCard') || $get('isInstructor')),

                            TextInput::make('user_number')
                                ->label('User ID Card Number')
                                ->placeholder('Enter the user ID card number')
                                ->helperText('The user\'s ID card number.')
                                ->disabled(fn($get) => $get('disableUserIdCard')),

                            Select::make('block_id')
                                ->relationship('block', 'block')
                                ->label('Block')
                                ->placeholder('Select a block')
                                ->helperText('Choose the block assigned to this user.')
                                ->searchable(fn($get) => !$get('isInstructor'))
                                ->preload(10)
                                ->createOptionForm([
                                    Section::make('New Block Details')
                                        ->schema([
                                            Forms\Components\Grid::make(2)
                                                ->schema([
                                                    TextInput::make('block')
                                                        ->required()
                                                        ->label('Block Name')
                                                        ->placeholder('Enter the block name'),
                                                ]),
                                        ]),
                                ])
                                ->disabled(fn($get) => $get('disableUserIdCard') || $get('isInstructor')),

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
                                ->disabled(fn($get) => $get('disableUserIdCard') || $get('isInstructor'))
                                ->searchable(fn($get) => !$get('isInstructor')),

                        ]),
                ]),
            Section::make('Personal Information')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            TextInput::make('first_name')
                                ->required()
                                ->label('First Name')
                                ->placeholder('Enter the first name')
                                ->helperText('The user\'s first name.')
                                ->maxLength(255)
                                ->disabled(fn($get) => $get('disableUserIdCard')),
                            TextInput::make('middle_name')
                                ->label('Middle Name')
                                ->placeholder('Enter the middle name')
                                ->helperText('The user\'s middle name.')
                                ->maxLength(255)
                                ->default(null)
                                ->disabled(fn($get) => $get('disableUserIdCard')),
                            TextInput::make('last_name')
                                ->required()
                                ->label('Last Name')
                                ->placeholder('Enter the last name')
                                ->helperText('The user\'s last name.')
                                ->maxLength(255)
                                ->disabled(fn($get) => $get('disableUserIdCard')),
                            TextInput::make('suffix')
                                ->label('Suffix')
                                ->placeholder('Enter the suffix')
                                ->helperText('The user\'s suffix, if any.')
                                ->maxLength(255)
                                ->default(null)
                                ->disabled(fn($get) => $get('disableUserIdCard')),
                            DatePicker::make('date_of_birth')
                                ->required()
                                ->label('Date of Birth')
                                ->placeholder('Select the date of birth')
                                ->helperText('The user\'s date of birth.')
                                ->disabled(fn($get) => $get('disableUserIdCard')),
                            Select::make('gender')
                                ->options([
                                    'Male' => 'Male',
                                    'Female' => 'Female',
                                    'Other' => 'Other',
                                ])
                                ->label('Gender')
                                ->placeholder('Select the gender')
                                ->helperText('The user\'s gender.')
                                ->disabled(fn($get) => $get('disableUserIdCard')),
                        ]),
                ]),
            Section::make('Contact Information')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            TextInput::make('contact_number')
                                ->required()
                                ->label('Contact Number')
                                ->placeholder('Enter the contact number')
                                ->helperText('The user\'s contact number. E.g., 09123456789')
                                ->numeric()
                                ->minLength(11)
                                ->maxLength(11)
                                ->rules([
                                    'required',
                                    'string',
                                    'regex:/^09[0-9]{9}$/',
                                ])
                                ->disabled(fn($get) => $get('disableUserIdCard')),

                            TextInput::make('complete_address')
                                ->required()
                                ->label('Complete Address')
                                ->placeholder('Enter the complete address')
                                ->helperText('The user\'s complete address.')
                                ->maxLength(255)
                                ->rules(['required', 'string', 'min:10'])
                                ->disabled(fn($get) => $get('disableUserIdCard')),
                        ]),
                ]),
        ]);
}



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s name.')
                    ->alignLeft(),
                TextColumn::make('idCard.rfid_number')
                    ->label('RFID Number')
                    ->sortable()
                    ->searchable()
                    ->tooltip('The user\'s RFID number.')
                    ->alignLeft(),
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
                Tables\Filters\SelectFilter::make('program')
                    ->label('Program')
                    ->options([
                        'Bachelor of Science in Information Technology' => 'Bachelor of Science in Information Technology',
                        'Bachelor of Science in Computer Science' => 'Bachelor of Science in Computer Science',
                        'Bachelor of Science in Information Systems' => 'Bachelor of Science in Information Systems',
                        'Bachelor of Library and Information Science' => 'Bachelor of Library and Information Science',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListUserInformation::route('/'),
            'create' => Pages\CreateUserInformation::route('/create'),
            'edit' => Pages\EditUserInformation::route('/{record}/edit'),
        ];
    }
}
