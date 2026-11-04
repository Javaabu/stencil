---
title: Dhivehi and RTL
sidebar_position: 8
---

# Strings

Stencil's own strings are in `lang/en/strings.php` and `lang/dv/strings.php`, under `stencil::strings`:

| Key | English | Dhivehi |
| --- | --- | --- |
| `submit` | Save | ސޭވް ކުރައްވާ |
| `next` | Next | ކުރިއަށް |
| `previous` | Back | ފަހަތަށް |
| `step` | Step :current of :total | ސްޓެޕް :current / :total |
| `command_*` | `make:stencil-field` output | |

Change them in `lang/vendor/stencil/{locale}/strings.php` (`php artisan vendor:publish --tag=stencil-translations`). The React renderer gets these strings from the server in the schema (`submit`, the wizard's `nextLabel`, `previousLabel` and `stepLabel`), so both drivers show the same text in the request's locale.

Everything else is yours: pass labels, help, headings and options through `__()`. Labels generated from names are translated with `__()` too (`first_name` → `__('First Name')`), as in javaabu/forms, and they're the attribute names in validation messages.

## For review

These translations should be checked by a Dhivehi speaker:

- `submit`: ސޭވް ކުރައްވާ (a loanword; ރައްކާކުރައްވާ is an alternative)
- `step`: ސްޓެޕް :current / :total
- `command_created`, `command_exists`, `command_use`, `command_register`: developer-facing command output

# Thaana

`->thaana()` on text inputs and textareas types Thaana from a Latin keyboard, right to left (`dir="rtl" lang="dv"`). It's ignored on email, password, URL, phone and number inputs. Translatable fields turn it on for Dhivehi inputs by themselves.

# RTL

Stencil's stylesheet uses logical properties (`margin-block-end`, `padding-inline`, `inline-size`), and grids follow the page's direction, so forms work in RTL pages without changes. The javaabu/forms components inside handle their own RTL.
