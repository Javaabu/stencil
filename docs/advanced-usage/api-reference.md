---
title: API reference
sidebar_position: 9
---

# PHP

| Class | What |
| --- | --- |
| `Javaabu\Stencil\Form` | The root: `make(?id)`, `definition()`, `setUp()`, `model()`, `operation()`, `with()`/`context()`, `action()`, `method()`, `files()`, `submit()`, `embedded()`, `summary()`, `errorBag()`, `disabled()`, `columns()`, `schema()`, `getFields()`, `getField()`, `fill()`, `fillFromInput()`, `getState()`, `getStateValue()`, `setStateValue()`, `rules()`, `messages()`, `validationAttributes()`, `validator()`, `validate()`, `dehydrate()`, `fillModel()`, `save()`, `saveRelationships()`, `authorize()`, `getLiveConfig()`, `toNode()`, `render()`, `toHtml()`, `toArray()`, `assertable()`. `Htmlable`, not `Stringable` |
| `Javaabu\Stencil\Components\*` | `Section`, `Grid`, `Group`, `Fieldset`, `Tabs`, `Tab`, `Wizard`, `Step`, `Alert`, `TextEntry` |
| `Javaabu\Stencil\Fields\*` | `TextInput`, `Textarea`, `Wysiwyg`, `Hidden`, `Select`, `Checkbox`, `CheckboxList`, `Radio`, `DatePicker`, `DateTimePicker`, `TimePicker`, `FileUpload`, `ImageUpload`; extend `Field` for your own |
| `Javaabu\Stencil\Get`, `Set` | Closure arguments for reading and changing the state |
| `Javaabu\Stencil\Facades\Stencil` | `render()`, `renderer()`, `extend()`, `view()` |
| `Javaabu\Stencil\Contracts\Renderer` | The driver contract |
| `Javaabu\Stencil\Contracts\SavesRelationships` | For fields that save after the model |
| `Javaabu\Stencil\Contracts\ResolvesTranslatableFields` | For records and forms that decide which fields are translatable |
| `Javaabu\Stencil\Rendering\Node` | A resolved component |
| `Javaabu\Stencil\Renderers\BladeRenderer`, `JsonRenderer` | The built-in drivers |
| `Javaabu\Stencil\Support\Props` | Prop names and JSON values |
| `Javaabu\Stencil\Http\Controllers\EvaluateController`, `LiveToken` | The live route |
| `Javaabu\Stencil\Testing\*` | `AssertableForm`, `InteractsWithStencil`, `RendererTestKit`, `KitchenSinkForm` |

Every component: `key()`, `statePath()`, `view()`, `meta()`, `extraAttributes()`, `columnSpan()`, `columnSpanFull()`, `hidden()`, `visible()`, `hiddenOn()`, `visibleOn()`, `visibleWhen()`, `hiddenWhen()`, `enabledWhen()`, `disabledWhen()`, the `…WhenChecked()` variants, `when()`, `tap()`, macros. Containers: `schema()`, `columns()`. Fields: `getName()`, `getStatePath()`, `getInputName()`, `getId()`, `isDeclaredTranslatable()`, `isTranslatable()`; `TextInput::isInteger()`, `isNumeric()`.

Route macro: `Route::stencil(string $uri = 'stencil/evaluate', ?string $controller = null)`. Command: `php artisan make:stencil-field {name} {--force}`.

# JavaScript (`@javaabu/stencil`)

| Export | What |
| --- | --- |
| `StencilForm`, `StencilFields` | Render a schema, with or without a form |
| `StencilProvider`, `useStencil` | The component registry, and the schema for custom components |
| `builtInComponents`, `Field`, `OptionsField`, `Group`, `Section`, `Fieldset`, `Tabs`, `Wizard`, `Alert`, `TextEntry`, `TranslatableField` | The built-in components |
| `Children`, `StencilNodeRenderer`, `conditionProps` | Rendering nodes |
| `evaluate`, `evaluationBody`, `evaluationHeaders`, `toDotName` | The live route |
| `columnsStyle`, `spanStyle`, `replacePlaceholders`, `optionId`, `translatableInputNode` | Helpers |
| `StencilStyles`, `stencilCss` | The stylesheet |
| `StencilSchema`, `StencilNode`, `StencilCondition`, `StencilLive`, `StencilOption`, `StencilEvaluation`, `Columns`, `Span`, `StencilComponentProps`, `StencilComponents` | Types |

The Blade script, `@javaabu/stencil/blade/stencil`: `init(root)`, `initWizard(element)`, `initLive(form)`, `patch(current, next, changed)`, `keyedChildren(element)`.
