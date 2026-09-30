<template>
    <div class="app">
        <Topbar />

        <main class="dash-page">
            <section class="dash-hero">
                <div class="dash-hero-inner">
                    <div class="dash-avatar">{{ initials(displayName(currentUser)) }}</div>

                    <div class="dash-hero-text">
                        <div class="dash-kicker">Dashboard</div>
                        <h1>{{ displayName(currentUser) || 'Mein Dashboard' }}</h1>
                        <p class="dash-hint">
                            Verwalte deine Anfragen, geplanten Termine und vergangenen Sitzungen an einem Ort.
                        </p>
                    </div>

                    <div class="dash-new-badge" v-if="hasTutorProfile && openTutorBookings.length">
                        <span class="dash-new-dot"></span>
                        {{ openTutorBookings.length }} neue {{ openTutorBookings.length === 1 ? 'Anfrage' : 'Anfragen' }}
                    </div>
                </div>
            </section>

            <p v-if="successMessage" class="dash-message dash-success">{{ successMessage }}</p>
            <p v-if="errorMessage" class="dash-message dash-error">{{ errorMessage }}</p>

            <section class="dash-grid">
                <div class="dash-main">
                    <section v-if="hasTutorProfile" class="dashboard-panel">
                        <div class="collapsible-head">
                            <button
                                type="button"
                                class="section-toggle"
                                :aria-expanded="sectionOpen.incoming"
                                @click="toggleSection('incoming')"
                            >
                                <span class="section-title-wrap">
                                    <span class="head-tab"></span>
                                    <span class="section-title">Eingehende Anfragen</span>
                                    <span class="section-count">{{ incomingBookings.length }}</span>
                                </span>
                                <span class="section-chevron" :class="{ open: sectionOpen.incoming }">⌄</span>
                            </button>

                            
                        </div>

                        <Transition name="section-slide">
                            <div v-if="sectionOpen.incoming" class="section-body">
                                <div v-if="loading" class="dash-empty">
                                    <span class="dash-empty-ic">⏳</span>
                                    Anfragen werden geladen...
                                </div>

                                <div v-else-if="incomingBookings.length === 0" class="dash-empty">
                                    <span class="dash-empty-ic">📭</span>
                                    Keine offenen oder geplanten eingehenden Anfragen.
                                </div>

                                <div v-else class="request-list">
                                    <article
                                        v-for="booking in incomingBookings"
                                        :key="booking.id"
                                        class="request-card"
                                        :class="statusClass(booking.status)"
                                    >
                                        <div class="request-top">
                                            <div>
                                                <h3>{{ displayName(booking.student) || 'Unbekannter Nutzer' }}</h3>
                                                <p>{{ booking.subject?.name || 'Fach nicht angegeben' }}</p>
                                            </div>
                                            <span class="status-badge" :class="statusBadgeClass(booking.status)">
                                                <span class="status-dot"></span>
                                                {{ bookingStatusLabel(booking) }}
                                            </span>
                                        </div>

                                        <div class="request-perforation"></div>

                                        <div class="request-details">
                                            <div>
                                                <span>Termin</span>
                                                <strong>{{ formatDate(booking.requested_date) }}</strong>
                                            </div>
                                            <div>
                                                <span>Uhrzeit</span>
                                                <strong>{{ formatTimeRange(booking) }}</strong>
                                            </div>
                                            <div>
                                                <span>Modus</span>
                                                <strong>{{ modeLabel(booking.mode) }}</strong>
                                            </div>
                                            <div>
                                                <span>Art</span>
                                                <strong>{{ requestTypeLabel(booking.request_type) }}</strong>
                                            </div>
                                            <div>
                                                <span>Zahlung / Tausch</span>
                                                <strong>{{ booking.payment_method?.name || 'Keine Auswahl' }}</strong>
                                            </div>
                                        </div>

                                        <div v-if="booking.request_type === 'paid'" class="payment-strip" :class="paymentStatusClass(booking)">
                                            <div>
                                                <span>Betrag</span>
                                                <strong>{{ formatMoney(booking) }}</strong>
                                            </div>
                                            <div>
                                                <span>Zahlungsstatus</span>
                                                <strong>{{ paymentStatusLabel(booking) }}</strong>
                                            </div>
                                        </div>

                                        <div v-if="hasText(booking.message)" class="request-note">
                                            <span>Nachricht / Thema</span>
                                            <p>{{ booking.message }}</p>
                                        </div>

                                        <div v-if="hasText(booking.exchange_offer)" class="request-note exchange-note">
                                            <span>Tauschvorschlag</span>
                                            <p>{{ booking.exchange_offer }}</p>
                                        </div>

                                        <div class="request-actions" v-if="booking.status === 'open'">
                                            <button
                                                class="btn-accept"
                                                :disabled="actionLoading === booking.id"
                                                @click="changeStatus(booking, 'accepted')"
                                            >
                                                Annehmen ✓
                                            </button>
                                            <button
                                                class="btn-decline"
                                                :disabled="actionLoading === booking.id"
                                                @click="changeStatus(booking, 'declined')"
                                            >
                                                Ablehnen
                                            </button>
                                        </div>

                                        <div
                                            class="request-actions"
                                            v-if="booking.status === 'accepted' && bookingPhase(booking) === 'upcoming'"
                                        >
                                            <button
                                                class="btn-cancel"
                                                :disabled="actionLoading === booking.id"
                                                @click="cancelBooking(booking)"
                                            >
                                                {{ actionLoading === booking.id ? 'Wird abgesagt...' : 'Termin absagen' }}
                                            </button>
                                        </div>

                                        <div
                                            v-else-if="booking.status === 'accepted' && bookingPhase(booking) === 'running'"
                                            class="booking-phase-note is-running"
                                        >
                                            <span class="live-dot"></span>
                                            Termin läuft gerade
                                        </div>

                                        <div
                                            v-else-if="booking.status === 'accepted' && bookingPhase(booking) === 'ended'"
                                            class="booking-phase-note"
                                        >
                                            Termin ist beendet und wird automatisch abgeschlossen.
                                        </div>
                                    </article>
                                </div>
                            </div>
                        </Transition>
                    </section>

                    <section class="dashboard-panel">
                        <div class="collapsible-head">
                            <button
                                type="button"
                                class="section-toggle"
                                :aria-expanded="sectionOpen.sent"
                                @click="toggleSection('sent')"
                            >
                                <span class="section-title-wrap">
                                    <span class="head-tab"></span>
                                    <span class="section-title">Meine gesendeten Anfragen</span>
                                    <span class="section-count">{{ sentBookings.length }}</span>
                                </span>
                                <span class="section-chevron" :class="{ open: sectionOpen.sent }">⌄</span>
                            </button>
                        </div>

                        <Transition name="section-slide">
                            <div v-if="sectionOpen.sent" class="section-body">
                                <div v-if="loading" class="dash-empty small">
                                    <span class="dash-empty-ic">⏳</span>
                                    Anfragen werden geladen...
                                </div>

                                <div v-else-if="sentBookings.length === 0" class="dash-empty small">
                                    <span class="dash-empty-ic">📤</span>
                                    Du hast aktuell keine offenen oder geplanten Anfragen.
                                </div>

                                <div v-else class="request-list compact">
                                    <article
                                        v-for="booking in sentBookings"
                                        :key="booking.id"
                                        class="request-card student-card"
                                        :class="statusClass(booking.status)"
                                    >
                                        <div class="request-top">
                                            <div>
                                                <h3>{{ displayName(booking.tutor_profile?.user) || 'Tutor' }}</h3>
                                                <p>{{ booking.subject?.name || 'Fach nicht angegeben' }}</p>
                                            </div>
                                            <span class="status-badge" :class="statusBadgeClass(booking.status)">
                                                <span class="status-dot"></span>
                                                {{ bookingStatusLabel(booking) }}
                                            </span>
                                        </div>

                                        <div class="student-line">
                                            {{ formatDate(booking.requested_date) }} ·
                                            {{ formatTimeRange(booking) }} ·
                                            {{ requestTypeLabel(booking.request_type) }}
                                        </div>

                                        <div v-if="booking.request_type === 'paid'" class="paypal-payment-box" :class="paymentStatusClass(booking)">
                                            <div class="paypal-payment-head">
                                                <div>
                                                    <span class="payment-kicker">Zahlung</span>
                                                    <strong>{{ formatMoney(booking) }}</strong>
                                                </div>
                                                <span class="payment-status-pill">{{ paymentStatusLabel(booking) }}</span>
                                            </div>

                                            <p v-if="booking.status === 'open'">
                                                Die Zahlung wird erst freigeschaltet, wenn der Tutor die Anfrage annimmt.
                                            </p>
                                            <p v-else-if="canPayWithPayPal(booking)">
                                                Bezahle den Termin bitte vor Beginn sicher über PayPal.
                                            </p>
                                            <p v-else-if="booking.payment_status === 'paid'">
                                                PayPal hat die Zahlung bestätigt.
                                            </p>
                                            <p v-else-if="booking.payment_status === 'refunded'">
                                                Der Betrag wurde über PayPal zurückerstattet.
                                            </p>
                                            <p v-else-if="!isPayPalBooking(booking)">
                                                Zahlungsart: {{ booking.payment_method?.name || 'Nicht angegeben' }}
                                            </p>

                                            <button
                                                v-if="canPayWithPayPal(booking)"
                                                type="button"
                                                class="paypal-pay-btn"
                                                :disabled="paymentActionLoading === booking.id"
                                                @click="startPayPalPayment(booking)"
                                            >
                                                {{ paymentActionLoading === booking.id ? 'PayPal wird geöffnet...' : `Mit PayPal bezahlen · ${formatMoney(booking)}` }}
                                            </button>
                                        </div>
                                    </article>
                                </div>
                            </div>
                        </Transition>
                    </section>

                    <section class="dashboard-panel">
                        <div class="collapsible-head">
                            <button
                                type="button"
                                class="section-toggle"
                                :aria-expanded="sectionOpen.past"
                                @click="toggleSection('past')"
                            >
                                <span class="section-title-wrap">
                                    <span class="head-tab past-tab"></span>
                                    <span class="section-title">Vergangene Anfragen &amp; Sitzungen</span>
                                    <span class="section-count">{{ pastBookings.length }}</span>
                                </span>
                                <span class="section-chevron" :class="{ open: sectionOpen.past }">⌄</span>
                            </button>
                        </div>

                        <Transition name="section-slide">
                            <div v-if="sectionOpen.past" class="section-body">
                                <div v-if="loading" class="dash-empty small">
                                    <span class="dash-empty-ic">⏳</span>
                                    Vergangene Einträge werden geladen...
                                </div>

                                <div v-else-if="pastBookings.length === 0" class="dash-empty small">
                                    <span class="dash-empty-ic">🕘</span>
                                    Noch keine vergangenen Anfragen oder Sitzungen.
                                </div>

                                <div v-else class="request-list compact">
                                    <article
                                        v-for="booking in pastBookings"
                                        :key="`${booking._dashboardRole}-${booking.id}`"
                                        class="request-card student-card past-card"
                                        :class="statusClass(booking.status)"
                                    >
                                        <div class="request-top">
                                            <div>
                                                <div class="past-role">
                                                    {{ booking._dashboardRole === 'tutor' ? 'Als Tutor' : 'Als Schüler' }}
                                                </div>
                                                <h3>
                                                    {{
                                                        booking._dashboardRole === 'tutor'
                                                            ? (displayName(booking.student) || 'Unbekannter Nutzer')
                                                            : (displayName(booking.tutor_profile?.user) || 'Tutor')
                                                    }}
                                                </h3>
                                                <p>{{ booking.subject?.name || 'Fach nicht angegeben' }}</p>
                                            </div>

                                            <span class="status-badge" :class="statusBadgeClass(booking.status)">
                                                <span class="status-dot"></span>
                                                {{ statusLabel(booking.status) }}
                                            </span>
                                        </div>

                                        <div class="student-line">
                                            {{ formatDate(booking.requested_date) }} ·
                                            {{ formatTimeRange(booking) }} ·
                                            {{ requestTypeLabel(booking.request_type) }}
                                        </div>

                                        <div v-if="booking.request_type === 'paid'" class="past-payment-line" :class="paymentStatusClass(booking)">
                                            <span>{{ booking.payment_method?.name || 'Zahlung' }}</span>
                                            <strong>{{ formatMoney(booking) }} · {{ paymentStatusLabel(booking) }}</strong>
                                        </div>

                                        <div
                                            v-if="booking.status === 'completed' && booking._dashboardRole === 'student' && booking.review"
                                            class="review-given"
                                        >
                                            Deine Bewertung:
                                            <span class="review-stars-mini">{{ starString(booking.review.rating) }}</span>
                                            <p v-if="hasText(booking.review.comment)" class="review-given-comment">
                                                {{ booking.review.comment }}
                                            </p>
                                        </div>

                                        <div
                                            v-else-if="booking.status === 'completed' && booking._dashboardRole === 'student'"
                                            class="review-form"
                                        >
                                            <div class="review-form-stars">
                                                <button
                                                    v-for="n in 5"
                                                    :key="n"
                                                    type="button"
                                                    class="star-pick"
                                                    :class="{ filled: n <= (getDraft(booking.id).rating || 0) }"
                                                    :aria-label="`${n} von 5 Sternen bewerten`"
                                                    @click="setReviewRating(booking.id, n)"
                                                >★</button>
                                            </div>

                                            <textarea
                                                v-model="getDraft(booking.id).comment"
                                                class="review-comment"
                                                rows="2"
                                                placeholder="Wie war der Termin? (optional)"
                                            ></textarea>

                                            <button
                                                class="btn-review-submit"
                                                :disabled="!getDraft(booking.id).rating || reviewSubmitting === booking.id"
                                                @click="submitReview(booking)"
                                            >
                                                {{ reviewSubmitting === booking.id ? 'Wird gespeichert...' : 'Bewertung abschicken' }}
                                            </button>
                                        </div>

                                        <div
                                            v-else-if="booking.status === 'completed' && booking._dashboardRole === 'tutor' && booking.review"
                                            class="review-given"
                                        >
                                            Bewertung erhalten:
                                            <span class="review-stars-mini">{{ starString(booking.review.rating) }}</span>
                                            <p v-if="hasText(booking.review.comment)" class="review-given-comment">
                                                {{ booking.review.comment }}
                                            </p>
                                        </div>

                                        <div
                                            v-else-if="booking.status === 'completed' && booking._dashboardRole === 'tutor'"
                                            class="review-waiting"
                                        >
                                            Noch keine Bewertung erhalten.
                                        </div>
                                    </article>
                                </div>
                            </div>
                        </Transition>
                    </section>
                </div>

                <aside class="dash-side">
                    <template v-if="hasTutorProfile">
                        <div class="stat-duo">
                            <div class="stat-card">
                                <div class="stat-corner"></div>
                                <strong>{{ acceptedTutorBookings.length }}</strong>
                                <span>Geplante Termine</span>
                            </div>

                            <div class="stat-card amber">
                                <div class="stat-corner"></div>
                                <strong>{{ incomingBookings.length }}</strong>
                                <span>Aktive Eingänge</span>
                            </div>
                        </div>

                        <div class="help-card">
                            <h3><span class="help-ic">i</span>Anfragen verwalten</h3>
                            <p>Neue Anfragen annehmen oder ablehnen. Angenommene Termine können vor Beginn abgesagt werden.</p>
                        </div>
                    </template>

                    <template v-else>
                        <div class="stat-duo">
                            <div class="stat-card">
                                <div class="stat-corner"></div>
                                <strong>{{ acceptedStudentBookings.length }}</strong>
                                <span>Geplante Termine</span>
                            </div>

                            <div class="stat-card amber">
                                <div class="stat-corner"></div>
                                <strong>{{ sentBookings.length }}</strong>
                                <span>Aktive Anfragen</span>
                            </div>
                        </div>

                        <div class="help-card">
                            <h3><span class="help-ic">i</span>Deine Anfragen</h3>
                            <p>Hier siehst du offene und geplante Anfragen. Beendete, abgelehnte oder abgesagte Einträge findest du unter „Vergangene“.</p>
                        </div>
                    </template>

                    <div class="reviews-card" v-if="hasTutorProfile">
                        <h3>Bewertungen &amp; Feedback</h3>

                        <div v-if="reviewsLoading" class="reviews-empty">Bewertungen werden geladen...</div>

                        <div v-else-if="reviews.count === 0" class="reviews-empty">
                            Noch keine Bewertungen.
                        </div>

                        <template v-else>
                            <button
                                type="button"
                                class="reviews-summary-button"
                                aria-label="Alle Bewertungen ansehen"
                                @click="openReviewsModal"
                            >
                                <div class="reviews-score">
                                    <strong>{{ reviews.average }}</strong>
                                    <span>von 5</span>
                                </div>

                                <div class="reviews-stars">
                                    <span
                                        v-for="n in 5"
                                        :key="n"
                                        class="star"
                                        :class="{ filled: n <= Math.round(reviews.average) }"
                                    >★</span>
                                </div>

                                <div class="reviews-count">
                                    {{ reviews.count }} {{ reviews.count === 1 ? 'Bewertung' : 'Bewertungen' }}
                                </div>

                                <span class="reviews-open-hint">Alle ansehen →</span>
                            </button>

                            <div class="reviews-list">
                                <div v-for="review in reviews.reviews.slice(0, 3)" :key="review.id" class="review-item">
                                    <div class="review-top">
                                        <span>{{ displayName(review.student) || 'Anonym' }}</span>
                                        <span class="review-stars-mini">{{ starString(review.rating) }}</span>
                                    </div>
                                    <p v-if="hasText(review.comment)">{{ review.comment }}</p>
                                </div>
                            </div>
                        </template>
                    </div>
                </aside>
            </section>
        </main>

        <ReviewsModal
            :open="reviewsModalOpen"
            :tutor-profile-id="currentUser?.tutor_profile?.id"
            :title="`Bewertungen für ${displayName(currentUser)}`"
            @close="reviewsModalOpen = false"
        />
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import Topbar from '../Components/Topbar.vue'
import ReviewsModal from '../Components/ReviewsModal.vue'
import api from '../api'

const loading = ref(false)
const actionLoading = ref(null)
const paymentActionLoading = ref(null)
const reviewSubmitting = ref(null)
const errorMessage = ref('')
const successMessage = ref('')
const reviewsModalOpen = ref(false)
const currentUser = ref(null)
const now = ref(new Date())
let clockTimer = null
let dashboardRefreshTimer = null
const bookings = ref({
    as_tutor: [],
    as_student: [],
})
const sectionOpen = reactive({
    incoming: false,
    sent: false,
    past: false,
})
const sectionDefaultsApplied = ref(false)
const reviewDrafts = reactive({})
const reviews = ref({ average: null, count: 0, reviews: [] })
const reviewsLoading = ref(false)

const hasTutorProfile = computed(() => Boolean(currentUser.value?.tutor_profile))

const tutorSubjectsLabel = computed(() => {
    const subjects = currentUser.value?.tutor_profile?.subjects || []
    return subjects.length ? subjects.map((subject) => subject.name).join(', ') : 'Keine Fächer'
})

const paymentMethodsLabel = computed(() => {
    const methods = currentUser.value?.tutor_profile?.payment_methods || []
    return methods.length ? methods.map((method) => method.name).join(', ') : 'Keine Zahlungsarten'
})

const priceLabel = computed(() => {
    const price = currentUser.value?.tutor_profile?.price_per_hour
    if (price === null || price === undefined || price === '') {
        return 'Preis nicht angegeben'
    }

    const number = Number(price)
    if (Number.isNaN(number)) {
        return `${price} €/h`
    }

    return `${number.toFixed(2)} €/h`
})

const sortedTutorBookings = computed(() => sortBookings(bookings.value.as_tutor || []))
const sortedStudentBookings = computed(() => sortBookings(bookings.value.as_student || []))

const incomingBookings = computed(() =>
    sortedTutorBookings.value.filter((booking) => ['open', 'accepted'].includes(booking.status))
)

const sentBookings = computed(() =>
    sortedStudentBookings.value.filter((booking) => ['open', 'accepted'].includes(booking.status))
)

const pastBookings = computed(() => {
    const pastStatuses = ['completed', 'declined', 'cancelled']

    const asStudent = (bookings.value.as_student || [])
        .filter((booking) => pastStatuses.includes(booking.status))
        .map((booking) => ({ ...booking, _dashboardRole: 'student' }))

    const asTutor = hasTutorProfile.value
        ? (bookings.value.as_tutor || [])
            .filter((booking) => pastStatuses.includes(booking.status))
            .map((booking) => ({ ...booking, _dashboardRole: 'tutor' }))
        : []

    return [...asStudent, ...asTutor].sort((a, b) => {
        const aTime = bookingSortTimestamp(a)
        const bTime = bookingSortTimestamp(b)

        if (aTime !== bTime) {
            return bTime - aTime
        }

        return Number(b.id || 0) - Number(a.id || 0)
    })
})

const openTutorBookings = computed(() => incomingBookings.value.filter((booking) => booking.status === 'open'))
const acceptedTutorBookings = computed(() => incomingBookings.value.filter((booking) => booking.status === 'accepted'))
const acceptedStudentBookings = computed(() => sentBookings.value.filter((booking) => booking.status === 'accepted'))

onMounted(async () => {
    await loadDashboard()
    await handlePayPalReturn()

    clockTimer = setInterval(() => {
        now.value = new Date()
    }, 30000)

    dashboardRefreshTimer = setInterval(refreshBookingsSilently, 60000)
})

onBeforeUnmount(() => {
    if (clockTimer) {
        clearInterval(clockTimer)
    }

    if (dashboardRefreshTimer) {
        clearInterval(dashboardRefreshTimer)
    }
})

async function loadDashboard() {
    loading.value = true
    reviewsLoading.value = true
    errorMessage.value = ''

    try {
        const [bookingResponse, meResponse, reviewsResponse] = await Promise.all([
            api.get('/api/bookings'),
            api.get('/api/chat/me'),
            api.get('/api/reviews/me'),
        ])

        bookings.value = {
            as_tutor: bookingResponse.data?.as_tutor || [],
            as_student: bookingResponse.data?.as_student || [],
        }
        currentUser.value = meResponse.data
        applyDefaultSectionState()
        reviews.value = {
            average: reviewsResponse.data?.average ?? null,
            count: reviewsResponse.data?.count ?? 0,
            reviews: reviewsResponse.data?.reviews ?? [],
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Dashboard konnte nicht geladen werden.'
    } finally {
        loading.value = false
        reviewsLoading.value = false
    }
}

async function refreshBookingsSilently() {
    try {
        const response = await api.get('/api/bookings')
        bookings.value = {
            as_tutor: response.data?.as_tutor || [],
            as_student: response.data?.as_student || [],
        }
        now.value = new Date()
    } catch {
        // Stille Aktualisierung im Hintergrund.
    }
}

async function startPayPalPayment(booking) {
    if (!canPayWithPayPal(booking) || paymentActionLoading.value) {
        return
    }

    paymentActionLoading.value = booking.id
    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response = await api.post(`/api/bookings/${booking.id}/paypal/order`)
        const approveUrl = response.data?.approve_url

        if (!approveUrl) {
            throw new Error('PayPal hat keinen Zahlungslink zurückgegeben.')
        }

        window.location.href = approveUrl
    } catch (error) {
        errorMessage.value = error.response?.data?.message || error.message || 'PayPal-Zahlung konnte nicht gestartet werden.'
        paymentActionLoading.value = null
    }
}

async function handlePayPalReturn() {
    const params = new URLSearchParams(window.location.search)
    const paypalResult = params.get('paypal')
    const bookingId = params.get('booking')

    if (!paypalResult || !bookingId) {
        return
    }

    if (paypalResult === 'cancelled') {
        successMessage.value = 'PayPal-Zahlung wurde abgebrochen. Du kannst sie später erneut starten.'
        cleanupPayPalQuery()
        return
    }

    if (paypalResult !== 'return') {
        cleanupPayPalQuery()
        return
    }

    const orderId = params.get('token')
    paymentActionLoading.value = Number(bookingId)
    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response = await api.post(`/api/bookings/${bookingId}/paypal/capture`, {
            order_id: orderId,
        })

        if (response.data?.booking) {
            replaceBooking(response.data.booking)
        }

        successMessage.value = response.data?.message || 'Zahlung erfolgreich bestätigt.'
        await refreshBookingsSilently()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'PayPal-Zahlung konnte nicht bestätigt werden.'
    } finally {
        paymentActionLoading.value = null
        cleanupPayPalQuery()
    }
}

function cleanupPayPalQuery() {
    window.history.replaceState({}, '', window.location.pathname)
}

function isPayPalBooking(booking) {
    return String(booking?.payment_method?.name || '').toLowerCase().includes('paypal')
}

function canPayWithPayPal(booking) {
    return booking?.status === 'accepted'
        && isPayPalBooking(booking)
        && !['paid', 'refunded'].includes(booking?.payment_status)
        && Number(booking?.payment_amount || 0) > 0
}

function paymentStatusLabel(booking) {
    return {
        not_required: 'Nicht erforderlich',
        unpaid: 'Ausstehend',
        pending: 'PayPal läuft',
        paid: 'Bezahlt',
        failed: 'Fehlgeschlagen',
        refund_pending: 'Erstattung läuft',
        refunded: 'Erstattet',
    }[booking?.payment_status] || 'Ausstehend'
}

function paymentStatusClass(booking) {
    return `payment-${booking?.payment_status || 'unpaid'}`
}

function formatMoney(booking) {
    const amount = Number(booking?.payment_amount || 0)
    const currency = String(booking?.payment_currency || 'EUR').toUpperCase()

    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency,
    }).format(amount)
}

async function cancelBooking(booking) {
    const confirmed = window.confirm(
        'Möchtest du diesen Termin wirklich absagen? Der Schüler wird per E-Mail und im Chat informiert.'
    )

    if (!confirmed) {
        return
    }

    actionLoading.value = booking.id
    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response = await api.post(`/api/bookings/${booking.id}/cancel`)
        replaceBooking(response.data)
        successMessage.value = 'Termin wurde abgesagt. Der Schüler bekommt eine E-Mail und eine Chat-Info.'
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Termin konnte nicht abgesagt werden.'
    } finally {
        actionLoading.value = null
    }
}

function toggleSection(section) {
    sectionOpen[section] = !sectionOpen[section]
}

function applyDefaultSectionState() {
    if (sectionDefaultsApplied.value) {
        return
    }

    sectionOpen.incoming = hasTutorProfile.value
    sectionOpen.sent = !hasTutorProfile.value
    sectionOpen.past = false
    sectionDefaultsApplied.value = true
}

function getDraft(bookingId) {
    if (!reviewDrafts[bookingId]) {
        reviewDrafts[bookingId] = { rating: 0, comment: '' }
    }

    return reviewDrafts[bookingId]
}

function setReviewRating(bookingId, rating) {
    getDraft(bookingId).rating = rating
}

async function submitReview(booking) {
    const draft = getDraft(booking.id)

    if (!draft.rating) {
        return
    }

    reviewSubmitting.value = booking.id
    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response = await api.post(`/api/bookings/${booking.id}/review`, {
            rating: draft.rating,
            comment: draft.comment || null,
        })

        booking.review = response.data

        const sourceBooking = bookings.value.as_student.find(
            (item) => Number(item.id) === Number(booking.id)
        )

        if (sourceBooking) {
            sourceBooking.review = response.data
        }

        successMessage.value = 'Danke für deine Bewertung!'
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Bewertung konnte nicht gespeichert werden.'
    } finally {
        reviewSubmitting.value = null
    }
}

function starString(rating) {
    const filled = Math.max(0, Math.min(5, Math.round(rating || 0)))
    return '★'.repeat(filled) + '☆'.repeat(5 - filled)
}

function openReviewsModal() {
    if (!currentUser.value?.tutor_profile?.id) {
        return
    }

    reviewsModalOpen.value = true
}

async function changeStatus(booking, status) {
    actionLoading.value = booking.id
    errorMessage.value = ''
    successMessage.value = ''

    try {
        const endpoint = status === 'accepted'
            ? `/api/bookings/${booking.id}/accept`
            : `/api/bookings/${booking.id}/decline`

        const response = await api.post(endpoint)
        replaceBooking(response.data)
        successMessage.value = status === 'accepted'
            ? 'Anfrage wurde angenommen. Der Sender bekommt eine E-Mail und eine Chat-Info.'
            : 'Anfrage wurde abgelehnt. Der Sender bekommt eine E-Mail und eine Chat-Info.'
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Anfrage konnte nicht bearbeitet werden.'
    } finally {
        actionLoading.value = null
    }
}

function replaceBooking(updatedBooking) {
    const replaceIn = (items) => items.map((booking) => (
        Number(booking.id) === Number(updatedBooking.id) ? updatedBooking : booking
    ))

    bookings.value = {
        as_tutor: replaceIn(bookings.value.as_tutor || []),
        as_student: replaceIn(bookings.value.as_student || []),
    }
}

function bookingSortTimestamp(booking) {
    const date = String(booking?.requested_date || '').slice(0, 10)
    const time = shortTime(booking?.start_time)

    if (!date) {
        return new Date(booking?.updated_at || booking?.created_at || 0).getTime()
    }

    const value = new Date(`${date}T${time === '--:--' ? '00:00' : time}:00`)
    return Number.isNaN(value.getTime()) ? 0 : value.getTime()
}

function sortBookings(items) {
    return [...items].sort((a, b) => {
        const statusOrder = { open: 0, accepted: 1, cancelled: 2, declined: 3, completed: 4 }
        const aStatus = statusOrder[a.status] ?? 9
        const bStatus = statusOrder[b.status] ?? 9

        if (aStatus !== bStatus) {
            return aStatus - bStatus
        }

        return new Date(b.created_at || 0) - new Date(a.created_at || 0)
    })
}

function displayName(user) {
    if (!user) {
        return ''
    }

    return user.name || `${user.vorname || ''} ${user.nachname || ''}`.trim()
}

function initials(name) {
    const parts = String(name || 'TC').trim().split(/\s+/).filter(Boolean)
    return parts.slice(0, 2).map((part) => part[0]?.toUpperCase()).join('') || 'TC'
}

function hasText(value) {
    return String(value || '').trim().length > 0
}

function bookingStatusLabel(booking) {
    if (booking?.status === 'accepted') {
        const phase = bookingPhase(booking)

        if (phase === 'upcoming') {
            return 'Geplant'
        }

        if (phase === 'running') {
            return 'Läuft gerade'
        }

        return 'Beendet'
    }

    return statusLabel(booking?.status)
}

function bookingPhase(booking) {
    if (booking?.status !== 'accepted') {
        return null
    }

    const start = bookingDateTime(booking, 'start_time')
    const end = bookingDateTime(booking, 'end_time')

    if (!start || !end) {
        return 'upcoming'
    }

    if (now.value < start) {
        return 'upcoming'
    }

    if (now.value < end) {
        return 'running'
    }

    return 'ended'
}

function bookingDateTime(booking, field) {
    const rawDate = String(booking?.requested_date || '')
    const date = rawDate.slice(0, 10)
    const time = shortTime(booking?.[field])

    if (!date || time === '--:--') {
        return null
    }

    const value = new Date(`${date}T${time}:00`)
    return Number.isNaN(value.getTime()) ? null : value
}

function statusLabel(status) {
    return {
        open: 'Neu',
        accepted: 'Angenommen',
        cancelled: 'Abgesagt',
        declined: 'Abgelehnt',
        completed: 'Abgeschlossen',
    }[status] || status || 'Offen'
}

function statusClass(status) {
    return {
        open: 'is-open',
        accepted: 'is-accepted',
        cancelled: 'is-cancelled',
        declined: 'is-declined',
        completed: 'is-completed',
    }[status] || ''
}

function statusBadgeClass(status) {
    return {
        open: 'badge-open',
        accepted: 'badge-accepted',
        cancelled: 'badge-cancelled',
        declined: 'badge-declined',
        completed: 'badge-completed',
    }[status] || 'badge-open'
}

function requestTypeLabel(type) {
    return {
        paid: 'Bezahlte Nachhilfe',
        exchange: 'Tauschleistung',
        free: 'Kostenlos',
    }[type] || 'Anfrage'
}

function modeLabel(mode) {
    return {
        online: 'Online',
        presence: 'Vor Ort',
        in_person: 'Vor Ort',
        both: 'Online & vor Ort',
    }[mode] || 'Flexibel'
}

function formatDate(value) {
    if (!value) {
        return 'Datum nicht angegeben'
    }

    const raw = String(value)
    const date = new Date(raw.includes('T') ? raw : `${raw}T00:00:00`)

    if (Number.isNaN(date.getTime())) {
        return raw
    }

    return date.toLocaleDateString('de-DE', {
        weekday: 'short',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    })
}

function formatTimeRange(booking) {
    return `${shortTime(booking.start_time)} - ${shortTime(booking.end_time)} Uhr`
}

function shortTime(value) {
    if (!value) {
        return '--:--'
    }

    return String(value).slice(0, 5)
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Work+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap');

.dash-page {
    --brand: #0f6b4f;
    --brand-deep: #0a4230;
    --ink: #16352c;
    --ink-soft: #55746c;
    --paper: #f2f6f2;
    --surface: #ffffff;
    --line: #dde6df;
    --amber: #d99a3d;
    --amber-soft: #fbedd6;
    --red: #c0503f;
    --red-soft: #fbe6e2;

    min-height: calc(100vh - 68px);
    background:
        radial-gradient(circle at 1px 1px, rgba(15, 107, 79, 0.05) 1px, transparent 0) 0 0 / 20px 20px,
        var(--paper);
    padding: 32px 32px 48px;
    font-family: 'Work Sans', sans-serif;
    color: var(--ink);
}

.dash-hero {
    background: linear-gradient(135deg, var(--brand) 0%, var(--brand-deep) 100%);
    border-radius: 20px;
    box-shadow: 0 16px 32px rgba(10, 66, 48, 0.24);
    position: relative;
    overflow: hidden;
}

.dash-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, 0.14) 1px, transparent 0);
    background-size: 16px 16px;
    pointer-events: none;
}

.dash-hero-inner {
    position: relative;
    display: flex;
    align-items: center;
    gap: 18px;
    color: #fff;
    padding: 26px 28px;
}

.dash-avatar {
    width: 56px;
    height: 56px;
    flex-shrink: 0;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: rgba(255, 255, 255, 0.14);
    border: 1.5px solid rgba(255, 255, 255, 0.35);
    font-family: 'Fraunces', serif;
    font-weight: 600;
    font-size: 17px;
}

.dash-hero-text {
    flex: 1;
    min-width: 0;
}

.dash-kicker {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .14em;
    opacity: .8;
    font-weight: 600;
}

.dash-hero h1 {
    font-family: 'Fraunces', serif;
    font-weight: 600;
    margin: 3px 0 9px;
    font-size: 25px;
    letter-spacing: -.01em;
}

.dash-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.dash-tag {
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 999px;
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 500;
}

.dash-hint {
    margin: 0;
    font-size: 13px;
    opacity: .92;
    max-width: 46ch;
}

.dash-new-badge {
    display: flex;
    align-items: center;
    gap: 7px;
    background: var(--amber);
    color: #3a2504;
    padding: 9px 16px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
}

.dash-new-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #3a2504;
    display: inline-block;
}

.dash-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 288px;
    gap: 22px;
    margin-top: 22px;
}

.dash-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0 0 12px;
}

.dash-section-head h2 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 9px;
    font-family: 'Fraunces', serif;
    font-weight: 600;
    font-size: 17px;
    color: var(--ink);
}

.head-tab {
    width: 4px;
    height: 16px;
    border-radius: 2px;
    background: var(--brand);
    display: inline-block;
}

.sent-head {
    margin-top: 28px;
}

.dash-small-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 1px solid var(--line);
    background: var(--surface);
    color: var(--brand);
    padding: 8px 13px;
    border-radius: 9px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    font-family: 'Work Sans', sans-serif;
    transition: border-color .15s ease, background .15s ease;
}

.dash-small-btn:hover:not(:disabled) {
    border-color: var(--brand);
    background: #eef6f1;
}

.btn-refresh-ic {
    display: inline-block;
    font-size: 14px;
    line-height: 1;
}

.btn-refresh-ic.spin {
    animation: dash-spin .9s linear infinite;
}

@keyframes dash-spin {
    to { transform: rotate(360deg); }
}

.dash-message {
    margin: 16px 0 0;
    padding: 12px 16px;
    border-radius: 11px;
    font-size: 13px;
    font-weight: 600;
}

.dash-success {
    color: #0a4230;
    background: #dcf1e6;
    border: 1px solid #b7e0c8;
}

.dash-error {
    color: #7a2e22;
    background: var(--red-soft);
    border: 1px solid #f0c3ba;
}

.request-list {
    display: grid;
    gap: 12px;
}

.request-list.compact {
    gap: 10px;
}

.request-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-left: 4px solid var(--brand);
    border-radius: 14px;
    padding: 17px 18px;
    box-shadow: 0 2px 8px rgba(20, 50, 40, 0.05);
}

.request-card.is-accepted {
    border-left-color: var(--brand);
}

.request-card.is-declined,
.request-card.is-cancelled {
    border-left-color: var(--red);
    opacity: .82;
}

.request-card.is-completed {
    border-left-color: #b7c2bc;
    opacity: .88;
}

.request-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}

.request-top h3 {
    margin: 0 0 3px;
    font-family: 'Fraunces', serif;
    font-weight: 600;
    font-size: 15px;
    color: var(--ink);
}

.request-top p {
    margin: 0;
    font-size: 13px;
    color: var(--ink-soft);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
    font-family: 'IBM Plex Mono', monospace;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    display: inline-block;
}

.badge-open {
    background: #dcf1e6;
    color: #0d7a4e;
}

.badge-accepted {
    background: #dbe7f7;
    color: #1e5aa8;
}

.badge-declined,
.badge-cancelled {
    background: var(--red-soft);
    color: #a03f30;
}

.badge-completed {
    background: #eef1ef;
    color: #5c6a64;
}

.request-perforation {
    height: 1px;
    margin: 13px 0;
    background-image: repeating-linear-gradient(to right, var(--line) 0, var(--line) 5px, transparent 5px, transparent 10px);
}

.request-details {
    display: grid;
    grid-template-columns: repeat(5, minmax(120px, 1fr));
    gap: 8px;
    margin-bottom: 10px;
}

.request-details div,
.student-line {
    background: var(--paper);
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 9px 11px;
}

.request-details span,
.request-note span {
    display: block;
    color: var(--ink-soft);
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 4px;
}

.request-details strong {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 12px;
    font-weight: 600;
    color: var(--ink);
}

.request-note {
    background: var(--paper);
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 11px 13px;
    margin-top: 9px;
}

.exchange-note {
    background: var(--amber-soft);
    border-color: #ecd4a4;
}

.request-note p {
    margin: 0;
    font-size: 13px;
    line-height: 1.5;
    color: var(--ink);
}

.request-actions {
    display: flex;
    gap: 9px;
    margin-top: 14px;
}

.booking-phase-note {
    margin-top: 14px;
    padding: 10px 12px;
    border-radius: 9px;
    background: #eef1ef;
    color: #5c6a64;
    font-size: 12.5px;
    font-weight: 700;
}

.booking-phase-note.is-running {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #dcf1e6;
    color: #0d7a4e;
}

.live-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    box-shadow: 0 0 0 4px rgba(13, 122, 78, 0.12);
}

.btn-accept,
.btn-decline,
.btn-cancel {
    border: 0;
    border-radius: 9px;
    padding: 10px 16px;
    cursor: pointer;
    font-weight: 700;
    font-size: 13px;
    font-family: 'Work Sans', sans-serif;
    transition: transform .12s ease, opacity .12s ease;
}

.btn-accept:active:not(:disabled),
.btn-decline:active:not(:disabled),
.btn-cancel:active:not(:disabled) {
    transform: scale(.97);
}

.btn-accept {
    background: var(--brand);
    color: #fff;
}

.btn-decline {
    background: #fff;
    color: var(--red);
    border: 1px solid #eab8ae;
}

.btn-cancel {
    background: #fff;
    color: var(--red);
    border: 1px solid #eab8ae;
}

.btn-accept:disabled,
.btn-decline:disabled,
.btn-cancel:disabled,
.dash-small-btn:disabled {
    opacity: .5;
    cursor: not-allowed;
}

.dash-empty {
    background: var(--surface);
    border: 1.5px dashed var(--line);
    border-radius: 14px;
    padding: 22px;
    color: var(--ink-soft);
    font-weight: 600;
    font-size: 13.5px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dash-empty-ic {
    font-size: 17px;
}

.dash-empty.small {
    padding: 15px 18px;
    font-size: 13px;
}

.dash-side {
    display: grid;
    gap: 14px;
    align-content: start;
}

.stat-duo {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.stat-card,
.help-card,
.reviews-card {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(20, 50, 40, 0.05);
}

.stat-card {
    position: relative;
    overflow: hidden;
    border-top: 3px solid var(--brand);
}

.stat-card.amber {
    border-top-color: var(--amber);
}

.stat-corner {
    position: absolute;
    top: 0;
    right: 0;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 0 16px 16px 0;
    border-color: transparent var(--paper) transparent transparent;
}

.stat-card strong {
    display: block;
    font-family: 'IBM Plex Mono', monospace;
    color: var(--brand);
    font-size: 26px;
    font-weight: 600;
    margin-bottom: 2px;
}

.stat-card.amber strong {
    color: var(--amber);
}

.stat-card span {
    font-size: 11.5px;
    color: var(--ink-soft);
    font-weight: 600;
}

.help-card h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 9px;
    font-size: 13px;
    color: var(--ink);
    font-weight: 700;
}

.help-ic {
    width: 17px;
    height: 17px;
    border-radius: 50%;
    background: var(--brand);
    color: #fff;
    font-family: 'IBM Plex Mono', monospace;
    font-size: 10px;
    font-style: italic;
    display: inline-grid;
    place-items: center;
    flex-shrink: 0;
}

.help-card p {
    margin: 0;
    line-height: 1.5;
    font-size: 12.5px;
    color: var(--ink-soft);
}

.reviews-card h3 {
    margin: 0 0 10px;
    font-size: 13px;
    color: var(--ink);
    font-weight: 700;
}

.reviews-empty {
    color: var(--ink-soft);
    font-size: 12px;
    font-weight: 600;
}

.reviews-summary-button {
    width: 100%;
    border: 0;
    border-radius: 12px;
    padding: 8px 6px 10px;
    background: transparent;
    color: inherit;
    cursor: pointer;
}

.reviews-summary-button:hover,
.reviews-summary-button:focus-visible {
    background: rgba(45, 106, 79, 0.06);
}

.reviews-summary-button:focus-visible {
    outline: 2px solid #2d6a4f;
    outline-offset: 2px;
}

.reviews-open-hint {
    display: inline-block;
    margin-top: 7px;
    color: #2d6a4f;
    font-size: 0.72rem;
    font-weight: 800;
}

.reviews-score {
    display: flex;
    align-items: baseline;
    gap: 6px;
    margin-bottom: 2px;
}

.reviews-score strong {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 27px;
    font-weight: 600;
    color: var(--brand);
    line-height: 1;
}

.reviews-score span {
    font-size: 11px;
    color: var(--ink-soft);
}

.reviews-stars {
    margin-bottom: 4px;
}

.reviews-stars .star {
    font-size: 14px;
    color: #d9dfda;
}

.reviews-stars .star.filled {
    color: var(--amber);
}

.reviews-count {
    font-size: 11px;
    color: var(--ink-soft);
    margin-bottom: 13px;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 9px;
    border-top: 1px solid var(--line);
    padding-top: 11px;
}

.review-item {
    background: var(--paper);
    border-radius: 9px;
    padding: 9px 11px;
}

.review-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 3px;
    font-size: 11px;
    font-weight: 700;
    color: var(--ink);
}

.review-stars-mini {
    color: var(--amber);
    font-weight: 400;
    letter-spacing: 1px;
}

.review-item p {
    margin: 0;
    font-size: 11px;
    line-height: 1.45;
    color: var(--ink-soft);
}

.student-card {
    padding: 14px;
}

.student-line {
    color: var(--ink-soft);
    font-size: 12px;
    margin-top: 10px;
    font-family: 'IBM Plex Mono', monospace;
}

.review-given {
    margin-top: 10px;
    font-size: 12px;
    color: var(--ink-soft);
}

.review-given-comment {
    margin: 5px 0 0;
    font-size: 12.5px;
    color: var(--ink);
    line-height: 1.45;
}

.review-form {
    margin-top: 11px;
    padding-top: 11px;
    border-top: 1px solid var(--line);
}

.review-form-stars {
    display: flex;
    gap: 4px;
    margin-bottom: 7px;
}

.star-pick {
    border: 0;
    background: none;
    padding: 0;
    font-size: 19px;
    color: #d9dfda;
    cursor: pointer;
    line-height: 1;
}

.star-pick.filled {
    color: var(--amber);
}

.review-comment {
    width: 100%;
    resize: vertical;
    border: 1px solid var(--line);
    background: var(--paper);
    border-radius: 9px;
    padding: 9px;
    font-family: 'Work Sans', sans-serif;
    font-size: 12.5px;
    color: var(--ink);
    margin-bottom: 9px;
}

.btn-review-submit {
    border: 0;
    background: var(--brand);
    color: #fff;
    border-radius: 9px;
    padding: 8px 13px;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    font-family: 'Work Sans', sans-serif;
}

.btn-review-submit:disabled {
    opacity: .5;
    cursor: not-allowed;
}


.dashboard-panel {
    margin-bottom: 14px;
}

.collapsible-head {
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-toggle {
    flex: 1;
    min-width: 0;
    border: 1px solid var(--line);
    background: var(--surface);
    border-radius: 14px;
    padding: 15px 17px;
    color: var(--ink);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    font-family: 'Work Sans', sans-serif;
    box-shadow: 0 2px 8px rgba(20, 50, 40, 0.05);
    transition: border-color .15s ease, background .15s ease, transform .15s ease;
}

.section-toggle:hover {
    border-color: rgba(15, 107, 79, 0.45);
    background: #fbfdfb;
}

.section-toggle:active {
    transform: translateY(1px);
}

.section-title-wrap {
    display: flex;
    align-items: center;
    gap: 9px;
    min-width: 0;
}

.section-title {
    font-family: 'Fraunces', serif;
    font-weight: 600;
    font-size: 17px;
    text-align: left;
}

.section-count {
    min-width: 25px;
    height: 25px;
    padding: 0 7px;
    border-radius: 999px;
    display: inline-grid;
    place-items: center;
    background: #eef6f1;
    color: var(--brand);
    font-family: 'IBM Plex Mono', monospace;
    font-size: 11px;
    font-weight: 700;
}

.past-tab {
    background: var(--amber);
}

.section-chevron {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 21px;
    line-height: 1;
    color: var(--ink-soft);
    transition: transform .2s ease;
}

.section-chevron.open {
    transform: rotate(180deg);
}

.section-body {
    padding-top: 12px;
}

.section-slide-enter-active,
.section-slide-leave-active {
    transition: opacity .18s ease, transform .18s ease;
}

.section-slide-enter-from,
.section-slide-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}

.past-card {
    position: relative;
}

.past-role {
    display: inline-flex;
    margin-bottom: 6px;
    padding: 3px 8px;
    border-radius: 999px;
    background: #eef6f1;
    color: var(--brand);
    font-family: 'IBM Plex Mono', monospace;
    font-size: 9.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.review-waiting {
    margin-top: 10px;
    color: var(--ink-soft);
    font-size: 12px;
    font-weight: 600;
}


@media (max-width: 1050px) {
    .dash-grid {
        grid-template-columns: 1fr;
    }

    .request-details {
        grid-template-columns: repeat(2, minmax(120px, 1fr));
    }
}

@media (max-width: 650px) {
    .dash-page {
        padding: 18px;
    }

    .dash-hero-inner {
        align-items: flex-start;
        flex-direction: column;
        padding: 22px;
    }

    .request-details {
        grid-template-columns: 1fr;
    }

    .stat-duo {
        grid-template-columns: 1fr 1fr;
    }
}


.payment-strip {
    margin-top: 14px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    padding: 11px 13px;
    border: 1px solid #dbe5df;
    background: #f8fbf9;
    border-radius: 12px;
}

.payment-strip div {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.payment-strip span,
.payment-kicker {
    font-size: 11px;
    color: var(--ink-soft);
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 700;
}

.payment-strip strong {
    font-size: 13px;
    color: var(--ink);
}

.paypal-payment-box {
    margin-top: 14px;
    padding: 14px;
    border-radius: 14px;
    border: 1px solid #cbd7e6;
    background: #f7f9fc;
}

.paypal-payment-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.paypal-payment-head > div {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.paypal-payment-head strong {
    font-size: 17px;
    color: #173f6b;
}

.payment-status-pill {
    border-radius: 999px;
    background: #e7edf5;
    color: #35506e;
    padding: 5px 10px;
    font-size: 11px;
    font-weight: 800;
}

.paypal-payment-box p {
    margin: 9px 0 0;
    color: #587087;
    font-size: 12px;
    line-height: 1.5;
}

.paypal-pay-btn {
    width: 100%;
    margin-top: 12px;
    border: 0;
    border-radius: 10px;
    padding: 11px 14px;
    background: #0070ba;
    color: #fff;
    font-family: inherit;
    font-weight: 800;
    cursor: pointer;
    transition: transform .15s ease, filter .15s ease;
}

.paypal-pay-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(.96);
}

.paypal-pay-btn:disabled {
    opacity: .55;
    cursor: not-allowed;
}

.payment-paid,
.payment-refunded {
    border-color: #a9d8bb;
    background: #eff9f3;
}

.payment-paid .payment-status-pill,
.payment-refunded .payment-status-pill {
    background: #d9f2e2;
    color: #17633c;
}

.payment-failed {
    border-color: #efb5aa;
    background: #fff3f0;
}

.payment-failed .payment-status-pill {
    background: #f9ddd7;
    color: #9a382b;
}

.past-payment-line {
    margin-top: 10px;
    padding: 8px 10px;
    border-radius: 9px;
    background: #f6f8f7;
    border: 1px solid #e1e8e3;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    font-size: 12px;
    color: var(--ink-soft);
}

.past-payment-line strong {
    color: var(--ink);
    text-align: right;
}

@media (max-width: 680px) {
    .payment-strip {
        grid-template-columns: 1fr;
    }

    .paypal-payment-head,
    .past-payment-line {
        align-items: flex-start;
        flex-direction: column;
    }

    .past-payment-line strong {
        text-align: left;
    }
}
</style>