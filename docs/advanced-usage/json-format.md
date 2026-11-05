---
title: JSON format
sidebar_position: 4
---

The `json` driver's output, version 1. It's what `$form->toArray()` and `json_encode($form)` return and what Inertia sends. The React renderer reads it, and TypeScript types for it are exported from `@javaabu/stencil` (`StencilSchema`, `StencilNode`, …).

# The form

```jsonc
{
  "stencil": 1,
  "id": "island-form",
  "action": "/islands/1",
  "method": "PATCH",
  "operation": "edit",
  "files": true,
  "submit": "Save",
  "summary": true,
  "errorBag": "island",
  "embedded": true,
  "columns": { "default": 1, "md": 2 },
  "state": { "name": "މާލެ", "atoll_id": 3, "tags": [1, 2], "address": { "city": "މާލެ" } },
  "live": { "url": "/stencil/evaluate", "token": "…", "debounce": 500 },
  "components": []
}
```

| Key | What |
| --- | --- |
| `stencil` | The format version. It changes only with a breaking change |
| `id` | The HTML id |
| `action`, `method` | Where and how to submit. Left out when there's no action |
| `operation` | `create`, `edit`, `view`, or your own |
| `files`, `summary`, `embedded` | Present (`true`) only when on |
| `submit` | The submit button's label; left out for no button |
| `errorBag`, `columns` | Present only when set |
| `state` | Values, nested by field name. Dates are strings, enums their values, uploads `null` |
| `live` | Where live fields post, or `null` |
| `components` | Nodes |

# Nodes

```jsonc
{
  "type": "text",
  "key": "name",
  "name": "name",
  "component": "Text",
  "props": { "label": "ނަން", "required": true, "thaana": true, "maxLength": 255 },
  "children": [],
  "conditions": [{ "effect": "visible", "field": "type", "selector": "#type", "values": ["company"], "checkbox": false }],
  "live": { "debounce": null, "onBlur": false },
  "span": { "md": "full" },
  "translatable": { "layout": "tabs", "inputs": [] },
  "meta": {}
}
```

| Key | What |
| --- | --- |
| `type` | What to render |
| `key` | Unique in the form; the field's name for fields, generated for layouts (`section-0`, `grid-0-1`, `tab-details`, `step-3-0`) |
| `name` | Fields: the input name (`address[city]`, `tags[]`, `channels[]`) |
| `component` | Fields: the `@javaabu/forms` export to render: `Text`, `Email`, `Password`, `Number`, `Tel`, `Url`, `Hidden`, `Textarea`, `Wysiwyg`, `Select`, `Select2`, `Checkbox`, `Radio`, `Date`, `Datetime`, `Time`, `File`, `Image` |
| `props` | camelCase. For fields, `@javaabu/forms`' props. Left out when empty |
| `children` | Containers only |
| `hidden` | `true` when hidden on the server; then only `type`, `key`, `name` and `span` are sent |
| `conditions` | Conditions to apply in the browser |
| `live` | The field is live |
| `span` | Grid columns taken, per breakpoint (`default`, `sm`, `md`, `lg`, `xl`), a number or `"full"` |
| `translatable` | Translatable fields: `layout` and one input per locale (`field`, `name` in bracket notation, `statePath` in dot notation, `locale`, `direction`, `thaana`, `primary`, `label`, `language`) |
| `meta` | From `->meta()`, untouched |

Optional keys are left out rather than `null`.

`options` are always a list, so JavaScript keeps their order: `[{ "value": "2", "label": "ކުންފުންޏެއް" }]`, with string values.

## Conditions

Render each condition as `@javaabu/forms`' `ConditionalWrapper` (nested for several):

| Wrapper prop | From the condition |
| --- | --- |
| `enableElem` | `selector` |
| `enableValue` | `1` when `checkbox`, else `values` (one value when there's one) |
| `enableCheckbox` | `checkbox` |
| `hideFields` | `effect` is `visible` or `hidden` |
| `disable` | `effect` is `hidden` or `disabled` |

## Built-in types

| Type | Props |
| --- | --- |
| `section` | `heading`, `description`, `columns` |
| `grid`, `group` | `columns` |
| `fieldset` | `label`, `columns` |
| `tabs` | `active` (a tab's key); children are `tab`s with `label`, `icon`, `columns` |
| `wizard` | `startStep` (from 1), `nextLabel`, `previousLabel`, `stepLabel` (`:current` and `:total` placeholders); children are `step`s with `label`, `description`, `columns` |
| `alert` | `message`, `type`, `heading`, `icon` |
| `text-entry` | `name`, `label`, `value`, `multiline`, `inline` |
| `checkbox-list`, `radio` | `options`, `columns` and the field props; `component` is `Checkbox` or `Radio`, one per option, named `name` |
| other fields | Render `component` with `name` and `props` |

# The live route

When a live field changes, post the form's values (without files or `_method`) to `live.url` as form data, with:

| Field | Value |
| --- | --- |
| `_stencil` | `live.token` |
| `_stencil_changed` | The field's name, in dot notation |
| `_stencil_old` | Its value before the change (optional) |
| `_stencil_driver` | `json` or `blade` |

and the CSRF token (the `_token` field, `X-CSRF-TOKEN` or `X-XSRF-TOKEN`). The response is JSON:

```jsonc
{ "schema": { /* the form, as above */ }, "state": { }, "changed": ["slug"] }
```

`changed` lists the fields `$set` changed. With `_stencil_driver=blade`, `html` (the form rendered by the Blade driver) replaces `schema`. An invalid or expired token, or `authorize()` returning `false`, is a `403`.
