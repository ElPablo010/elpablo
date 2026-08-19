<?php

namespace App\Filament\Resources\Mixtapes\Pages;

use App\Filament\Resources\Mixtapes\MixtapeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMixtapes extends ManageRecords
{
    protected static string $resource = MixtapeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Mixtape toevoegen')
                ->modalSubmitActionLabel('Opslaan')
                ->createAnother(false),
        ];
    }
}
