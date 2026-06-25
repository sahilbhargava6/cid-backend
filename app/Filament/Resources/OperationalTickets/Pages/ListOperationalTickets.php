<?php

namespace App\Filament\Resources\OperationalTickets\Pages;

use App\Filament\Resources\OperationalTickets\OperationalTicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOperationalTickets extends ListRecords
{
    protected static string $resource = OperationalTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
