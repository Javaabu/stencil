import type { StencilEvaluation, StencilLiveConfig } from "./types"

/** What the live route returns for the Blade driver. */
export interface StencilHtmlEvaluation {
  html: string
  state: Record<string, unknown>
  changed: string[]
}

export interface EvaluationRequest {
  /** The form to read values from. */
  form: HTMLFormElement
  live: StencilLiveConfig
  /** The name of the field that changed, in dot or bracket notation. */
  changed: string
  /** Its value before the change, sent to afterStateUpdated() as $old. */
  old?: unknown
  driver: "json" | "blade"
  signal?: AbortSignal
}

const RESERVED = ["_method", "_stencil", "_stencil_changed", "_stencil_old", "_stencil_driver"]

/** Field names in dot notation: `address[city]` → `address.city`, `tags[]` → `tags`. */
export function toDotName(name: string): string {
  return name
    .replace(/\[\]$/, "")
    .replace(/\[([^\]]*)\]/g, ".$1")
    .replace(/^\.+|\.+$/g, "")
}

/**
 * The body of a live request: the form's values without files or method spoofing
 * (the live route is a POST), and stencil's own fields.
 */
export function evaluationBody({ form, live, changed, old, driver }: Omit<EvaluationRequest, "signal">): FormData {
  const body = new FormData()

  for (const [name, value] of new FormData(form).entries()) {
    if (RESERVED.includes(name)) continue
    if (typeof File !== "undefined" && value instanceof File) continue
    body.append(name, value)
  }

  body.append("_stencil", live.token)
  body.append("_stencil_changed", toDotName(changed))
  body.append("_stencil_driver", driver)

  if (old !== undefined && old !== null) {
    for (const value of Array.isArray(old) ? old : [old]) {
      body.append(Array.isArray(old) ? "_stencil_old[]" : "_stencil_old", String(value))
    }
  }

  return body
}

function cookie(name: string): string | null {
  if (typeof document === "undefined") return null
  const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`))
  return match?.[1] ? decodeURIComponent(match[1]) : null
}

/** Headers Laravel needs for a same-origin JSON POST: the CSRF token from the meta tag or the XSRF cookie. */
export function evaluationHeaders(): Record<string, string> {
  const headers: Record<string, string> = {
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
  }
  const meta = typeof document === "undefined" ? null : document.querySelector('meta[name="csrf-token"]')
  const token = meta?.getAttribute("content")
  if (token) headers["X-CSRF-TOKEN"] = token
  const xsrf = cookie("XSRF-TOKEN")
  if (xsrf) headers["X-XSRF-TOKEN"] = xsrf
  return headers
}

/** Sends the form's state to the live route and returns the re-evaluated form. */
export async function evaluate(request: EvaluationRequest & { driver: "json" }): Promise<StencilEvaluation>
export async function evaluate(request: EvaluationRequest & { driver: "blade" }): Promise<StencilHtmlEvaluation>
export async function evaluate(request: EvaluationRequest): Promise<StencilEvaluation | StencilHtmlEvaluation> {
  const response = await fetch(request.live.url, {
    method: "POST",
    body: evaluationBody(request),
    headers: evaluationHeaders(),
    credentials: "same-origin",
    signal: request.signal,
  })

  if (!response.ok) throw new Error(`[stencil] The live route returned ${response.status}.`)

  return response.json()
}

/** Components whose value changes as the user types: they wait for typing to stop. */
export const TYPED_COMPONENTS = ["Text", "Email", "Password", "Number", "Tel", "Url", "Textarea", "Wysiwyg", "Input"]
