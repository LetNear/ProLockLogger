<?php

namespace App\Filament\Resources;

use App\Filament\Imports\UserImporter;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $title = 'User';

    protected static ?string $label = 'User';

    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
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
                                    ->helperText('The email address of the user.'),
                                // ->rules(function () {
                                //     $rules = ['required', 'email'];
                                //     if (request()->routeIs('filament.resources.users.create')) {
                                //         $rules[] = 'unique:users,email';
                                //     } else {
                                //         $userId = request()->route('record');
                                //         $rules[] = "unique:users,email,$userId";
                                //     }
                                //     return $rules;
                                // }),

                                // TODO make validations
                                Select::make('role_number')
                                    ->label('Roles')
                                    ->relationship('roles', 'name')
                                    ->preload(3)
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

                            ]),

                    ]),
                Section::make('Verification & Security')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                DateTimePicker::make('email_verified_at')
                                    ->label('Email Verified At')
                                    ->helperText('The date and time when the email was verified.'),
                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->placeholder('Enter a new password')
                                    ->dehydrated(fn($state) => filled($state))
                                    ->maxLength(255)
                                    ->helperText('The password for the user. Leave blank to keep the current password.'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->headerActions([
                ImportAction::make()
                    ->importer(UserImporter::class)
                    ->label('Import Instructors')
                    ->visible(fn() => Auth::user()->hasRole('Administrator')), // Only visible to Administrators
                // ImportAction::make()
                //     ->importer(UserImporter::class)
                //     ->label('Import Students')
                //     ->visible(fn() => Auth::user()->hasRole('Administrator')), // Only visible to Administrators
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
                
                        // Ensure fingerprintData is treated as an array
                        if (is_string($fingerprintData)) {
                            // Try to decode if it's a JSON string
                            $fingerprintData = json_decode($fingerprintData, true);
                        }
                
                        // If it's now an array of objects, we proceed
                        if (is_array($fingerprintData)) {
                            // Check if it's an associative array (single fingerprint object) or a list of arrays
                            if (isset($fingerprintData[0]) && is_array($fingerprintData[0])) {
                                // Case where we have multiple fingerprint objects
                                $fingerprintIds = array_map(function($item) {
                                    return $item['fingerprint_id'] ?? null;
                                }, $fingerprintData);
                            } else {
                                // Case where it's a single fingerprint object
                                $fingerprintIds = [$fingerprintData['fingerprint_id'] ?? null];
                            }
                
                            // Filter out any null values
                            $fingerprintIds = array_filter($fingerprintIds);
                
                            // If there are no valid fingerprint IDs, return a default message
                            if (empty($fingerprintIds)) {
                                return 'No fingerprints';
                            }
                
                            // Implode the array into a comma-separated string
                            return implode(', ', $fingerprintIds);
                        }
                
                        // If not an array or valid JSON, handle as a string or return 'None'
                        return is_string($fingerprintData) ? $fingerprintData : 'None';
                    })
                    ->searchable()
                    ->sortable()
                    ->tooltip('The fingerprint IDs of the user.'),


            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-s-pencil')
                    ->tooltip('Edit this user'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-s-trash')
                    ->tooltip('Delete this user'),
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
