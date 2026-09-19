<?php declare(strict_types=1);

namespace Phlox\Components\Grid\Column;

use Phlox\Components\Grid\Theme\Theme;

/**
 * Column for row-level action buttons.
 *
 * With a primary action — renders a split button group:
 *   [ Primary Action | ▾ ] → dropdown with secondary actions
 *
 * Without a primary action — renders just a ⋮ menu toggle button.
 *
 * Usage:
 *   $grid->addActionColumn()
 *       ->addPrimaryAction('Detail', fn($row) => $this->link('detail', $row['id']), 'bi bi-eye')
 *       ->addAction('Upravit', fn($row) => $this->link('edit', $row['id']), 'bi bi-pencil')
 *       ->addAction('Smazat',  fn($row) => $this->link('delete!', $row['id']), 'bi bi-trash',
 *                   'text-danger', 'Opravdu smazat?');
 *
 * Note: the $class parameter on addAction() is APPENDED to the base item class
 * from the theme (e.g. 'dropdown-item text-danger'), not replacing it.
 */
final class ActionColumn extends Column
{
    /** @var array{label:string,link:\Closure,icon:string,extraClass:string,confirm:string|null,ajax:bool}|null */
    private ?array $primaryAction = null;

    /** @var list<array{label:string,link:\Closure,icon:string,extraClass:string,confirm:string|null,ajax:bool}> */
    private array $actions = [];

    private ?Theme $theme = null;

    public function __construct(string $key = 'actions', string $label = 'Actions')
    {
        parent::__construct($key, $label, '_actions');
        $this->hideable = false;
    }

    /** @internal Called by GridControl to inject the active theme. */
    public function setTheme(Theme $theme): void
    {
        $this->theme = $theme;
    }

    /**
     * Add a primary action — shown as the left part of a split button.
     * Only one primary action is supported; calling this again replaces the previous.
     *
     * @param string $extraClass  Extra CSS classes appended to the button's base class.
     */
    public function addPrimaryAction(
        string   $label,
        \Closure $linkCallback,
        string   $icon       = '',
        string   $extraClass = '',
        ?string  $confirm    = null,
        bool     $ajax       = false,
        ?string  $title      = null,
    ): static {
        $this->primaryAction = [
            'label'      => $label,
            'link'       => $linkCallback,
            'icon'       => $icon,
            'extraClass' => $extraClass,
            'confirm'    => $confirm,
            'ajax'       => $ajax,
            'title'      => $title,
        ];
        return $this;
    }

    /**
     * Add an action to the dropdown menu.
     *
     * @param string $extraClass  Extra CSS classes appended to the item's base class (e.g. 'text-danger').
     */
    public function addAction(
        string   $label,
        \Closure $linkCallback,
        string   $icon       = '',
        string   $extraClass = '',
        ?string  $confirm    = null,
        bool     $ajax       = false,
        ?string  $title      = null,
    ): static {
        $this->actions[] = [
            'label'      => $label,
            'link'       => $linkCallback,
            'icon'       => $icon,
            'extraClass' => $extraClass,
            'confirm'    => $confirm,
            'ajax'       => $ajax,
            'title'      => $title,
        ];
        return $this;
    }

    public function renderCell(mixed $row): string
    {
        if ($this->renderer !== null) {
            return ($this->renderer)($row);
        }

        if ($this->primaryAction === null && $this->actions === []) {
            return '';
        }

        $theme         = $this->theme;
        $primaryBtnCls = trim(($theme?->actionPrimaryBtn ?? 'btn btn-sm btn-outline-primary'));
        $toggleCls     = $theme?->actionMenuToggle ?? 'btn btn-sm btn-outline-secondary phx-grid-action-toggle';
        $menuCls       = $theme?->actionMenu       ?? 'phx-grid-action-menu dropdown-menu dropdown-menu-end';
        $menuItemBase  = $theme?->actionMenuItem   ?? 'dropdown-item';
        $menuIcon      = $theme?->iconActionMenu   ?? '<i class="bi bi-three-dots-vertical"></i>';

        // ── No secondary actions — just the primary button ────────────────────
        if ($this->actions === [] && $this->primaryAction !== null) {
            return '<div class="phx-grid-actions">'
                . $this->renderLink($this->primaryAction, $row, $this->primaryClass($primaryBtnCls))
                . '</div>';
        }

        // ── No primary action — standalone toggle only ────────────────────────
        if ($this->primaryAction === null) {
            return '<div class="phx-grid-actions"><div class="btn-group">'
                . $this->renderMenu($row, $toggleCls, $menuCls, $menuItemBase, $menuIcon)
                . '</div></div>';
        }

        // ── Split button: primary + dropdown toggle ────────────────────────────
        $primaryHtml = $this->renderLink($this->primaryAction, $row, $this->primaryClass($primaryBtnCls));
        $menuHtml    = $this->renderMenu($row, $toggleCls, $menuCls, $menuItemBase, $menuIcon, split: true);

        return '<div class="phx-grid-actions"><div class="btn-group">'
            . $primaryHtml
            . $menuHtml
            . '</div></div>';
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Merges the primary action's own $extraClass (set via addPrimaryAction())
     * into the theme's base button class - same "append, don't replace" idea
     * renderMenu() already applies per dropdown item below, just previously
     * missing here, which silently dropped extraClass for the primary button
     * (both call sites in renderCell() passed $primaryBtnCls straight
     * through unmodified).
     */
    private function primaryClass(string $baseClass): string
    {
        $extraClass = $this->primaryAction['extraClass'] ?? '';
        return trim($baseClass . ($extraClass !== '' ? ' ' . $extraClass : ''));
    }


    private function renderMenu(
        mixed  $row,
        string $toggleCls,
        string $menuCls,
        string $itemBase,
        string $menuIcon,
        bool   $split = false,
    ): string {
        $splitCls = $split ? ' dropdown-toggle-split' : '';
        $items = '';
        foreach ($this->actions as $action) {
            $cls    = trim($itemBase . ($action['extraClass'] !== '' ? ' ' . $action['extraClass'] : ''));
            $items .= '<li>' . $this->renderLink($action, $row, $cls) . '</li>';
        }

        return sprintf(
            '<button type="button" class="%s%s" aria-haspopup="true" aria-expanded="false">'
            . '%s</button>'
            . '<ul class="%s" style="display:none">%s</ul>',
            htmlspecialchars($toggleCls),
            htmlspecialchars($splitCls),
            $split ? '<span class="visually-hidden">Toggle Dropdown</span>' : $menuIcon,
            htmlspecialchars($menuCls),
            $items
        );
    }

    /** @param array{label:string,link:\Closure,icon:string,extraClass:string,confirm:string|null,ajax:bool,title:string|null} $action */
    private function renderLink(array $action, mixed $row, string $class): string
    {
        $href    = ($action['link'])($row);
        $icon    = $action['icon'] ? '<i class="' . htmlspecialchars($action['icon']) . '"></i> ' : '';
        $confirm = $action['confirm']
            ? ' data-confirm="' . htmlspecialchars($action['confirm']) . '"'
            : '';
        $naja    = $action['ajax'] ? ' data-naja' : '';
        // Show title only when label is empty — otherwise the label itself is self-describing
        $titleVal = ($action['label'] === '' && $action['title'] !== null) ? $action['title'] : null;
        $title    = $titleVal !== null ? ' title="' . htmlspecialchars($titleVal) . '"' : '';

        return sprintf(
            '<a href="%s" class="%s"%s%s%s>%s%s</a>',
            htmlspecialchars($href),
            htmlspecialchars($class),
            $title,
            $confirm,
            $naja,
            $icon,
            htmlspecialchars($action['label'])
        );
    }
}
