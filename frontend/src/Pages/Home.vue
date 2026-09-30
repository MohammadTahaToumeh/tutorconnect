
<template>
    <div class="app">
        <Topbar />

        <div class="landing">
            <div class="landing-body">
                <div class="landing-left">
                    <div class="landing-eyebrow">
                        Hochschule Bochum · TutorConnect
                    </div>

                    <div class="landing-h">
                        Lerne von<br>
                        Kommilitonen<span class="landing-dot">.</span>
                    </div>

                    <div class="landing-sub">
                        <template v-if="auth.isAuthenticated">
                            Finde passende Tutoren für deinen Studiengang
                            und dein Fach oder biete selbst Nachhilfe für
                            andere Studierende an.
                        </template>

                        <template v-else>
                            Entdecke beliebte Fächer und verfügbare
                            Tutoren. Melde dich an, um alle Details
                            und Funktionen zu nutzen.
                        </template>
                    </div>

                    <div class="landing-checks">
                        <div
                            v-for="item in checks"
                            :key="item"
                            class="lc"
                        >
                            <div class="lc-ic">
                                ✓
                            </div>

                            {{ item }}
                        </div>
                    </div>

                    <div class="landing-cta">
                        <RouterLink
                            class="btn-cta-main"
                            :to="studyProgramsTarget"
                        >
                            Studiengang auswählen
                        </RouterLink>

                        <RouterLink
                            class="btn-cta-sec"
                            :to="tutorsTarget"
                        >
                            Alle Tutoren
                        </RouterLink>
                    </div>
                </div>

                <div class="landing-right">
                    <div class="mockup-frame">
                        <div class="mf-bar">
                            <div
                                class="mf-dot"
                                style="background:#ff5f57"
                            ></div>

                            <div
                                class="mf-dot"
                                style="background:#febc2e"
                            ></div>

                            <div
                                class="mf-dot"
                                style="background:#28c840"
                            ></div>

                            <div class="mf-url">
                                TutorConnect
                            </div>
                        </div>

                        <div class="mf-content">
                            <div class="mf-header">
                                <span class="mf-logo">
                                    TutorConnect
                                </span>

                                <div class="mf-nav">
                                    <div class="mf-nl"></div>
                                    <div class="mf-nl"></div>
                                    <div class="mf-nl"></div>
                                </div>
                            </div>

                                <div class="mf-title">
                                    Beliebte Fächer
                                </div>

                                <div
                                    v-if="loading"
                                    class="mf-title small"
                                >
                                    Daten werden geladen …
                                </div>

                                <div
                                    v-else
                                    class="mf-cards"
                                >
                                    <div
                                        v-for="subject in previewSubjects"
                                        :key="subject.id"
                                        class="mf-card"
                                    >
                                        <span class="mf-card-ic">
                                            {{ subjectIcon(subject.name) }}
                                        </span>

                                        <div class="mf-card-t">
                                            {{ subject.name }}
                                        </div>

                                        <span class="mf-card-b">
                                            {{
                                                subject.study_program?.name
                                                || 'Fach'
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mf-title small">
                                    Verfügbare Tutoren
                                </div>

                                <template v-if="previewTutors.length">
                                    <div
                                        v-for="tutor in previewTutors"
                                        :key="tutor.id"
                                        class="mf-tutor-row"
                                    >
                                        <div class="mf-tav">
                                            {{ tutorInitials(tutor) }}
                                        </div>

                                        <div class="mf-tinfo">
                                            <div class="mf-tname">
                                                {{ tutorName(tutor) }}
                                            </div>

                                            <div class="mf-tmeta">
                                                {{
                                                    tutorMode(
                                                        tutor.teaching_mode
                                                    )
                                                }}
                                                ·
                                                {{ tutorPrice(tutor) }}
                                            </div>
                                        </div>

                                        <RouterLink
                                            class="mf-tbtn"
                                            :to="tutorsTarget"
                                        >
                                            ANSEHEN
                                        </RouterLink>
                                    </div>
                                </template>

                                <div
                                    v-else-if="!loading"
                                    class="mf-title small"
                                >
                                    Noch keine Tutoren eingetragen.
                                </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="landing-stats">
                <div
                    v-for="stat in stats"
                    :key="stat.label"
                    class="lst"
                >
                    <div class="lst-n">
                        {{ stat.value }}
                    </div>

                    <div class="lst-l">
                        {{ stat.label }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../api'
import { useAuthStore } from '../auth'
import Topbar from '../Components/Topbar.vue'

const auth = useAuthStore()
const subjects = ref([])
const tutors = ref([])
const loading = ref(true)

const statistics = ref({
    study_programs: 0,
    subjects: 0,
    tutors: 0,
})

const checks = [
    'Studiengänge und Fächer übersichtlich auswählen',
    'Passende Tutoren direkt nach Fach finden',
    'Online oder vor Ort flexibel lernen',
    'Selbst ein Tutor-Profil erstellen',
]

const studyProgramsTarget = computed(() =>
    auth.isAuthenticated
        ? '/study-programs'
        : {
            name: 'login',
            query: {
                redirect: '/study-programs',
            },
        }
)

const tutorsTarget = computed(() =>
    auth.isAuthenticated
        ? '/tutors'
        : {
            name: 'login',
            query: {
                redirect: '/tutors',
            },
        }
)

const previewSubjects = computed(() =>
    subjects.value.slice(0, 3)
)

const previewTutors = computed(() =>
    tutors.value.slice(0, 2)
)

const stats = computed(() => [
    {
        value: statistics.value.study_programs,
        label: 'Studiengänge',
    },
    {
        value: statistics.value.subjects,
        label: 'Verfügbare Fächer',
    },
    {
        value: statistics.value.tutors,
        label: 'Aktive Tutoren',
    },
])

onMounted(async () => {
    try {
        const response = await api.get('/api/home')

        statistics.value = response.data.statistics
        subjects.value = response.data.subjects
        tutors.value = response.data.tutors
    } catch (error) {
        console.error(
            'Startseitendaten konnten nicht geladen werden:',
            error
        )
    } finally {
        loading.value = false
    }
})

function tutorName(tutor) {
    return tutor.user?.name
        || `${
            tutor.user?.vorname ?? ''
        } ${
            tutor.user?.nachname ?? ''
        }`.trim()
        || `Tutor #${tutor.user_id}`
}

function tutorInitials(tutor) {
    return tutorName(tutor)
        .split(' ')
        .filter(Boolean)
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()
}

function tutorMode(mode) {
    if (mode === 'online') {
        return 'Online'
    }

    if (mode === 'in_person') {
        return 'Vor Ort'
    }

    if (mode === 'both') {
        return 'Online & vor Ort'
    }

    return 'Flexibel'
}

function tutorPrice(tutor) {
    if (
        tutor.price_per_hour === null
        || tutor.price_per_hour === undefined
    ) {
        return 'Preis auf Anfrage'
    }

    return `${tutor.price_per_hour} €/h`
}

function subjectIcon(name) {
    const normalizedName = name.toLowerCase()

    if (
        normalizedName.includes('mathe')
        || normalizedName.includes('analysis')
    ) {
        return '🔢'
    }

    if (
        normalizedName.includes('programm')
        || normalizedName.includes('informatik')
    ) {
        return '💻'
    }

    if (normalizedName.includes('daten')) {
        return '🗄️'
    }

    if (normalizedName.includes('elektro')) {
        return '⚡'
    }

    if (normalizedName.includes('wirtschaft')) {
        return '📊'
    }

    return '📘'
}
</script>

