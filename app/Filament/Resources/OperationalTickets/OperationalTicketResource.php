<?php

namespace App\Filament\Resources\OperationalTickets;

use App\Filament\Resources\OperationalTickets\Pages\CreateOperationalTicket;
use App\Filament\Resources\OperationalTickets\Pages\EditOperationalTicket;
use App\Filament\Resources\OperationalTickets\Pages\ListOperationalTickets;
use App\Filament\Resources\OperationalTickets\Schemas\OperationalTicketForm;
use App\Filament\Resources\OperationalTickets\Tables\OperationalTicketsTable;
use App\Models\OperationalTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OperationalTicketResource extends Resource
{
    protected static ?string $model = OperationalTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return OperationalTicketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OperationalTicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOperationalTickets::route('/'),
            'create' => CreateOperationalTicket::route('/create'),
            'edit' => EditOperationalTicket::route('/{record}/edit'),
        ];
    }
}
