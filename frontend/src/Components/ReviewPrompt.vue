<template>
    <div v-if="currentBooking" class="review-prompt-overlay">
        <div class="review-prompt-card">
            <div class="rp-icon">🎓</div>

            <template v-if="mode === 'ask'">
                <h3>Wie war dein Termin?</h3>
                <p class="rp-sub">
                    {{ tutorName }} · {{ subjectName }}
                    <span v-if="dateLabel"> · {{ dateLabel }}</span>
                </p>
                <p class="rp-text">Dein Termin ist beendet. Magst du ihn kurz bewerten?</p>

                <div class="rp-actions">
                    <button class="rp-btn-secondary" @click="dismissForNow">
                        Später
                    </button>
                    <button class="rp-btn-primary" @click="mode = 'rate'">
                        Jetzt bewerten
                    </button>
                </div>
            </template>

            <template v-else>
                <h3>Bewerte {{ tutorName }}</h3>
                <p class="rp-sub">{{ subjectName }}<span v-if="dateLabel"> · {{ dateLabel }}</span></p>

                <div class="rp-stars">
                    <button
                        v-for="n in 5"
                        :key="n"
                        type="button"
                        class="rp-star"
                        :class="{ filled: n <= rating }"
                        :aria-label="`${n} von 5 Sternen bewerten`"
                        @click="rating = n"
                    >★</button>
                </div>

                <textarea
                    v-model="comment"
                    class="rp-comment"
                    rows="3"
                    placeholder="Wie war die Nachhilfe? (optional)"
                ></textarea>

                <p v-if="error" class="rp-error">{{ error }}</p>

                <div class="rp-actions">
                    <button class="rp-btn-secondary" :disabled="submitting" @click="mode = 'ask'">
                        Zurück
                    </button>
                    <button
                        class="rp-btn-primary"
                        :disabled="!rating || submitting"
                        @click="submitReview"
                    >
                        {{ submitting ? 'Wird gesendet...' : 'Bewertung abschicken' }}
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useAuthStore } from '../auth'
import api from '../api'

const DISMISS_KEY = 'reviewPromptDismissed'
const POLL_INTERVAL_MS = 90000

const auth = useAuthStore()

const pendingBookings = ref([])
const mode = ref('ask')
const rating = ref(0)
const comment = ref('')
const submitting = ref(false)
const error = ref('')
let pollTimer = null

const currentBooking = computed(() => pendingBookings.value[0] || null)

const tutorName = computed(() => {
    const user = currentBooking.value?.tutor_profile?.user
    if (!user) {
        return 'dein Tutor'
    }
    return user.name || `${user.vorname || ''} ${user.nachname || ''}`.trim()
})

const subjectName = computed(() => currentBooking.value?.subject?.name || 'Nachhilfe')

const dateLabel = computed(() => {
    const value = currentBooking.value?.requested_date
    if (!value) {
        return ''
    }

    const raw = String(value)
    const date = new Date(raw.includes('T') ? raw : `${raw}T00:00:00`)

    if (Number.isNaN(date.getTime())) {
        return ''
    }

    return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
})

function getDismissed() {
    try {
        return JSON.parse(sessionStorage.getItem(DISMISS_KEY) || '[]')
    } catch {
        return []
    }
}

function addDismissed(bookingId) {
    const dismissed = getDismissed()
    dismissed.push(bookingId)
    sessionStorage.setItem(DISMISS_KEY, JSON.stringify(dismissed))
}

async function loadPending() {
    if (!auth.isAuthenticated) {
        return
    }

    try {
        const response = await api.get('/api/reviews/pending')
        const dismissed = getDismissed()

        pendingBookings.value = (response.data || [])
            .filter((booking) => !dismissed.includes(booking.id))
    } catch {
        // Stille im Hintergrund - die Benachrichtigung ist nicht kritisch.
    }
}

function dismissForNow() {
    if (!currentBooking.value) {
        return
    }

    addDismissed(currentBooking.value.id)
    pendingBookings.value = pendingBookings.value.slice(1)
    resetRatingForm()
}

function resetRatingForm() {
    mode.value = 'ask'
    rating.value = 0
    comment.value = ''
    error.value = ''
}

async function submitReview() {
    if (!currentBooking.value || !rating.value) {
        return
    }

    submitting.value = true
    error.value = ''

    try {
        await api.post(`/api/bookings/${currentBooking.value.id}/review`, {
            rating: rating.value,
            comment: comment.value || null,
        })

        pendingBookings.value = pendingBookings.value.slice(1)
        resetRatingForm()
    } catch (err) {
        error.value = err.response?.data?.message || 'Bewertung konnte nicht gespeichert werden.'
    } finally {
        submitting.value = false
    }
}

onMounted(() => {
    loadPending()
    pollTimer = setInterval(loadPending, POLL_INTERVAL_MS)
})

onBeforeUnmount(() => {
    if (pollTimer) {
        clearInterval(pollTimer)
    }
})
</script>

<style scoped>
.review-prompt-overlay {
    position: fixed;
    inset: 0;
    background: rgba(18, 53, 45, 0.35);
    display: grid;
    place-items: center;
    z-index: 1000;
    padding: 20px;
}

.review-prompt-card {
    background: #fff;
    border-radius: 18px;
    padding: 26px 24px;
    max-width: 380px;
    width: 100%;
    box-shadow: 0 20px 45px rgba(13, 107, 82, 0.28);
    text-align: center;
}

.rp-icon {
    font-size: 30px;
    margin-bottom: 8px;
}

.review-prompt-card h3 {
    margin: 0 0 4px;
    font-size: 17px;
    color: #12352d;
}

.rp-sub {
    margin: 0 0 10px;
    font-size: 13px;
    color: #4d6f64;
    font-weight: 700;
}

.rp-text {
    margin: 0 0 18px;
    font-size: 13px;
    color: #4d6f64;
    line-height: 1.5;
}

.rp-stars {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-bottom: 12px;
}

.rp-star {
    border: 0;
    background: none;
    padding: 0;
    font-size: 30px;
    color: #d1d5db;
    cursor: pointer;
    line-height: 1;
}

.rp-star.filled {
    color: #f0a83a;
}

.rp-comment {
    width: 100%;
    resize: vertical;
    border: 1px solid #b7dfcf;
    border-radius: 10px;
    padding: 10px;
    font-family: inherit;
    font-size: 13px;
    color: #12352d;
    margin-bottom: 10px;
}

.rp-error {
    margin: 0 0 10px;
    font-size: 12px;
    font-weight: 700;
    color: #991b1b;
}

.rp-actions {
    display: flex;
    gap: 10px;
}

.rp-btn-secondary,
.rp-btn-primary {
    flex: 1;
    border-radius: 10px;
    padding: 11px 14px;
    font-weight: 900;
    cursor: pointer;
    font-size: 13px;
}

.rp-btn-secondary {
    border: 1px solid #b7dfcf;
    background: #fff;
    color: #0d6b52;
}

.rp-btn-primary {
    border: 0;
    background: #0d6b52;
    color: #fff;
}

.rp-btn-primary:disabled,
.rp-btn-secondary:disabled {
    opacity: .55;
    cursor: not-allowed;
}
</style>
