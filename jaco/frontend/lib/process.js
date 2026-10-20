// A process is a small, client-side workflow: it captures context once, walks
// through steps, validates each step and calls the existing domain/API code at
// the end. It is deliberately not a backend job or a second business layer.
export const processState = {
  open: false,
  definition: null,
  step: 0,
  data: {},
  errors: [],
  busy: false,
  loading: false,
}

const currentStep = () =>
  processState.definition?.steps[processState.step] ?? null

export const startProcess = (definition) => {
  processState.definition = definition
  processState.step = 0
  processState.errors = []
  processState.busy = false
  processState.loading = false
  processState.open = true
  processState.data = definition.initialData ? definition.initialData() : {}

  if (definition.init) {
    const init = definition.init(processState)
    if (init && typeof init.then === 'function') {
      processState.loading = true
      Promise.resolve(init)
        .catch(() => {})
        .finally(() => {
          processState.loading = false
          m.redraw()
        })
    }
  }

  m.redraw()
}

export const closeProcess = () => {
  processState.open = false
  processState.definition = null
  processState.step = 0
  processState.data = {}
  processState.errors = []
  processState.busy = false
  processState.loading = false
  m.redraw()
}

const commit = () => {
  processState.busy = true
  processState.errors = []
  m.redraw()

  Promise.resolve()
    .then(() => processState.definition.commit(processState.data, processState))
    .then(() => closeProcess())
    .catch(({code, response} = {}) => {
      if (code === 422 && Array.isArray(response)) {
        processState.errors = response
        if (processState.definition?.stepForErrors) {
          processState.step = processState.definition.stepForErrors(response, processState.step)
        }
      }
      processState.busy = false
      m.redraw()
    })
}

export const nextProcess = () => {
  if (!processState.open || processState.busy || processState.loading) {
    return
  }

  const step = currentStep()
  if (!step) {
    return
  }

  const errors = step.validate ? step.validate(processState.data) : []
  if (errors.length > 0) {
    processState.errors = errors
    m.redraw()
    return
  }

  processState.errors = []
  if (processState.step < processState.definition.steps.length - 1) {
    processState.step += 1
    m.redraw()
    return
  }

  commit()
}

export const backProcess = () => {
  if (!processState.open || processState.busy || processState.loading || processState.step === 0) {
    return
  }
  processState.step -= 1
  processState.errors = []
  m.redraw()
}
