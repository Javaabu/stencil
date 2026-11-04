"use client"

import * as React from "react"
import { Alert as FormsAlert, Card, Tabs as FormsTabs, TextEntry as FormsTextEntry } from "@javaabu/forms"

import type { StencilComponentProps } from "../context"

type Props = Record<string, unknown>

function props(node: StencilComponentProps["node"]): Props {
  return node.props ?? {}
}

/** `group`, `grid`, `tab`, `step`: the children, in a grid when the node has columns. */
export function Group({ node, renderChildren }: StencilComponentProps) {
  return <>{renderChildren(node.children, props(node).columns)}</>
}

/** `section`: a card with the heading and description. */
export function Section({ node, renderChildren }: StencilComponentProps) {
  const { heading, description, columns } = props(node) as { heading?: string; description?: string; columns?: unknown }
  return (
    <Card title={heading} subtitle={description} className="stencil-section mb-4">
      {renderChildren(node.children, columns)}
    </Card>
  )
}

/** `fieldset`: a fieldset with a legend. */
export function Fieldset({ node, renderChildren }: StencilComponentProps) {
  const { label, columns } = props(node) as { label?: string; columns?: unknown }
  return (
    <fieldset className="stencil-fieldset">
      {label ? <legend className="stencil-legend">{label}</legend> : null}
      {renderChildren(node.children, columns)}
    </fieldset>
  )
}

/** `tabs`: `@javaabu/forms` Tabs, one per visible `tab` child. */
export function Tabs({ node, renderChildren }: StencilComponentProps) {
  const tabs = (node.children ?? [])
    .filter((tab) => !tab.hidden)
    .map((tab) => ({
      name: tab.key,
      title: (tab.props?.label as string | undefined) ?? tab.key,
      content: renderChildren([tab]),
    }))
  return <FormsTabs tabs={tabs} active={props(node).active as string | undefined} className="stencil-tabs mb-4" />
}

/** `alert`: `@javaabu/forms` Alert with the message. */
export function Alert({ node }: StencilComponentProps) {
  const { message, ...rest } = props(node) as {
    message?: React.ReactNode
    type?: string
    heading?: string
    icon?: string
  }
  return <FormsAlert {...rest}>{message}</FormsAlert>
}

/** `text-entry`: a read-only value. */
export function TextEntry({ node }: StencilComponentProps) {
  return <FormsTextEntry {...(props(node) as Record<string, never>)} model={false} />
}
