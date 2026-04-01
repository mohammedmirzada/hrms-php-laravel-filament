<?php

namespace App\Filament\Resources;

use App\Enums\DocumentType;
use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use App\Models\Employer;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::DocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Employees';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('employer_id')
                    ->native(false)
                    ->label('Employee')
                    ->relationship('employer', 'full_name')
                    ->getOptionLabelFromRecordUsing(fn (Employer $record) => $record->getTranslation('full_name', 'en'))
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('document_type')
                    ->native(false)
                    ->options(DocumentType::labels())
                    ->required(),
                FileUpload::make('file_path')
                    ->label('File')
                    ->directory('documents')
                    ->disk('public')
                    ->maxSize(5120)
                    ->openable()
                    ->previewable()
                    ->required()
                    ->columnSpanFull(),
                DatePicker::make('expiry_date')
                    ->native(false)
                    ->label('Expiry Date'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('employer.full_name')
                    ->label('Employee')
                    ->formatStateUsing(fn ($record) => $record->employer?->getTranslation('full_name', 'en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('document_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('file_path')
                    ->label('File')
                    ->formatStateUsing(fn ($state) => $state ? basename($state) : '—')
                    ->url(fn ($record) => $record->file_path ? asset('storage/' . $record->file_path) : null)
                    ->openUrlInNewTab()
                    ->icon(Heroicon::ArrowTopRightOnSquare)
                    ->color('primary'),
                TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : ($record->isExpiringSoon() ? 'warning' : null)),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_by')
                    ->label('Created By')
                    ->formatStateUsing(fn ($record) => $record->createdBy?->name)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->options(DocumentType::labels())
                    ->native(false)
                    ->multiple(true)
                    ->searchable(),
                SelectFilter::make('employer_id')
                    ->label('Employee')
                    ->relationship('employer', 'full_name')
                    ->native(false)
                    ->preload()
                    ->searchable(),
                Filter::make('expiry_date')
                    ->schema([
                        DatePicker::make('expiry_date_from')
                            ->native(false)
                            ->label('Expiry Date From'),
                        DatePicker::make('expiry_date_to')
                            ->native(false)
                            ->label('Expiry Date To'),
                    ])
                    ->query(function ($query, $data) {
                        if ($data['expiry_date_from']) {
                            $query->whereDate('expiry_date', '>=', $data['expiry_date_from']);
                        }
                        if ($data['expiry_date_to']) {
                            $query->whereDate('expiry_date', '<=', $data['expiry_date_to']);
                        }
                    }),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
