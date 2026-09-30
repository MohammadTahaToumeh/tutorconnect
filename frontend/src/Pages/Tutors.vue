<template>
    <div class="app">
        <Topbar />

        <div style="background:var(--bg);min-height:584px">
            <div class="tutors-page">
                <RouterLink class="tutors-back" :to="backTarget">
                    ← {{ backLabel }}
                </RouterLink>

                <div class="tutors-h">{{ selectedSubjectName }}</div>
                <div class="tutors-sub">{{ filteredTutors.length }} Tutor(en) verfügbar</div>

                <div v-if="loading" class="tutors-sub">
                    Tutoren werden geladen...
                </div>

                <div v-else-if="errorMessage" class="tutors-sub" style="color:#dc2626;">
                    {{ errorMessage }}
                </div>

                <div v-else-if="filteredTutors.length === 0" class="tutors-sub">
                    Für dieses Fach wurde noch kein Tutor gefunden.
                </div>

                <div v-else class="tutor-grid">
                    <div v-for="tutor in filteredTutors" :key="tutor.id" class="tutor-card">
                        <div class="tc-top">
                            <div class="tc-av">
                                {{ getInitials(tutor) }}
                            </div>

                            <div>
                                <div class="tc-name">{{ getTutorName(tutor) }}</div>
                                <div class="tc-sem">{{ getModeLabel(tutor.teaching_mode) }}</div>

                                <button
                                    type="button"
                                    class="tc-stars tc-stars-button"
                                    :aria-label="`Bewertungen von ${getTutorName(tutor)} ansehen`"
                                    @click="openReviews(tutor)"
                                >
                                    <span class="tc-stars-icons">{{ getStars(tutor) }}</span>
                                    <span>{{ getReviewText(tutor) }}</span>
                                </button>

                                <div class="tc-price">
                                    {{ tutor.price_per_hour }} € / Stunde · {{ getPaymentMethods(tutor) }}
                                </div>
                            </div>
                        </div>

                        <div class="tc-sl">Beschreibung:</div>
                        <div class="tutors-sub" style="margin-bottom:12px;">
                            {{ tutor.description || 'Keine Beschreibung vorhanden.' }}
                        </div>

                        <div class="tc-sl">Fächer:</div>

                        <div class="tc-tags">
                            <span
                                v-for="subject in tutor.subjects"
                                :key="subject.id"
                                class="tc-tag"
                            >
                                {{ subject.name }}
                            </span>
                        </div>

                        <div class="tc-bot">
                            <span class="av-g">{{ getLocation(tutor) }}</span>

                            <div class="tutor-actions">
                                <RouterLink
                                    class="btn-kont btn-profile"
                                    :to="{ name: 'tutor-profile', params: { id: tutor.id } }"
                                >
                                    Profil ansehen
                                </RouterLink>

                                <RouterLink
                                    class="btn-kont"
                                    :to="{ path: '/chat', query: { tutor_profile_id: tutor.id } }"
                                >
                                    Kontakt aufnehmen
                                </RouterLink>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ReviewsModal
            :open="reviewsModalOpen"
            :tutor-profile-id="reviewsModalTutorId"
            :title="reviewsModalTitle"
            @close="closeReviews"
        />
    </div>
</template>

<script setup>
import Topbar from '../Components/Topbar.vue'
import ReviewsModal from '../Components/ReviewsModal.vue'
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'

const tutors = ref([])
const subjects = ref([])
const loading = ref(true)
const errorMessage = ref('')
const reviewsModalOpen = ref(false)
const reviewsModalTutorId = ref(null)
const reviewsModalTitle = ref('')

const route = useRoute()
const selectedSubjectId = computed(() => route.query.subject_id ?? null)
const selectedStudyProgramId = computed(() => route.query.study_program_id ?? null)

const backTarget = computed(() => {
    if (selectedStudyProgramId.value) {
        return {
            path: '/subjects',
            query: { study_program_id: selectedStudyProgramId.value },
        }
    }

    return { path: '/study-programs' }
})

const backLabel = computed(() =>
    selectedStudyProgramId.value
        ? 'Zurück zur Fächerübersicht'
        : 'Zurück zu den Studiengängen'
)

onMounted(async () => {
    try {
        const [tutorsResponse, subjectsResponse] = await Promise.all([
            api.get('/api/tutors'),
            api.get('/api/subjects'),
        ])

        tutors.value = tutorsResponse.data
        subjects.value = subjectsResponse.data
    } catch (error) {
        console.error('Fehler beim Laden der Tutoren:', error)
        errorMessage.value = 'Die Tutoren konnten nicht geladen werden.'
    } finally {
        loading.value = false
    }
})

const filteredTutors = computed(() => {
    if (!selectedSubjectId.value) {
        return tutors.value
    }

    return tutors.value.filter((tutor) => {
        return tutor.subjects?.some((subject) => String(subject.id) === String(selectedSubjectId.value))
    })
})

const selectedSubjectName = computed(() => {
    if (!selectedSubjectId.value) {
        return 'Alle Tutoren'
    }

    const subject = subjects.value.find(
        (item) => String(item.id) === String(selectedSubjectId.value)
    )

    return subject ? `Tutoren für ${subject.name}` : 'Tutoren'
})

function getTutorName(tutor) {
    return tutor.user?.name || `${tutor.user?.vorname ?? ''} ${tutor.user?.nachname ?? ''}`.trim() || `Tutor #${tutor.user_id}`
}

function getInitials(tutor) {
    const name = getTutorName(tutor)

    return name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()
}

function getModeLabel(mode) {
    if (mode === 'online') return 'Online'
    if (mode === 'in_person') return 'Vor Ort'
    if (mode === 'presence') return 'Vor Ort'
    if (mode === 'both') return 'Online & vor Ort'

    return 'Flexibel'
}

function getPaymentMethods(tutor) {
    if (!tutor.payment_methods || tutor.payment_methods.length === 0) {
        return 'Keine Angabe'
    }

    return tutor.payment_methods.map((method) => method.name).join(', ')
}

function getLocation(tutor) {
    if (!tutor.locations || tutor.locations.length === 0) {
        return 'Ort offen'
    }

    return tutor.locations[0].location
}

function getReviewText(tutor) {
    const count = tutor.reviews?.length ?? 0

    if (count === 0) {
        return 'Noch keine Bewertungen'
    }

    return `${count} ${count === 1 ? 'Bewertung' : 'Bewertungen'}`
}

function getStars(tutor) {
    const reviews = tutor.reviews ?? []

    if (reviews.length === 0) {
        return '☆☆☆☆☆'
    }

    const average = reviews.reduce(
        (sum, review) => sum + Number(review.rating || 0),
        0
    ) / reviews.length

    const rounded = Math.round(average)

    return '★'.repeat(rounded) + '☆'.repeat(5 - rounded)
}

function openReviews(tutor) {
    reviewsModalTutorId.value = tutor.id
    reviewsModalTitle.value = `Bewertungen für ${getTutorName(tutor)}`
    reviewsModalOpen.value = true
}

function closeReviews() {
    reviewsModalOpen.value = false
}
</script>

<style scoped>
.tc-stars-button {
    appearance: none;
    border: 0;
    background: transparent;
    padding: 0;
    font: inherit;
    text-align: left;
    cursor: pointer;
}

.tc-stars-button:hover span:last-child,
.tc-stars-button:focus-visible span:last-child {
    text-decoration: underline;
}

.tc-stars-button:focus-visible {
    outline: 2px solid #2d6a4f;
    outline-offset: 4px;
    border-radius: 6px;
}

.tc-stars-icons {
    color: #e08c00;
    letter-spacing: 1px;
}

.tutor-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.btn-profile {
    background: #2d6a4f;
}

@media (max-width: 700px) {
    .tc-bot {
        align-items: flex-start;
        gap: 12px;
        flex-direction: column;
    }

    .tutor-actions {
        width: 100%;
        justify-content: flex-start;
    }
}
</style>
