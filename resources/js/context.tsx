"use client"

import * as React from "react"

import type { StencilNode, StencilSchema } from "./types"

/** What a component registered for a node type receives. */
export interface StencilComponentProps {
  node: StencilNode
  /** Renders child nodes (or `node.children`), in a grid when `columns` is given. */
  renderChildren: (nodes?: StencilNode[], columns?: unknown) => React.ReactNode
}

export type StencilComponent = React.ComponentType<StencilComponentProps>

/** Components by node type: built-ins, then yours. */
export type StencilComponents = Record<string, StencilComponent>

export interface StencilContextValue {
  schema: StencilSchema
  components: StencilComponents
}

const StencilContext = React.createContext<StencilContextValue | null>(null)

export const StencilContextProvider = StencilContext.Provider

/** The schema being rendered and the component registry, for custom components. */
export function useStencil(): StencilContextValue {
  const value = React.useContext(StencilContext)
  if (!value) throw new Error("useStencil() must be used inside <StencilForm> or <StencilFields>.")
  return value
}

const RegistryContext = React.createContext<StencilComponents>({})

export interface StencilProviderProps {
  /** Components for custom node types, or replacements for built-ins: `{ rating: RatingField }`. */
  components: StencilComponents
  children?: React.ReactNode
}

/**
 * Registers components for every `StencilForm` inside: put it at the root of the
 * app next to `FormsProvider`. Nested providers merge; a form's own `components` win.
 */
export function StencilProvider({ components, children }: StencilProviderProps) {
  const parent = React.useContext(RegistryContext)
  const merged = React.useMemo(() => ({ ...parent, ...components }), [parent, components])
  return <RegistryContext.Provider value={merged}>{children}</RegistryContext.Provider>
}

export function useRegisteredComponents(): StencilComponents {
  return React.useContext(RegistryContext)
}
