<template>
  <div class="asistencia-page">
    <!-- Header Navigation Tabs -->
    <div class="asistencia-page__header">
      <div>
        <h1 class="asistencia-page__title">Control de Asistencia e Inspección</h1>
        <p class="asistencia-page__subtitle">Cierre de asistencia obligatorio a las 08:00 AM e historial de paradas</p>
      </div>

      <div class="tab-switcher">
        <button
          class="tab-btn"
          :class="{ 'tab-btn--active': activeTab === 'tomar' }"
          @click="activeTab = 'tomar'"
        >
          📋 Tomar Asistencia Hoy
        </button>
        <button
          class="tab-btn"
          :class="{ 'tab-btn--active': activeTab === 'historial' }"
          @click="activeTab = 'historial'; inicializarCalendario();"
        >
          📅 Historial con Calendario
        </button>
      </div>
    </div>

    <!-- Alert Notifications -->
    <transition name="fade">
      <div v-if="mensajeExito" class="alert alert-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <span>{{ mensajeExito }}</span>
      </div>
    </transition>

    <transition name="fade">
      <div v-if="errorMsg" class="alert alert-danger">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <span>{{ errorMsg }}</span>
      </div>
    </transition>

    <!-- ═════════════════════════════════════════════════════════════════ -->
    <!-- TAB 1: TOMAR ASISTENCIA (HOY)                                    -->
    <!-- ═════════════════════════════════════════════════════════════════ -->
    <div v-if="activeTab === 'tomar'">
      <AppLoader v-if="loading" :visible="true" message="Verificando horario de cierre (08:00 AM) y choferes de su grupo..." style="padding: 4rem 0;" />

      <div v-else class="asistencia-content">
        <!-- Banner de Bloqueo a las 8:00 AM -->
        <div v-if="datosHoy.bloqueado" class="lock-banner">
          <div class="lock-banner__icon">🔒</div>
          <div class="lock-banner__content">
            <h3 class="lock-banner__title">Cierre de Asistencia Ejecutado (Hora límite: 08:00 AM)</h3>
            <p class="lock-banner__text">
              La toma de asistencia para el día de hoy ya ha finalizado. Los registros se encuentran guardados en la base de datos y bloqueados en <strong>Modo Lectura únicamente</strong>.
            </p>
          </div>
        </div>

        <!-- Banner Parada Asignada Hoy -->
        <div class="parada-banner">
          <div class="parada-banner__main">
            <div class="parada-banner__badge">PARADA ROTATORIA HOY</div>
            <h2 class="parada-banner__location">📍 {{ datosHoy.lugar?.nombre }}</h2>
            <div class="parada-banner__meta">
              <span>📅 {{ datosHoy.fecha_formateada }}</span>
              <span>👥 {{ datosHoy.grupo?.nombre }} — {{ datosHoy.grupo?.descripcion }}</span>
              <span>👮 Inspector: {{ datosHoy.inspector?.nombre_completo }}</span>
              <span>⏰ Hora Límite: {{ datosHoy.hora_limite }} AM</span>
            </div>
          </div>

          <!-- Itinerario Semanal Rotatorio -->
          <div class="itinerario-box">
            <span class="itinerario-title">Itinerario de su Grupo esta Semana:</span>
            <div class="itinerario-days">
              <div
                v-for="item in datosHoy.itinerario_semanal"
                :key="item.dia"
                class="itinerario-day"
                :class="{ 'itinerario-day--active': item.fecha === datosHoy.fecha }"
              >
                <span class="itinerario-day-name">{{ item.dia }}</span>
                <strong class="itinerario-day-parada">{{ item.parada }}</strong>
                <small class="itinerario-day-date">{{ item.fecha_form }}</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Control Toolbar & Save Button -->
        <div class="control-toolbar">
          <div class="control-toolbar__stats">
            <span>Total Choferes del Grupo: <strong>{{ choferes.length }}</strong></span> |
            <span class="text-success">Presentes: <strong>{{ presentesCount }}</strong></span> |
            <span class="text-danger">Ausentes: <strong>{{ ausentesCount }}</strong></span>
          </div>

          <div v-if="!datosHoy.bloqueado" class="control-toolbar__buttons">
            <button class="btn btn-sm btn-outline-success" @click="marcarTodos(true)">
              ✓ Marcar Todos Presentes
            </button>
            <button class="btn btn-sm btn-outline-danger" @click="marcarTodos(false)">
              ✗ Marcar Todos Ausentes
            </button>

            <button
              class="btn btn-success"
              @click="guardarAsistencia"
              :disabled="guardando"
              id="btn-guardar-asistencia-top"
              style="margin-left: 0.75rem;"
            >
              <span v-if="guardando">Guardando...</span>
              <span v-else>Guardar Asistencia</span>
            </button>
          </div>

          <div v-else class="control-toolbar__locked-badge">
            🔒 Edición Deshabilitada (Pasadas las 08:00 AM)
          </div>
        </div>

        <!-- Lista de Choferes de su Grupo -->
        <div class="choferes-grid">
          <div
            v-for="c in choferes"
            :key="c.chofer_id"
            class="chofer-card"
            :class="c.asistencia ? 'chofer-card--presente' : 'chofer-card--ausente'"
          >
            <div class="chofer-card__avatar">
              {{ getInitials(c.nombre_completo) }}
            </div>

            <div class="chofer-card__info">
              <h4 class="chofer-card__name">{{ c.nombre_completo }}</h4>
              <div class="chofer-card__details">
                <span>CI: <strong>{{ c.ci }}</strong></span>
                <span>Placa: <strong>{{ c.placa }}</strong></span>
                <span>{{ c.vehiculo_info }}</span>
                <span v-if="c.hora_registro" class="text-muted" style="font-size:0.7rem;">Hora reg: {{ c.hora_registro }}</span>
              </div>
            </div>

            <div class="chofer-card__status-btn">
              <button
                class="toggle-asistencia-btn"
                :class="c.asistencia ? 'toggle-asistencia-btn--presente' : 'toggle-asistencia-btn--ausente'"
                :disabled="datosHoy.bloqueado"
                @click="!datosHoy.bloqueado && (c.asistencia = !c.asistencia)"
              >
                <span class="btn-icon">{{ c.asistencia ? '✓' : '✗' }}</span>
                <span class="btn-text">{{ c.asistencia ? 'PRESENTE' : 'AUSENTE' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════ -->
    <!-- TAB 2: HISTORIAL CON CALENDARIO INTERACTIVO (2026 ➔ HOY)         -->
    <!-- ═════════════════════════════════════════════════════════════════ -->
    <div v-else-if="activeTab === 'historial'" class="historial-section">
      <!-- Custom Visual Weekly Calendar Widget -->
      <div class="calendar-widget">
        <div class="calendar-widget__header">
          <div class="calendar-widget__nav">
            <button
              class="nav-arrow-btn"
              :disabled="!puedeNavegarAnterior"
              @click="cambiarSemana(-1)"
              title="Semana Anterior"
            >
              ◄
            </button>
            <h3 class="calendar-widget__month">{{ mesAñoActualStr }}</h3>
            <button
              class="nav-arrow-btn"
              :disabled="!puedeNavegarSiguiente"
              @click="cambiarSemana(1)"
              title="Semana Siguiente"
            >
              ►
            </button>
          </div>

          <button class="btn btn-sm btn-secondary" @click="irAHoy">
            📍 Ir a Hoy
          </button>
        </div>

        <!-- Days Grid (Lunes a Viernes) -->
        <div class="calendar-widget__days">
          <div
            v-for="day in diasSemanaVisual"
            :key="day.fechaStr"
            class="day-card"
            :class="{
              'day-card--selected': day.fechaStr === fechaSeleccionadaStr,
              'day-card--today': day.esHoy,
              'day-card--disabled': day.deshabilitado
            }"
            @click="!day.deshabilitado && seleccionarFecha(day.fechaStr)"
          >
            <span class="day-card__name">{{ day.nombreDia }}</span>
            <span class="day-card__number">{{ day.numeroDia }}</span>
            <span class="day-card__month-short">{{ day.mesCorto }}</span>
            <span v-if="day.esHoy" class="day-card__today-badge">HOY</span>
            <span v-else-if="day.deshabilitado" class="day-card__disabled-icon">🚫</span>
          </div>
        </div>
        <div class="calendar-widget__footer-info">
          <span>* Historial disponible únicamente desde el <strong>01/01/2026</strong> hasta <strong>HOY</strong>. Las fechas futuras no se pueden consultar.</span>
        </div>
      </div>

      <!-- Results for Selected Date -->
      <AppLoader v-if="loadingHistorial" :visible="true" message="Consultando asistencia del calendario..." style="padding: 3rem 0;" />

      <div v-else class="historial-results">
        <div class="historial-results__header">
          <h2>📅 Asistencias del {{ datosHistorial.fecha_formateada || fechaSeleccionadaStr }}</h2>
        </div>

        <div v-for="grupoData in datosHistorial.grupos" :key="grupoData.grupo_id" class="grupo-historial-card">
          <div class="grupo-historial-card__header">
            <div>
              <h3>{{ grupoData.grupo_nombre }}</h3>
              <span class="parada-tag">📍 Parada Rotatoria Asignada: <strong>{{ grupoData.parada }}</strong></span>
            </div>
            <div class="grupo-historial-card__summary">
              <span class="badge badge-success">✓ {{ grupoData.presentes }} Presentes</span>
              <span class="badge badge-danger">✗ {{ grupoData.ausentes }} Ausentes</span>
            </div>
          </div>

          <div v-if="grupoData.asistencias.length === 0" class="empty-historial">
            No existen registros de asistencia para su grupo en esta fecha.
          </div>

          <div v-else class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Chofer</th>
                  <th>CI</th>
                  <th>Estado</th>
                  <th>Inspector Responsable</th>
                  <th>Hora Registro</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="a in grupoData.asistencias" :key="a.id">
                  <td><strong>{{ a.chofer }}</strong></td>
                  <td>{{ a.ci }}</td>
                  <td>
                    <span :class="a.asistencia ? 'status-pill status-pill--active' : 'status-pill status-pill--inactive'">
                      {{ a.asistencia ? '✓ PRESENTE' : '✗ AUSENTE' }}
                    </span>
                  </td>
                  <td>{{ a.inspector }}</td>
                  <td>{{ a.hora || 'N/A' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { asistenciaApi } from '@/api/asistenciaApi'
import AppLoader from '@/components/common/AppLoader.vue'

const activeTab = ref('tomar')
const loading = ref(true)
const guardando = ref(false)
const errorMsg = ref(null)
const mensajeExito = ref(null)

const datosHoy = ref({})
const choferes = ref([])

// ─── Calendario Semanal Interactivo (2026 -> HOY) ──────────────────
const FECHA_MINIMA_2026 = '2026-01-01'
const todayObj = new Date()
const hoyStr = todayObj.toISOString().slice(0, 10)

const fechaSeleccionadaStr = ref(hoyStr)
const lunesSemanaVista = ref(getLunesDeSemana(new Date()))

const loadingHistorial = ref(false)
const datosHistorial = ref({})

const presentesCount = computed(() => choferes.value.filter(c => c.asistencia).length)
const ausentesCount = computed(() => choferes.value.filter(c => !c.asistencia).length)

// Helper para obtener el lunes de cualquier fecha
function getLunesDeSemana(dateObj) {
  const d = new Date(dateObj)
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1) // ajustar si es domingo
  return new Date(d.setDate(diff))
}

// Días visuales de la semana actual en vista (Lunes a Viernes)
const diasSemanaVisual = computed(() => {
  const lunes = new Date(lunesSemanaVista.value)
  const nombresDias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes']
  const nombresMesesCortos = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']

  const result = []
  for (let i = 0; i < 5; i++) {
    const curDate = new Date(lunes)
    curDate.setDate(lunes.getDate() + i)

    const dateStr = curDate.toISOString().slice(0, 10)

    // Validar rango: deshabilitar si > HOY o < 2026-01-01
    const esFuturo = dateStr > hoyStr
    const esPrevio2026 = dateStr < FECHA_MINIMA_2026
    const deshabilitado = esFuturo || esPrevio2026

    result.push({
      fechaStr: dateStr,
      nombreDia: nombresDias[i],
      numeroDia: curDate.getDate(),
      mesCorto: nombresMesesCortos[curDate.getMonth()],
      esHoy: dateStr === hoyStr,
      deshabilitado,
    })
  }

  return result
})

// Texto de Mes y Año en el Header del Widget
const mesAñoActualStr = computed(() => {
  const lunes = new Date(lunesSemanaVista.value)
  const nombresMeses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
  return `${nombresMeses[lunes.getMonth()]} ${lunes.getFullYear()}`
})

const puedeNavegarAnterior = computed(() => {
  const prevLunes = new Date(lunesSemanaVista.value)
  prevLunes.setDate(prevLunes.getDate() - 7)
  return prevLunes.toISOString().slice(0, 10) >= FECHA_MINIMA_2026
})

const puedeNavegarSiguiente = computed(() => {
  const nextLunes = new Date(lunesSemanaVista.value)
  nextLunes.setDate(nextLunes.getDate() + 7)
  const primerDiaNextWeek = nextLunes.toISOString().slice(0, 10)
  return primerDiaNextWeek <= hoyStr
})

function cambiarSemana(direction) {
  const currentLunes = new Date(lunesSemanaVista.value)
  currentLunes.setDate(currentLunes.getDate() + (direction * 7))
  lunesSemanaVista.value = currentLunes
}

function irAHoy() {
  lunesSemanaVista.value = getLunesDeSemana(new Date())
  seleccionarFecha(hoyStr)
}

function seleccionarFecha(fechaStr) {
  fechaSeleccionadaStr.value = fechaStr
  cargarHistorialFecha()
}

function inicializarCalendario() {
  lunesSemanaVista.value = getLunesDeSemana(new Date())
  fechaSeleccionadaStr.value = hoyStr
  cargarHistorialFecha()
}

async function cargarPlanilla() {
  loading.value = true
  errorMsg.value = null
  try {
    const res = await asistenciaApi.obtenerHoy()
    datosHoy.value = res.data.data
    choferes.value = res.data.data.choferes || []
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Error al cargar planilla de asistencia'
  } finally {
    loading.value = false
  }
}

function marcarTodos(estado) {
  if (datosHoy.value.bloqueado) return
  choferes.value.forEach(c => c.asistencia = estado)
}

function getInitials(nombre) {
  if (!nombre) return 'C'
  return nombre.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

async function guardarAsistencia() {
  if (datosHoy.value.bloqueado) return
  guardando.value = true
  errorMsg.value = null
  mensajeExito.value = null

  try {
    const payload = {
      lugar_id: datosHoy.value.lugar?.id,
      fecha: datosHoy.value.fecha,
      asistencias: choferes.value.map(c => ({
        chofer_id: c.chofer_id,
        asistencia: c.asistencia,
      })),
    }

    const res = await asistenciaApi.guardar(payload)
    mensajeExito.value = res.data.message || 'Asistencia registrada correctamente'

    setTimeout(() => {
      mensajeExito.value = null
    }, 4000)
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Error al guardar la asistencia'
  } finally {
    guardando.value = false
  }
}

async function cargarHistorialFecha() {
  loadingHistorial.value = true
  try {
    const res = await asistenciaApi.obtenerHistorialPorFecha({ fecha: fechaSeleccionadaStr.value })
    datosHistorial.value = res.data.data
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Error al consultar historial del calendario'
  } finally {
    loadingHistorial.value = false
  }
}

onMounted(() => {
  cargarPlanilla()
})
</script>

<style scoped>
.asistencia-page__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}
.asistencia-page__title {
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--color-text-primary);
}
.asistencia-page__subtitle {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.tab-switcher {
  display: flex;
  background: #ffffff;
  padding: 0.25rem;
  border-radius: var(--radius-xl);
  border: 1px solid var(--color-border);
}
.tab-btn {
  padding: 0.6rem 1.2rem;
  border-radius: var(--radius-lg);
  border: none;
  background: none;
  color: var(--color-text-secondary);
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all var(--transition-fast);
}
.tab-btn--active {
  background: var(--color-primary-600);
  color: white;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.alert {
  padding: 0.85rem 1.25rem;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 1.25rem;
  font-size: 0.9rem;
}
.alert svg { width: 20px; height: 20px; flex-shrink: 0; }
.alert-success { background: var(--color-success-bg); border: 1px solid #bbf7d0; color: var(--color-success); font-weight: 600; }
.alert-danger { background: var(--color-error-bg); border: 1px solid #fecaca; color: var(--color-error); font-weight: 600; }

.lock-banner {
  background: var(--color-error-bg);
  border: 1px solid #fecaca;
  border-radius: var(--radius-xl);
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
}
.lock-banner__icon { font-size: 2rem; }
.lock-banner__title { font-size: 1rem; font-weight: 700; color: var(--color-error); margin-bottom: 0.2rem; }
.lock-banner__text { font-size: 0.85rem; color: #7f1d1d; }

.parada-banner {
  background: var(--color-primary-50);
  border: 1px solid var(--color-primary-200);
  border-radius: var(--radius-2xl);
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.parada-banner__badge {
  display: inline-block;
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 0.2rem 0.6rem;
  background: var(--color-primary-700);
  color: white;
  border-radius: var(--radius-sm);
  margin-bottom: 0.5rem;
}
.parada-banner__location {
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--color-primary-900);
  margin-bottom: 0.75rem;
}
.parada-banner__meta {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.itinerario-box {
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: 1rem;
  display: flex;
  flex-direction: column;
}
.itinerario-title { font-size: 0.75rem; color: var(--color-text-muted); margin-bottom: 0.75rem; text-transform: uppercase; font-weight: 700; }
.itinerario-days { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.5rem; }
.itinerario-day {
  background: var(--color-bg-tertiary); border: 1px solid var(--color-border);
  border-radius: var(--radius-md); padding: 0.5rem; text-align: center;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
}
.itinerario-day--active {
  border-color: var(--color-primary-600); background: var(--color-primary-50);
  box-shadow: 0 0 8px rgba(37, 99, 235, 0.2);
}
.itinerario-day-name { font-size: 0.65rem; color: var(--color-text-muted); font-weight: 700; }
.itinerario-day-parada { font-size: 0.8rem; color: var(--color-text-primary); margin: 0.2rem 0; font-weight: 700; }
.itinerario-day-date { font-size: 0.6rem; color: var(--color-text-secondary); }

.control-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  background: #ffffff;
  padding: 0.85rem 1.25rem;
  border-radius: var(--radius-xl);
  border: 1px solid var(--color-border);
}
.control-toolbar__stats { font-size: 0.9rem; color: var(--color-text-secondary); }
.control-toolbar__buttons { display: flex; gap: 0.5rem; align-items: center; }
.control-toolbar__locked-badge { font-size: 0.85rem; font-weight: 700; color: var(--color-error); }

.choferes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1rem;
}

.chofer-card {
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-fast);
}
.chofer-card--presente { border-left: 4px solid var(--color-success); }
.chofer-card--ausente { border-left: 4px solid var(--color-error); opacity: 0.85; }

.chofer-card__avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--color-bg-tertiary);
  border: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  color: var(--color-primary-800);
  flex-shrink: 0;
}

.chofer-card__info { flex: 1; min-width: 0; }
.chofer-card__name { font-size: 0.95rem; font-weight: 700; color: var(--color-text-primary); margin-bottom: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.chofer-card__details { display: flex; flex-direction: column; font-size: 0.75rem; color: var(--color-text-secondary); }

.toggle-asistencia-btn {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.85rem;
  border-radius: var(--radius-lg);
  border: none;
  font-size: 0.8rem;
  font-weight: 800;
  cursor: pointer;
  transition: all var(--transition-fast);
}
.toggle-asistencia-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.toggle-asistencia-btn--presente { background: var(--color-success-bg); color: var(--color-success); border: 1px solid #bbf7d0; }
.toggle-asistencia-btn--ausente { background: var(--color-error-bg); color: var(--color-error); border: 1px solid #fecaca; }

/* ─── CALENDARIO SEMANAL INTERACTIVO STYLES ────────────────────── */
.calendar-widget {
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-2xl);
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: var(--shadow-sm);
}
.calendar-widget__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}
.calendar-widget__nav {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.calendar-widget__month {
  font-size: 1.3rem;
  font-weight: 800;
  color: var(--color-text-primary);
  min-width: 160px;
  text-align: center;
}

.nav-arrow-btn {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  border: 1px solid var(--color-border);
  background: #ffffff;
  color: var(--color-text-primary);
  font-size: 0.9rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transition-fast);
}
.nav-arrow-btn:hover:not(:disabled) {
  background: var(--color-primary-600);
  color: white;
  border-color: var(--color-primary-600);
}
.nav-arrow-btn:disabled { opacity: 0.3; cursor: not-allowed; }

.calendar-widget__days {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0.75rem;
}

.day-card {
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: 1rem 0.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  position: relative;
  transition: all var(--transition-fast);
  user-select: none;
}
.day-card:hover:not(.day-card--disabled) {
  border-color: var(--color-primary-600);
  transform: translateY(-2px);
}
.day-card--selected {
  background: var(--color-primary-50);
  border: 2px solid var(--color-primary-600);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
}
.day-card--today {
  border-color: var(--color-success);
}
.day-card--disabled {
  opacity: 0.35;
  cursor: not-allowed;
  background: #f1f5f9;
}

.day-card__name { font-size: 0.75rem; font-weight: 700; color: var(--color-text-secondary); text-transform: uppercase; }
.day-card__number { font-size: 1.6rem; font-weight: 800; color: var(--color-text-primary); margin: 0.2rem 0; }
.day-card__month-short { font-size: 0.65rem; color: var(--color-text-muted); }

.day-card__today-badge {
  position: absolute;
  top: 6px;
  right: 6px;
  font-size: 0.55rem;
  font-weight: 800;
  background: var(--color-success);
  color: white;
  padding: 1px 4px;
  border-radius: var(--radius-sm);
}
.day-card__disabled-icon {
  position: absolute;
  top: 6px;
  right: 6px;
  font-size: 0.7rem;
}

.calendar-widget__footer-info {
  margin-top: 1rem;
  font-size: 0.75rem;
  color: var(--color-text-muted);
  text-align: center;
}

/* Historial Results Styles */
.grupo-historial-card {
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: 1.25rem;
  margin-bottom: 1.5rem;
  box-shadow: var(--shadow-sm);
}
.grupo-historial-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}
.grupo-historial-card__header h3 { font-size: 1.2rem; font-weight: 800; color: var(--color-text-primary); margin-bottom: 0.2rem; }
.parada-tag { font-size: 0.85rem; color: var(--color-primary-700); font-weight: 600; }

.badge { padding: 0.35rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.75rem; font-weight: 700; margin-left: 0.5rem; }
.badge-success { background: var(--color-success-bg); color: var(--color-success); border: 1px solid #bbf7d0; }
.badge-danger { background: var(--color-error-bg); color: var(--color-error); border: 1px solid #fecaca; }

.empty-historial {
  padding: 2rem;
  text-align: center;
  color: var(--color-text-muted);
  font-size: 0.9rem;
}

.data-table { width: 100%; border-collapse: collapse; text-align: left; }
.data-table th, .data-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--color-border); font-size: 0.85rem; }
.data-table th { color: var(--color-text-secondary); text-transform: uppercase; font-size: 0.75rem; }

.status-pill { display: inline-block; padding: 0.25rem 0.6rem; border-radius: var(--radius-sm); font-size: 0.75rem; font-weight: 700; }
.status-pill--active { background: var(--color-success-bg); color: var(--color-success); }
.status-pill--inactive { background: var(--color-error-bg); color: var(--color-error); }

.btn { display: inline-flex; align-items: center; padding: 0.6rem 1.2rem; font-size: 0.85rem; font-weight: 600; border-radius: var(--radius-lg); border: none; cursor: pointer; }
.btn-sm { padding: 0.35rem 0.75rem; font-size: 0.75rem; }
.btn-secondary { background: #ffffff; color: var(--color-text-primary); border: 1px solid var(--color-border); }
.btn-secondary:hover { background: var(--color-bg-tertiary); }
.btn-success { background: var(--color-success-bg); color: var(--color-success); border: 1px solid #bbf7d0; font-weight: 700; }
.btn-success:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-outline-success { background: none; border: 1px solid #22c55e; color: var(--color-success); font-weight: 700; }
.btn-outline-danger { background: none; border: 1px solid #ef4444; color: var(--color-error); font-weight: 700; }
.text-success { color: var(--color-success); font-weight: 700; }
.text-danger { color: var(--color-error); font-weight: 700; }
</style>
