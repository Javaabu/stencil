<?php

namespace Javaabu\Stencil\Testing;

use Illuminate\Support\Str;
use Javaabu\Stencil\Components\Alert;
use Javaabu\Stencil\Components\Fieldset;
use Javaabu\Stencil\Components\Section;
use Javaabu\Stencil\Components\Step;
use Javaabu\Stencil\Components\Tab;
use Javaabu\Stencil\Components\Tabs;
use Javaabu\Stencil\Components\TextEntry;
use Javaabu\Stencil\Components\Wizard;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\CheckboxList;
use Javaabu\Stencil\Fields\Radio;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Fields\Textarea;
use Javaabu\Stencil\Fields\TextInput;
use Javaabu\Stencil\Form;
use Javaabu\Stencil\Get;
use Javaabu\Stencil\Set;

/**
 * A form with every built-in component type, for testing drivers and renderers
 * (RendererTestKit) and the JavaScript renderers in this package.
 */
class KitchenSinkForm extends Form
{
    protected function definition(): array
    {
        return [
            Section::make('ތަފްޞީލް')->description('ކުންފުނީގެ މަޢުލޫމާތު')->columns(2)->schema([
                TextInput::make('title')->label('ސުރުހީ')->required()->thaana()
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug'),
                Select::make('type')->label('ބާވަތް')->options(['person' => 'ފަރުދެއް', 'company' => 'ކުންފުންޏެއް'])->live(),
                TextInput::make('company_name')->label('ކުންފުނީގެ ނަން')->visible(fn (Get $get) => $get('type') === 'company'),
                TextInput::make('id_card')->label('އައިޑީ ކާޑު')->visibleWhen('type', 'person'),
                Textarea::make('note')->columnSpanFull(),
            ]),
            Fieldset::make('ގުޅުން')->schema([
                Checkbox::make('subscribe')->label('ސަބްސްކްރައިބް'),
                CheckboxList::make('channels')->options(['sms' => 'SMS', 'email' => 'Email'])->gridColumns(2),
                Radio::make('size')->options(['s' => 'ކުޑަ', 'l' => 'ބޮޑު']),
            ]),
            Tabs::make([
                Tab::make('Extra')->schema([TextInput::make('extra')]),
                Tab::make('More')->schema([TextInput::make('more')]),
            ]),
            Wizard::make([
                Step::make('އެކައުންޓް')->description('ފުރަތަމަ')->schema([TextInput::make('email')->email()->required()]),
                Step::make('ޕްރޮފައިލް')->schema([TextInput::make('name')]),
            ]),
            Alert::make('ސަމާލުކަމަށް')->color('warning'),
            TextEntry::make('title')->label('ސުރުހީ (ބަލާލުމަށް)'),
        ];
    }
}
