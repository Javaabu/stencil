// The React renderer of javaabu/stencil. Apps import it as @javaabu/stencil,
// through javaabu/manifest's Vite plugin.

export { StencilForm, StencilFields, type StencilFormProps, type StencilFieldsProps } from "./StencilForm"
export {
  StencilProvider,
  useStencil,
  type StencilProviderProps,
  type StencilComponent,
  type StencilComponentProps,
  type StencilComponents,
  type StencilContextValue,
} from "./context"
export { Children, StencilNodeRenderer, conditionProps } from "./render"
export {
  builtInComponents,
  Field,
  OptionsField,
  Group,
  Section,
  Fieldset,
  Tabs,
  Wizard,
  Alert,
  TextEntry,
  optionId,
} from "./components"
export { evaluate, evaluationBody, evaluationHeaders, toDotName, type StencilHtmlEvaluation } from "./live"
export { columnsStyle, spanStyle, replacePlaceholders } from "./layout"
export { StencilStyles, stencilCss } from "./styles"
export type * from "./types"
