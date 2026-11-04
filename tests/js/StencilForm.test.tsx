import { act, fireEvent, render, screen, waitFor } from "@testing-library/react"
import userEvent from "@testing-library/user-event"
import { afterEach, describe, expect, it, vi } from "vitest"

import {
  StencilFields,
  StencilForm,
  StencilProvider,
  type StencilComponentProps,
  type StencilNode,
  type StencilSchema,
} from "@javaabu/stencil"

import fixture from "./fixtures/kitchen-sink.json"

const schema = fixture as unknown as StencilSchema

function clone(): StencilSchema {
  return structuredClone(schema)
}

function findNode(nodes: StencilNode[], key: string): StencilNode | undefined {
  for (const node of nodes) {
    if (node.key === key) return node
    const found = findNode(node.children ?? [], key)
    if (found) return found
  }
  return undefined
}

afterEach(() => {
  vi.restoreAllMocks()
  vi.useRealTimers()
})

describe("StencilForm", () => {
  it("renders the schema with @javaabu/forms, bound to the state", () => {
    const { container } = render(<StencilForm schema={schema} />)

    const form = container.querySelector("form")!
    expect(form).toHaveAttribute("id", "kitchen-sink-form")
    expect(form).toHaveAttribute("action", "/kitchen-sink")

    expect(screen.getByText("ތަފްޞީލް")).toBeInTheDocument()
    expect(screen.getByText("ކުންފުނީގެ މަޢުލޫމާތު")).toBeInTheDocument()
    expect(screen.getByLabelText(/ސުރުހީ/, { selector: "input" })).toHaveValue("ރާއްޖެ")
    expect(screen.getByLabelText(/ސުރުހީ/, { selector: "input" })).toBeRequired()
    expect(container.querySelector('select[name="type"]')).toHaveValue("person")
    // Hidden until the wizard's last step.
    expect(screen.getByRole("button", { name: "Save", hidden: true })).toBeInTheDocument()
  })

  it("leaves out components hidden on the server", () => {
    const { container } = render(<StencilForm schema={schema} />)

    expect(container.querySelector('[name="company_name"]')).toBeNull()
  })

  it("lays components out in a grid with spans", () => {
    const { container } = render(<StencilForm schema={schema} />)

    const grid = container.querySelector(".stencil-grid") as HTMLElement
    expect(grid.style.getPropertyValue("--stencil-cols-md")).toBe("2")

    const note = container.querySelector('[data-stencil-key="note"]') as HTMLElement
    expect(note.style.getPropertyValue("--stencil-span-md")).toBe("1 / -1")
  })

  it("applies conditions in the browser", async () => {
    const user = userEvent.setup()
    const { container } = render(<StencilForm schema={{ ...clone(), live: null }} />)

    const idCard = container.querySelector('[name="id_card"]') as HTMLInputElement
    expect(idCard.closest("fieldset")).not.toHaveAttribute("hidden")

    await user.selectOptions(container.querySelector('select[name="type"]')!, "company")

    await waitFor(() => expect(idCard.closest("fieldset")).toHaveAttribute("hidden"))
  })

  it("renders checkbox lists and radios with one control per option", () => {
    const { container } = render(<StencilForm schema={schema} />)

    expect(container.querySelector("#channels-email")).toBeChecked()
    expect(container.querySelector("#channels-sms")).not.toBeChecked()
    expect(container.querySelector("#size-l")).toBeChecked()
    expect(container.querySelectorAll('input[name="channels[]"]')).toHaveLength(2)
    expect(container.querySelector("#subscribe")).toBeChecked()
  })

  it("renders tabs, alerts and text entries", () => {
    render(<StencilForm schema={schema} />)

    expect(screen.getByRole("tab", { name: "Extra" })).toBeInTheDocument()
    expect(screen.getByText("ސަމާލުކަމަށް")).toBeInTheDocument()
    expect(screen.getByText("ސުރުހީ (ބަލާލުމަށް)")).toBeInTheDocument()
  })

  it("steps through a wizard and shows the submit button on the last step", async () => {
    const user = userEvent.setup()
    const { container } = render(<StencilForm schema={schema} />)

    const panels = container.querySelectorAll(".stencil-wizard-panel")
    expect(panels[0]).not.toHaveAttribute("hidden")
    expect(panels[1]).toHaveAttribute("hidden")
    expect(container.querySelector("[data-stencil-submit]")).toHaveAttribute("hidden")
    expect(panels[0]).toHaveAttribute("aria-label", "Step 1 of 2")

    // The step's required email stops Next.
    await user.click(screen.getByRole("button", { name: "Next" }))
    expect(panels[1]).toHaveAttribute("hidden")

    await user.type(container.querySelector('[name="email"]')!, "ali@example.mv")
    await user.click(screen.getByRole("button", { name: "Next" }))

    expect(panels[1]).not.toHaveAttribute("hidden")
    expect(container.querySelector("[data-stencil-submit]")).not.toHaveAttribute("hidden")

    await user.click(screen.getByRole("button", { name: "Back" }))
    expect(panels[0]).not.toHaveAttribute("hidden")
  })

  it("renders custom types from the registry", () => {
    function Rating({ node }: StencilComponentProps) {
      return <output data-testid="rating">{`${node.name}:${node.props?.max}`}</output>
    }

    const custom = clone()
    custom.components.push({ type: "rating", key: "stars", name: "stars", props: { max: 5 } })

    render(
      <StencilProvider components={{ rating: Rating }}>
        <StencilForm schema={custom} />
      </StencilProvider>,
    )

    expect(screen.getByTestId("rating")).toHaveTextContent("stars:5")
  })

  it("lets a form replace a built-in component", () => {
    const Section = ({ node }: StencilComponentProps) => <h2>{String(node.props?.heading)}!</h2>

    render(<StencilForm schema={schema} components={{ section: Section }} />)

    expect(screen.getByRole("heading", { name: "ތަފްޞީލް!" })).toBeInTheDocument()
  })

  it("replaces or removes the submit button", () => {
    const { rerender } = render(<StencilForm schema={schema} submit={<button type="submit">ފޮނުވާ</button>} />)
    expect(screen.getByRole("button", { name: "ފޮނުވާ", hidden: true })).toBeInTheDocument()

    rerender(<StencilForm schema={schema} submit={false} />)
    expect(screen.queryByRole("button", { name: "Save", hidden: true })).toBeNull()
  })

  it("re-evaluates the form when a live field changes", async () => {
    const evaluated = clone()
    evaluated.state.type = "company"
    const node = findNode(evaluated.components, "company_name")!
    Object.assign(node, { component: "Text", props: { label: "ކުންފުނީގެ ނަން" }, hidden: undefined })

    const fetch = vi.fn(
      async () =>
        new Response(JSON.stringify({ schema: evaluated, state: evaluated.state, changed: [] }), {
          headers: { "Content-Type": "application/json" },
        }),
    )
    vi.stubGlobal("fetch", fetch)
    const onEvaluated = vi.fn()

    const user = userEvent.setup()
    const { container } = render(<StencilForm schema={schema} onEvaluated={onEvaluated} />)

    await user.selectOptions(container.querySelector('select[name="type"]')!, "company")

    await waitFor(() => expect(container.querySelector('[name="company_name"]')).not.toBeNull())

    expect(fetch).toHaveBeenCalledTimes(1)
    const [url, init] = fetch.mock.calls[0] as unknown as [string, RequestInit]
    const body = init.body as FormData
    expect(url).toBe("/stencil/evaluate")
    expect(body.get("_stencil")).toBe("test-token")
    expect(body.get("_stencil_changed")).toBe("type")
    expect(body.get("_stencil_old")).toBe("person")
    expect(body.get("_stencil_driver")).toBe("json")
    expect(body.get("type")).toBe("company")
    expect(body.get("_method")).toBeNull()
    expect(onEvaluated).toHaveBeenCalledOnce()
  })

  it("applies values the server set, and debounces typing", async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })

    const evaluated = clone()
    evaluated.state.slug = "raajje"

    const fetch = vi.fn(
      async () =>
        new Response(JSON.stringify({ schema: evaluated, state: evaluated.state, changed: ["slug"] }), {
          headers: { "Content-Type": "application/json" },
        }),
    )
    vi.stubGlobal("fetch", fetch)

    const { container } = render(<StencilForm schema={schema} />)
    const title = container.querySelector('[name="title"]') as HTMLInputElement

    fireEvent.change(title, { target: { value: "R" } })
    fireEvent.change(title, { target: { value: "Raajje" } })

    expect(fetch).not.toHaveBeenCalled()

    await act(async () => {
      await vi.advanceTimersByTimeAsync(600)
    })

    await waitFor(() => expect(container.querySelector('[name="slug"]')).toHaveValue("raajje"))
    expect(fetch).toHaveBeenCalledTimes(1)
    expect((fetch.mock.calls[0] as unknown as [string, RequestInit])[1].body).toBeInstanceOf(FormData)
  })

  it("reports a failed evaluation and keeps the schema", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async () => new Response("", { status: 403 })),
    )
    const onEvaluationError = vi.fn()

    const user = userEvent.setup()
    const { container } = render(<StencilForm schema={schema} onEvaluationError={onEvaluationError} />)

    await user.selectOptions(container.querySelector('select[name="type"]')!, "company")

    await waitFor(() => expect(onEvaluationError).toHaveBeenCalledOnce())
    expect(container.querySelector('[name="id_card"]')).not.toBeNull()
  })

  it("takes a new schema from the server", () => {
    const { container, rerender } = render(<StencilForm schema={schema} />)

    const next = clone()
    next.state.title = "އާ ސުރުހީ"
    rerender(<StencilForm schema={next} />)

    expect(container.querySelector('[name="title"]')).toHaveValue("އާ ސުރުހީ")
  })
})

describe("StencilFields", () => {
  it("renders the fields without a form", () => {
    const { container } = render(
      <form>
        <StencilFields schema={{ ...clone(), embedded: true }} />
      </form>,
    )

    expect(container.querySelectorAll("form")).toHaveLength(1)
    expect(container.querySelector('[name="title"]')).toHaveValue("ރާއްޖެ")
  })

  it("is what StencilForm renders for an embedded schema", () => {
    const { container } = render(<StencilForm schema={{ ...clone(), embedded: true }} />)

    expect(container.querySelector("form")).toBeNull()
    expect(container.querySelector('[name="title"]')).toHaveValue("ރާއްޖެ")
  })
})
