<?php declare(strict_types=1);

/**
 * EXAMPLE – UserPresenter
 *
 * Copy / adapt this file into your own application.
 * This file is NOT part of the library itself.
 */

namespace App\Presenters;

use Phlox\Components\Grid\GridControl;
use Phlox\Components\Grid\GridPreset;
use Nette\Application\UI\Presenter;

class UserPresenter extends Presenter
{
    // Inject your repository via constructor or @inject
    public function __construct(
        // private readonly UserRepository $users,
        // private readonly GridPresetRepository $presetRepo,
    ) {
        parent::__construct();
    }

    // -------------------------------------------------------------------------
    // GridControl component factory
    // -------------------------------------------------------------------------

    protected function createComponentUserGrid(): GridControl
    {
        // $selection = $this->users->findAll();
        // For the example we show the configuration only:

        $grid = new GridControl();

        // $grid->setDataSource($selection)
        $grid->setItemsPerPage(25)
             ->setDefaultSort('name')
             ->useAjax()                   // opt-in: requires Naja loaded on the page
             ->setResizable()              // column resize by dragging header edge
             ->setReorderable()            // column reorder by drag & drop
             ->showColumnToggle()          // toolbar dropdown for column visibility
             ->setPerPageOptions([10, 25, 50, 100, 250]);

        // ── Columns ──────────────────────────────────────────────────────────

        $grid->addTextColumn('name', 'Name')
             ->sortable()
             ->searchable()
             ->filterable();

        $grid->addTextColumn('email', 'E-mail')
             ->sortable()
             ->searchable()
             ->width('220px');

        $grid->addDateColumn('created_at', 'Registered', 'd.m.Y')
             ->sortable();

        $grid->addNumberColumn('balance', 'Balance', 2)
             ->suffix(' €')
             ->sortable()
             ->footerSum();

        $grid->addBadgeColumn('role', 'Role', [
                'admin'   => ['label' => 'Admin',    'color' => 'danger'],
                'editor'  => ['label' => 'Editor',   'color' => 'warning'],
                'user'    => ['label' => 'User',     'color' => 'primary'],
                'guest'   => ['label' => 'Guest',    'color' => 'secondary'],
             ])
             ->filterable();

        $grid->addBoolColumn('active', 'Active')
             ->trueLabel('✔ Yes', 'text-success fw-semibold')
             ->falseLabel('✘ No', 'text-muted');

        $grid->addActionColumn()
             ->addAction(
                 'Detail',
                 fn($row) => $this->link('User:detail', $row['id']),
                 'bi bi-eye'
             )
             ->addAction(
                 'Edit',
                 fn($row) => $this->link('User:edit', $row['id']),
                 'bi bi-pencil'
             )
             ->addAction(
                 'Delete',
                 fn($row) => $this->link('delete!', $row['id']),
                 'bi bi-trash',
                 'btn btn-sm btn-outline-danger',
                 'Really delete this user?'
             );

        // ── Optional: dynamic row colour ─────────────────────────────────────

        $grid->setRowCallback(
            fn($row) => $row['active'] ? '' : 'table-secondary text-muted'
        );

        // ── Optional: bulk actions (sticky bar with checkboxes) ─────────────

        // $grid->setPrimaryKey('id')
        //      ->addBulkAction(
        //          'export',
        //          'Exportovat',
        //          fn(array $ids) => $this->handleExport($ids),
        //          icon: 'bi bi-download',
        //      )
        //      ->addBulkAction(
        //          'delete',
        //          'Smazat',
        //          fn(array $ids) => $this->handleBulkDelete($ids),
        //          icon: 'bi bi-trash',
        //          extraClass: 'text-danger',
        //          confirm: 'Opravdu smazat vybrané záznamy?',
        //      );

        // ── Optional: presets (saved grid layouts stored in DB) ──────────────

        // Load presets lazily — called before each render for fresh data.
        // $userId = $this->getUser()->getId();
        // $grid->onPresetsLoad(fn() => $this->presetRepo->findByUser($userId));

        // Preset callbacks — each one writes to your database.
        // $grid->onPresetSave(function (string $presetId, array $config) {
        //     $this->presetRepo->update($presetId, $config);
        //     $this->flashMessage('Předvolba uložena.', 'success');
        // });

        // $grid->onPresetSaveAs(function (string $name, array $config) use ($userId): string {
        //     $preset = $this->presetRepo->create($userId, $name, $config);
        //     $this->flashMessage("Předvolba „{$name}" vytvořena.", 'success');
        //     return (string) $preset->id; // returned ID becomes active preset
        // });

        // $grid->onPresetRename(function (string $presetId, string $newName) {
        //     $this->presetRepo->rename($presetId, $newName);
        //     $this->flashMessage('Předvolba přejmenována.', 'success');
        // });

        // $grid->onPresetDefault(function (string $presetId) use ($userId) {
        //     $this->presetRepo->setDefault($userId, $presetId);
        //     $this->flashMessage('Výchozí předvolba nastavena.', 'success');
        // });

        // $grid->onPresetDelete(function (string $presetId) {
        //     $this->presetRepo->delete($presetId);
        //     $this->flashMessage('Předvolba smazána.', 'success');
        // });

        return $grid;
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    public function actionDefault(): void
    {
        // Nothing needed – grid loads its own data
    }

    public function handleDelete(int $id): void
    {
        // $this->users->delete($id);
        $this->flashMessage("User #$id deleted.", 'success');
        $this->redirect('this');
    }
}
