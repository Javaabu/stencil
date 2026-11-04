# Extending javaabu/stencil

## Custom field types

```bash
php artisan make:stencil-field Rating
```

Creates `app/Stencil/Fields/Rating.php` (extends `Javaabu\Stencil\Fields\Field`, `protected string $type = 'rating'`, `protected ?string $view = 'stencil.fields.rating'`), the Blade view and `resources/js/stencil/fields/Rating.tsx`. Override `getFieldProps()` (props for both drivers), `getTypeRules()`, `getAllValidationRules()`, `readFromRecord()`, `normalizeState()`, `castDehydratedState()`, `getInputName()`, `getSelector()`; implement `SavesRelationships` for fields that save after the model.

Blade views get `$node` (`name`, `key`, `props`, `prop()`) and `$stencil` (`value($node)`, `component('text', $props)`, `field($node)`, `children($nodes, $columns)`). Register a view for a type with `Stencil::view('rating', 'view.name')`.

React: `StencilProvider components={{ rating: Rating }}`; the component gets `{ node, renderChildren }`.

A field that is a javaabu/forms component sets `protected ?string $component = 'text'` instead of a view.

## Drivers

```php
class VueRenderer implements \Javaabu\Stencil\Contracts\Renderer
{
    public function render(Form $form): mixed
    {
        $root = $form->toNode();   // Node tree, closures evaluated, hidden marked
        // …
    }
}

Stencil::extend('vue', fn ($app) => new VueRenderer);
$form->render('vue');
```

Test with `use Javaabu\Stencil\Testing\RendererTestKit;` and `protected function renderer(): Renderer`.

## JSON format (v1)

Form: `stencil`, `id`, `action`, `method`, `operation`, `files`, `submit`, `summary`, `errorBag`, `embedded`, `columns`, `state`, `live {url, token, debounce}`, `components`. Node: `type`, `key`, `name`, `component` (the @javaabu/forms export), `props` (camelCase), `children`, `hidden`, `conditions [{effect, field, selector, values, checkbox}]`, `live {debounce, onBlur}`, `span`, `translatable {layout, inputs}`, `meta`. Options are lists of `{value, label}`. Optional keys are left out.

Live route: POST the form's values plus `_stencil` (token), `_stencil_changed`, `_stencil_old`, `_stencil_driver` (`json`|`blade`) → `{schema|html, state, changed}`.

## Testing

```php
$form->assertable()
    ->fillState(['type' => 'company'])
    ->assertFieldVisible('company_name')
    ->assertRules('company_name', ['required', 'string'])
    ->assertInvalid([], 'company_name')
    ->assertValidated([...], [...])
    ->assertRendersField('company_name')
    ->assertJsonPath('components.0.type', 'section');
```

Or `use Javaabu\Stencil\Testing\InteractsWithStencil;` and `$this->assertForm($form)`.

## Translatable fields

`->translatable()` turns a field into one input per locale (`name_en`, `name_dv`) when javaabu/translatable is installed and the form's model translates it; otherwise it's a plain field. Rules go through `Translatable::rules()`; `validate()` returns the per-locale keys.
