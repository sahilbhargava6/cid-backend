<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->label('Client'),
                Select::make('operational_ticket_id')
                    ->relationship('ticket', 'id')
                    ->label('Associated Ticket ID (Optional)'),
                TextInput::make('name')
                    ->required()
                    ->label('Document Name'),
                FileUpload::make('file_path')
                    ->disk('local') // uses local disk for local dev; easily switched to s3
                    ->directory('documents')
                    ->required()
                    ->preserveFilenames()
                    ->label('File Upload'),
                TextInput::make('file_type')
                    ->label('File Extension / Type (e.g. pdf, csv)'),
            ]);
    }
}
