<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Concerns\ManagesEventFormData;
use App\Filament\Resources\Events\EventResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    use ManagesEventFormData;

    protected static string $resource = EventResource::class;

    public function getTitle(): string
    {
        return 'Event toevoegen';
    }

    public function getBreadcrumb(): string
    {
        return 'Toevoegen';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extractEventFormData($data, null);
    }

    protected function afterCreate(): void
    {
        $this->persistEventTranslations($this->getRecord());
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Opslaan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Annuleren');
    }

    public function canCreateAnother(): bool
    {
        return false;
    }
}
