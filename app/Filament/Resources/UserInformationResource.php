<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserInformationResource\Pages;
use App\Filament\Resources\UserInformationResource\RelationManagers;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;
use App\Models\User;
class UserInformationResource extends Resource
{
    protected static ?string $model = UserInformation::class;

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    protected static ?string $title = 'User Information';

    protected static ?string $label = 'User Information';
    protected static ?string $navigationGroup = 'User';



    public static function form(Form $form): Form
    {

        $assignedUserIds = UserInformation::pluck('user_id')->toArray();

        // Fetch available users excluding assigned ones
        $availableUsers = User::whereNotIn('id', $assignedUserIds)
            ->pluck('name', 'id')
            ->toArray();
        return $form
            ->schema([
                Section::make('User Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->label('User')
                                    ->options($availableUsers)
                                    ->searchable()
                                    ->preload(10),
                                Select::make('role_id')
                                    ->relationship('role', 'name')
                                    ->label('Role')
                                    ->searchable()
                                    ->preload(10),
                                Select::make('id_card_id')
                                    ->relationship('idCard', 'rfid_number')
                                    ->label('RFID Number')
                                    ->searchable()
                                    ->preload(10),
                                Select::make('seat_id')
                                    ->relationship('seat', 'computer_number')
                                    ->label('Computer Number')
                                    ->searchable()
                                    ->preload(10),
                                Select::make('block_id')
                                    ->relationship('block', 'block')
                                    ->label('Block')
                                    ->searchable()
                                    ->preload(10),
                                Select::make('year')
                                    ->options([
                                        '1' => '1',
                                        '2' => '2',
                                        '3' => '3',
                                        '4' => '4',
                                    ])
                                    ->label('Year'),
                                Select::make('program')
                                    ->options([
                                        'Batchelor of Science in Information Technology' => 'Batchelor of Science in Information Technology',
                                        'Batchelor of Science in Computer Science' => 'Batchelor of Science in Computer Science',
                                        'Batchelor of Science in Information Systems' => 'Batchelor of Science in Information Systems',
                                        'Batchelor of Library and Information Science' => 'Batchelor of Library and Information Science',
                                    ])
                                    ->label('Program'),
                            ]),
                    ]),
                Section::make('Personal Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('first_name')
                                    ->required()
                                    ->label('First Name')
                                    ->maxLength(255),
                                TextInput::make('middle_name')
                                    ->maxLength(255)
                                    ->label('Middle Name')
                                    ->default(null),
                                TextInput::make('last_name')
                                    ->required()
                                    ->label('Last Name')
                                    ->maxLength(255),
                                TextInput::make('suffix')
                                    ->maxLength(255)
                                    ->default(null),
                                DatePicker::make('date_of_birth')
                                    ->required()
                                    ->label('Date of Birth'),
                                Select::make('gender')
                                    ->options([
                                        'Male' => 'Male',
                                        'Female' => 'Female',
                                        'Other' => 'Other',
                                    ])
                                    ->label('Gender'),
                            ]),
                    ]),
                Section::make('Contact Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('contact_number')
                                    ->required()
                                    ->label('Contact Number'),
                                // ->tel()
                                // ->telRegex('/^(\+63|0)[1-9][0-9]{9}$/')
                                // ->maxLength(15),
                                TextInput::make('complete_address')
                                    ->required()
                                    ->label('Complete Address')
                                    ->maxLength(255),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->label('User ID')
                    ->numeric()
                    ->sortable()
                    ->searchable()
                    ->alignRight(),
                Tables\Columns\TextColumn::make('idCard.rfid_number')
                    ->label('RFID Number')
                    ->numeric()
                    ->sortable()
                    ->searchable()
                    ->alignRight(),
                Tables\Columns\TextColumn::make('role.name')
                    ->label('Role')
                    ->sortable()
                    ->searchable()
                    ->alignRight(),
                Tables\Columns\TextColumn::make('seat_id')
                    ->label('Computer Number')
                    ->numeric()
                    ->sortable()
                    ->searchable()
                    ->alignRight(),
                Tables\Columns\TextColumn::make('block.block')
                    ->label('Block')
                    ->sortable()
                    ->searchable()
                    ->alignRight(),
                Tables\Columns\TextColumn::make('year')
                    ->label('Year')
                    ->sortable()
                    ->numeric()
                    ->searchable()
                    ->alignRight(),
                Tables\Columns\TextColumn::make('program')
                    ->label('Program')
                    ->sortable()
                    ->searchable()
                    ->alignLeft(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable()
                    ->alignLeft(),
                Tables\Columns\TextColumn::make('middle_name')
                    ->label('Middle Name')
                    ->searchable()
                    ->alignLeft(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable()
                    ->alignLeft(),
                Tables\Columns\TextColumn::make('suffix')
                    ->label('Suffix')
                    ->searchable()
                    ->alignLeft(),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->label('Date of Birth')
                    ->dateTime('M d, Y') // Custom date format
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('Gender')
                    ->searchable()
                    ->sortable()
                    ->alignLeft(),
                Tables\Columns\TextColumn::make('contact_number')
                    ->label('Contact Number')
                    ->searchable()
                    ->alignLeft(),
                Tables\Columns\TextColumn::make('complete_address')
                    ->label('Complete Address')
                    ->searchable()
                    ->alignLeft(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M d, Y h:i A') // Custom date-time format
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime('M d, Y h:i A') // Custom date-time format
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
