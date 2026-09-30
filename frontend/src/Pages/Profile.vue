<template>
    <div class="app">
	<Topbar />
               

        <div class="profile-bg">
            <div v-if="loading" class="form-wrap">
                <div class="tc-card">Profil wird geladen...</div>
            </div>

            <div v-else>
                <div v-if="successMessage" class="message-wrap">
                    <div class="alert-success">✓ {{ successMessage }}</div>
                </div>

                <div v-if="errorMessage" class="message-wrap">
                    <div class="alert-error">{{ errorMessage }}</div>
                </div>

                <!-- FORMULAR: wenn kein Profil existiert oder Bearbeiten aktiv ist -->
                <div v-if="!tutorProfile || editMode" class="form-wrap">
                    <h1 class="form-title">
                        {{ tutorProfile ? 'Profil bearbeiten' : 'Tutor-Profil erstellen' }}
                    </h1>

                    <div class="tc-card">
                        <div class="form-section">
                            <div class="form-section-title">Über dich</div>

                            <div class="form-group">
                                <label class="form-label">Beschreibung</label>
                                <textarea
                                    class="form-control"
                                    v-model="form.description"
                                    placeholder="Beschreibe dich als Tutor – Stärken, Methoden, Erfahrungen …"
                                ></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">Preis & Unterrichtsart</div>

                            <div class="two-col">
                                <div class="form-group">
                                    <label class="form-label">Preis pro Stunde (€)</label>
                                    <input
                                        class="form-control"
                                        type="number"
                                        min="0"
                                        max="9999"
                                        step="0.50"
                                        v-model="form.price_per_hour"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Unterrichtsart</label>
                                    <select class="form-control" v-model="form.teaching_mode">
                                        <option value="" disabled>– bitte wählen –</option>
                                        <option value="online">Online</option>
                                        <option value="in_person">Vor Ort</option>
                                        <option value="both">Beides</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">Tutorfächer</div>

                            <div>
                                <div
                                    v-for="(subjectId, index) in form.subjects"
                                    :key="index"
                                    class="subject-row"
                                >
                                    <select class="form-control subject-select" v-model="form.subjects[index]">
                                        <option value="">– Fach wählen –</option>
                                        <option
                                            v-for="subject in availableSubjectsFor(index)"
                                            :key="subject.id"
                                            :value="subject.id"
                                        >
                                            {{ subject.name }}
                                            <span v-if="subject.study_program">
                                                ({{ subject.study_program.name }})
                                            </span>
                                        </option>
                                    </select>

                                    <button type="button" class="btn-remove" @click="removeSubjectRow(index)">
                                        ✕
                                    </button>
                                </div>
                            </div>

                            <button type="button" class="btn-add" @click="addSubjectRow">
                                + Weiteres Fach
                            </button>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">Zahlungsarten</div>

                            <div class="checkbox-grid">
                                <label
                                    v-for="method in paymentMethods"
                                    :key="method.id"
                                    class="checkbox-item"
                                >
                                    <input
                                        type="checkbox"
                                        :value="method.id"
                                        v-model="form.payment_methods"
                                    >
                                    {{ method.name }}
                                </label>
                            </div>

                            <div v-if="hasPayPalSelected" class="paypal-connect-card" :class="paypalCardClass">
                                <div class="paypal-connect-head">
                                    <div>
                                        <strong>PayPal Checkout</strong>
                                        <p>{{ paypalStatusText }}</p>
                                    </div>
                                    <span class="paypal-status-pill">{{ paypalStatusLabel }}</span>
                                </div>
                                <p class="paypal-help">
                                    PayPal-Zahlungen laufen sicher über den TutorConnect-Sandbox-Checkout. Der Schüler bezahlt erst nach deiner Annahme des Termins.
                                </p>

                                <p v-if="!paypal.configured" class="paypal-config-warning">
                                    Sandbox-Konfiguration fehlt: {{ (paypal.missing_configuration || []).join(', ') }}
                                </p>
                            </div>
                        </div>

                        <div v-if="hasExchangeSelected" class="form-section">
                            <div class="form-section-title">Fächer, in denen du Hilfe brauchst</div>

                            <p class="empty-hint" style="margin-top:0">
                                Pflicht, wenn du Tauschleistung auswählst. Nur Tutoren, die eines dieser Fächer anbieten, können dir Tauschleistung vorschlagen.
                            </p>

                            <div>
                                <div
                                    v-for="(subjectId, index) in form.help_subjects"
                                    :key="index"
                                    class="subject-row"
                                >
                                    <select class="form-control subject-select" v-model="form.help_subjects[index]">
                                        <option value="">– Hilfefach wählen –</option>
                                        <option
                                            v-for="subject in availableHelpSubjectsFor(index)"
                                            :key="subject.id"
                                            :value="subject.id"
                                        >
                                            {{ subject.name }}
                                            <span v-if="subject.study_program">
                                                ({{ subject.study_program.name }})
                                            </span>
                                        </option>
                                    </select>

                                    <button type="button" class="btn-remove" @click="removeHelpSubjectRow(index)">
                                        ✕
                                    </button>
                                </div>
                            </div>

                            <button type="button" class="btn-add" @click="addHelpSubjectRow">
                                + Weiteres Hilfefach
                            </button>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">Lernorte</div>

                            <div>
                                <div
                                    v-for="(location, index) in form.locations"
                                    :key="index"
                                    class="subject-row"
                                >
                                    <input
                                        class="form-control"
                                        type="text"
                                        v-model="form.locations[index]"
                                        placeholder="z. B. Bibliothek HS Bochum, Mensa, Online …"
                                    >

                                    <button type="button" class="btn-remove" @click="removeLocationRow(index)">
                                        ✕
                                    </button>
                                </div>
                            </div>

                            <button type="button" class="btn-add" @click="addLocationRow">
                                + Weiteren Ort
                            </button>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">Verfügbarkeit (Mo – Fr)</div>

                            <div class="avail-box">
                                <div
                                    v-for="(dayName, dayKey) in weekdays"
                                    :key="dayKey"
                                    class="avail-form-row"
                                >
                                    <label class="avail-check">
                                        <input
                                            type="checkbox"
                                            v-model="form.availability[dayKey].active"
                                        >
                                        <span class="avail-day-name">{{ dayName }}</span>
                                    </label>

                                    <div
                                        class="avail-times"
                                        :class="{ disabled: !form.availability[dayKey].active }"
                                    >
                                        <input
                                            type="time"
                                            class="form-control time-input"
                                            v-model="form.availability[dayKey].start_time"
                                        >
                                        <span>bis</span>
                                        <input
                                            type="time"
                                            class="form-control time-input"
                                            v-model="form.availability[dayKey].end_time"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-submit" @click="saveProfile" :disabled="saving">
                                {{ saving ? 'Speichern...' : (tutorProfile ? 'Änderungen speichern' : 'Profil erstellen') }}
                            </button>

                            <button
                                v-if="tutorProfile"
                                type="button"
                                class="btn-cancel"
                                @click="cancelEdit"
                            >
                                Abbrechen
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PROFILANSICHT: wenn Profil existiert und nicht im Bearbeitungsmodus -->
                <div v-else class="profile-wrap">
                    <aside>
                        <div class="tc-card sidebar-card">
                            <div class="avatar-circle">{{ initials }}</div>

                            <p class="sidebar-name">{{ userFullName }}</p>
                            <p class="sidebar-email">{{ user?.email }}</p>

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

                                <div v-if="profileHasPayPal" class="paypal-profile-status" :class="paypalCardClass">
                                    <div class="paypal-profile-row">
                                        <strong>PayPal</strong>
                                        <span>{{ paypalStatusLabel }}</span>
                                    </div>
                                    <p>{{ paypalStatusText }}</p>
                                </div>
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
                            <button class="btn-edit" @click="startEdit">
                                ✏ Profil bearbeiten
                            </button>
                        </div>
                    </aside>

                    <div class="right-col">
                        <div class="tc-card">
                            <div class="info-block-label">Hochschule</div>
                            <div class="hs-title">HS Bochum</div>
                            <div class="hs-sub">
                                {{ user?.studiengang || 'Studiengang nicht angegeben' }}
                                <span v-if="user?.semester"> · {{ user.semester }}. Semester</span>
                            </div>

                            <div v-if="tutorProfile.description">
                                <hr class="sidebar-divider">
                                <div class="info-block-label">Über mich</div>
                                <p class="description-text">{{ tutorProfile.description }}</p>
                            </div>
                        </div>

                        <div class="tc-card">
                            <div class="info-block-label">Meine Tutorfächer</div>

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
                            <div class="info-block-label">Ich brauche Hilfe in</div>

                            <span
                                v-for="subject in tutorProfile.help_subjects"
                                :key="subject.id"
                                class="subject-chip"
                            >
                                {{ subject.name }}
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

                            <div v-if="tutorProfile.availabilities && tutorProfile.availabilities.length > 0" class="avail-grid">
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
import Topbar from '../Components/Topbar.vue';
import ReviewsModal from '../Components/ReviewsModal.vue';
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const loading = ref(true);
const saving = ref(false);
const editMode = ref(false);
const paypalBusy = ref(false);

const successMessage = ref('');
const errorMessage = ref('');

const user = ref(null);
const tutorProfile = ref(null);
const paypal = ref({
    configured: false,
    missing_configuration: [],
    status: 'not_connected',
    connected: false,
    payments_receivable: false,
    email_confirmed: false,
});
const reviewsModalOpen = ref(false);
const subjects = ref([]);
const paymentMethods = ref([]);
const weekdays = ref({});

const form = ref({
    description: '',
    price_per_hour: '',
    teaching_mode: '',
    subjects: [''],
    help_subjects: [''],
    payment_methods: [],
    locations: [''],
    availability: {},
});

const hasExchangeSelected = computed(() => {
    return form.value.payment_methods.some((id) => {
        const method = paymentMethods.value.find((paymentMethod) => String(paymentMethod.id) === String(id));
        return String(method?.name ?? '').toLowerCase().includes('tausch');
    });
});

const paypalMethod = computed(() => {
    return paymentMethods.value.find((method) =>
        String(method?.name ?? '').toLowerCase().includes('paypal')
    ) ?? null;
});

const hasPayPalSelected = computed(() => {
    if (!paypalMethod.value) return false;

    return form.value.payment_methods.some((id) => String(id) === String(paypalMethod.value.id));
});

const profileHasPayPal = computed(() => {
    return (tutorProfile.value?.payment_methods ?? []).some((method) =>
        String(method?.name ?? '').toLowerCase().includes('paypal')
    );
});

const paypalStatusLabel = computed(() => {
    return paypal.value.configured ? 'Sandbox aktiv' : 'Nicht konfiguriert';
});

const paypalStatusText = computed(() => {
    if (paypal.value.configured) {
        return 'PayPal Checkout ist über TutorConnect aktiviert. Schüler können angenommene Termine direkt in der Sandbox bezahlen.';
    }

    return 'Trage PAYPAL_CLIENT_ID und PAYPAL_CLIENT_SECRET im Backend ein, um PayPal Checkout zu aktivieren.';
});

const paypalCardClass = computed(() => ({
    'is-connected': paypal.value.configured,
    'needs-action': !paypal.value.configured,
}));

onMounted(async () => {
    await loadProfileOptions();
});

async function loadProfileOptions() {
    try {
        const response = await axios.get('/api/profile/options');

        user.value = response.data.user;
        tutorProfile.value = response.data.tutorProfile;
        paypal.value = response.data.paypal ?? paypal.value;
        subjects.value = response.data.subjects;
        paymentMethods.value = response.data.paymentMethods;
        weekdays.value = response.data.weekdays;

        prepareAvailability();

        if (tutorProfile.value) {
            fillForm(tutorProfile.value);
        } else {
            editMode.value = true;
        }
    } catch (error) {
        console.error('Fehler beim Laden vom Profil:', error);
        errorMessage.value = 'Profil konnte nicht geladen werden.';
    } finally {
        loading.value = false;
    }
}

function prepareAvailability() {
    form.value.availability = {};

    for (const day in weekdays.value) {
        form.value.availability[day] = {
            active: false,
            start_time: '08:00',
            end_time: '18:00',
        };
    }
}

function fillForm(profile) {
    form.value.description = profile.description ?? '';
    form.value.price_per_hour = profile.price_per_hour ?? '';
    form.value.teaching_mode = profile.teaching_mode ?? '';

    const selectedSubjects = (profile.subjects ?? []).map((subject) => subject.id);
    form.value.subjects = selectedSubjects.length > 0 ? selectedSubjects : [''];

    const selectedHelpSubjects = (profile.help_subjects ?? []).map((subject) => subject.id);
    form.value.help_subjects = selectedHelpSubjects.length > 0 ? selectedHelpSubjects : [''];

    form.value.payment_methods = (profile.payment_methods ?? []).map((method) => method.id);

    const selectedLocations = (profile.locations ?? []).map((location) => location.location);
    form.value.locations = selectedLocations.length > 0 ? selectedLocations : [''];

    prepareAvailability();

    for (const availability of profile.availabilities ?? []) {
        if (form.value.availability[availability.weekday]) {
            form.value.availability[availability.weekday] = {
                active: true,
                start_time: formatTime(availability.start_time),
                end_time: formatTime(availability.end_time),
            };
        }
    }
}

function startEdit() {
    fillForm(tutorProfile.value);
    editMode.value = true;
    successMessage.value = '';
    errorMessage.value = '';
}

function cancelEdit() {
    fillForm(tutorProfile.value);
    editMode.value = false;
    successMessage.value = '';
    errorMessage.value = '';
}

function addSubjectRow() {
    form.value.subjects.push('');
}

function removeSubjectRow(index) {
    form.value.subjects.splice(index, 1);

    if (form.value.subjects.length === 0) {
        form.value.subjects.push('');
    }
}

function addHelpSubjectRow() {
    form.value.help_subjects.push('');
}

function removeHelpSubjectRow(index) {
    form.value.help_subjects.splice(index, 1);

    if (form.value.help_subjects.length === 0) {
        form.value.help_subjects.push('');
    }
}

function addLocationRow() {
    form.value.locations.push('');
}

function removeLocationRow(index) {
    form.value.locations.splice(index, 1);

    if (form.value.locations.length === 0) {
        form.value.locations.push('');
    }
}

function availableSubjectsFor(index) {
    const selected = form.value.subjects
        .map((id, selectedIndex) => selectedIndex !== index ? String(id) : null)
        .filter(Boolean);

    return subjects.value.filter((subject) => {
        return !selected.includes(String(subject.id));
    });
}

function availableHelpSubjectsFor(index) {
    const selected = form.value.help_subjects
        .map((id, selectedIndex) => selectedIndex !== index ? String(id) : null)
        .filter(Boolean);

    return subjects.value.filter((subject) => {
        return !selected.includes(String(subject.id));
    });
}

async function saveProfile() {
    successMessage.value = '';
    errorMessage.value = '';

    const helpSubjectIds = form.value.help_subjects.filter((id) => id !== '' && id !== null);

    if (hasExchangeSelected.value && helpSubjectIds.length === 0) {
        errorMessage.value = 'Bitte wähle mindestens ein Fach aus, in dem du selbst Hilfe brauchst, wenn du Tauschleistung auswählst.';
        return;
    }

    saving.value = true;

    const payload = {
        description: form.value.description,
        price_per_hour: form.value.price_per_hour,
        teaching_mode: form.value.teaching_mode,
        subjects: form.value.subjects.filter((id) => id !== '' && id !== null),
        help_subjects: helpSubjectIds,
        payment_methods: form.value.payment_methods,
        locations: form.value.locations.filter((location) => String(location).trim() !== ''),
        availability: form.value.availability,
    };

    try {
        const response = tutorProfile.value
            ? await axios.put('/api/profile', payload)
            : await axios.post('/api/profile', payload);

        tutorProfile.value = response.data.tutorProfile;
        paypal.value = response.data.paypal ?? paypal.value;
        fillForm(tutorProfile.value);

        successMessage.value = response.data.message ?? 'Profil erfolgreich gespeichert.';

        if (profileHasPayPal.value && !paypal.value.configured) {
            successMessage.value += ' PayPal ist im Backend noch nicht konfiguriert.';
        }
        editMode.value = false;
    } catch (error) {
        console.error('Fehler beim Speichern:', error);

        if (error.response?.data?.message) {
            errorMessage.value = error.response.data.message;
        } else {
            errorMessage.value = 'Profil konnte nicht gespeichert werden.';
        }
    } finally {
        saving.value = false;
    }
}

const userFullName = computed(() => {
    if (!user.value) return '';

    return user.value.name
        || `${user.value.vorname ?? ''} ${user.value.nachname ?? ''}`.trim()
        || user.value.email
        || 'Unbekannter Nutzer';
});

const initials = computed(() => {
    const first = user.value?.vorname?.[0] ?? userFullName.value?.[0] ?? '';
    const second = user.value?.nachname?.[0] ?? '';

    return `${first}${second}`.toUpperCase() || '?';
});

const reviewCount = computed(() => {
    return tutorProfile.value?.reviews?.length ?? 0;
});

const averageRating = computed(() => {
    const reviews = tutorProfile.value?.reviews ?? [];

    if (reviews.length === 0) return '0,0';

    const sum = reviews.reduce((total, review) => total + Number(review.rating ?? 0), 0);
    return (sum / reviews.length).toFixed(1).replace('.', ',');
});

const sessionsDone = computed(() => {
    return tutorProfile.value?.completed_bookings?.length
        ?? tutorProfile.value?.completedBookings?.length
        ?? 0;
});

const formattedPrice = computed(() => {
    const price = Number(tutorProfile.value?.price_per_hour ?? 0);

    return price.toLocaleString('de-DE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }) + ' €';
});

const modeLabel = computed(() => {
    const labels = {
        online: 'Online',
        in_person: 'Vor Ort',
        both: 'Online & Vor Ort',
    };

    return labels[tutorProfile.value?.teaching_mode] ?? tutorProfile.value?.teaching_mode ?? 'Nicht angegeben';
});

function weekdayLabel(dayKey) {
    return weekdays.value[dayKey] ?? dayKey;
}

function formatTime(time) {
    if (!time) return '';

    return String(time).slice(0, 5);
}

function stars(rating) {
    const value = Number(rating ?? 0);

    let output = '';

    for (let i = 1; i <= 5; i++) {
        output += i <= value ? '★' : '☆';
    }

    return output;
}

function openReviews() {
    reviewsModalOpen.value = true;
}
</script>

<style scoped>
:global(body) {
    background: #eef6f1;
}

.profile-bg {
    background: #eef6f1;
    color: var(--tc-text);
    min-height: 584px;
    padding-top: 1px;
}

.profile-bg {
    --tc-green: #2d6a4f;
    --tc-green-mid: #40916c;
    --tc-green-light: #d8f3dc;
    --tc-green-pale: #f0faf3;
    --tc-text: #1b2e23;
    --tc-muted: #6b8a76;
    --tc-border: #b7dfc5;
    --tc-card: #ffffff;
    --tc-radius: 14px;
    --tc-shadow: 0 2px 16px 0 rgba(45,106,79,.09);
}

.profile-wrap {
    max-width: 1100px;
    margin: 2rem auto;
    padding: 0 1.25rem 3rem;
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 1.5rem;
    align-items: start;
}

.tc-card {
    background: var(--tc-card);
    border-radius: var(--tc-radius);
    box-shadow: var(--tc-shadow);
    border: 1px solid var(--tc-border);
    padding: 1.5rem;
}

.avatar-circle {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    background: var(--tc-green);
    color: #fff;
    font-size: 2rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    letter-spacing: .05em;
    box-shadow: 0 4px 16px rgba(45,106,79,.25);
}

.sidebar-card {
    text-align: center;
}

.sidebar-name {
    font-size: 1.15rem;
    font-weight: 700;
    text-align: center;
    margin: 0;
}

.sidebar-email {
    font-size: .8rem;
    color: var(--tc-muted);
    text-align: center;
    word-break: break-all;
}

.sidebar-divider {
    border: none;
    border-top: 1px solid var(--tc-border);
    margin: 1rem 0;
}

.rating-button {
    width: 100%;
    border: 0;
    border-radius: 12px;
    padding: .45rem .25rem;
    background: transparent;
    color: inherit;
    cursor: pointer;
}

.rating-button:hover,
.rating-button:focus-visible {
    background: var(--tc-green-pale);
}

.rating-button:focus-visible {
    outline: 2px solid var(--tc-green);
    outline-offset: 3px;
}

.rating-big {
    text-align: center;
    font-size: 1.9rem;
    font-weight: 800;
    color: #e08c00;
}

.rating-big span {
    font-size: 1.5rem;
}

.rating-open-hint {
    display: inline-block;
    margin-top: .35rem;
    color: var(--tc-green);
    font-size: .76rem;
    font-weight: 800;
}

.rating-empty {
    margin-bottom: .15rem;
}

.rating-sub {
    text-align: center;
    font-size: .78rem;
    color: var(--tc-muted);
    margin-top: -.2rem;
}

.price-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-top: .75rem;
}

.centered {
    justify-content: center;
    gap: .35rem;
}

.price-value {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--tc-green);
}

.price-unit {
    font-size: .8rem;
    color: var(--tc-muted);
    margin-left: .15rem;
}

.section-label {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--tc-muted);
    margin-bottom: .5rem;
}

.pm-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    background: var(--tc-green-pale);
    border: 1px solid var(--tc-border);
    border-radius: 20px;
    padding: .25rem .75rem;
    font-size: .8rem;
    font-weight: 500;
    color: var(--tc-green);
    margin: .2rem .2rem 0 0;
}

.activity-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: .4rem 0;
    font-size: .88rem;
    border-bottom: 1px solid var(--tc-border);
}

.activity-row:last-child {
    border-bottom: none;
}

.activity-num {
    font-weight: 700;
    color: var(--tc-green);
}

.left-text {
    text-align: left;
}

.right-col {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.info-block-label {
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--tc-muted);
    margin-bottom: .3rem;
}

.hs-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--tc-text);
}

.hs-sub {
    font-size: .88rem;
    color: var(--tc-muted);
}

.description-text {
    font-size: .92rem;
    line-height: 1.6;
    margin: 0;
    white-space: pre-line;
}

.subject-chip {
    display: inline-block;
    background: var(--tc-green-light);
    color: var(--tc-green);
    border-radius: 20px;
    padding: .3rem .9rem;
    font-size: .82rem;
    font-weight: 600;
    margin: .2rem .2rem 0 0;
}

.mode-badge {
    display: inline-block;
    background: var(--tc-green);
    color: #fff;
    border-radius: 8px;
    padding: .3rem .85rem;
    font-size: .82rem;
    font-weight: 600;
}

.location-line {
    font-size: .88rem;
    padding: .2rem 0;
    color: var(--tc-text);
}

.avail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: .6rem;
    margin-top: .5rem;
}

.avail-card {
    background: var(--tc-green-pale);
    border: 1px solid var(--tc-border);
    border-radius: 10px;
    padding: .55rem .85rem;
    font-size: .82rem;
}

.avail-day {
    font-weight: 700;
    color: var(--tc-green);
}

.avail-time {
    color: var(--tc-muted);
}

.empty-hint {
    font-size: .85rem;
    color: var(--tc-muted);
    font-style: italic;
}

.center {
    text-align: center;
}

.btn-edit {
    display: block;
    width: 100%;
    background: var(--tc-green);
    color: #fff;
    border: none;
    border-radius: 9px;
    padding: .6rem 1.4rem;
    font-size: .9rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background .15s;
}

.btn-edit:hover {
    background: var(--tc-green-mid);
}

.edit-button-wrap {
    margin-top: 1rem;
    text-align: center;
}

.form-wrap {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1.25rem 3rem;
}

.form-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--tc-green);
    margin-bottom: 1.5rem;
}

.form-section {
    margin-bottom: 2rem;
}

.form-section-title {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--tc-muted);
    margin-bottom: .75rem;
    border-bottom: 1px solid var(--tc-border);
    padding-bottom: .4rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    font-size: .85rem;
    font-weight: 600;
    margin-bottom: .35rem;
    color: var(--tc-text);
}

.form-control {
    width: 100%;
    padding: .55rem .85rem;
    border: 1.5px solid var(--tc-border);
    border-radius: 9px;
    font-size: .9rem;
    background: #fff;
    color: var(--tc-text);
    outline: none;
    transition: border-color .15s;
    box-sizing: border-box;
}

.form-control:focus {
    border-color: var(--tc-green);
}

textarea.form-control {
    min-height: 110px;
    resize: vertical;
}

.two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.subject-row {
    display: flex;
    gap: .5rem;
    align-items: center;
    margin-bottom: .5rem;
}

.subject-row select {
    flex: 1;
}

.btn-remove {
    background: none;
    border: 1.5px solid #ccc;
    border-radius: 7px;
    width: 32px;
    height: 32px;
    cursor: pointer;
    font-size: 1rem;
    color: #999;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.btn-remove:hover {
    border-color: #e74c3c;
    color: #e74c3c;
}

.btn-add {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: var(--tc-green-pale);
    border: 1.5px dashed var(--tc-green-mid);
    color: var(--tc-green);
    border-radius: 9px;
    padding: .4rem .9rem;
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: .25rem;
    transition: background .15s;
}

.btn-add:hover {
    background: var(--tc-green-light);
}

.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: .5rem;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: .5rem;
    background: var(--tc-green-pale);
    border: 1.5px solid var(--tc-border);
    border-radius: 9px;
    padding: .45rem .75rem;
    cursor: pointer;
    font-size: .88rem;
}

.checkbox-item input {
    accent-color: var(--tc-green);
}

.avail-box {
    background: var(--tc-green-pale);
    border: 1px solid var(--tc-border);
    border-radius: 12px;
    overflow: hidden;
}

.avail-form-row {
    display: grid;
    grid-template-columns: 180px 1fr;
    gap: .75rem;
    align-items: center;
    padding: .6rem;
    border-bottom: 1px solid var(--tc-border);
    font-size: .88rem;
}

.avail-form-row:last-child {
    border-bottom: none;
}

.avail-check {
    display: flex;
    align-items: center;
    gap: .4rem;
    cursor: pointer;
}

.avail-check input {
    accent-color: var(--tc-green);
    width: 16px;
    height: 16px;
}

.avail-day-name {
    font-weight: 600;
    min-width: 90px;
}

.avail-times {
    display: flex;
    gap: .5rem;
    align-items: center;
}

.avail-times.disabled {
    opacity: .35;
    pointer-events: none;
}

.time-input {
    width: auto;
    padding: .35rem .6rem;
}

.btn-submit {
    background: var(--tc-green);
    color: #fff;
    border: none;
    border-radius: 9px;
    padding: .75rem 2rem;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s;
}

.btn-submit:hover {
    background: var(--tc-green-mid);
}

.btn-submit:disabled {
    opacity: .6;
    cursor: not-allowed;
}

.btn-cancel {
    background: transparent;
    border: none;
    color: var(--tc-muted);
    font-size: .88rem;
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.message-wrap {
    max-width: 1100px;
    margin: 1.5rem auto 0;
    padding: 0 1.25rem;
}

.alert-success {
    background: var(--tc-green-light);
    color: var(--tc-green);
    border: 1px solid var(--tc-border);
    border-radius: 10px;
    padding: .75rem 1.25rem;
    margin-bottom: 1.25rem;
    font-size: .9rem;
    font-weight: 600;
}

.alert-error {
    background: #ffeaea;
    color: #c0392b;
    border: 1px solid #f5c6c6;
    border-radius: 10px;
    padding: .75rem 1.25rem;
    margin-bottom: 1.25rem;
    font-size: .88rem;
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
    color: var(--tc-green);
    font-weight: 800;
    cursor: pointer;
}

.reviews-all-button:hover,
.reviews-all-button:focus-visible {
    text-decoration: underline;
}

.review-row {
    padding: .7rem 0;
    border-bottom: 1px solid var(--tc-border);
}

.review-row:last-child {
    border-bottom: none;
}

.review-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: .3rem;
    font-size: .82rem;
    font-weight: 600;
    color: var(--tc-text);
}

.review-stars {
    color: #e08c00;
    font-weight: 700;
}

.review-comment {
    font-size: .85rem;
    color: var(--tc-muted);
    margin: 0;
}

@media (max-width: 768px) {
    .profile-wrap {
        grid-template-columns: 1fr;
    }

    .two-col {
        grid-template-columns: 1fr;
    }

    .avail-form-row {
        grid-template-columns: 1fr;
    }

    .avail-times {
        flex-wrap: wrap;
    }
}


.paypal-connect-card,
.paypal-profile-status {
    margin-top: 1rem;
    border: 1px solid #b8c7da;
    background: #f7f9fc;
    border-radius: 14px;
    padding: 1rem;
}

.paypal-connect-card.is-connected,
.paypal-profile-status.is-connected {
    border-color: #9ed4b4;
    background: #eef9f2;
}

.paypal-connect-card.needs-action,
.paypal-profile-status.needs-action {
    border-color: #e8c984;
    background: #fff8e7;
}

.paypal-connect-head,
.paypal-profile-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.paypal-connect-head strong,
.paypal-profile-row strong {
    color: #123f78;
    font-size: .95rem;
}

.paypal-connect-head p,
.paypal-profile-status p,
.paypal-help {
    margin: .35rem 0 0;
    color: #53677d;
    font-size: .83rem;
    line-height: 1.5;
}

.paypal-status-pill,
.paypal-profile-row span {
    flex-shrink: 0;
    border-radius: 999px;
    padding: .28rem .65rem;
    background: #e7edf5;
    color: #34506f;
    font-size: .72rem;
    font-weight: 800;
}

.is-connected .paypal-status-pill,
.is-connected .paypal-profile-row span {
    background: #d7f2e1;
    color: #17613c;
}

.paypal-connect-btn,
.paypal-refresh-btn {
    margin-top: .9rem;
    border: 0;
    border-radius: 10px;
    padding: .7rem 1rem;
    cursor: pointer;
    font-weight: 800;
    font-family: inherit;
}

.paypal-connect-btn {
    background: #0070ba;
    color: #fff;
}

.paypal-refresh-btn {
    background: #e7edf5;
    color: #23496f;
}

.paypal-connect-btn.compact,
.paypal-refresh-btn.compact {
    width: 100%;
    padding: .55rem .75rem;
    font-size: .78rem;
}

.paypal-connect-btn:disabled,
.paypal-refresh-btn:disabled {
    opacity: .55;
    cursor: not-allowed;
}

.paypal-config-warning {
    margin: .75rem 0 0;
    color: #a14b34;
    font-size: .78rem;
    line-height: 1.45;
}
</style>