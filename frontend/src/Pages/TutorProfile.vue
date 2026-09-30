<template>
    <div class="app">
        <Topbar />

        <div class="profile-bg">
            <div v-if="loading" class="form-wrap">
                <div class="tc-card">Tutorprofil wird geladen...</div>
            </div>

            <div v-else-if="errorMessage" class="form-wrap">
                <div class="alert-error">{{ errorMessage }}</div>

                <RouterLink class="btn-back" to="/tutors">
                    ← Zurück zu allen Tutoren
                </RouterLink>
            </div>

            <div v-else-if="tutorProfile" class="profile-wrap">
                <aside>
                    <div class="tc-card sidebar-card">
                        <div class="avatar-circle">{{ initials }}</div>

                        <p class="sidebar-name">{{ userFullName }}</p>
                        <p class="sidebar-email">{{ tutorProfile.user?.email }}</p>

                        <hr class="sidebar-divider">

                        <button
                            type="button"
                            class="rating-button"
                            aria-label="Alle Bewertungen ansehen"
                            @click="openReviews"
                        >
                            <template v-if="reviewCount > 0">
                                <div class="rating-big">
                                    {{ averageRating }} <span>★</span>
                                </div>
                                <p class="rating-sub">
                                    als Tutor ({{ reviewCount }} {{ reviewCount === 1 ? 'Bewertung' : 'Bewertungen' }})
                                </p>
                            </template>

                            <p v-else class="empty-hint center rating-empty">
                                ☆ Noch keine Bewertung
                            </p>

                            <span class="rating-open-hint">Bewertungen ansehen →</span>
                        </button>

                        <hr class="sidebar-divider">

                        <div>
                            <div class="section-label">Stundenpreis</div>
                            <div class="price-row centered">
                                <span class="price-value">{{ formattedPrice }}</span>
                                <span class="price-unit">/ Std.</span>
                            </div>
                        </div>

                        <hr class="sidebar-divider">

                        <div class="left-text">
                            <div class="section-label">Zahlungsmethoden</div>

                            <span
                                v-for="method in tutorProfile.payment_methods"
                                :key="method.id"
                                class="pm-chip"
                            >
                                {{ method.name }}
                            </span>

                            <p v-if="!tutorProfile.payment_methods || tutorProfile.payment_methods.length === 0" class="empty-hint">
                                Keine Zahlungsart gespeichert
                            </p>
                        </div>

                        <hr class="sidebar-divider">

                        <div class="left-text">
                            <div class="section-label">Aktivität</div>

                            <div class="activity-row">
                                <span>Sitzungen gegeben</span>
                                <span class="activity-num">{{ sessionsDone }}</span>
                            </div>

                            <div class="activity-row">
                                <span>Bewertungen</span>
                                <span class="activity-num">{{ reviewCount }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="edit-button-wrap">
                        <RouterLink
                            class="btn-edit"
                            :to="{ path: '/chat', query: { tutor_profile_id: tutorProfile.id } }"
                        >
                            Nachricht schreiben
                        </RouterLink>
                    </div>
                </aside>

                <div class="right-col">
                    <div class="tc-card">
                        <div class="info-block-label">Hochschule</div>
                        <div class="hs-title">HS Bochum</div>
                        <div class="hs-sub">
                            {{ tutorProfile.user?.studiengang || 'Studiengang nicht angegeben' }}
                            <span v-if="tutorProfile.user?.semester">
                                · {{ tutorProfile.user.semester }}. Semester
                            </span>
                        </div>

                        <div v-if="tutorProfile.description">
                            <hr class="sidebar-divider">
                            <div class="info-block-label">Über mich</div>
                            <p class="description-text">{{ tutorProfile.description }}</p>
                        </div>
                    </div>

                    <div class="tc-card">
                        <div class="info-block-label">Tutorfächer</div>

                        <span
                            v-for="subject in tutorProfile.subjects"
                            :key="subject.id"
                            class="subject-chip"
                        >
                            {{ subject.name }}
                        </span>

                        <p v-if="!tutorProfile.subjects || tutorProfile.subjects.length === 0" class="empty-hint">
                            Keine Fächer gespeichert
                        </p>
                    </div>

                    <div v-if="tutorProfile.help_subjects && tutorProfile.help_subjects.length > 0" class="tc-card">
                        <div class="info-block-label">Braucht Hilfe in</div>

                        <p class="empty-hint" style="margin-top: 0;">
                            
                        </p>

                        <span
                            v-for="subject in tutorProfile.help_subjects"
                            :key="subject.id"
                            class="subject-chip"
                        >
                            {{ subject.name }}
                            <span v-if="subject.study_program">
                                ({{ subject.study_program.name }})
                            </span>
                        </span>
                    </div>

                    <div class="tc-card">
                        <div class="two-col">
                            <div>
                                <div class="info-block-label">Unterrichtsart</div>
                                <span class="mode-badge">{{ modeLabel }}</span>
                            </div>

                            <div>
                                <div class="info-block-label">Lernorte</div>

                                <div
                                    v-for="location in tutorProfile.locations"
                                    :key="location.id"
                                    class="location-line"
                                >
                                    📍 {{ location.location }}
                                </div>

                                <p v-if="!tutorProfile.locations || tutorProfile.locations.length === 0" class="empty-hint">
                                    Keine Orte gespeichert
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="tc-card">
                        <div class="info-block-label">Verfügbarkeit</div>

                        <div
                            v-if="tutorProfile.availabilities && tutorProfile.availabilities.length > 0"
                            class="avail-grid"
                        >
                            <div
                                v-for="availability in tutorProfile.availabilities"
                                :key="availability.id"
                                class="avail-card"
                            >
                                <div class="avail-day">
                                    {{ weekdayLabel(availability.weekday) }}
                                </div>

                                <div class="avail-time">
                                    {{ formatTime(availability.start_time) }} – {{ formatTime(availability.end_time) }} Uhr
                                </div>
                            </div>
                        </div>

                        <p v-else class="empty-hint">
                            Keine Verfügbarkeit gespeichert
                        </p>
                    </div>

                    <div v-if="tutorProfile.reviews && tutorProfile.reviews.length > 0" class="tc-card">
                        <div class="reviews-heading-row">
                            <div class="info-block-label">Bewertungen</div>
                            <button type="button" class="reviews-all-button" @click="openReviews">
                                Alle ansehen
                            </button>
                        </div>

                        <div
                            v-for="review in tutorProfile.reviews.slice(0, 3)"
                            :key="review.id"
                            class="review-row"
                        >
                            <div class="review-head">
                                <span>{{ review.student?.name || 'Studierender' }}</span>
                                <span class="review-stars">{{ stars(review.rating) }}</span>
                            </div>

                            <p v-if="review.comment" class="review-comment">
                                {{ review.comment }}
                            </p>
                        </div>
                    </div>

                    <div class="tc-card">
                        <RouterLink class="btn-back" to="/tutors">
                            ← Zurück zu allen Tutoren
                        </RouterLink>
                    </div>
                </div>
            </div>
        </div>

        <ReviewsModal
            :open="reviewsModalOpen"
            :tutor-profile-id="tutorProfile?.id"
            :title="`Bewertungen für ${userFullName}`"
            @close="reviewsModalOpen = false"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import Topbar from '../Components/Topbar.vue'
import ReviewsModal from '../Components/ReviewsModal.vue'
import api from '../api'

const route = useRoute()

const loading = ref(true)
const errorMessage = ref('')
const tutorProfile = ref(null)
const reviewsModalOpen = ref(false)

const weekdays = {
    monday: 'Montag',
    tuesday: 'Dienstag',
    wednesday: 'Mittwoch',
    thursday: 'Donnerstag',
    friday: 'Freitag',
    saturday: 'Samstag',
    sunday: 'Sonntag',
}

onMounted(async () => {
    await loadTutorProfile()
})

async function loadTutorProfile() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get(`/api/tutors/${route.params.id}`)
        tutorProfile.value = response.data
    } catch (error) {
        console.error(error)
        errorMessage.value = error.response?.data?.message || 'Tutorprofil konnte nicht geladen werden.'
    } finally {
        loading.value = false
    }
}

const userFullName = computed(() => {
    const user = tutorProfile.value?.user

    if (!user) {
        return 'Unbekannter Tutor'
    }

    return user.name
        || `${user.vorname ?? ''} ${user.nachname ?? ''}`.trim()
        || user.email
        || 'Unbekannter Tutor'
})

const initials = computed(() => {
    const user = tutorProfile.value?.user

    const first = user?.vorname?.[0] ?? userFullName.value?.[0] ?? ''
    const second = user?.nachname?.[0] ?? ''

    return `${first}${second}`.toUpperCase() || '?'
})

const reviewCount = computed(() => {
    return tutorProfile.value?.reviews?.length ?? 0
})

const averageRating = computed(() => {
    const reviews = tutorProfile.value?.reviews ?? []

    if (reviews.length === 0) {
        return '0,0'
    }

    const sum = reviews.reduce((total, review) => total + Number(review.rating ?? 0), 0)

    return (sum / reviews.length).toFixed(1).replace('.', ',')
})

const sessionsDone = computed(() => {
    return tutorProfile.value?.completed_bookings?.length
        ?? tutorProfile.value?.completedBookings?.length
        ?? 0
})

const formattedPrice = computed(() => {
    const price = Number(tutorProfile.value?.price_per_hour ?? 0)

    return price.toLocaleString('de-DE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }) + ' €'
})

const modeLabel = computed(() => {
    const labels = {
        online: 'Online',
        in_person: 'Vor Ort',
        presence: 'Vor Ort',
        both: 'Online & Vor Ort',
    }

    return labels[tutorProfile.value?.teaching_mode] ?? tutorProfile.value?.teaching_mode ?? 'Nicht angegeben'
})

function weekdayLabel(dayKey) {
    return weekdays[dayKey] ?? dayKey
}

function formatTime(time) {
    if (!time) {
        return ''
    }

    return String(time).slice(0, 5)
}

function stars(rating) {
    const value = Number(rating ?? 0)

    let output = ''

    for (let i = 1; i <= 5; i++) {
        output += i <= value ? '★' : '☆'
    }

    return output
}

function openReviews() {
    reviewsModalOpen.value = true
}
</script>

<style scoped>
.profile-bg {
    min-height: calc(100vh - 64px);
    background: #eaf5ef;
    padding: 2rem 1.25rem 4rem;
    color: #1f3f34;
}

.form-wrap,
.profile-wrap {
    max-width: 1100px;
    margin: 0 auto;
}

.profile-wrap {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 1.5rem;
    align-items: start;
}

.tc-card {
    background: white;
    border: 1px solid #c9e6d6;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 12px 30px rgba(45, 106, 79, .08);
}

.sidebar-card {
    text-align: center;
}

.avatar-circle {
    width: 92px;
    height: 92px;
    border-radius: 50%;
    background: #2d6a4f;
    color: white;
    display: grid;
    place-items: center;
    font-size: 2rem;
    font-weight: 800;
    margin: 0 auto 1rem;
    box-shadow: 0 8px 20px rgba(45, 106, 79, .22);
}

.sidebar-name {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 800;
}

.sidebar-email {
    color: #6b8b7c;
    margin: .25rem 0 0;
    font-size: .9rem;
}

.sidebar-divider {
    border: 0;
    border-top: 1px solid #c9e6d6;
    margin: 1.25rem 0;
}

.rating-button {
    width: 100%;
    border: 0;
    border-radius: 12px;
    padding: .4rem .25rem;
    background: transparent;
    color: inherit;
    cursor: pointer;
}

.rating-button:hover,
.rating-button:focus-visible {
    background: #f0faf3;
}

.rating-button:focus-visible {
    outline: 2px solid #2d6a4f;
    outline-offset: 3px;
}

.rating-big {
    font-size: 1.5rem;
    font-weight: 800;
    color: #2d6a4f;
}

.rating-big span,
.review-stars {
    color: #e08c00;
}

.rating-open-hint {
    display: inline-block;
    margin-top: .35rem;
    color: #2d6a4f;
    font-size: .76rem;
    font-weight: 800;
}

.rating-empty {
    margin-bottom: .15rem;
}

.rating-sub,
.empty-hint {
    color: #6b8b7c;
    font-size: .9rem;
}

.center {
    text-align: center;
}

.section-label,
.info-block-label {
    text-transform: uppercase;
    letter-spacing: .12em;
    font-size: .72rem;
    font-weight: 800;
    color: #6b8b7c;
    margin-bottom: .5rem;
}

.price-row {
    display: flex;
    align-items: baseline;
    gap: .35rem;
}

.centered {
    justify-content: center;
}

.price-value {
    color: #2d6a4f;
    font-size: 1.5rem;
    font-weight: 800;
}

.price-unit {
    color: #6b8b7c;
}

.left-text {
    text-align: left;
}

.pm-chip,
.subject-chip {
    display: inline-flex;
    background: #d8f3dc;
    color: #2d6a4f;
    border: 1px solid #b7e4c7;
    border-radius: 999px;
    padding: .28rem .65rem;
    margin: .2rem .2rem .2rem 0;
    font-size: .82rem;
    font-weight: 700;
}

.activity-row {
    display: flex;
    justify-content: space-between;
    font-size: .9rem;
    margin: .35rem 0;
}

.activity-num {
    font-weight: 800;
    color: #2d6a4f;
}

.edit-button-wrap {
    margin-top: 1rem;
}

.btn-edit,
.btn-back {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    text-decoration: none;
    background: #2d6a4f;
    color: white;
    border-radius: 10px;
    padding: .8rem 1rem;
    font-weight: 800;
    border: 0;
}

.btn-back {
    width: auto;
}

.right-col {
    display: grid;
    gap: 1.25rem;
}

.hs-title {
    font-size: 1.35rem;
    font-weight: 800;
}

.hs-sub,
.description-text,
.location-line,
.avail-time {
    color: #527266;
}

.two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.mode-badge {
    display: inline-flex;
    background: #2d6a4f;
    color: white;
    border-radius: 8px;
    padding: .45rem .75rem;
    font-weight: 800;
    font-size: .9rem;
}

.avail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: .75rem;
}

.avail-card {
    border: 1px solid #b7e4c7;
    background: #f0faf3;
    border-radius: 12px;
    padding: .85rem;
}

.avail-day {
    color: #2d6a4f;
    font-weight: 800;
    margin-bottom: .2rem;
}

.reviews-heading-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: .35rem;
}

.reviews-heading-row .info-block-label {
    margin-bottom: 0;
}

.reviews-all-button {
    border: 0;
    background: transparent;
    color: #2d6a4f;
    font-weight: 800;
    cursor: pointer;
}

.reviews-all-button:hover,
.reviews-all-button:focus-visible {
    text-decoration: underline;
}

.review-row {
    padding: .7rem 0;
    border-bottom: 1px solid #c9e6d6;
}

.review-row:last-child {
    border-bottom: none;
}

.review-head {
    display: flex;
    justify-content: space-between;
    font-weight: 700;
}

.review-comment {
    color: #6b8b7c;
    margin: .4rem 0 0;
}

.alert-error {
    background: #ffeaea;
    color: #c0392b;
    border: 1px solid #f5c6c6;
    border-radius: 10px;
    padding: .85rem 1.25rem;
    margin-bottom: 1rem;
    font-weight: 700;
}

@media (max-width: 768px) {
    .profile-wrap {
        grid-template-columns: 1fr;
    }

    .two-col {
        grid-template-columns: 1fr;
    }
}
</style>
