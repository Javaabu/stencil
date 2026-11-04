"use client"

import * as React from "react"
import * as Forms from "@javaabu/forms"

import type { StencilComponentProps } from "../context"

type AnyComponent = React.ComponentType<Record<string, unknown>>

/** A field: the `@javaabu/forms` component named by `node.component`, with the node's props. */
export function Field({ node }: StencilComponentProps) {
  const Component = (Forms as unknown as Record<string, AnyComponent | undefined>)[node.component ?? ""]

  if (!Component) {
    console.warn(`[stencil] @javaabu/forms has no component "${node.component}" for the field "${node.key}".`)
    return null
  }

  return <Component name={node.name} {...node.props} />
}

/** The id of one option of a group: `channels[]` + `sms` → `channels-sms`. Matches the Blade driver. */
export function optionId(name: string, value: string): string {
  const base = name.replace(/\[\]$/, "").replace(/\[/g, "-").replace(/\]/g, "")
  const slug = value
    .normalize("NFKD")
    .replace(/[̀-ͯ]/g, "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
  return `${base}-${slug || value}`
}

/** `checkbox-list` and `radio`: one control per option, with one label, help and error list. */
export function OptionsField({ node }: StencilComponentProps) {
  const name = node.name ?? node.key
  const {
    options = [],
    columns,
    label,
    required,
    inline,
    showLabel,
    help,
    disabled,
  } = (node.props ?? {}) as {
    options?: { value: string; label: string }[]
    columns?: Record<string, number> | null
    label?: string
    required?: boolean
    inline?: boolean
    showLabel?: boolean
    help?: React.ReactNode
    disabled?: boolean
  }
  const radio = node.type === "radio"
  const Control = (radio ? Forms.Radio : Forms.Checkbox) as unknown as AnyComponent
  const style: Record<string, string> = {}
  for (const [breakpoint, count] of Object.entries(columns ?? {})) {
    style[`--stencil-cols${breakpoint === "default" ? "" : `-${breakpoint}`}`] = String(count)
  }

  return (
    <Forms.FormGroup
      name={name.replace(/\[\]$/, "")}
      label={label}
      required={required}
      inline={inline}
      showLabel={showLabel}
      help={help}
    >
      <div
        className={columns ? "stencil-options stencil-grid" : "stencil-options"}
        role={radio ? "radiogroup" : "group"}
        style={columns ? (style as React.CSSProperties) : undefined}
      >
        {options.map((option) => (
          <Control
            key={option.value}
            name={name}
            id={optionId(name, option.value)}
            value={option.value}
            label={option.label}
            showLabel={false}
            showErrors={false}
            disabled={disabled}
          />
        ))}
      </div>
    </Forms.FormGroup>
  )
}
