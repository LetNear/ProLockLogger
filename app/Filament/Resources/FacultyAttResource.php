<?php

namespace App\Filament\Resources;

use App\Exports\FacultyExport;
use App\Exports\LogBookExport;
use App\Filament\Resources\FacultyAttResource\Pages;
use App\Filament\Resources\FacultyAttResource\RelationManagers;
use App\Models\FacultyAtt;
use App\Models\RecentLogs;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

class FacultyAttResource extends Resource
{
    protected static ?string $model = RecentLogs::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $title = 'Faculty Logs';

    protected static ?string $label = 'Faculty Logs';

    protected static ?string $navigationGroup = 'Laboratory Management';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->poll('2s')
        ->headerActions([
            Action::make('export')
                ->label('Export Faculty Logs')
                ->action(function () {
                    // Trigger the export using Maatwebsite Excel and your custom exporter
                    return Excel::download(new FacultyExport, 'logbookfaculty.xlsx');
                }),
                // Action::make('export')
                // ->label('Export Faculty Log Book')
                // ->action(function () {
                //     // Trigger the export using Maatwebsite Excel and your custom exporter
                //     return Excel::download(new FacultyExport, 'logbookfaculty.xlsx');
                // }),
        ])
            ->columns([
                Tables\Columns\TextColumn::make('user_name')->label('User Name'),
               
                Tables\Columns\TextColumn::make('time_in')->label('Time In'),
                Tables\Columns\TextColumn::make('time_out')->label('Time Out'),
                
            ])
            ->filters([
                //
            ])
            
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        // Modify the query to filter by role_id = 2 (Faculty)
        return parent::getEloquentQuery()->where('role_id', 2);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacultyAtts::route('/'),
            'create' => Pages\CreateFacultyAtt::route('/create'),
            'edit' => Pages\EditFacultyAtt::route('/{record}/edit'),
        ];
    }
}
