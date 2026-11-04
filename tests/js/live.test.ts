import { afterEach, describe, expect, it, vi } from "vitest"

import { evaluate, evaluationBody, evaluationHeaders, toDotName } from "@javaabu/stencil"

const live = { url: "/stencil/evaluate", token: "token", debounce: 500 }

function form(html: string): HTMLFormElement {
  const element = document.createElement("form")
  element.innerHTML = html
  return element
}

afterEach(() => {
  vi.restoreAllMocks()
  document.head.innerHTML = ""
  document.cookie = "XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT"
})

describe("live evaluation", () => {
  it("converts names to dot notation", () => {
    expect(toDotName("address[city]")).toBe("address.city")
    expect(toDotName("tags[]")).toBe("tags")
    expect(toDotName("title")).toBe("title")
  })

  it("sends the values without files or method spoofing", () => {
    const body = evaluationBody({
      form: form(`
        <input name="_method" value="PATCH">
        <input name="_token" value="csrf">
        <input name="title" value="ސުރުހީ">
        <input type="file" name="cv">
        <select name="tags[]" multiple><option value="1" selected></option><option value="2" selected></option></select>
      `),
      live,
      changed: "tags[]",
      old: ["1"],
      driver: "blade",
    })

    expect(body.get("_method")).toBeNull()
    expect(body.get("cv")).toBeNull()
    expect(body.get("_token")).toBe("csrf")
    expect(body.get("title")).toBe("ސުރުހީ")
    expect(body.getAll("tags[]")).toEqual(["1", "2"])
    expect(body.get("_stencil_changed")).toBe("tags")
    expect(body.getAll("_stencil_old[]")).toEqual(["1"])
    expect(body.get("_stencil_driver")).toBe("blade")
  })

  it("sends the CSRF token from the meta tag and the XSRF cookie", () => {
    document.head.innerHTML = '<meta name="csrf-token" content="meta-token">'
    document.cookie = "XSRF-TOKEN=cookie%3Dtoken"

    expect(evaluationHeaders()).toMatchObject({
      Accept: "application/json",
      "X-CSRF-TOKEN": "meta-token",
      "X-XSRF-TOKEN": "cookie=token",
    })
  })

  it("throws when the route fails", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async () => new Response("", { status: 419 })),
    )

    await expect(evaluate({ form: form(""), live, changed: "a", driver: "json" })).rejects.toThrow("419")
  })
})
