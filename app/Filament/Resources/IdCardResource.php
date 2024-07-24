<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IdCardResource\Pages;
use App\Filament\Resources\IdCardResource\RelationManagers;
use App\Filament\Resources\IdCardResource\RelationManagers\UserInformationRelationManager;
use App\Models\IdCard;
use App\Models\User;
use App\Models\UserInformation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class IdCardResource extends Resource
{
    protected static ?string $model = IdCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('image_id')
                    
                    ->default(null),
                Forms\Components\TextInput::make('rfid_number')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('image_id')
                    ->numeric()
                    ->sortable(),
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
            UserInformationRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIdCards::route('/'),
            'create' => Pages\CreateIdCard::route('/create'),
            'edit' => Pages\EditIdCard::route('/{record}/edit'),
        ];
    }
}
