"use client"

import * as React from "react"
import { Form, Model, Submit, useWatchField, type FormAction, type FormProps } from "@javaabu/forms"

import { builtInComponents } from "./components"
import {
  StencilContextProvider,
  useRegisteredComponents,
  type StencilComponents,
  type StencilContextValue,
} from "./context"
import { evaluate, TYPED_COMPONENTS } from "./live"
import { Children } from "./render"
import { StencilStyles } from "./styles"
import type { StencilEvaluation, StencilNode, StencilSchema } from "./types"
import { WizardSubmitProvider } from "./wizard-context"

function liveNodes(nodes: StencilNode[]): StencilNode[] {
  return nodes.flatMap((node) => [
    ...(node.live && node.name && !node.hidden ? [node] : []),
    ...(node.hidden ? [] : liveNodes(node.children ?? [])),
  ])
}

function useStencilContext(schema: StencilSchema, components?: StencilComponents): StencilContextValue {
  const registered = useRegisteredComponents()
  return React.useMemo(
    () => ({ schema, components: { ...builtInComponents, ...registered, ...components } }),
    [schema, registered, components],
  )
}

export interface StencilFieldsProps {
  /** The form from PHP: `$form->toArray()`, or an Inertia prop. */
  schema: StencilSchema
  /** Components for node types, merged over `StencilProvider`'s and the built-ins. */
  components?: StencilComponents
}

/**
 * Renders a schema's components without a form, bound to its state: for fields
 * inside a form of your own (`->embedded()` in PHP).
 */
export function StencilFields({ schema, components }: StencilFieldsProps) {
  const context = useStencilContext(schema, components)
  return (
    <StencilContextProvider value={context}>
      <StencilStyles />
      <Model model={schema.state}>
        <Children nodes={schema.components} columns={schema.columns} />
      </Model>
    </StencilContextProvider>
  )
}

export interface StencilFormProps extends Omit<
  FormProps,
  "action" | "method" | "model" | "files" | "children" | "summary" | "errorBag"
> {
  /** The form from PHP: `$form->toArray()`, or an Inertia prop. */
  schema: StencilSchema
  /** Components for node types, merged over `StencilProvider`'s and the built-ins. */
  components?: StencilComponents
  /** Where to submit instead of the schema's action: a URL or a Wayfinder route. */
  action?: FormAction
  method?: string
  /** Rendered after the components, before the submit button. */
  children?: React.ReactNode
  /** Replaces the submit button; `false` for none. */
  submit?: React.ReactNode | false
  /** Called after a live field re-evaluated the form. */
  onEvaluated?: (evaluation: StencilEvaluation) => void
  /** Called when the live route fails. The form keeps its current schema. */
  onEvaluationError?: (error: unknown) => void
}

/**
 * Renders a stencil form with `@javaabu/forms`: `<StencilForm schema={form} />`.
 * Submits like `Form` (set `router={inertia}` on `FormsProvider` for Inertia).
 * Live fields send the form's values to the server when they change, and the
 * form re-renders with what comes back.
 */
export function StencilForm({
  schema: initial,
  components,
  action,
  method,
  children,
  submit,
  onEvaluated,
  onEvaluationError,
  ...formProps
}: StencilFormProps) {
  const [schema, setSchema] = React.useState(initial)
  const [submitHidden, setSubmitHidden] = React.useState(false)
  const [previous, setPrevious] = React.useState(initial)

  // A new schema from the server (an Inertia visit) replaces the evaluated one.
  if (previous !== initial) {
    setPrevious(initial)
    setSchema(initial)
  }

  const context = useStencilContext(schema, components)
  const anchor = React.useRef<HTMLSpanElement>(null)
  const controller = React.useRef<AbortController | null>(null)
  const ignored = React.useRef(new Set<string>())

  const reevaluate = React.useCallback(
    async (name: string, old: unknown) => {
      const form = anchor.current?.closest("form")
      if (!form || !schema.live) return

      controller.current?.abort()
      const abort = new AbortController()
      controller.current = abort

      try {
        const evaluation = await evaluate({
          form,
          live: schema.live,
          changed: name,
          old,
          driver: "json",
          signal: abort.signal,
        })
        if (abort.signal.aborted) return
        for (const path of evaluation.changed) ignored.current.add(path)
        setSchema(evaluation.schema)
        onEvaluated?.(evaluation)
      } catch (error) {
        if (abort.signal.aborted) return
        if (onEvaluationError) onEvaluationError(error)
        else console.error(error)
      }
    },
    [schema.live, onEvaluated, onEvaluationError],
  )

  React.useEffect(() => () => controller.current?.abort(), [])

  const live = schema.live ? liveNodes(schema.components) : []
  const button = submit === undefined ? schema.submit ? <Submit>{schema.submit}</Submit> : null : submit || null

  if (schema.embedded) return <StencilFields schema={schema} components={components} />

  return (
    <StencilContextProvider value={context}>
      <WizardSubmitProvider value={setSubmitHidden}>
        <Form
          id={schema.id}
          className="stencil-form"
          {...formProps}
          action={action ?? schema.action}
          method={method ?? schema.method}
          model={schema.state}
          files={schema.files}
          summary={schema.summary}
          errorBag={schema.errorBag}
        >
          <StencilStyles />
          <span ref={anchor} hidden />
          {live.map((node) => (
            <LiveWatcher
              key={node.key}
              node={node}
              debounce={
                node.live?.debounce ??
                (TYPED_COMPONENTS.includes(node.component ?? "") ? (schema.live?.debounce ?? 500) : 0)
              }
              ignored={ignored}
              onChange={reevaluate}
            />
          ))}
          <Children nodes={schema.components} columns={schema.columns} />
          {children}
          {button ? (
            <div className="stencil-submit" data-stencil-submit="" hidden={submitHidden}>
              {button}
            </div>
          ) : null}
        </Form>
      </WizardSubmitProvider>
    </StencilContextProvider>
  )
}

interface LiveWatcherProps {
  node: StencilNode
  debounce: number
  ignored: React.RefObject<Set<string>>
  onChange: (name: string, old: unknown) => void
}

/** Watches one live field and reports its changes, debounced, or when it loses focus. */
function LiveWatcher({ node, debounce, ignored, onChange }: LiveWatcherProps) {
  const name = node.name as string
  const watched = useWatchField(name)
  const current = JSON.stringify([watched.value, watched.checked])
  const last = React.useRef<{ value: string; raw: unknown } | null>(null)
  const pending = React.useRef<{ old: unknown } | null>(null)
  const timer = React.useRef<ReturnType<typeof setTimeout> | null>(null)
  const dotName = node.key

  React.useEffect(() => {
    // Until the field has registered its value, there is nothing to compare with.
    if (!watched.found) return

    const before = last.current
    last.current = { value: current, raw: watched.value }
    if (!before || before.value === current) return

    if (ignored.current.has(dotName)) {
      ignored.current.delete(dotName)
      return
    }

    pending.current = { old: pending.current?.old ?? before.raw }
    if (node.live?.onBlur) return

    if (timer.current) clearTimeout(timer.current)
    timer.current = setTimeout(() => {
      const change = pending.current
      pending.current = null
      if (change) onChange(name, change.old)
    }, debounce)
  }, [current, watched.found, watched.value, debounce, dotName, ignored, name, node.live?.onBlur, onChange])

  React.useEffect(() => {
    if (!node.live?.onBlur) return
    const listener = (event: FocusEvent) => {
      const target = event.target as HTMLInputElement | null
      if (target?.name !== name || !pending.current) return
      const change = pending.current
      pending.current = null
      onChange(name, change.old)
    }
    document.addEventListener("focusout", listener)
    return () => document.removeEventListener("focusout", listener)
  }, [name, node.live?.onBlur, onChange])

  React.useEffect(
    () => () => {
      if (timer.current) clearTimeout(timer.current)
    },
    [],
  )

  return null
}
