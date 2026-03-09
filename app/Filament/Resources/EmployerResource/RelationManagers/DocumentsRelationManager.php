<?php

namespace App\Filament\Resources\EmployerResource\RelationManagers;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::DocumentText;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('document_type')
                    ->native(false)
                    ->options([
                        'ID Card' => 'ID Card',
                        'Passport' => 'Passport',
                        'Driver License' => 'Driver License',
                        'Work Permit' => 'Work Permit',
                        'Visa' => 'Visa',
                        'Contract' => 'Contract',
                        'Certificate' => 'Certificate',
                        'Degree' => 'Degree',
                        'CV' => 'CV',
                        'Other' => 'Other',
                    ])
                    ->required(),
                FileUpload::make('file_path')
                    ->label('File')
                    ->directory('documents')
                    ->disk('public')
                    ->required()
                    ->columnSpanFull(),
                DatePicker::make('expiry_date')
                    ->native(false)
                    ->label('Expiry Date'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : ($record->isExpiringSoon() ? 'warning' : null)),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
