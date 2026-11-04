"use client"

import * as React from "react"
import { Tabs, useFormState } from "@javaabu/forms"

import type { StencilComponentProps } from "../context"
import type { StencilNode } from "../types"
import { Field } from "./field"

/** One input of a translatable field, as javaabu/translatable serializes a LocaleInput. */
export interface TranslatableInput {
  field: string
  name: string
  locale: string
  direction: "ltr" | "rtl"
  thaana: boolean
  primary: boolean
  label: string
  language?: { native_name?: string; name?: string; code?: string }
}

/** The props of one input: its name, label, direction and Thaana; required only in the primary locale. */
export function translatableInputNode(node: StencilNode, input: TranslatableInput): StencilNode {
  const props = { ...(node.props ?? {}) }
  props.label = input.label
  props.dir = input.direction
  props.lang = input.locale
  if (!input.primary) delete props.required
  if (input.thaana && (node.component === "Text" || node.component === "Textarea")) props.thaana = true
  return { ...node, key: input.name, name: input.name, props, translatable: undefined }
}

/**
 * A translatable field (javaabu/translatable): one `@javaabu/forms` field per
 * locale, in tabs or stacked. The tab of the first invalid input opens.
 * Register your own component as `translatable` to replace it.
 */
export function TranslatableField({ node, renderChildren }: StencilComponentProps) {
  const { layout = "tabs", inputs = [] } = (node.translatable ?? {}) as {
    layout?: string
    inputs?: TranslatableInput[]
  }
  const errors = useFormState()?.errors
  const base = node.key.replace(/\./g, "-")

  const fields = inputs.map((input) => (
    <Field key={input.name} node={translatableInputNode(node, input)} renderChildren={renderChildren} />
  ))

  if (layout === "stacked" || inputs.length < 2) {
    return (
      <div className="stencil-translatable" data-stencil-translatable="stacked">
        {fields}
      </div>
    )
  }

  const invalid = inputs.find((input) => errors && Object.keys(errors).some((key) => key === input.name))

  return (
    <Tabs
      key={invalid?.name ?? "tabs"}
      className="stencil-translatable mb-4"
      active={`${base}-${(invalid ?? inputs[0])?.locale}`}
      tabs={inputs.map((input, index) => ({
        name: `${base}-${input.locale}`,
        title: input.language?.native_name ?? input.locale,
        content: fields[index],
      }))}
    />
  )
}
