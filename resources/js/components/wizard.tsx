"use client"

import * as React from "react"
import { Button, useFormState } from "@javaabu/forms"

import type { StencilComponentProps } from "../context"
import { replacePlaceholders } from "../layout"
import { useWizardSubmit } from "../wizard-context"

const CONTROLS = "input, select, textarea"

/** Checks the controls of a step with the browser's validation, reporting the first problem. */
export function reportStepValidity(panel: HTMLElement | null): boolean {
  if (!panel) return true
  for (const control of panel.querySelectorAll<HTMLInputElement>(CONTROLS)) {
    if (control.disabled || control.closest("[hidden]")) continue
    if (typeof control.checkValidity === "function" && !control.checkValidity()) {
      control.reportValidity?.()
      return false
    }
  }
  return true
}

/**
 * `wizard`: steps shown one at a time with Back and Next. Next checks the step's
 * controls first. When errors come back, the first step with an invalid field opens.
 * Every step stays mounted, so all values submit; the form's submit button shows
 * on the last step.
 */
export function Wizard({ node, renderChildren }: StencilComponentProps) {
  const steps = (node.children ?? []).filter((step) => !step.hidden)
  const {
    startStep = 1,
    nextLabel = "Next",
    previousLabel = "Back",
    stepLabel = "Step :current of :total",
  } = (node.props ?? {}) as { startStep?: number; nextLabel?: string; previousLabel?: string; stepLabel?: string }
  const [current, setCurrent] = React.useState(() =>
    Math.min(Math.max(startStep - 1, 0), Math.max(steps.length - 1, 0)),
  )
  const panels = React.useRef<(HTMLDivElement | null)[]>([])
  const errors = useFormState()?.errors
  const setSubmitHidden = useWizardSubmit()
  const last = current >= steps.length - 1

  React.useEffect(() => {
    setSubmitHidden?.(!last)
    return () => setSubmitHidden?.(false)
  }, [last, setSubmitHidden])

  React.useEffect(() => {
    if (!errors || Object.keys(errors).length === 0) return
    const index = panels.current.findIndex((panel) => panel?.querySelector('[aria-invalid="true"]'))
    if (index >= 0) setCurrent(index)
  }, [errors])

  return (
    <div className="stencil-wizard mb-4" data-stencil-wizard="">
      <ol className="stencil-wizard-steps">
        {steps.map((step, index) => (
          <li
            key={step.key}
            className="stencil-wizard-step"
            aria-current={index === current ? "step" : undefined}
            data-complete={index < current ? "" : undefined}
          >
            <span className="stencil-wizard-number">{index + 1}</span>
            <span className="stencil-wizard-text">
              <span className="stencil-wizard-label">{step.props?.label as string}</span>
              {step.props?.description ? (
                <span className="stencil-wizard-description">{step.props.description as string}</span>
              ) : null}
            </span>
          </li>
        ))}
      </ol>

      {steps.map((step, index) => (
        <div
          key={step.key}
          ref={(element) => {
            panels.current[index] = element
          }}
          className="stencil-wizard-panel"
          role="group"
          aria-label={replacePlaceholders(stepLabel, { current: index + 1, total: steps.length })}
          hidden={index !== current}
        >
          {renderChildren([step])}
        </div>
      ))}

      <div className="stencil-wizard-actions">
        {current > 0 ? (
          <Button color="outline-secondary" onClick={() => setCurrent(current - 1)}>
            {previousLabel}
          </Button>
        ) : null}
        {!last ? (
          <Button
            onClick={() => {
              if (reportStepValidity(panels.current[current] ?? null)) setCurrent(current + 1)
            }}
          >
            {nextLabel}
          </Button>
        ) : null}
      </div>
    </div>
  )
}
