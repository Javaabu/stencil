---
title: Layout
sidebar_position: 3
---

Layout components hold other components. Each takes `schema([...])` (or its components as the last argument of `make()`), and `null` entries are skipped, so `$condition ? TextInput::make('x') : null` works.

```php
use Javaabu\Stencil\Components\Fieldset;
use Javaabu\Stencil\Components\Grid;
use Javaabu\Stencil\Components\Group;
use Javaabu\Stencil\Components\Section;
use Javaabu\Stencil\Components\Step;
use Javaabu\Stencil\Components\Tab;
use Javaabu\Stencil\Components\Tabs;
use Javaabu\Stencil\Components\Wizard;
```

| Component | Renders as | Takes |
| --- | --- | --- |
| `Section::make(heading)` | `<x-forms::card>` / `Card` with the heading as title | `description()`, `columns()` |
| `Grid::make(columns)` | a grid, no other markup | `columns()` |
| `Group::make([...])` | nothing of its own | `columns()`; use it to show, hide or span components together |
| `Fieldset::make(label)` | `<fieldset>` with a `<legend>` | `columns()` |
| `Tabs::make([Tab::make(label)->schema([...])])` | `<x-forms::tabs>` / `Tabs` | `activeTab(key)`; tabs take `icon()` and `columns()` |
| `Wizard::make([Step::make(label)->schema([...])])` | steps with Back and Next | `startOnStep(int)`; steps take `description()` and `columns()` |

Two components show things rather than take input:

```php
use Javaabu\Stencil\Components\Alert;
use Javaabu\Stencil\Components\TextEntry;

Alert::make(__('Published articles are visible to everyone.'))->color('warning')->heading(__('Note'));
TextEntry::make('created_at')->label(__('Created'));
TextEntry::make('author')->value(fn (Article $record) => $record->author->name);
```

`TextEntry` shows the field's state, or the record's attribute, or `value()`. Enums show their `getLabel()`.

# Columns

`columns(2)` lays the components inside out in two columns from the `md` breakpoint (768px), one below it. Pass breakpoints for more control: `default`, `sm` (640px), `md`, `lg` (1024px), `xl` (1280px).

```php
Section::make(__('Address'))->columns(['sm' => 2, 'xl' => 4])->schema([
    TextInput::make('address.line')->columnSpanFull(),
    TextInput::make('address.island'),
    TextInput::make('address.postcode'),
]);
```

`columnSpan(2)` takes two columns from `md` up, `columnSpan(['md' => 2, 'xl' => 1])` per breakpoint, `columnSpanFull()` the whole row. The form itself takes `columns()` too.

Grids are CSS grid with logical properties, in stencil's stylesheet (`stencil::styles`), so they work with Onu and Bootstrap 6, left to right and right to left.

# Tabs

```php
Tabs::make([
    Tab::make(__('Details'))->schema([...]),
    Tab::make(__('Address'))->columns(2)->schema([...]),
])->activeTab('tab-address');
```

A tab's key is `tab-` and its label in snake case (`tab-address`), or `key()`. Every tab is rendered, so all the fields submit. Hidden tabs (`->hidden()`, `->visibleOn()`) are left out.

# Wizards

```php
Wizard::make([
    Step::make(__('Account'))->description(__('How you sign in'))->schema([
        TextInput::make('email')->email()->required(),
        TextInput::make('password')->password()->required(),
    ]),
    Step::make(__('Profile'))->schema([
        TextInput::make('name')->thaana(),
    ]),
]);
```

One step shows at a time. Next checks the step's fields with the browser's validation first; the submit button shows on the last step. Every step is in the form, so the server validates them all when it's submitted, and the first step with an error opens when the page comes back. The buttons are "Next" and "Back" (`stencil::strings.next`, `stencil::strings.previous`).
