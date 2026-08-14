<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Concerns\ManagesEventFormData;
use App\Filament\Resources\Events\EventResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditEvent extends EditRecord
{
    use ManagesEventFormData;

    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillEventFormData($data, $this->getRecord());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractEventFormData($data, $this->getRecord());
    }

    protected function afterSave(): void
    {
        $this->persistEventTranslations($this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Opslaan')
                ->icon(Heroicon::OutlinedCheck)
                ->color('primary')
                ->keyBindings(['mod+s'])
                ->action(fn () => $this->save()),
            Action::make('view')
                ->icon(Heroicon::OutlinedEye)
                ->hiddenLabel()
                ->tooltip('Bekijk op site')
                ->url(fn (): string => $this->getRecord()->publicUrl()),
            DeleteAction::make(),
        ];
    }
}
