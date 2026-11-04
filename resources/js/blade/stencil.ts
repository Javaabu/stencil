/**
 * javaabu/stencil's script for the Blade driver: wizard steps, and live fields
 * that re-render the form on the server. Conditions (visibleWhen, …) need
 * nothing here: javaabu/forms' conditional-wrapper handles them.
 *
 * Loaded as a script it enhances the page by itself, and forms added later.
 * With a bundler: `import { init } from "@javaabu/stencil/blade/stencil"` and call `init(root)`.
 */
import { evaluate, type StencilHtmlEvaluation } from "../live"
import type { StencilLive, StencilLiveConfig } from "../types"

const KEY = "data-stencil-key"
const INVALID = '[aria-invalid="true"], .is-invalid'
const CONTROLS = "input, select, textarea"
const TYPED = new Set(["text", "email", "password", "number", "tel", "url", "search", "textarea"])

function json<T>(value: string | null | undefined): T | null {
  if (!value) return null
  try {
    return JSON.parse(value) as T
  } catch {
    return null
  }
}

/* -------------------------------------------------------------------------- */
/* Wizard                                                                     */
/* -------------------------------------------------------------------------- */

function stepIsValid(panel: Element): boolean {
  for (const control of panel.querySelectorAll<HTMLInputElement>(CONTROLS)) {
    if (control.disabled || control.closest("[hidden]")) continue
    if (!control.checkValidity()) {
      control.reportValidity()
      return false
    }
  }
  return true
}

/** Shows one step at a time, with Back and Next, and the form's submit button on the last step. */
export function initWizard(wizard: HTMLElement): void {
  if (wizard.dataset.stencilReady !== undefined) return
  wizard.dataset.stencilReady = ""

  const panels = [...wizard.querySelectorAll<HTMLElement>("[data-stencil-wizard-panel]")]
  const steps = [...wizard.querySelectorAll<HTMLElement>("[data-stencil-wizard-nav]")]
  const previous = wizard.querySelector<HTMLElement>("[data-stencil-wizard-previous]")
  const next = wizard.querySelector<HTMLElement>("[data-stencil-wizard-next]")
  const submit = wizard.closest("form")?.querySelector<HTMLElement>("[data-stencil-submit]")

  const show = (index: number) => {
    const current = Math.min(Math.max(index, 0), panels.length - 1)
    panels.forEach((panel, i) => (panel.hidden = i !== current))
    steps.forEach((step, i) => {
      step.toggleAttribute("data-complete", i < current)
      if (i === current) step.setAttribute("aria-current", "step")
      else step.removeAttribute("aria-current")
    })
    if (previous) previous.hidden = current === 0
    if (next) next.hidden = current === panels.length - 1
    if (submit) submit.hidden = current !== panels.length - 1
    wizard.dataset.currentStep = String(current)
  }

  const current = () => Number(wizard.dataset.currentStep ?? 0)

  previous?.addEventListener("click", () => show(current() - 1))
  next?.addEventListener("click", () => {
    const panel = panels[current()]
    if (!panel || stepIsValid(panel)) show(current() + 1)
  })

  const invalid = panels.findIndex((panel) => panel.querySelector(INVALID))
  show(invalid >= 0 ? invalid : Number(wizard.dataset.startStep ?? 0))
}

/* -------------------------------------------------------------------------- */
/* Live fields                                                                */
/* -------------------------------------------------------------------------- */

/** The components directly inside an element (or form), by their stencil key. */
export function keyedChildren(parent: Element): HTMLElement[] {
  const owner = parent.hasAttribute(KEY) ? parent : (parent.parentElement?.closest(`[${KEY}]`) ?? null)
  return [...parent.querySelectorAll<HTMLElement>(`[${KEY}]`)].filter(
    (element) => (element.parentElement?.closest(`[${KEY}]`) ?? null) === owner,
  )
}

/**
 * Replaces the components the server changed: those whose hash differs, and
 * those whose value a closure set. Containers that didn't change keep their
 * element, and their children are compared in turn, so focus and widgets survive.
 */
export function patch(current: Element, next: Element, changed: Set<string>): void {
  const existing = new Map(keyedChildren(current).map((element) => [element.getAttribute(KEY), element]))

  for (const replacement of keyedChildren(next)) {
    const key = replacement.getAttribute(KEY) as string
    const element = existing.get(key)
    if (!element) continue

    if (element.dataset.stencilHash !== replacement.dataset.stencilHash || changed.has(key)) {
      element.replaceWith(document.importNode(replacement, true))
      continue
    }

    patch(element, replacement, changed)
  }
}

function valueOf(form: HTMLFormElement, name: string): unknown {
  const values = new FormData(form).getAll(name)
  if (name.endsWith("[]")) return values.map(String)
  return values.length ? String(values[values.length - 1]) : null
}

/** Sends the form's state to the server when a live field changes, and applies the result. */
export function initLive(form: HTMLFormElement): void {
  if (form.dataset.stencilLiveReady !== undefined) return
  form.dataset.stencilLiveReady = ""

  let controller: AbortController | null = null
  let timer: ReturnType<typeof setTimeout> | null = null
  const values = new Map<string, unknown>()
  const dirty = new Map<string, { name: string; old: unknown }>()

  const config = () => json<StencilLiveConfig>(form.dataset.stencilLive)

  const remember = () => {
    for (const item of form.querySelectorAll<HTMLElement>(`[${KEY}][data-stencil-live]`)) {
      const control = item.querySelector<HTMLInputElement>("[name]")
      if (control) values.set(item.getAttribute(KEY) as string, valueOf(form, control.name))
    }
  }

  const send = async (key: string, name: string, old: unknown) => {
    const live = config()
    if (!live) return

    controller?.abort()
    const abort = new AbortController()
    controller = abort
    form.dataset.stencilLoading = ""

    try {
      const evaluation: StencilHtmlEvaluation = await evaluate({
        form,
        live,
        changed: name,
        old,
        driver: "blade",
        signal: abort.signal,
      })
      if (abort.signal.aborted) return

      const template = document.createElement("template")
      template.innerHTML = evaluation.html
      const next = template.content.querySelector("form")
      if (!next) return

      if (next.dataset.stencilLive) form.dataset.stencilLive = next.dataset.stencilLive
      patch(form, next, new Set(evaluation.changed))
      init(form)
      remember()
      form.dispatchEvent(new CustomEvent("stencil:evaluated", { detail: evaluation, bubbles: true }))
    } catch (error) {
      if (abort.signal.aborted) return
      form.dispatchEvent(new CustomEvent("stencil:error", { detail: error, bubbles: true }))
      console.error(error)
    } finally {
      if (controller === abort) delete form.dataset.stencilLoading
    }
  }

  const handle = (event: Event) => {
    const target = event.target as HTMLInputElement | null
    const item = target?.closest<HTMLElement>(`[${KEY}][data-stencil-live]`)
    if (!target?.name || !item || !form.contains(item)) return

    const key = item.getAttribute(KEY) as string
    const settings = json<StencilLive>(item.dataset.stencilLive) ?? { debounce: null, onBlur: false }
    const typed = TYPED.has(target.type) || target.tagName === "TEXTAREA"

    if (event.type === "focusout") {
      const change = dirty.get(key)
      if (!settings.onBlur || !change) return
      dirty.delete(key)
      void send(key, change.name, change.old)
      return
    }

    if (event.type === "input" && !typed) return

    const value = valueOf(form, target.name)
    const old = values.get(key)
    if (JSON.stringify(value) === JSON.stringify(old)) return

    if (!dirty.has(key)) dirty.set(key, { name: target.name, old })
    values.set(key, value)

    if (settings.onBlur) return

    const change = dirty.get(key) as { name: string; old: unknown }

    const wait = typed && event.type === "input" ? (settings.debounce ?? config()?.debounce ?? 500) : 0

    if (timer) clearTimeout(timer)
    timer = setTimeout(() => {
      dirty.delete(key)
      void send(key, change.name, change.old)
    }, wait)
  }

  form.addEventListener("input", handle)
  form.addEventListener("change", handle)
  form.addEventListener("focusout", handle)

  remember()
}

export function init(root: ParentNode = document): void {
  root.querySelectorAll<HTMLElement>("[data-stencil-wizard]").forEach(initWizard)
  if (root instanceof HTMLFormElement && root.dataset.stencilLive) initLive(root)
  root.querySelectorAll<HTMLFormElement>("form[data-stencil-live]").forEach(initLive)
}

function autoInit(): void {
  if (typeof document === "undefined") return

  const start = () => {
    init(document)
    new MutationObserver((records) => {
      for (const record of records) {
        for (const node of record.addedNodes) {
          if (node instanceof Element) init(node)
        }
      }
    }).observe(document.body, { childList: true, subtree: true })
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", start, { once: true })
  else start()
}

autoInit()
