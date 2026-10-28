import {app} from './app.js'

// The ui mirrors the role matrix from the backend (see docs/constraints.md). It is not a
// security boundary: every route still checks the signed token. Hiding a button only
// keeps a read-only account from clicking something that would answer 403.
export const ROLE_EDITOR = 'editor'
export const ROLE_ADMIN = 'admin'

const roleRank = {
  [ROLE_EDITOR]: 1,
  [ROLE_ADMIN]: 2,
}

const rank = (role) => Object.prototype.hasOwnProperty.call(roleRank, role) ? roleRank[role] : 0

let cachedToken = null
let cachedClaims = null

// Reads the payload without verifying it. The signature is the server's job; the ui
// only needs to know which controls to render. An unreadable token falls back to null,
// which auth.role() treats as no role at all - every menu entry is then greyed out.
const decodeClaims = (token) => {
  const payload = token.split('.')[1]
  if (!payload) {
    return null
  }

  try {
    const base64 = payload.replace(/-/g, '+').replace(/_/g, '/')
    const padded = base64.padEnd(Math.ceil(base64.length / 4) * 4, '=')
    const bytes = Uint8Array.from(atob(padded), (character) => character.charCodeAt(0))
    const claims = JSON.parse(new TextDecoder().decode(bytes))
    return claims !== null && typeof claims === 'object' ? claims : null
  } catch {
    return null
  }
}

const claims = () => {
  const token = app.getAuthToken()
  if (token !== cachedToken) {
    cachedToken = token
    cachedClaims = token === null ? null : decodeClaims(token)
  }
  return cachedClaims
}

export const auth = {
  username: () => {
    const user = claims()?.user
    return typeof user === 'string' ? user : ''
  },
  role: () => {
    const role = claims()?.role
    return typeof role === 'string' && rank(role) > 0 ? role : ''
  },
  language: () => {
    const language = claims()?.language
    return typeof language === 'string' ? language : ''
  },
  // a read-only account keeps its role and may not store anything: the ui keeps
  // every control visible so the interface can be shown, the server refuses the writes
  isReadOnly: () => claims()?.readOnly === true,
  can: (required) => {
    const requiredRank = rank(required)
    return requiredRank > 0 && rank(auth.role()) >= requiredRank
  },
}
