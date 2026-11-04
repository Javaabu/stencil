"use client"

import css from "../css/stencil.css?raw"

/** stencil's stylesheet (grids, fieldsets, the wizard), added to the page once. */
export function StencilStyles() {
  return (
    <style href="javaabu-stencil" precedence="default">
      {css}
    </style>
  )
}

export { css as stencilCss }
