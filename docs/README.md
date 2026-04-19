# GridControl — Kompletní dokumentace

## Obsah

1. [Přehled](#přehled)
2. [Konfigurace gridu](#konfigurace-gridu)
3. [Typy sloupců](#typy-sloupců)
4. [Filtry](#filtry)
5. [Řazení a vyhledávání](#řazení-a-vyhledávání)
6. [Stránkování a počet záznamů](#stránkování-a-počet-záznamů)
7. [Akce na řádcích](#akce-na-řádcích)
8. [Hromadné akce](#hromadné-akce)
9. [Patička — agregáty](#patička--agregáty)
10. [Změna velikosti sloupců](#změna-velikosti-sloupců)
11. [Přeřazování sloupců](#přeřazování-sloupců)
12. [Výběr viditelných sloupců](#výběr-viditelných-sloupců)
13. [Předvolby (Presets)](#předvolby-presets)
14. [Témata a stylování](#témata-a-stylování)
15. [Texty a lokalizace](#texty-a-lokalizace)
16. [AJAX (Naja)](#ajax-naja)
17. [Dialogové okna (Capify)](#dialogové-okna-capify)
18. [JavaScript API](#javascript-api)
19. [Řádkový callback](#řádkový-callback)
20. [Tabulkový layout](#tabulkový-layout)
21. [Práce s JOINy a related tabulkami](#práce-s-joiny-a-related-tabulkami)

---

## Přehled

`GridControl` je Nette UI Control pro zobrazení tabulkových dat z `Nette\Database\Table\Selection`. Podporuje:

- Řazení sloupců (trojstavové: ↑ ↓ žádné)
- Globální fulltextové vyhledávání
- Filtrování po sloupcích (text, select, multiselect, datum, číslo, bool, AJAX dropdown)
- Stránkování s volbou počtu záznamů na stránku
- Akce na řádcích (primární tlačítko + dropdown menu)
- Hromadné akce se sticky action barem a checkboxy
- Agregáty v patičce (sum, avg, count, min, max)
- Změna velikosti sloupců tažením (resize)
- Přeřazování sloupců tažením (drag & drop reorder)
- Výběr viditelných sloupců (column toggle)
- Předvolby — uložení a obnovení kompletního stavu gridu (presets)
- Bootstrap 5, Tailwind CSS nebo vlastní téma
- AJAX s Naja — snippet redraw, URL history, loading stav
- Volitelná integrace s Capify pro stylizované dialogy

---

## Konfigurace gridu

### Základní nastavení

```php
$grid = new GridControl();

$grid->setDataSource($this->db->table('user'))  // Nette Selection
     ->setItemsPerPage(25)                        // výchozí 20
     ->setDefaultSort('created_at', 'desc')       // výchozí řazení
     ->setTheme(GridControl::BOOTSTRAP);          // Bootstrap | Tailwind | DEFAULT
```

### Zobrazení prvků

```php
$grid->showToolbar(false);       // skryje celý toolbar (vyhledávání + tlačítka)
$grid->showSearch(false);        // skryje jen vyhledávací pole, toolbar zůstane
$grid->showPagination(false);    // skryje stránkování
```

### Interaktivní funkce

```php
$grid->useAjax();                // AJAX režim (vyžaduje Naja)
$grid->setResizable();           // resize sloupců tažením
$grid->setReorderable();         // přeřazování sloupců drag & drop
$grid->showColumnToggle();       // dropdown pro výběr viditelných sloupců
$grid->showPresets();             // UI pro předvolby
```

### Table layout

```php
$grid->setTableLayout('auto');   // výchozí — šířka dle obsahu
$grid->setTableLayout('fixed');  // fixní — text-overflow: ellipsis
```

Při `fixed` layoutu je vhodné nastavit šířky sloupcům pomocí `->width()`.

---

## Typy sloupců

Každý sloupec se přidává metodou `add*Column()`. Všechny metody vracejí instanci sloupce pro fluent konfiguraci.

### TextColumn

```php
$grid->addTextColumn('name', 'Jméno')
     ->sortable()
     ->searchable()
     ->filterable()
     ->truncate(50);           // ořízne text na 50 znaků s "…"
```

```php
addTextColumn(
    string  $key,              // klíč (= URL parametr + CSS třída buňky)
    string  $label,            // záhlaví sloupce
    ?string $dbColumn = null   // název DB sloupce (výchozí = $key)
): TextColumn
```

### DateColumn

```php
$grid->addDateColumn('created_at', 'Vytvořeno', 'd.m.Y')
     ->sortable()
     ->emptyValue('—')
     ->filterableDateRange()
     ->footerMax();            // zobrazí max datum ve formátu sloupce
```

Agregáty (footerMin, footerMax) respektují nastavený formát data.

### NumberColumn

```php
$grid->addNumberColumn('price', 'Cena', 2)   // 2 desetinná místa
     ->prefix('Kč ')
     ->suffix(' bez DPH')
     ->sortable()
     ->filterableNumberRange()
     ->footerSum();            // zobrazí součet s formátováním (prefix, suffix, decimals)
```

### BoolColumn

```php
$grid->addBoolColumn('active', 'Aktivní')
     ->trueLabel('✔ Ano', 'text-success fw-semibold')
     ->falseLabel('✘ Ne',  'text-muted')
     ->filterableBool();
```

### BadgeColumn

```php
$grid->addBadgeColumn('status', 'Stav', [
    'new'      => ['label' => 'Nový',      'color' => 'primary'],
    'approved' => ['label' => 'Schválený', 'color' => 'success'],
    'rejected' => ['label' => 'Zamítnutý', 'color' => 'danger'],
])
->sortable()
->filterableMultiSelect([
    'new'      => 'Nový',
    'approved' => 'Schválený',
    'rejected' => 'Zamítnutý',
]);
```

### ActionColumn

Viz sekci [Akce na řádcích](#akce-na-řádcích).

### Společné metody všech sloupců

| Metoda | Popis |
|--------|-------|
| `->sortable()` | Umožní řazení kliknutím na záhlaví |
| `->searchable()` | Zahrne sloupec do globálního vyhledávání |
| `->width('120px')` | Pevná šířka sloupce |
| `->class('text-end')` | CSS třída buněk těla |
| `->headerClass('text-end')` | CSS třída buňky záhlaví |
| `->renderer(fn($row) => '...')` | Vlastní render buňky |
| `->filterDbColumn('tabulka.sloupec')` | Přepíše DB sloupec použitý ve WHERE |
| `->hideable(false)` | Vyloučí sloupec z column toggle (default `true`, ActionColumn `false`) |
| `->footerSum()` | Součet v patičce |
| `->footerAvg()` | Průměr v patičce |
| `->footerCount()` | Počet v patičce |
| `->footerMin()` | Minimum v patičce |
| `->footerMax()` | Maximum v patičce |

### Vlastní renderer buňky

Renderer dostane `ActiveRow|array $row` a musí vrátit HTML string.

```php
$grid->addTextColumn('fullName', 'Celé jméno', '')
     ->renderer(fn($row) =>
         htmlspecialchars($row['first_name'] . ' ' . $row['last_name'])
     );
```

---

## Filtry

Filtry se konfigurují na sloupcích. Zobrazují se jako druhý řádek záhlaví tabulky.

| Metoda | Typ | WHERE |
|--------|-----|-------|
| `->filterable()` | Textový input | `LIKE %value%` |
| `->filterableSelect([...])` | Statický select | `= value` |
| `->filterableMultiSelect([...])` | Checkbox dropdown | `IN (?, ?, …)` |
| `->filterableSelectAjax(Selection, ...)` | AJAX dropdown (DropDownList) | `= value` |
| `->filterableDateRange()` | Dva date inputy (Od / Do) | `>= from AND <= to` |
| `->filterableNumberRange()` | Dva number inputy (Min / Max) | `>= min AND <= max` |
| `->filterableBool()` | Select: Vše / Ano / Ne | `= 1` nebo `= 0` |

### AJAX select filtr

```php
$grid->addTextColumn('createdBy', 'Vytvořil', 'creator.username')
     ->filterableSelectAjax(
         $this->db->table('user'),
         'id', 'username',
         minChars: 0
     );
```

### Přepis filtrovacího DB sloupce

```php
$grid->addTextColumn('statusId', 'Stav', 'status.name')
     ->filterDbColumn('u.status_id')
     ->filterableMultiSelect($statuses);
```

---

## Řazení a vyhledávání

### Řazení

Kliknutím na záhlaví se cykluje: ↑ → ↓ → žádné.

```php
$grid->setDefaultSort('created_at', 'desc');
```

### Globální vyhledávání

```php
$grid->addTextColumn('name',  'Jméno')->searchable();
$grid->addTextColumn('email', 'E-mail')->searchable();
// WHERE (name LIKE ? OR email LIKE ?)
```

---

## Stránkování a počet záznamů

```php
$grid->setItemsPerPage(25);
$grid->setPerPageOptions([10, 25, 50, 100, 250]);
```

---

## Akce na řádcích

```php
$grid->addActionColumn('actions', '')
     ->width('130px')
     ->addPrimaryAction(
         '', fn($row) => $this->link('detail', $row['id']),
         'bi bi-eye', title: 'Detail'
     )
     ->addAction('Upravit', fn($row) => $this->link('edit', $row['id']), 'bi bi-pencil')
     ->addAction(
         'Smazat',
         fn($row) => $this->link('delete!', $row['id']),
         'bi bi-trash', 'text-danger', 'Opravdu smazat?'
     );
```

ActionColumn má `hideable(false)` — nelze ho skrýt přes column toggle. Lze přeřazovat drag & drop.

Dropdown menu akčního sloupce se při otevření teleportuje mimo `<table>` (do `.phx-grid-wrapper`) s `position: fixed`, aby nebylo oříznuté overflow kontejnerem. Menu se automaticky zarovná k pravému nebo levému okraji tlačítka (podle pozice na obrazovce) a flipne nahoru, pokud by přetékalo spodní okraj viewportu.

### Parametry `addAction`

```php
addAction(
    string   $label,
    Closure  $linkCallback,   // fn($row): string
    string   $icon = '',      // CSS třída ikony
    string   $extraClass = '',
    ?string  $confirm = null, // confirm dialog
    bool     $ajax = false,
    ?string  $title = null,
)
```

---

## Hromadné akce

Hromadné akce se zobrazují ve sticky action baru nad tabulkou. Bar je vždy viditelný, tlačítka jsou `disabled` dokud uživatel nezaškrtne alespoň jeden řádek.

```php
$grid->setPrimaryKey('id')
     ->addBulkAction(
         'export',
         'Exportovat',
         fn(array $ids) => $this->handleExport($ids),
         icon: 'bi bi-download',
     )
     ->addBulkAction(
         'delete',
         'Smazat',
         fn(array $ids) => $this->handleBulkDelete($ids),
         icon: 'bi bi-trash',
         extraClass: 'text-danger',
         confirm: 'Opravdu smazat vybrané záznamy?',
     );
```

### Parametry `addBulkAction`

```php
addBulkAction(
    string   $id,             // unikátní identifikátor
    string   $caption,        // text tlačítka
    Closure  $callback,       // fn(array $ids): void
    string   $icon = '',      // CSS třída ikony (např. 'bi bi-trash')
    string   $extraClass = '', // extra CSS třídy na tlačítko
    ?string  $confirm = null, // confirm dialog (podporuje Capify)
)
```

### Sticky bar

Bar má `position: sticky; top: 0` — přilepí se k hornímu okraji viewportu při scrollu, ale nepřekročí hranice `.phx-grid-wrapper`. Text počtu vybraných řádků je lokalizovatelný přes text klíč `bulkSelected`.

### Checkboxy bez bulk akcí

```php
$grid->setPrimaryKey('id', showCheckboxes: true);
```

---

## Patička — agregáty

Agregáty se počítají z celé filtrované sady (ne jen aktuální stránky):

```php
$grid->addNumberColumn('price', 'Cena', 2)->footerSum();
$grid->addDateColumn('created_at', 'Datum', 'd.m.Y')->footerMax();
```

NumberColumn a DateColumn formátují agregáty ve svém formátu (prefix/suffix/decimals, date format). Ostatní sloupce zobrazí číselnou hodnotu.

---

## Změna velikosti sloupců

```php
$grid->setResizable();
```

Na pravé straně záhlaví každého sloupce se vykreslí neviditelná 6px oblast. Po najetí myši se kurzor změní na `col-resize`. Tažením se mění šířka sloupce v reálném čase. Při prvním tažení se tabulka automaticky přepne na `table-layout: fixed`.

Šířky se ukládají do `localStorage` (pokud nejsou aktivní předvolby — viz [Předvolby](#předvolby-presets)).

---

## Přeřazování sloupců

```php
$grid->setReorderable();
```

Záhlaví sloupců se stanou draggable (HTML5 Drag & Drop). Při přetažení se zobrazí modrý indikátor pozice. Po puštění se přeřadí buňky ve všech řádcích (header, filtrový řádek, tělo, patička) i `<col>` elementy.

Pořadí se ukládá do `localStorage` (pokud nejsou aktivní předvolby).

---

## Výběr viditelných sloupců

```php
$grid->showColumnToggle();
```

V toolbaru se zobrazí dropdown s checkboxy. Odškrtnutím se sloupec skryje (CSS třída `phx-grid-col-hidden`). Zobrazují se pouze sloupce s `hideable(true)` — ActionColumn je výchozí `hideable(false)`.

Stav se ukládá do `localStorage` (pokud nejsou aktivní předvolby).

### Vyloučení sloupce z column toggle

```php
$grid->addTextColumn('id', 'ID')->hideable(false);
```

---

## Předvolby (Presets)

Předvolby ukládají kompletní nastavení gridu na server (typicky do databáze): pořadí sloupců, šířky, viditelnost, řazení, filtry, vyhledávání a počet záznamů na stránku.

### Základní nastavení

```php
$grid->onPresetsLoad(fn() => $this->presetRepo->findByUser($userId));
```

`onPresetsLoad()` přijímá callback `fn(): GridPreset[]`. Volá se lazy před každým renderem — po uložení/smazání presetu vrátí čerstvá data. Zaregistrování callbacku automaticky zapíná `showPresets()`.

### Toolbar UI

V toolbaru se zobrazí select s předvolbami, tlačítko Uložit a split dropdown s akcemi: Uložit jako, Přejmenovat, Nastavit jako výchozí, Smazat. Přejmenovat / Nastavit jako výchozí / Smazat jsou `disabled` bez vybraného presetu. Uložit bez presetu se chová jako Uložit jako (zeptá se na název).

### Callbacky

```php
$grid->onPresetSave(function (string $presetId, array $config) {
    $this->presetRepo->update($presetId, $config);
});

// Musí vrátit ID nového presetu
$grid->onPresetSaveAs(function (string $name, array $config): string|int {
    $preset = $this->presetRepo->create($userId, $name, $config);
    return $preset->id;
});

$grid->onPresetRename(function (string $presetId, string $newName) {
    $this->presetRepo->rename($presetId, $newName);
});

$grid->onPresetDefault(function (string $presetId) {
    $this->presetRepo->setDefault($userId, $presetId);
});

$grid->onPresetDelete(function (string $presetId) {
    $this->presetRepo->delete($presetId);
});
```

### Struktura konfigurace (GridPreset::$config)

```php
[
    // Column layout
    'columnOrder'   => ['id', 'fullname', 'email', 'actions'],
    'columnWidths'  => ['id' => '60px', 'fullname' => '200px'],
    'hiddenColumns' => ['guid', 'created_at'],

    // Grid state (null = neměnit)
    'orderBy'       => 'fullname asc',
    'search'        => 'admin',
    'filters'       => ['status' => '1'],
    'multiFilters'  => ['role' => ['admin', 'editor']],
    'itemsPerPage'  => 50,
]
```

### Jak preset funguje

**Při ukládání:** JS sbírá column layout (pořadí, šířky, viditelnost z DOM). PHP doplní aktuální sort, filtry, search a perPage z URL parametrů.

**Při přepnutí:** Signal handler aplikuje sort/filtry/search/perPage z presetu a zapíše je do URL. Column layout (pořadí, šířky, viditelnost) se aplikuje v `render()` na serveru.

**Výchozí preset:** Pokud je nastavený `isDefault: true` a v URL není parametr `preset`, automaticky se aplikuje při `loadState()`. Uživatel ho může zrušit volbou „— Předvolby —" v selectu.

**Tlačítko Reset:** Zruší filtry, sort, vyhledávání i aktivní preset.

### Interakce s localStorage

Pokud jsou presety aktivní, resize, reorder a column toggle **neukládají** do localStorage — stav řídí server. Uživatel provede změny v prohlížeči a uloží je tlačítkem Uložit.

### Zpětná vazba

V callbacích volejte `$this->flashMessage()` + `$this->redrawControl('flashes')` pro zobrazení zprávy po akci.

### GridPreset

```php
new GridPreset(
    id: '1',
    name: 'Výchozí pohled',
    isDefault: true,
    config: [...],
)
```

Gettery: `getColumnOrder()`, `getColumnWidths()`, `getHiddenColumns()`, `getOrderBy()`, `getSearch()`, `getFilters()`, `getMultiFilters()`, `getItemsPerPage()`.

---

## Témata a stylování

### Vestavěná témata

```php
$grid->setTheme(GridControl::BOOTSTRAP);  // Bootstrap 5 (výchozí)
$grid->setTheme(GridControl::TAILWIND);   // Tailwind CSS
$grid->setTheme(GridControl::DEFAULT);    // Jen phx-grid-* třídy
```

### Přepsání konkrétních tříd

```php
$grid->setTheme([
    'table'            => 'table table-sm table-striped',
    'actionPrimaryBtn' => 'btn btn-sm btn-primary',
]);
```

Klíče odpovídají parametrům konstruktoru `Theme`. Neuvedené přebírají Bootstrap preset.

### CSS třídy

| Třída | Element |
|-------|---------|
| `phx-grid-wrapper` | Outer wrapper |
| `phx-grid-table` | `<table>` |
| `phx-grid-table-fixed` | `<table>` při fixed layoutu |
| `phx-grid-col-{key}` | `<col>` element |
| `phx-grid-col-sorted` | Řazený sloupec |
| `phx-grid-col-filtered` | Filtrovaný sloupec |
| `phx-grid-col-hidden` | Skrytý sloupec |
| `phx-grid-resize-handle` | Resize handle v záhlaví |
| `phx-grid-bulk-bar` | Sticky bar s hromadnými akcemi |

### Theme properties pro toolbar

| Property | Popis |
|----------|-------|
| `colToggleBtn` | Tlačítko „Sloupce" |
| `colToggleDropdown` | Dropdown panel s checkboxy |
| `presetGroup` | Wrapper preset selectu a tlačítek |
| `presetSelect` | Select s předvolbami |
| `presetSaveBtn` | Tlačítko „Uložit" |
| `presetSaveSplitBtn` | Split dropdown toggle |
| `bulkBar` | Sticky bar s hromadnými akcemi |
| `bulkBarCount` | Text počtu vybraných |
| `bulkBarBtn` | Tlačítko hromadné akce |

---

## Texty a lokalizace

```php
$grid->setTexts([
    'noData'              => 'Žádné záznamy.',
    'search'              => 'Hledat…',
    'apply'               => 'Použít',
    'reset'               => 'Zrušit',
    'filter'              => 'Filtrovat…',
    'ajaxFilter'          => 'Začněte psát…',
    'selectAll'           => '— vše —',
    'yes'                 => 'Ano',
    'no'                  => 'Ne',
    'checkAll'            => 'Vybrat / zrušit vše',
    'bulkSelected'        => '{count} vybráno',
    'dateFrom'            => 'Od:',
    'dateTo'              => 'Do:',
    'numMin'              => 'Min:',
    'numMax'              => 'Max:',
    'perPage'             => 'Záznamů na stránku:',
    'columns'             => 'Sloupce',
    'presetNone'          => '— Předvolby —',
    'presetSave'          => 'Uložit',
    'presetSaveAs'        => 'Uložit jako…',
    'presetRename'        => 'Přejmenovat…',
    'presetSetDefault'    => 'Nastavit jako výchozí',
    'presetDelete'        => 'Smazat',
    'presetDeleteConfirm' => 'Opravdu smazat tuto předvolbu?',
    'presetNamePrompt'    => 'Název předvolby:',
    'presetRenamePrompt'  => 'Nový název:',
]);
```

Předáváte pouze klíče, které chcete přepsat. Text `bulkSelected` obsahuje placeholder `{count}`, který JS nahradí číslem.

---

## AJAX (Naja)

```php
$grid->useAjax();
```

```js
import naja from 'naja';
import GridInit from './grid.js';
import './dropdownlist.js';

naja.initialize();
GridInit(naja);
```

`GridInit(naja)` uloží referenci na Naja instanci. Bez ní by preset přepínání, per-page selector a další funkce dělaly plný page redirect místo AJAX.

Grid automaticky aktivuje loading stav (tabulka zešedne), synchronizaci checkboxů a obnovení uloženého layoutu po překreslení snippetů.

Vyhledávání a filtry se odesílají stiskem **Enter** nebo kliknutím na **Použít**. URL se aktualizuje automaticky — F5 zachová stav.

### Fallback

Pokud je Naja dostupná jako globální `window.naja` (UMD build), grid se napojí automaticky. `GridInit()` pak není nutné.

---

## Dialogové okna (Capify)

Grid podporuje knihovnu [Capify](https://github.com/phloxcz/capify) pro stylizované dialogy. Pokud je `window.Capify` dostupné na stránce, grid ho automaticky použije místo nativních `alert()`, `confirm()` a `prompt()`.

### Co se změní s Capify

- **Confirm dialogy** (`data-confirm` na řádkových akcích, bulk akcích) — zobrazí se jako Capify modal/popover místo nativního confirm boxu.
- **Preset prompt** (Uložit jako, Přejmenovat) — zobrazí se jako Capify popover ukotvený k tlačítku Uložit v toolbaru.
- **Preset delete confirm** — zobrazí se s červeným tlačítkem OK (`danger: true`).

### Bez Capify

Všechny funkce fungují i bez Capify — použijí se nativní `window.alert()`, `window.confirm()`, `window.prompt()`.

### Nastavení

```js
import Capify from '@phloxcz/capify';
import '@phloxcz/capify/capify.css';

window.Capify = Capify;
Capify.config({ theme: 'bootstrap', darkMode: 'auto' });
```

---

## JavaScript API

### Escape key

Stisknutí Escape zavře všechny otevřené panely: akční dropdown menu, multiselect filtry, column toggle, preset dropdown.

### Data atributy na wrapperu

| Atribut | Přítomnost |
|---------|------------|
| `data-datagrid` | Vždy |
| `data-grid-id` | Vždy (uniqueId komponenty) |
| `data-resizable` | Pokud `setResizable()` |
| `data-reorderable` | Pokud `setReorderable()` |
| `data-coltoggle` | Pokud `showColumnToggle()` |
| `data-presets` | Pokud `showPresets()` |

### Data atributy na buňkách

Všechny `<col>`, `<th>` a `<td>` mají `data-col-key="{key}"`.

### Čtení zaškrtnutých řádků

```js
const wrapper = document.querySelector('.phx-grid-wrapper');
const selected = [...wrapper.querySelectorAll('.phx-grid-row-check:checked')]
    .map(cb => cb.value);
```

### Teleport dropdown menu

Dropdown menu akčního sloupce se při otevření přesouvá z `<td>` do `.phx-grid-wrapper` a pozicuje se přes `position: fixed`. Při zavření se vrací na původní místo v DOM. Tím se vyřeší problém s `overflow: hidden` na tabulce a `table-responsive` wrapperu.

---

## Řádkový callback

```php
// String → CSS třída řádku
$grid->setRowCallback(fn($row) =>
    $row['active'] ? '' : 'table-secondary text-muted'
);

// Array → HTML atributy řádku
$grid->setRowCallback(fn($row, int $index) => [
    'class'   => $row['active'] ? '' : 'table-secondary',
    'data-id' => $row['id'],
]);
```

---

## Tabulkový layout

```php
$grid->setTableLayout('fixed');

$grid->addTextColumn('name', 'Jméno')->width('200px');
$grid->addActionColumn()->width('130px');
```

Tabulka se automaticky přepne na `fixed` layout pokud existují šířky z preset configu, nebo pokud uživatel začne resizovat sloupce.

---

## Práce s JOINy a related tabulkami

### Zobrazení hodnoty z related tabulky

```php
$grid->addTextColumn('statusId', 'Stav', 'status.name');
// renderCell: $row->status->name
```

### Řazení a filtrování přes JOIN

```php
$selection = $this->db->table('user')
    ->select('user.*, s.name AS statusName')
    ->joinWhere('status AS s', 'user.statusId = s.id');

$grid->setDataSource($selection);
$grid->addTextColumn('statusId', 'Stav', 's.name')
     ->sortable()
     ->filterableMultiSelect($statuses);
```
