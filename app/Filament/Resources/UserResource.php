<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;

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
                                Select::make('role_number')
                                    ->label('Roles')
                                    ->relationship('roles', 'name')
                                    ->preload(3),
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
                                    ->required(fn(string $context): bool => $context === 'create')
                                    ->maxLength(255)
                                    ->helperText('The password for the user. Leave blank to keep the current password.'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('The date and time when the user was last updated.'),
                Tables\Columns\TextColumn::make('roles')
                    ->label('Roles')
                    ->getStateUsing(fn($record) => $record->roles->pluck('name')->join(', '))
                    ->sortable()
                    ->tooltip('The roles assigned to the user.'),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('The date and time when the user was soft-deleted.'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-s-pencil')
                    ->tooltip('Edit this user'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-s-trash')
                    ->tooltip('Delete this user'),
                RestoreAction::make()
                    ->icon('heroicon-s-reply')
                    ->tooltip('Restore this user')
                    ->visible(fn($record) => $record->trashed()),
                ForceDeleteAction::make()
                    ->icon('heroicon-s-x-circle')
                    ->tooltip('Permanently delete this user')
                    ->visible(fn($record) => $record->trashed()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-s-trash')
                        ->tooltip('Delete selected users'),
                        RestoreBulkAction::make()
                        ->icon('heroicon-s-reply')
                        ->tooltip('Restore selected users')
                        ->visible(fn($records) => $records && $records->contains(fn($record) => $record->trashed())),
                    ForceDeleteBulkAction::make()
                        ->icon('heroicon-s-x-circle')
                        ->tooltip('Permanently delete selected users')
                        ->visible(fn($records) => $records && $records->contains(fn($record) => $record->trashed())),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
