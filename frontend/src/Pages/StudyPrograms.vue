<template>
  <div class="study-programs-page">
    <Topbar />

    <main class="sg">
    <section class="sg-hero" aria-label="Seiteneinleitung">
      <div class="sg-hero-photo" role="presentation"></div>
      <div class="sg-hero-blob1" aria-hidden="true"></div>
      <div class="sg-hero-blob2" aria-hidden="true"></div>
      <svg class="sg-hero-wave" viewBox="0 0 1440 70" preserveAspectRatio="none" style="display:block;height:70px" aria-hidden="true">
        <path d="M0,40 C200,75 440,10 680,46 C880,78 1150,15 1440,52 L1440,70 L0,70 Z" fill="#edf6f1"/>
      </svg>
      <div class="sg-hero-inner">
        <div class="sg-hero-badge">
          <svg width="7" height="7" viewBox="0 0 8 8" fill="currentColor" aria-hidden="true"><circle cx="4" cy="4" r="4"/></svg>
          Dein Studiengang
        </div>
        <h1 class="sg-hero-title">Deine Module.<br><em>Deine Tutoren.</em></h1>
        <p class="sg-hero-sub">Wähle deinen Studiengang. Danach zeigen wir dir automatisch nur die dazugehörigen Fächer.</p>
      </div>
    </section>

    <section class="sg-section" aria-label="Studiengänge">
      <p class="sg-section-label">Verfügbare Studiengänge</p>

      <p v-if="loading" class="sg-status">
  Studiengänge werden geladen …
</p>

<p v-else-if="errorMessage" class="sg-status sg-status--error">
  {{ errorMessage }}
</p>

<div v-else>
  <div class="search-box">
    <input
      v-model="search"
      type="text"
      placeholder="Studiengang suchen..."
    />
  </div>

  <div v-if="filteredPrograms.length" class="sg-grid" role="list">
    <button
      v-for="program in filteredPrograms"
      :key="program.id"
      type="button"
      class="sg-card"
      :class="{ 'sg-card--bau': program.variant === 'bau' }"
      role="listitem"
      @click="goToSubjects(program)"
    >
          <div class="sg-illo" aria-hidden="true">
            <component :is="program.icon" />
          </div>
          <div class="sg-body">
            <h2 class="sg-name">{{ program.name }}</h2>
            <p class="sg-degree">{{ program.subjectsCount }} {{ program.subjectsCount === 1 ? 'Fach' : 'Fächer' }}</p>
            <p class="sg-tags">{{ program.description || 'Passende Fächer und Tutoren entdecken' }}</p>
          </div>
          <div class="sg-arrow" aria-hidden="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"/>
              <polyline points="12 5 19 12 12 19"/>
            </svg>
          </div>
        </button>
      </div>

      <p v-else class="sg-status">
  Noch keine Studiengänge vorhanden.
</p>
</div>
    </section>

    <div class="sg-footer" aria-hidden="true">
      <svg viewBox="0 0 1440 70" preserveAspectRatio="none" style="display:block;width:100%;height:70px">
        <path d="M0,20 C300,65 700,0 1100,46 C1260,66 1380,28 1440,38 L1440,70 L0,70 Z" fill="#1a4d3a"/>
      </svg>
    </div>
    </main>
  </div>
</template>
 
<script setup>
import { defineComponent, h } from 'vue'
 
// ── Inline SVG icon components ────────────────────────────────────────────────
 
const InformatikIcon = defineComponent({
  render: () => h('svg', { width: 52, height: 52, viewBox: '0 0 80 80', fill: 'none' }, [
    h('rect', { x: 8, y: 18, width: 56, height: 36, rx: 5, fill: '#b8deca', stroke: '#1a4d3a', 'stroke-width': '1.4' }),
    h('rect', { x: 12, y: 22, width: 48, height: 28, rx: 3, fill: '#0b2e20' }),
    h('rect', { x: 17, y: 28, width: 15, height: 2.5, rx: 1, fill: '#2d8c68' }),
    h('rect', { x: 17, y: 33, width: 22, height: 2.5, rx: 1, fill: '#4aba8a' }),
    h('rect', { x: 17, y: 38, width: 11, height: 2.5, rx: 1, fill: '#2d8c68' }),
    h('rect', { x: 30, y: 38, width: 9, height: 2.5, rx: 1, fill: '#4aba8a' }),
    h('rect', { x: 17, y: 43, width: 17, height: 2.5, rx: 1, fill: '#2d8c68' }),
    h('text', { x: 42, y: 43, 'font-size': 11, fill: '#6dd4a0', 'font-family': 'monospace', 'font-weight': 'bold' }, '</> '),
    h('rect', { x: 6, y: 54, width: 60, height: 6, rx: 3, fill: '#9acdb8', stroke: '#1a4d3a', 'stroke-width': '1.2' }),
  ]),
})
 
const WirtschaftsinformatikIcon = defineComponent({
  render: () => h('svg', { width: 52, height: 52, viewBox: '0 0 80 80', fill: 'none' }, [
    h('rect', { x: 8, y: 20, width: 54, height: 34, rx: 5, fill: '#b8deca', stroke: '#1a4d3a', 'stroke-width': '1.4' }),
    h('rect', { x: 12, y: 24, width: 46, height: 26, rx: 3, fill: '#0b2e20' }),
    h('rect', { x: 18, y: 42, width: 6, height: 6, rx: 1, fill: '#2d8c68' }),
    h('rect', { x: 27, y: 37, width: 6, height: 11, rx: 1, fill: '#4aba8a' }),
    h('rect', { x: 36, y: 33, width: 6, height: 15, rx: 1, fill: '#2d8c68' }),
    h('rect', { x: 45, y: 29, width: 6, height: 19, rx: 1, fill: '#6dd4a0' }),
    h('polyline', { points: '21,43 30,38 39,34 48,29', stroke: '#7adbaa', 'stroke-width': '1.5', fill: 'none', 'stroke-linecap': 'round' }),
    h('rect', { x: 6, y: 54, width: 58, height: 6, rx: 3, fill: '#9acdb8', stroke: '#1a4d3a', 'stroke-width': '1.2' }),
    h('circle', { cx: 66, cy: 17, r: 7, fill: '#fef3c7', stroke: '#f59e0b', 'stroke-width': '1.4' }),
    h('text', { x: 63.5, y: 20.5, 'font-size': 8, fill: '#b45309', 'font-weight': 'bold' }, '€'),
  ]),
})
 
const MaschinenbauIcon = defineComponent({
  render: () => h('svg', { width: 52, height: 52, viewBox: '0 0 80 80', fill: 'none' }, [
    h('circle', { cx: 32, cy: 44, r: 18, fill: '#b8deca', stroke: '#1a4d3a', 'stroke-width': '1.4' }),
    h('circle', { cx: 32, cy: 44, r: 6.5, fill: '#fff', stroke: '#1a4d3a', 'stroke-width': '1.4' }),
    h('rect', { x: 29, y: 22, width: 7, height: 8, rx: 2, fill: '#1a4d3a' }),
    h('rect', { x: 29, y: 58, width: 7, height: 8, rx: 2, fill: '#1a4d3a' }),
    h('rect', { x: 10, y: 41, width: 8, height: 7, rx: 2, fill: '#1a4d3a' }),
    h('rect', { x: 46, y: 41, width: 8, height: 7, rx: 2, fill: '#1a4d3a' }),
    h('rect', { x: 14, y: 26, width: 6, height: 8, rx: 2, fill: '#1a4d3a', transform: 'rotate(-45 17 30)' }),
    h('rect', { x: 46, y: 26, width: 6, height: 8, rx: 2, fill: '#1a4d3a', transform: 'rotate(45 49 30)' }),
    h('rect', { x: 14, y: 48, width: 6, height: 8, rx: 2, fill: '#1a4d3a', transform: 'rotate(45 17 52)' }),
    h('rect', { x: 46, y: 48, width: 6, height: 8, rx: 2, fill: '#1a4d3a', transform: 'rotate(-45 49 52)' }),
    h('circle', { cx: 58, cy: 28, r: 11, fill: '#9acdb8', stroke: '#1a4d3a', 'stroke-width': '1.2' }),
    h('circle', { cx: 58, cy: 28, r: 4, fill: '#fff', stroke: '#1a4d3a', 'stroke-width': '1.2' }),
    h('rect', { x: 55, y: 14, width: 6, height: 6, rx: 1.5, fill: '#1a4d3a' }),
    h('rect', { x: 55, y: 36, width: 6, height: 6, rx: 1.5, fill: '#1a4d3a' }),
    h('rect', { x: 44, y: 25, width: 6, height: 6, rx: 1.5, fill: '#1a4d3a' }),
    h('rect', { x: 66, y: 25, width: 6, height: 6, rx: 1.5, fill: '#1a4d3a' }),
  ]),
})
 
const BauingenieurwesenIcon = defineComponent({
  render: () => h('svg', { width: 52, height: 52, viewBox: '0 0 80 80', fill: 'none' }, [
    h('rect', { x: 34, y: 6, width: 8, height: 52, rx: 2.5, fill: '#f59e0b' }),
    h('rect', { x: 16, y: 8, width: 52, height: 7, rx: 2.5, fill: '#f59e0b' }),
    h('rect', { x: 14, y: 14, width: 13, height: 10, rx: 2, fill: '#92400e' }),
    h('line', { x1: 54, y1: 15, x2: 54, y2: 40, stroke: '#92400e', 'stroke-width': '1.8' }),
    h('line', { x1: 47, y1: 15, x2: 54, y2: 40, stroke: '#92400e', 'stroke-width': '1.2' }),
    h('line', { x1: 61, y1: 15, x2: 54, y2: 40, stroke: '#92400e', 'stroke-width': '1.2' }),
    h('rect', { x: 47, y: 40, width: 14, height: 11, rx: 2, fill: '#fbbf24', stroke: '#92400e', 'stroke-width': '1.2' }),
    h('rect', { x: 8, y: 48, width: 26, height: 24, rx: 2, fill: '#fed7aa', stroke: '#b45309', 'stroke-width': '1.2' }),
    h('rect', { x: 12, y: 52, width: 6, height: 6, rx: 1, fill: '#fef3c7' }),
    h('rect', { x: 21, y: 52, width: 6, height: 6, rx: 1, fill: '#fef3c7' }),
    h('rect', { x: 12, y: 61, width: 6, height: 11, rx: 1, fill: '#fef3c7' }),
    h('rect', { x: 21, y: 61, width: 6, height: 11, rx: 1, fill: '#fef3c7' }),
    h('rect', { x: 4, y: 70, width: 72, height: 5, rx: 2, fill: '#d97706', opacity: '0.3' }),
  ]),
})
 
// ── Daten aus der Laravel-API ────────────────────────────────────────────────
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Topbar from '../Components/Topbar.vue'

const router = useRouter()

const studyPrograms = ref([])
const loading = ref(true)
const errorMessage = ref('')

const search = ref('')

const filteredPrograms = computed(() => {
  if (!search.value) return studyPrograms.value

  return studyPrograms.value.filter(program =>
    program.name.toLowerCase().includes(search.value.toLowerCase())
  )
})

const iconPool = [
  InformatikIcon,
  WirtschaftsinformatikIcon,
  MaschinenbauIcon,
  BauingenieurwesenIcon,
]

function selectIcon(name, index) {
  const normalized = String(name || '').toLowerCase()

  if (normalized.includes('wirtschaft') && normalized.includes('informatik')) return WirtschaftsinformatikIcon
  if (normalized.includes('informatik')) return InformatikIcon
  if (normalized.includes('maschinen')) return MaschinenbauIcon
  if (normalized.includes('bau')) return BauingenieurwesenIcon

  return iconPool[index % iconPool.length]
}

onMounted(async () => {
  try {
    const response = await axios.get('/api/study-programs')

    studyPrograms.value = response.data.map((program, index) => ({
      id: program.id,
      name: program.name,
      description: program.description,
      subjectsCount: Array.isArray(program.subjects)
        ? program.subjects.length
        : Number(program.subjects_count || 0),
      icon: selectIcon(program.name, index),
      variant: String(program.name || '').toLowerCase().includes('bau') ? 'bau' : 'default',
    }))
  } catch (error) {
    console.error('Fehler beim Laden der Studiengänge:', error)
    errorMessage.value = 'Die Studiengänge konnten nicht geladen werden.'
  } finally {
    loading.value = false
  }
})

function goToSubjects(program) {
  router.push({
    path: '/subjects',
    query: { study_program_id: program.id },
  })
}
</script>
 
<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,800;1,700&family=DM+Sans:wght@300;400;500;600&display=swap');
 
.sg * { box-sizing: border-box; margin: 0; padding: 0; }
.sg {
  font-family: 'DM Sans', sans-serif;
  background: #edf6f1;
  --green-dark:  #1a4d3a;
  --green-mid:   #2d8c68;
  --green-light: #e6f4ec;
  --green-pale:  #edf6f1;
  --amber:       #f59e0b;
  --amber-dark:  #b45309;
  --amber-light: #fef3c7;
  --text-dark:   #0b2e20;
  --text-mid:    #2e6650;
  --text-muted:  #4a7a64;
}
 
/* Die Navigation kommt vollständig aus Components/Topbar.vue. */

/* HERO */
.sg-hero {
  position: relative;
  background: #c8e8d8;
  background-image: radial-gradient(circle, rgba(26,77,58,0.07) 1px, transparent 1px);
  background-size: 24px 24px;
  padding: 32px 44px 76px;
  overflow: hidden;
}
.sg-hero-photo {
  position: absolute;
  top: 0; right: 0;
  width: 52%; height: 100%;
  background-image: url('https://images.unsplash.com/photo-1564981797816-1043664bf78d?w=1200&q=85&auto=format&fit=crop');
  background-size: cover;
  background-position: center;
  -webkit-mask-image: linear-gradient(to right, transparent 0%, rgba(0,0,0,0.5) 35%, rgba(0,0,0,0.75) 100%);
  mask-image: linear-gradient(to right, transparent 0%, rgba(0,0,0,0.5) 35%, rgba(0,0,0,0.75) 100%);
  opacity: 0.45;
}
.sg-hero-blob1 {
  position: absolute; bottom: -50px; left: 30px;
  width: 190px; height: 190px; border-radius: 50%;
  background: rgba(26,77,58,0.07); pointer-events: none;
}
.sg-hero-blob2 {
  position: absolute; bottom: 10px; left: 200px;
  width: 100px; height: 100px; border-radius: 50%;
  background: rgba(26,77,58,0.05); pointer-events: none;
}
.sg-hero-wave { position: absolute; bottom: -1px; left: 0; right: 0; }
.sg-hero-inner { position: relative; z-index: 2; max-width: 500px; }
 
.sg-hero-badge {
  display: inline-flex; align-items: center; gap: 7px;
  font-size: 11px; font-weight: 600; letter-spacing: 0.09em;
  text-transform: uppercase; color: var(--green-mid);
  background: rgba(45,140,104,0.13); border: 1px solid rgba(45,140,104,0.22);
  border-radius: 20px; padding: 4px 12px; margin-bottom: 14px;
}
.sg-hero-title {
  font-family: 'Playfair Display', serif;
  font-size: 2.5rem; font-weight: 800; color: var(--text-dark);
  line-height: 1.08; margin-bottom: 12px; letter-spacing: -0.025em;
}
.sg-hero-title em { font-style: italic; color: var(--green-mid); }
.sg-hero-sub { font-size: 13.5px; color: var(--text-mid); line-height: 1.65; max-width: 360px; }
 
/* CARDS */
.sg-section { background: var(--green-pale); padding: 14px 40px 28px; }
.sg-section-label {
  font-size: 10.5px; font-weight: 600; letter-spacing: 0.09em;
  text-transform: uppercase; color: var(--text-muted);
  margin-bottom: 10px; max-width: 1180px; margin-left: auto; margin-right: auto;
}
.sg-grid {
  display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px; max-width: 1180px; margin: 0 auto;
}
 
.sg-card {
  width: 100%; text-align: left; font: inherit;
  background: #fff; border-radius: 16px;
  border: 1.5px solid rgba(26,77,58,0.07);
  border-left: 4px solid var(--green-mid);
  padding: 14px 14px 14px 12px;
  display: flex; align-items: center; gap: 12px;
  cursor: pointer; text-decoration: none;
  transition: transform 0.28s cubic-bezier(.34,1.4,.64,1), box-shadow 0.28s ease, border-color 0.18s;
  animation: sg-in 0.4s ease both;
}
.sg-card:nth-child(1) { animation-delay: .05s; }
.sg-card:nth-child(2) { animation-delay: .11s; }
.sg-card:nth-child(3) { animation-delay: .17s; }
.sg-card:nth-child(4) { animation-delay: .23s; }
@keyframes sg-in {
  from { opacity: 0; transform: translateY(14px); }
  to   { opacity: 1; transform: translateY(0); }
}
.sg-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 14px 36px rgba(26,77,58,0.11);
  border-color: rgba(26,77,58,0.16);
}
.sg-card--bau { border-left-color: var(--amber); }
.sg-card--bau:hover { border-color: rgba(180,83,9,0.18); }
 
.sg-illo {
  width: 72px; height: 72px; flex-shrink: 0;
  border-radius: 12px; background: var(--green-light);
  display: flex; align-items: center; justify-content: center; overflow: hidden;
  transition: transform 0.28s cubic-bezier(.34,1.4,.64,1);
}
.sg-card:hover .sg-illo { transform: scale(1.05) rotate(-2deg); }
.sg-card--bau .sg-illo { background: var(--amber-light); }
 
.sg-body { flex: 1; min-width: 0; }
.sg-name {
  font-family: 'Playfair Display', serif;
  font-size: 1rem; font-weight: 700; color: var(--text-dark);
  line-height: 1.2; margin-bottom: 2px;
}
.sg-degree { font-size: 11px; font-weight: 600; color: var(--green-mid); letter-spacing: 0.04em; margin-bottom: 5px; }
.sg-card--bau .sg-degree { color: var(--amber-dark); }
.sg-tags { font-size: 11.5px; color: var(--text-muted); line-height: 1.5; }
.sg-card--bau .sg-tags { color: #92400e; }
 
.sg-arrow {
  width: 32px; height: 32px; flex-shrink: 0; border-radius: 50%;
  background: var(--green-light); border: 1px solid rgba(26,77,58,0.1);
  display: flex; align-items: center; justify-content: center; color: var(--green-dark);
  transition: background 0.2s, color 0.2s, transform 0.28s cubic-bezier(.34,1.4,.64,1), border-color 0.2s;
}
.sg-card:hover .sg-arrow { background: var(--green-dark); color: #fff; transform: translateX(3px); border-color: transparent; }
.sg-card--bau .sg-arrow { background: var(--amber-light); color: var(--amber-dark); border-color: rgba(180,83,9,0.15); }
.sg-card--bau:hover .sg-arrow { background: var(--amber-dark); color: #fff; border-color: transparent; }
 
.sg-status { max-width: 1180px; margin: 20px auto; color: var(--text-muted); }
.sg-status--error { color: #b91c1c; }

.sg-footer { background: var(--green-pale); margin-top: -2px; }
 
@media (max-width: 980px) {
  .sg-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 680px) {
  .sg-hero { padding: 26px 20px 76px; }
  .sg-hero-title { font-size: 2rem; }
  .sg-section { padding: 12px 16px 24px; }
  .sg-grid { grid-template-columns: 1fr; }
  .sg-illo { width: 64px; height: 64px; }

}
.search-box{
  max-width:1180px;
  margin:20px auto;
}

.search-box input{
  width:100%;
  padding:14px 18px;
  border:1px solid #d7d7d7;
  border-radius:12px;
  font-size:16px;
}

.search-box input:focus{
  outline:none;
  border-color:#2d8c68;
}
</style>
 
