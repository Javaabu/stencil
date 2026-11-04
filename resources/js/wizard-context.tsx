"use client"

import * as React from "react"

const WizardSubmitContext = React.createContext<((hidden: boolean) => void) | null>(null)

export const WizardSubmitProvider = WizardSubmitContext.Provider

/** Lets a wizard hide the form's submit button until its last step. */
export function useWizardSubmit() {
  return React.useContext(WizardSubmitContext)
}
