"use client"

import * as React from "react"
import { ConditionalWrapper } from "@javaabu/forms"

import { useStencil, type StencilComponentProps } from "./context"
import { columnsStyle, spanStyle } from "./layout"
import type { Columns, StencilCondition, StencilNode } from "./types"

/** `ConditionalWrapper` props for a condition, as the Blade driver passes them. */
export function conditionProps(condition: StencilCondition) {
  const values = condition.values ?? []
  return {
    enableElem: condition.selector,
    enableValue: condition.checkbox ? 1 : values.length === 1 ? values[0] : values,
    enableCheckbox: condition.checkbox || undefined,
    hideFields: condition.effect === "visible" || condition.effect === "hidden" || undefined,
    disable: condition.effect === "hidden" || condition.effect === "disabled" || undefined,
  }
}

/** Renders nodes, in a stencil grid when `columns` is given. */
export function Children({ nodes, columns }: { nodes: StencilNode[]; columns?: Columns | null }) {
  const items = nodes.map((node) => <StencilNodeRenderer key={node.key} node={node} />)
  if (!columns) return <>{items}</>
  return (
    <div className="stencil-grid" style={columnsStyle(columns)}>
      {items}
    </div>
  )
}

/**
 * Renders one node with the component registered for its type, wrapped for
 * grid spans and conditions. Hidden nodes render nothing.
 */
export function StencilNodeRenderer({ node }: { node: StencilNode }) {
  const { components } = useStencil()

  if (node.hidden) return null

  const Component =
    (node.translatable ? components.translatable : undefined) ??
    components[node.type] ??
    (node.component ? components.field : undefined) ??
    components.group

  const renderChildren: StencilComponentProps["renderChildren"] = (nodes = node.children ?? [], columns) => (
    <Children nodes={nodes} columns={(columns as Columns | null | undefined) ?? null} />
  )

  let content: React.ReactNode = Component ? <Component node={node} renderChildren={renderChildren} /> : null

  for (const condition of [...(node.conditions ?? [])].reverse()) {
    content = <ConditionalWrapper {...conditionProps(condition)}>{content}</ConditionalWrapper>
  }

  return (
    <div className="stencil-item" data-stencil-key={node.key} style={node.span ? spanStyle(node.span) : undefined}>
      {content}
    </div>
  )
}
