/**
  * Utilidades de Máscara y Validación en Tiempo Real (Real-time Input Masking & Instant Validation)
  */

// 1. Bloqueo físico de números en nombres: permite sólo letras, espacios, apostrofes y guiones
export function maskOnlyLetters(val) {
  if (!val) return ''
  // Elimina dígitos 0-9 y caracteres especiales no alfabéticos
  return val.replace(/[0-9!@#$%^&*()_+={}\[\]:;<>,.?\/\\|~`"]/g, '')
}

// 2. Convierte a Title Case al instante (Primera letra mayúscula de cada palabra)
export function formatTitleCase(val) {
  if (!val) return ''
  const clean = maskOnlyLetters(val)
  return clean.replace(/\w\S*/g, (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase())
}

// 3. Bloqueo físico de letras en celular: permite sólo dígitos 0-9, máx 8 caracteres
export function maskCelular(val) {
  if (!val) return ''
  return val.replace(/\D/g, '').slice(0, 8)
}

// Validación en tiempo real del celular boliviano
export function validateCelular(val) {
  if (!val) return ''
  const clean = maskCelular(val)
  if (clean.length > 0 && !['6', '7'].includes(clean[0])) {
    return 'El celular debe comenzar con el dígito 6 o 7.'
  }
  if (clean.length > 0 && clean.length < 8) {
    return `Debe ingresar 8 dígitos (faltan ${8 - clean.length} dígitos).`
  }
  return ''
}

// 4. Bloqueo físico en Cédula de Identidad (CI): permite sólo números y complemento opcional
export function maskCi(val) {
  if (!val) return ''
  // Permite números, guion y complemento corto (ej. 1234567 o 1234567-1A)
  return val.toUpperCase().replace(/[^0-9A-Z\-]/g, '').slice(0, 15)
}

// 5. Máscara de Placa de Vehículo: convierte a mayúsculas y quita caracteres raros (ej. 1234-ABC)
export function maskPlaca(val) {
  if (!val) return ''
  return val.toUpperCase().replace(/[^A-Z0-9\-]/g, '').slice(0, 10)
}

// 6. Máscara de Montos Numéricos
export function maskMonto(val) {
  if (!val) return ''
  return val.replace(/[^0-9.]/g, '')
}
