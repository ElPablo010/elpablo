<?php

namespace App\Filament\Pages;

use App\Filament\Schemas\Components\PageLinkField;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\Translation\ClaudeTranslator;
use App\Services\Translation\TranslationException;
use App\Support\Locale;
use App\Support\Url;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageMenus extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.manage-menus';

    /**
     * Vaste menu's. `children` = of het Hoofdmenu submenu's mag hebben.
     * Volgorde = volgorde in de admin-UI.
     */
    public const MENUS = [
        'main' => ['name' => 'Hoofdmenu', 'children' => true, 'showTitle' => false],
        'footer_1' => ['name' => 'Footer menu 1', 'children' => false, 'showTitle' => true],
        'footer_2' => ['name' => 'Footer menu 2', 'children' => false, 'showTitle' => true],
        'footer_3' => ['name' => 'Footer menu 3', 'children' => false, 'showTitle' => true],
    ];

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return 'Menu\'s';
    }

    public function getTitle(): string
    {
        return 'Website menu\'s';
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    public function menuForm(Schema $schema): Schema
    {
        return $schema
            ->components(
                collect(self::MENUS)
                    ->map(fn (array $config, string $location) => $this->menuSection($location, $config))
                    ->values()
                    ->all(),
            )
            ->statePath('data');
    }

    protected function menuSection(string $location, array $config): Section
    {
        $components = [];

        if ($config['showTitle']) {
            $components[] = Grid::make(['default' => 1, 'md' => 3])->schema([
                TextInput::make("{$location}.title")
                    ->label('Titel')
                    ->maxLength(64)
                    ->helperText('Wordt als kop boven deze footerkolom getoond (bv. "Ontdekken").'),
                TextInput::make("{$location}.title_en")
                    ->label('Titel (EN)')
                    ->maxLength(64),
                TextInput::make("{$location}.title_es")
                    ->label('Titel (ES)')
                    ->maxLength(64),
            ]);
        }

        $components[] = Repeater::make("{$location}.items")
            ->label('Menu-items')
            ->hiddenLabel()
            ->addActionLabel('Item toevoegen')
            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
            ->collapsible()
            ->collapsed()
            ->reorderable()
            ->defaultItems(0)
            ->schema($this->itemSchema($config['children']));

        return Section::make($config['name'])
            ->collapsible()
            ->collapsed()
            ->schema($components);
    }

    /**
     * @return array<int, mixed>
     */
    protected function itemSchema(bool $withChildren): array
    {
        $schema = [
            $this->labelFields(),
            PageLinkField::make(false),
            Toggle::make('target_blank')
                ->label('Openen in nieuw tabblad')
                ->inline(false),
        ];

        if ($withChildren) {
            $schema[] = Repeater::make('children')
                ->label('Submenu-items')
                ->addActionLabel('Submenu-item toevoegen')
                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                ->collapsible()
                ->collapsed()
                ->reorderable()
                ->defaultItems(0)
                ->schema([
                    $this->labelFields(),
                    PageLinkField::make(false),
                    Toggle::make('target_blank')
                        ->label('Openen in nieuw tabblad')
                        ->inline(false),
                ]);
        }

        return $schema;
    }

    /**
     * Label per taal. EN/ES leeg = de site valt terug op lang/{locale}.json en
     * daarna op het NL-label (zie MenuItem::labelFor()).
     */
    protected function labelFields(): Grid
    {
        return Grid::make(['default' => 1, 'md' => 3])->schema([
            TextInput::make('label')
                ->label('Label')
                ->required()
                ->maxLength(255),
            TextInput::make('label_en')
                ->label('Label (EN)')
                ->maxLength(255),
            TextInput::make('label_es')
                ->label('Label (ES)')
                ->maxLength(255),
        ]);
    }

    protected function fillForm(): void
    {
        $data = [];

        foreach (self::MENUS as $location => $config) {
            $menu = Menu::firstOrCreate(
                ['location' => $location],
                ['name' => $config['name']],
            );
            $menu->load(['items.children']);

            $data[$location] = [
                'title' => $menu->title,
                'title_en' => $menu->title_en,
                'title_es' => $menu->title_es,
                'items' => $menu->items
                    ->map(fn (MenuItem $item) => $this->itemToState($item, $config['children']))
                    ->all(),
            ];
        }

        $this->menuForm->fill($data);
    }

    /**
     * @return array<string, mixed>
     */
    protected function itemToState(MenuItem $item, bool $withChildren): array
    {
        $state = [
            'label' => $item->label,
            'label_en' => $item->label_en,
            'label_es' => $item->label_es,
            'link_type' => $item->page_id ? 'page' : 'url',
            'page_id' => $item->page_id,
            'href' => $item->page_id ? $item->resolvedHref() : $item->url,
            'target_blank' => $item->target_blank,
        ];

        if ($withChildren) {
            $state['children'] = $item->children
                ->map(fn (MenuItem $child) => $this->itemToState($child, false))
                ->all();
        }

        return $state;
    }

    public function save(): void
    {
        $state = $this->menuForm->getState();

        foreach (self::MENUS as $location => $config) {
            $menu = Menu::firstOrCreate(
                ['location' => $location],
                ['name' => $config['name']],
            );

            $menu->update([
                'title' => $state[$location]['title'] ?? null,
                'title_en' => $state[$location]['title_en'] ?? null,
                'title_es' => $state[$location]['title_es'] ?? null,
            ]);

            // Delete-and-recreate: eenvoudiger en betrouwbaarder dan diffen.
            MenuItem::where('menu_id', $menu->id)->delete();

            foreach ($state[$location]['items'] ?? [] as $position => $item) {
                $parent = $this->createItem($menu->id, null, $position, $item);

                if ($config['children']) {
                    foreach ($item['children'] ?? [] as $childPosition => $child) {
                        $this->createItem($menu->id, $parent->id, $childPosition, $child);
                    }
                }
            }
        }

        Notification::make()
            ->title('Menu\'s bijgewerkt.')
            ->success()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function createItem(int $menuId, ?int $parentId, int $position, array $item): MenuItem
    {
        $isPage = ($item['link_type'] ?? 'page') === 'page';

        return MenuItem::create([
            'menu_id' => $menuId,
            'parent_id' => $parentId,
            'label' => $item['label'],
            'label_en' => $item['label_en'] ?? null,
            'label_es' => $item['label_es'] ?? null,
            'page_id' => $isPage ? ($item['page_id'] ?? null) : null,
            'url' => $isPage ? null : Url::normalize($item['href'] ?? null),
            'position' => $position,
            'target_blank' => $item['target_blank'] ?? false,
        ]);
    }

    /**
     * Vertaalt alle NL-labels en -titels naar elke andere taal en zet het
     * resultaat in het formulier — niet in de database. Zo zie je de
     * vertaling eerst en bewaar je ze zelf met Opslaan. Bestaande EN/ES-waarden
     * worden overschreven (zelfde gedrag als "Vertalen met AI" op pagina's).
     */
    public function translateWithAi(): void
    {
        $data = $this->data;
        $texts = [];

        // Sleutel = het pad in $data waar de vertaling naast komt te staan
        // (label → label_en/label_es), zodat terugschrijven een data_set() is.
        foreach (self::MENUS as $location => $config) {
            if (filled($data[$location]['title'] ?? null)) {
                $texts["{$location}.title"] = $data[$location]['title'];
            }

            foreach ($data[$location]['items'] ?? [] as $key => $item) {
                if (filled($item['label'] ?? null)) {
                    $texts["{$location}.items.{$key}.label"] = $item['label'];
                }

                foreach ($item['children'] ?? [] as $childKey => $child) {
                    if (filled($child['label'] ?? null)) {
                        $texts["{$location}.items.{$key}.children.{$childKey}.label"] = $child['label'];
                    }
                }
            }
        }

        if ($texts === []) {
            Notification::make()->title('Er staan nog geen menu-items om te vertalen.')->warning()->send();

            return;
        }

        try {
            foreach (Locale::supported() as $locale) {
                if ($locale === Locale::DEFAULT) {
                    continue;
                }

                $translated = app(ClaudeTranslator::class)->translate(
                    $texts,
                    Locale::DEFAULT,
                    $locale,
                    context: 'Website navigation labels and footer column headings for an Urban Latin DJ. Keep them short, like menu items.',
                );

                foreach ($translated as $path => $text) {
                    data_set($data, "{$path}_{$locale}", $text);
                }
            }
        } catch (TranslationException $exception) {
            Notification::make()->title('Vertalen mislukt')->body($exception->getMessage())->danger()->send();

            return;
        }

        $this->data = $data;

        Notification::make()
            ->title('Menu\'s vertaald.')
            ->body('Controleer de EN/ES-velden en klik op Opslaan.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('translate')
                ->label('Vertalen met AI')
                ->icon(Heroicon::OutlinedLanguage)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Menu\'s vertalen met AI')
                ->modalDescription('Alle labels en titels worden naar het Engels en Spaans vertaald. Bestaande vertalingen worden overschreven. Je controleert het resultaat en slaat daarna zelf op.')
                ->modalSubmitActionLabel('Vertalen')
                ->action(fn () => $this->translateWithAi()),
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
                ->url('/'),
        ];
    }
}
