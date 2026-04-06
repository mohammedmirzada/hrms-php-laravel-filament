<?php

namespace App\Filament\Resources\ActivityLogResource\Pages;

use App\Filament\Resources\ActivityLogResource;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Activity Details')
                    ->schema([
                        TextEntry::make('id')->label('ID'),
                        TextEntry::make('log_name')->label('Category')->badge(),
                        TextEntry::make('event')->badge(),
                        TextEntry::make('description'),
                        TextEntry::make('created_at')->label('Date')->dateTime('M d, Y H:i:s'),
                    ])
                    ->columns(3),

                Section::make('Subject')
                    ->schema([
                        TextEntry::make('subject_type')
                            ->label('Type')
                            ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-'),
                        TextEntry::make('subject_id')->label('ID'),
                        TextEntry::make('subject_name')->label('Name'),
                    ])
                    ->columns(3),

                Section::make('Performed By')
                    ->schema([
                        TextEntry::make('causer_type')
                            ->label('Type')
                            ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-'),
                        TextEntry::make('causer_id')->label('ID'),
                        TextEntry::make('causer_name')->label('Name'),
                    ])
                    ->columns(3),

                Section::make('Old Values')
                    ->schema([
                        KeyValueEntry::make('properties.old')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => ! empty($record->properties->get('old'))),

                Section::make('New Values')
                    ->schema([
                        KeyValueEntry::make('properties.attributes')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => ! empty($record->properties->get('attributes'))),

                Section::make('Extra Properties')
                    ->schema([
                        KeyValueEntry::make('properties')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => $record->properties->except(['old', 'attributes'])->isNotEmpty()),
            ]);
    }
}
