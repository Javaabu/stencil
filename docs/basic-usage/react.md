---
title: React
sidebar_position: 7
---

The React renderer draws the JSON driver's output with [@javaabu/forms](https://github.com/Javaabu/react-forms). It ships in the Composer package and your app imports it as `@javaabu/stencil` through javaabu/manifest; see [Installation](../installation-and-setup.md#inertia-react).

# StencilForm

```php
// The controller
return Inertia::render('Islands/Edit', [
    'form' => IslandForm::make()->model($island)->action(route('islands.update', $island)),
]);
```

```tsx
// resources/js/pages/Islands/Edit.tsx
import { StencilForm, type StencilSchema } from "@javaabu/stencil"

export default function Edit({ form }: { form: StencilSchema }) {
  return <StencilForm schema={form} />
}
```

`StencilForm` is `@javaabu/forms`' `Form` with the schema's action, method, model (the state), files, summary and error bag, and the components inside. It submits like any `Form`: with `FormsProvider router={inertia}`, it's an Inertia visit, and errors come back to their fields.

| Prop | What |
| --- | --- |
| `schema` | The form from PHP |
| `components` | Components for node types, over the registered and built-in ones |
| `action`, `method` | Submit elsewhere: a URL or a Wayfinder route (`action={update}`) |
| `submit` | Replace the submit button, or `false` for none |
| `children` | Rendered after the components, before the submit button |
| `onEvaluated`, `onEvaluationError` | [Live fields](./live-fields.md) re-evaluated the form, or failed to |
| any `Form` prop | `onSuccess`, `preserveScroll`, `resetOnSuccess`, `disabled`, … |

When Inertia sends a new schema (after a visit), the form uses it.

# StencilFields

`<StencilFields schema={form} />` renders the components without a form, bound to the schema's state, to put inside a `Form` of your own. A schema from an `embedded()` form renders this way in `StencilForm` too.

# Built-in components

| Type | Renders |
| --- | --- |
| fields (`component` in the node) | The `@javaabu/forms` component (`Text`, `Select2`, `Date`, `File`, …) with the node's props |
| `checkbox-list`, `radio` | `FormGroup` with a `Checkbox` or `Radio` per option |
| `section` | `Card` with the heading and description |
| `grid`, `group`, `tab`, `step` | The children, in a grid when they have columns |
| `fieldset` | `<fieldset>` and `<legend>` |
| `tabs` | `Tabs` |
| `wizard` | Steps with Back and Next; the submit button shows on the last step |
| `alert`, `text-entry` | `Alert`, `TextEntry` |
| translatable fields | One field per locale, in tabs or stacked |

Conditions render as `ConditionalWrapper`, and stencil's stylesheet (grids, fieldsets, the wizard) is added to the page once by React.

# Your own components

Register components by node type, once, next to `FormsProvider`:

```tsx
import { StencilProvider } from "@javaabu/stencil"
import { Rating } from "./stencil/fields/Rating"

<FormsProvider ui={onu} router={inertia}>
  <StencilProvider components={{ rating: Rating }}>
    <App />
  </StencilProvider>
</FormsProvider>
```

A component gets the node and a function that renders child nodes:

```tsx
import type { StencilComponentProps } from "@javaabu/stencil"

export function Callout({ node, renderChildren }: StencilComponentProps) {
  return (
    <aside className="rounded-lg border p-4">
      <h3>{String(node.props?.heading)}</h3>
      {renderChildren(node.children, node.props?.columns)}
    </aside>
  )
}
```

Registering a built-in type replaces it (`{ section: MySection }`), and `components` on one form wins over the provider. `useStencil()` gives a component the schema and the registry. See [Custom fields](../advanced-usage/custom-fields.md).
