import { afterEach, beforeEach, describe, expect, it, vi } from "vitest"

import { init, keyedChildren, patch } from "@javaabu/stencil/blade/stencil"

import company from "./fixtures/kitchen-sink-company.html?raw"
import person from "./fixtures/kitchen-sink.html?raw"

const fixtures: Record<string, string> = { "kitchen-sink.html": person, "kitchen-sink-company.html": company }
const fixture = (name: string) => fixtures[name] as string

function mount(html = fixture("kitchen-sink.html")): HTMLFormElement {
  document.body.innerHTML = html
  const form = document.querySelector("form") as HTMLFormElement
  init(document)
  return form
}

beforeEach(() => {
  HTMLElement.prototype.scrollIntoView = () => {}
})

afterEach(() => {
  vi.restoreAllMocks()
  vi.useRealTimers()
  document.body.innerHTML = ""
})

describe("wizard", () => {
  it("shows one step at a time and the submit button on the last", () => {
    const form = mount()
    const panels = form.querySelectorAll<HTMLElement>("[data-stencil-wizard-panel]")
    const next = form.querySelector<HTMLElement>("[data-stencil-wizard-next]")!
    const previous = form.querySelector<HTMLElement>("[data-stencil-wizard-previous]")!
    const submit = form.querySelector<HTMLElement>("[data-stencil-submit]")!

    expect(panels[1]!.hidden).toBe(true)
    expect(submit.hidden).toBe(true)
    expect(previous.hidden).toBe(true)

    // The step's required email stops Next.
    next.click()
    expect(panels[1]!.hidden).toBe(true)

    form.querySelector<HTMLInputElement>('[name="email"]')!.value = "ali@example.mv"
    next.click()

    expect(panels[0]!.hidden).toBe(true)
    expect(panels[1]!.hidden).toBe(false)
    expect(submit.hidden).toBe(false)
    expect(next.hidden).toBe(true)
    expect(form.querySelector('[data-stencil-wizard-nav="1"]')).toHaveAttribute("aria-current", "step")
    expect(form.querySelector('[data-stencil-wizard-nav="0"]')).toHaveAttribute("data-complete")

    previous.click()
    expect(panels[0]!.hidden).toBe(false)
  })

  it("opens the step with an invalid field", () => {
    document.body.innerHTML = fixture("kitchen-sink.html")
    document.querySelector('[name="name"]')!.setAttribute("aria-invalid", "true")
    init(document)

    expect(document.querySelector<HTMLElement>('[data-stencil-wizard-panel="1"]')!.hidden).toBe(false)
  })
})

describe("patch", () => {
  it("finds the components directly inside an element", () => {
    const form = mount()

    expect(keyedChildren(form).map((element) => element.dataset.stencilKey)).toEqual([
      "section-0",
      "fieldset-1",
      "tabs-2",
      "wizard-3",
      "alert-4",
      "entry-title",
    ])
  })

  it("replaces only what changed", () => {
    const form = mount()
    const title = form.querySelector('[data-stencil-key="title"]')
    const section = form.querySelector('[data-stencil-key="section-0"]')

    const next = document.createElement("template")
    next.innerHTML = fixture("kitchen-sink-company.html")

    patch(form, next.content.querySelector("form")!, new Set(["slug"]))

    expect(form.querySelector('[data-stencil-key="section-0"]')).toBe(section)
    expect(form.querySelector('[data-stencil-key="title"]')).toBe(title)
    expect(form.querySelector('[name="company_name"]')).not.toBeNull()
    expect(form.querySelector<HTMLInputElement>('[name="slug"]')!.value).toBe("raajje")
  })
})

describe("live fields", () => {
  function respond(html: string, changed: string[] = []) {
    const fetch = vi.fn(
      async () =>
        new Response(JSON.stringify({ html, state: {}, changed }), { headers: { "Content-Type": "application/json" } }),
    )
    vi.stubGlobal("fetch", fetch)
    return fetch
  }

  it("re-renders the form when a live select changes", async () => {
    const fetch = respond(fixture("kitchen-sink-company.html"))
    const form = mount()
    const evaluated = vi.fn()
    form.addEventListener("stencil:evaluated", evaluated)

    const type = form.querySelector<HTMLSelectElement>('[name="type"]')!
    type.value = "company"
    type.dispatchEvent(new Event("change", { bubbles: true }))

    await vi.waitFor(() => expect(form.querySelector('[name="company_name"]')).not.toBeNull())

    const [url, init] = fetch.mock.calls[0] as unknown as [string, RequestInit]
    const body = init.body as FormData
    expect(url).toBe("/stencil/evaluate")
    expect(body.get("_stencil")).toBe("test-token")
    expect(body.get("_stencil_changed")).toBe("type")
    expect(body.get("_stencil_old")).toBe("person")
    expect(body.get("_stencil_driver")).toBe("blade")
    expect(evaluated).toHaveBeenCalledOnce()
    expect(form.dataset.stencilLoading).toBeUndefined()
  })

  it("waits for typing to stop", async () => {
    vi.useFakeTimers()
    const fetch = respond(fixture("kitchen-sink-company.html"), ["slug"])
    const form = mount()
    const title = form.querySelector<HTMLInputElement>('[name="title"]')!

    title.value = "R"
    title.dispatchEvent(new Event("input", { bubbles: true }))
    title.value = "Raajje"
    title.dispatchEvent(new Event("input", { bubbles: true }))

    expect(fetch).not.toHaveBeenCalled()

    await vi.advanceTimersByTimeAsync(500)

    expect(fetch).toHaveBeenCalledOnce()
    expect(((fetch.mock.calls[0] as unknown as [string, RequestInit])[1].body as FormData).get("_stencil_old")).toBe(
      "ރާއްޖެ",
    )

    vi.useRealTimers()
    await vi.waitFor(() => expect(form.querySelector<HTMLInputElement>('[name="slug"]')!.value).toBe("raajje"))
  })

  it("ignores fields that aren't live and unchanged values", async () => {
    const fetch = respond(fixture("kitchen-sink-company.html"))
    const form = mount()

    form.querySelector('[name="note"]')!.dispatchEvent(new Event("change", { bubbles: true }))
    form.querySelector('[name="type"]')!.dispatchEvent(new Event("change", { bubbles: true }))
    form.querySelector('[name="type"]')!.dispatchEvent(new Event("focusout", { bubbles: true }))

    await new Promise((resolve) => setTimeout(resolve, 10))
    expect(fetch).not.toHaveBeenCalled()
  })

  it("waits for focus to leave an on-blur field", async () => {
    const fetch = respond(fixture("kitchen-sink-company.html"))
    const form = mount()
    const item = form.querySelector<HTMLElement>('[data-stencil-key="type"]')!
    item.dataset.stencilLive = JSON.stringify({ debounce: null, onBlur: true })

    const type = form.querySelector<HTMLSelectElement>('[name="type"]')!
    type.value = "company"
    type.dispatchEvent(new Event("change", { bubbles: true }))

    await new Promise((resolve) => setTimeout(resolve, 10))
    expect(fetch).not.toHaveBeenCalled()

    type.dispatchEvent(new Event("focusout", { bubbles: true }))
    await vi.waitFor(() => expect(fetch).toHaveBeenCalledOnce())
  })

  it("reports failures with an event", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async () => new Response("", { status: 500 })),
    )
    vi.spyOn(console, "error").mockImplementation(() => {})
    const form = mount()
    const failed = vi.fn()
    form.addEventListener("stencil:error", failed)

    const type = form.querySelector<HTMLSelectElement>('[name="type"]')!
    type.value = "company"
    type.dispatchEvent(new Event("change", { bubbles: true }))

    await vi.waitFor(() => expect(failed).toHaveBeenCalledOnce())
  })
})
