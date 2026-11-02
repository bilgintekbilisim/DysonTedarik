<?php

namespace App\Filament\Resources\CorporatePages\Pages;

use App\Filament\Resources\CorporatePages\CorporatePageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCorporatePages extends ListRecords
{
    protected static string $resource = CorporatePageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
