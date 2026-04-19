# phlox/components-grid

DataGrid UI komponenta pro [Nette Framework](https://nette.org). Zobrazuje tabulková data s řazením, filtrováním, stránkováním a akcemi — bez závislosti na konkrétním CSS frameworku.

## Požadavky

- PHP 8.1+
- nette/application ^3.1 || ^4.0
- nette/database ^3.1 || ^4.0
- nette/utils ^3.2 || ^4.0

## Instalace

```bash
composer require phloxcz/components-grid
```

Zkopírujte assety do svého projektu:

```
vendor/phloxcz/components-grid/assets/grid.css
vendor/phloxcz/components-grid/assets/grid.js
vendor/phloxcz/components-grid/assets/dropdownlist.css
vendor/phloxcz/components-grid/assets/dropdownlist.js
```

## Quickstart

### 1. Zaregistrujte komponentu v presenteru

```php
use Phlox\Components\Grid\GridControl;

class UserPresenter extends Nette\Application\UI\Presenter
{
    public function __construct(
        private readonly Nette\Database\Explorer $db,
    ) {}

    protected function createComponentUserGrid(): GridControl
    {
        $grid = new GridControl();

        $grid->setDataSource($this->db->table('user'))
             ->setItemsPerPage(25)
             ->setTheme(GridControl::BOOTSTRAP)
             ->useAjax()
             ->setResizable()
             ->setReorderable()
             ->showColumnToggle();

        $grid->addTextColumn('name', 'Jméno')
             ->sortable()
             ->searchable()
             ->filterable();

        $grid->addTextColumn('email', 'E-mail')
             ->sortable()
             ->width('220px');

        $grid->addDateColumn('created_at', 'Registrace', 'd.m.Y')
             ->sortable();

        $grid->addBoolColumn('active', 'Aktivní');

        $grid->addActionColumn('actions', '')
             ->addPrimaryAction('', fn($row) => $this->link('edit', $row['id']), 'bi bi-pencil', title: 'Upravit')
             ->addAction('Smazat', fn($row) => $this->link('delete!', $row['id']), 'bi bi-trash', 'text-danger', 'Opravdu smazat?');

        // Hromadné akce
        $grid->setPrimaryKey('id')
             ->addBulkAction('delete', 'Smazat', fn($ids) => $this->handleBulkDelete($ids),
                 icon: 'bi bi-trash', confirm: 'Opravdu smazat?');

        return $grid;
    }
}
```

### 2. Vykreslete v šabloně

```latte
{control userGrid}
```

### 3. Načtěte assety

```html
<link rel="stylesheet" href="grid.css">
<link rel="stylesheet" href="dropdownlist.css">

<script type="module">
    import naja from 'naja';
    import GridInit from './grid.js';
    import './dropdownlist.js';

    naja.initialize();
    GridInit(naja);
</script>
```

## Funkce

- Řazení sloupců (trojstavové: ↑ ↓ žádné)
- Globální fulltextové vyhledávání
- Filtrování po sloupcích (text, select, multiselect, datum, číslo, bool, AJAX dropdown)
- Stránkování s volbou počtu záznamů na stránku
- Akce na řádcích (primární tlačítko + dropdown menu)
- Hromadné akce se sticky barem a confirm dialogy
- Agregáty v patičce (sum, avg, count, min, max)
- Změna velikosti sloupců tažením (`setResizable()`)
- Přeřazování sloupců drag & drop (`setReorderable()`)
- Výběr viditelných sloupců (`showColumnToggle()`)
- Předvolby — uložení a obnovení kompletního stavu gridu (`showPresets()`)
- Bootstrap 5, Tailwind CSS nebo vlastní téma
- AJAX s Naja — snippet redraw, URL history, loading stav
- Volitelná integrace s [Capify](https://github.com/phloxcz/capify) pro stylizované dialogy

## Dokumentace

Kompletní dokumentace všech možností je v [docs/README.md](docs/README.md).

## Licence

MIT
