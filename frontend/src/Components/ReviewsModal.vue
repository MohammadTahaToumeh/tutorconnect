<template>
    <Teleport to="body">
        <Transition name="reviews-modal-fade">
            <div
                v-if="open"
                class="reviews-modal-overlay"
                role="presentation"
                @click.self="closeModal"
            >
                <section
                    class="reviews-modal"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="modalTitle"
                >
                    <header class="reviews-modal-header">
                        <div>
                            <p class="reviews-modal-kicker">Bewertungen</p>
                            <h2>{{ modalTitle }}</h2>
                        </div>

                        <button
                            type="button"
                            class="reviews-modal-close"
                            aria-label="Bewertungen schließen"
                            @click="closeModal"
                        >
                            ×
                        </button>
                    </header>

                    <div v-if="loading" class="reviews-modal-state">
                        <span class="reviews-spinner" aria-hidden="true"></span>
                        Bewertungen werden geladen...
                    </div>

                    <div v-else-if="errorMessage" class="reviews-modal-state is-error">
                        <strong>Bewertungen konnten nicht geladen werden.</strong>
                        <span>{{ errorMessage }}</span>
                        <button type="button" class="reviews-retry" @click="loadReviews">
                            Erneut versuchen
                        </button>
                    </div>

                    <template v-else>
                        <div class="reviews-summary">
                            <div class="reviews-average-box">
                                <strong>{{ formattedAverage }}</strong>
                                <div class="reviews-summary-stars" aria-hidden="true">
                                    <span
                                        v-for="star in 5"
                                        :key="star"
                                        :class="{ filled: star <= roundedAverage }"
                                    >★</span>
                                </div>
                                <span>
                                    {{ reviewData.count }}
                                    {{ reviewData.count === 1 ? 'Bewertung' : 'Bewertungen' }}
                                </span>
                            </div>

                            <div class="reviews-distribution" aria-label="Verteilung der Bewertungen">
                                <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="reviews-distribution-row">
                                    <span class="reviews-distribution-label">{{ star }} ★</span>
                                    <div class="reviews-distribution-track">
                                        <span
                                            class="reviews-distribution-fill"
                                            :style="{ width: `${distributionPercent(star)}%` }"
                                        ></span>
                                    </div>
                                    <span class="reviews-distribution-count">{{ distributionCount(star) }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="reviewData.count === 0" class="reviews-empty-state">
                            <div class="reviews-empty-icon">☆</div>
                            <h3>Noch keine Bewertungen</h3>
                            <p>Nach abgeschlossenen Terminen können Studierende hier ihre Erfahrung teilen.</p>
                        </div>

                        <div v-else class="reviews-list">
                            <article
                                v-for="review in reviewData.reviews"
                                :key="review.id"
                                class="reviews-item"
                            >
                                <div class="reviews-item-top">
                                    <div class="reviews-reviewer">
                                        <div class="reviews-reviewer-avatar">
                                            {{ reviewerInitials(review) }}
                                        </div>
                                        <div>
                                            <strong>{{ reviewerName(review) }}</strong>
                                            <span>{{ formatDate(review.created_at) }}</span>
                                        </div>
                                    </div>

                                    <div class="reviews-item-stars" :aria-label="`${review.rating} von 5 Sternen`">
                                        <span
                                            v-for="star in 5"
                                            :key="star"
                                            :class="{ filled: star <= Number(review.rating || 0) }"
                                        >★</span>
                                    </div>
                                </div>

                                <p v-if="review.comment" class="reviews-comment">“{{ review.comment }}”</p>
                                <p v-else class="reviews-comment is-empty">Keine zusätzliche Nachricht geschrieben.</p>

                                <div class="reviews-meta">
                                    <span v-if="review.booking?.subject?.name" class="reviews-meta-chip">
                                        {{ review.booking.subject.name }}
                                    </span>
                                    <span v-if="review.booking?.requested_date" class="reviews-meta-chip">
                                        {{ formatDate(review.booking.requested_date) }}
                                    </span>
                                    <span v-if="review.booking" class="reviews-verified">
                                        ✓ Verifizierter Termin
                                    </span>
                                </div>
                            </article>
                        </div>
                    </template>
                </section>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import api from '../api'

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    tutorProfileId: {
        type: [Number, String],
        default: null,
    },
    title: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['close'])

const loading = ref(false)
const errorMessage = ref('')
const reviewData = ref(emptyReviewData())
let previousBodyOverflow = ''

const modalTitle = computed(() => {
    return props.title || reviewData.value.tutor_name || 'Alle Bewertungen'
})

const roundedAverage = computed(() => Math.round(Number(reviewData.value.average || 0)))

const formattedAverage = computed(() => {
    if (!reviewData.value.count || reviewData.value.average === null) {
        return '0,0'
    }

    return Number(reviewData.value.average).toFixed(1).replace('.', ',')
})

function emptyReviewData() {
    return {
        tutor_profile_id: null,
        tutor_name: '',
        average: null,
        count: 0,
        distribution: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
        reviews: [],
    }
}

async function loadReviews() {
    if (!props.tutorProfileId) {
        reviewData.value = emptyReviewData()
        return
    }

    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get(`/api/tutors/${props.tutorProfileId}/reviews`)
        reviewData.value = {
            ...emptyReviewData(),
            ...(response.data || {}),
            distribution: {
                ...emptyReviewData().distribution,
                ...(response.data?.distribution || {}),
            },
            reviews: response.data?.reviews || [],
        }
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Bitte versuche es noch einmal.'
    } finally {
        loading.value = false
    }
}

function closeModal() {
    emit('close')
}

function distributionCount(star) {
    return Number(reviewData.value.distribution?.[star] || 0)
}

function distributionPercent(star) {
    if (!reviewData.value.count) {
        return 0
    }

    return Math.round((distributionCount(star) / reviewData.value.count) * 100)
}

function reviewerName(review) {
    const student = review?.student

    return student?.name
        || `${student?.vorname || ''} ${student?.nachname || ''}`.trim()
        || 'Studierender'
}

function reviewerInitials(review) {
    return reviewerName(review)
        .split(/\s+/)
        .filter(Boolean)
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase() || '?'
}

function formatDate(value) {
    if (!value) {
        return ''
    }

    const raw = String(value)
    const date = new Date(raw.includes('T') ? raw : `${raw.slice(0, 10)}T00:00:00`)

    if (Number.isNaN(date.getTime())) {
        return raw.slice(0, 10)
    }

    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    })
}

function handleEscape(event) {
    if (props.open && event.key === 'Escape') {
        closeModal()
    }
}

watch(
    () => [props.open, props.tutorProfileId],
    ([open]) => {
        if (open) {
            loadReviews()
        }
    },
    { immediate: true }
)

watch(
    () => props.open,
    (open) => {
        if (typeof document === 'undefined') {
            return
        }

        if (open) {
            previousBodyOverflow = document.body.style.overflow
            document.body.style.overflow = 'hidden'
        } else {
            document.body.style.overflow = previousBodyOverflow
        }
    }
)

onMounted(() => {
    window.addEventListener('keydown', handleEscape)
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleEscape)

    if (typeof document !== 'undefined') {
        document.body.style.overflow = previousBodyOverflow
    }
})
</script>

<style scoped>
.reviews-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 3000;
    display: grid;
    place-items: center;
    padding: 24px;
    background: rgba(16, 47, 39, 0.58);
    backdrop-filter: blur(4px);
}

.reviews-modal {
    width: min(820px, 100%);
    max-height: min(760px, calc(100vh - 48px));
    overflow: auto;
    background: #f8fcf9;
    border: 1px solid #b7dfc5;
    border-radius: 22px;
    box-shadow: 0 30px 80px rgba(20, 70, 54, 0.28);
    color: #1b2e23;
}

.reviews-modal-header {
    position: sticky;
    top: 0;
    z-index: 2;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    padding: 22px 24px 18px;
    background: rgba(248, 252, 249, 0.96);
    border-bottom: 1px solid #d9eadf;
    backdrop-filter: blur(10px);
}

.reviews-modal-kicker {
    margin: 0 0 3px;
    color: #2d6a4f;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.reviews-modal-header h2 {
    margin: 0;
    color: #183c31;
    font-size: clamp(1.25rem, 3vw, 1.75rem);
}

.reviews-modal-close {
    width: 38px;
    height: 38px;
    flex: 0 0 auto;
    border: 1px solid #c8dfd0;
    border-radius: 50%;
    background: white;
    color: #315d4f;
    font-size: 1.65rem;
    line-height: 1;
    cursor: pointer;
}

.reviews-modal-close:hover {
    background: #edf8f1;
}

.reviews-summary {
    display: grid;
    grid-template-columns: minmax(180px, 0.7fr) minmax(260px, 1.3fr);
    gap: 26px;
    padding: 24px;
    background: white;
    border-bottom: 1px solid #d9eadf;
}

.reviews-average-box {
    display: flex;
    min-height: 150px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 18px;
    border: 1px solid #cfe5d7;
    border-radius: 18px;
    background: linear-gradient(145deg, #f4fbf6, #e9f7ee);
}

.reviews-average-box strong {
    color: #245d48;
    font-size: 2.8rem;
    line-height: 1;
}

.reviews-average-box > span {
    margin-top: 7px;
    color: #678277;
    font-size: 0.86rem;
    font-weight: 700;
}

.reviews-summary-stars,
.reviews-item-stars {
    display: flex;
    gap: 2px;
    color: #d2d7d4;
}

.reviews-summary-stars {
    margin-top: 8px;
    font-size: 1.35rem;
}

.reviews-summary-stars .filled,
.reviews-item-stars .filled {
    color: #e99a20;
}

.reviews-distribution {
    display: grid;
    align-content: center;
    gap: 9px;
}

.reviews-distribution-row {
    display: grid;
    grid-template-columns: 42px 1fr 28px;
    align-items: center;
    gap: 9px;
    font-size: 0.82rem;
}

.reviews-distribution-label,
.reviews-distribution-count {
    color: #58766a;
    font-weight: 700;
}

.reviews-distribution-count {
    text-align: right;
}

.reviews-distribution-track {
    height: 8px;
    overflow: hidden;
    border-radius: 999px;
    background: #e7efe9;
}

.reviews-distribution-fill {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: #e99a20;
    transition: width 0.25s ease;
}

.reviews-list {
    display: grid;
    gap: 14px;
    padding: 20px 24px 26px;
}

.reviews-item {
    padding: 18px;
    border: 1px solid #d5e7db;
    border-radius: 16px;
    background: white;
    box-shadow: 0 8px 22px rgba(45, 106, 79, 0.06);
}

.reviews-item-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.reviews-reviewer {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 10px;
}

.reviews-reviewer-avatar {
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #2d6a4f;
    color: white;
    font-size: 0.78rem;
    font-weight: 800;
}

.reviews-reviewer strong,
.reviews-reviewer span {
    display: block;
}

.reviews-reviewer strong {
    overflow: hidden;
    color: #1f4437;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.reviews-reviewer span {
    margin-top: 2px;
    color: #7a9087;
    font-size: 0.76rem;
}

.reviews-item-stars {
    flex: 0 0 auto;
    font-size: 1.05rem;
}

.reviews-comment {
    margin: 14px 0 0;
    color: #38594d;
    font-size: 0.95rem;
    line-height: 1.58;
    white-space: pre-wrap;
}

.reviews-comment.is-empty {
    color: #899b94;
    font-style: italic;
}

.reviews-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 7px;
    margin-top: 14px;
}

.reviews-meta-chip,
.reviews-verified {
    display: inline-flex;
    align-items: center;
    min-height: 26px;
    border-radius: 999px;
    padding: 4px 9px;
    font-size: 0.72rem;
    font-weight: 750;
}

.reviews-meta-chip {
    background: #eef6f1;
    color: #527266;
}

.reviews-verified {
    background: #e1f5e8;
    color: #26714e;
}

.reviews-empty-state,
.reviews-modal-state {
    display: flex;
    min-height: 280px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 36px 24px;
    text-align: center;
    color: #648074;
}

.reviews-empty-state h3,
.reviews-empty-state p,
.reviews-modal-state strong,
.reviews-modal-state span {
    margin: 0;
}

.reviews-empty-state h3 {
    color: #254e40;
}

.reviews-empty-state p {
    max-width: 430px;
    line-height: 1.5;
}

.reviews-empty-icon {
    color: #e99a20;
    font-size: 3rem;
}

.reviews-spinner {
    width: 34px;
    height: 34px;
    border: 3px solid #d8e8de;
    border-top-color: #2d6a4f;
    border-radius: 50%;
    animation: reviews-spin 0.8s linear infinite;
}

.reviews-modal-state.is-error {
    color: #a44135;
}

.reviews-retry {
    margin-top: 6px;
    border: 0;
    border-radius: 10px;
    padding: 9px 14px;
    background: #2d6a4f;
    color: white;
    font-weight: 750;
    cursor: pointer;
}

.reviews-modal-fade-enter-active,
.reviews-modal-fade-leave-active {
    transition: opacity 0.18s ease;
}

.reviews-modal-fade-enter-active .reviews-modal,
.reviews-modal-fade-leave-active .reviews-modal {
    transition: transform 0.18s ease, opacity 0.18s ease;
}

.reviews-modal-fade-enter-from,
.reviews-modal-fade-leave-to {
    opacity: 0;
}

.reviews-modal-fade-enter-from .reviews-modal,
.reviews-modal-fade-leave-to .reviews-modal {
    opacity: 0;
    transform: translateY(10px) scale(0.985);
}

@keyframes reviews-spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 680px) {
    .reviews-modal-overlay {
        padding: 10px;
    }

    .reviews-modal {
        max-height: calc(100vh - 20px);
        border-radius: 17px;
    }

    .reviews-modal-header,
    .reviews-summary,
    .reviews-list {
        padding-left: 16px;
        padding-right: 16px;
    }

    .reviews-summary {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .reviews-average-box {
        min-height: 120px;
    }

    .reviews-item-top {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
