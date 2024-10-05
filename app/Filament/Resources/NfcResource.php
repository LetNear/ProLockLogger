<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NfcResource\Pages;
use App\Filament\Resources\NfcResource\RelationManagers;
use App\Models\Nfc;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class NfcResource extends Resource
{
    protected static ?string $model = Nfc::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $title = 'NFC Card';

    protected static ?string $label = 'NFC Card';

    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('rfid_number')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->poll('2s')
            ->columns([
                Tables\Columns\TextColumn::make('rfid_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                
            ])
            ->bulkActions([
              
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
            'index' => Pages\ListNfcs::route('/'),
            'create' => Pages\CreateNfc::route('/create'),
            'edit' => Pages\EditNfc::route('/{record}/edit'),
        ];
    }
}
