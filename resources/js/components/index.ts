import type { StencilComponents } from "../context"
import { Field, OptionsField } from "./field"
import { Alert, Fieldset, Group, Section, Tabs, TextEntry } from "./layout"
import { TranslatableField } from "./translatable"
import { Wizard } from "./wizard"

export { Field, OptionsField, optionId } from "./field"
export { Alert, Fieldset, Group, Section, Tabs, TextEntry } from "./layout"
export { Wizard, reportStepValidity } from "./wizard"
export { TranslatableField, translatableInputNode, type TranslatableInput } from "./translatable"

/** The components for stencil's built-in node types. `field` renders any node with a `component`. */
export const builtInComponents: StencilComponents = {
  field: Field,
  group: Group,
  grid: Group,
  tab: Group,
  step: Group,
  section: Section,
  fieldset: Fieldset,
  tabs: Tabs,
  wizard: Wizard,
  alert: Alert,
  "text-entry": TextEntry,
  "checkbox-list": OptionsField,
  radio: OptionsField,
  translatable: TranslatableField,
}
