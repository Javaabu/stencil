import { render, screen } from "@testing-library/react"
import { describe, expect, it } from "vitest"

import { StencilForm, type StencilSchema } from "@javaabu/stencil"

const input = (locale: string, primary: boolean, label: string, native: string) => ({
  field: "title",
  name: `title_${locale}`,
  locale,
  direction: locale === "dv" ? ("rtl" as const) : ("ltr" as const),
  thaana: locale === "dv",
  primary,
  label,
  language: { native_name: native },
})

function schema(layout: "tabs" | "stacked"): StencilSchema {
  return {
    stencil: 1,
    id: "post",
    method: "POST",
    operation: "edit",
    state: { title: "Welcome", title_en: "Welcome", title_dv: "މަރުޙަބާ" },
    live: null,
    components: [
      {
        type: "text",
        key: "title",
        name: "title",
        component: "Text",
        props: { label: "Title", required: true },
        translatable: {
          layout,
          inputs: [input("en", true, "Title (English)", "English"), input("dv", false, "Title (ދިވެހި)", "ދިވެހި")],
        },
      },
    ],
  }
}

describe("translatable fields", () => {
  it("renders one input per locale in tabs", () => {
    const { container } = render(<StencilForm schema={schema("tabs")} />)

    expect(screen.getByRole("tab", { name: "English" })).toBeInTheDocument()
    expect(screen.getByRole("tab", { name: "ދިވެހި" })).toBeInTheDocument()
    expect(container.querySelector('[name="title_en"]')).toHaveValue("Welcome")
    expect(container.querySelector('[name="title_en"]')).toBeRequired()

    const dhivehi = container.querySelector('[name="title_dv"]') as HTMLInputElement
    expect(dhivehi).toHaveValue("މަރުޙަބާ")
    expect(dhivehi).toHaveAttribute("dir", "rtl")
    expect(dhivehi).toHaveAttribute("lang", "dv")
    expect(dhivehi).not.toBeRequired()
    expect(container.querySelector('[name="title"]')).toBeNull()
  })

  it("stacks the inputs", () => {
    const { container } = render(<StencilForm schema={schema("stacked")} />)

    expect(container.querySelector('[data-stencil-translatable="stacked"]')).not.toBeNull()
    expect(screen.queryByRole("tab")).toBeNull()
    expect(screen.getByLabelText(/Title \(ދިވެހި\)/)).toHaveValue("މަރުޙަބާ")
  })
})
