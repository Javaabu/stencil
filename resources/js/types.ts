/**
 * The JSON format of javaabu/stencil, version 1: what `$form->toArray()` returns
 * and Inertia sends as a prop. Documented in docs/advanced-usage/json-format.md.
 */

export type Breakpoint = "default" | "sm" | "md" | "lg" | "xl"

/** Grid columns per breakpoint, from the smallest up. */
export type Columns = Partial<Record<Breakpoint, number>>

/** Grid columns a component takes, per breakpoint. */
export type Span = Partial<Record<Breakpoint, number | "full">>

/** A condition on another field, checked in the browser with `ConditionalWrapper`. */
export interface StencilCondition {
  effect: "visible" | "hidden" | "enabled" | "disabled"
  /** The watched field's name, in dot notation. */
  field: string
  /** A CSS selector for the watched control: `#type`, `[name="channels[]"]`. */
  selector: string
  /** The values that match, or `null` when the condition watches a checkbox. */
  values: string[] | null
  checkbox: boolean
}

/** Re-evaluate the form on the server when this field changes. */
export interface StencilLive {
  /** Milliseconds to wait after typing; `null` for the form's default on text fields. */
  debounce: number | null
  /** Wait until the field loses focus. */
  onBlur: boolean
}

export interface StencilOption {
  value: string
  label: string
}

export interface StencilNode {
  /** What to render: a built-in type, or one from your registry. */
  type: string
  /** Unique in the form: the field name for fields. */
  key: string
  /** Fields: the input name in bracket notation. */
  name?: string
  /** Fields: the `@javaabu/forms` component to render (`Text`, `Select2`, …). */
  component?: string
  /** camelCase props; for fields, the `@javaabu/forms` props. */
  props?: Record<string, unknown>
  children?: StencilNode[]
  /** Hidden on the server: render nothing. */
  hidden?: boolean
  conditions?: StencilCondition[]
  live?: StencilLive
  span?: Span
  meta?: Record<string, unknown>
  /** One input per locale, when the field is translatable (javaabu/translatable). */
  translatable?: {
    layout: "tabs" | "stacked"
    inputs: {
      field: string
      name: string
      statePath?: string
      locale: string
      direction: "ltr" | "rtl"
      thaana: boolean
      primary: boolean
      label: string
      language?: Record<string, unknown>
    }[]
  }
}

export interface StencilLiveConfig {
  url: string
  token: string
  debounce: number
}

export interface StencilSchema {
  stencil: 1
  id: string
  action?: string
  method: string
  operation: string
  files?: boolean
  /** The submit button's label; no button when missing. */
  submit?: string
  summary?: boolean
  errorBag?: string
  /** Render the fields without a form. */
  embedded?: boolean
  columns?: Columns
  /** Values, nested by field name. */
  state: Record<string, unknown>
  live: StencilLiveConfig | null
  components: StencilNode[]
}

/** What the live route returns. */
export interface StencilEvaluation {
  schema: StencilSchema
  state: Record<string, unknown>
  /** Fields whose value a closure changed with `$set`. */
  changed: string[]
}
