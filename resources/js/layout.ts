import type * as React from "react"

import type { Columns, Span } from "./types"

function suffix(breakpoint: string): string {
  return breakpoint === "default" ? "" : `-${breakpoint}`
}

/** CSS variables for a grid's columns, read by stencil's stylesheet. */
export function columnsStyle(columns: Columns): React.CSSProperties {
  const style: Record<string, string> = {}
  for (const [breakpoint, count] of Object.entries(columns))
    style[`--stencil-cols${suffix(breakpoint)}`] = String(count)
  return style as React.CSSProperties
}

/** CSS variables for the columns a component spans. */
export function spanStyle(span: Span): React.CSSProperties {
  const style: Record<string, string> = {}
  for (const [breakpoint, value] of Object.entries(span)) {
    style[`--stencil-span${suffix(breakpoint)}`] = value === "full" ? "1 / -1" : `span ${value} / span ${value}`
  }
  return style as React.CSSProperties
}

/** Replaces `:name` placeholders, like Laravel's `__()`. */
export function replacePlaceholders(line: string, replace: Record<string, string | number>): string {
  return Object.entries(replace).reduce((text, [key, value]) => text.replaceAll(`:${key}`, String(value)), line)
}
