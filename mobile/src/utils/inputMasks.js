/**
  * Utilidades de Máscara y Validación en Tiempo Real (Real-time Input Masking & Instant Validation Móvil)
  */

export function maskOnlyLetters(val) {
  if (!val) return ''
  return val.replace(/[0-9!@#$%^&*()_+={}\[\]:;<>,.?\/\\|~`"]/g, '')
}

export function formatTitleCase(val) {
  if (!val) return ''
  const clean = maskOnlyLetters(val)
  return clean.replace(/\w\S*/g, (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase())
}

export function maskCelular(val) {
  if (!val) return ''
  return val.replace(/\D/g, '').slice(0, 8)
}

export function validateCelular(val) {
  if (!val) return ''
  const clean = maskCelular(val)
  if (clean.length > 0 && !['6', '7'].includes(clean[0])) {
    return 'El celular debe comenzar con 6 o 7.'
  }
  if (clean.length > 0 && clean.length < 8) {
    return `Debe tener 8 dígitos (faltan ${8 - clean.length}).`
  }
  return ''
}

export function maskCi(val) {
  if (!val) return ''
  return val.toUpperCase().replace(/[^0-9A-Z\-]/g, '').slice(0, 15)
}

export function maskPlaca(val) {
  if (!val) return ''
  return val.toUpperCase().replace(/[^A-Z0-9\-]/g, '').slice(0, 10)
}
