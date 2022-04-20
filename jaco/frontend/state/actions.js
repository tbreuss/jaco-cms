import { state } from './state.js'
import { guid } from '../lib/helpers.js'

export function addSuccess(text, timeout = 3000) {
    state.list.push({
        id: guid(),
        type: 'success',
        text,
        timeout
    })
}

export function addInfo(text, timeout = 3000) {
    state.list.push({
        id: guid(),
        type: 'info',
        text,
        timeout
    })
}

export function addWarning(text, timeout = 3000) {
    state.list.push({
        id: guid(),
        type: 'warning',
        text,
        timeout
    })
}

export function addDanger(text, timeout = 3000) {
    state.list.push({
        id: guid(),
        type: 'danger',
        text,
        timeout
    })
}
