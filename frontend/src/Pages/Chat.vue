<template>
    <div class="app">
        <Topbar />

        <div style="background:var(--bg);min-height:584px">
            <div class="chat-outer">
                <div class="chat-layout" :class="{ 'no-conversations': !selectedPartner && conversations.length === 0 }">
                    <!-- LINKE SEITE -->
                    <div class="chat-box">
                        <div class="chat-hd">
                            <div class="chat-hd-av">
                                {{ selectedPartner ? getInitials(selectedPartner.name) : 'TC' }}
                            </div>

                            <div>
                                <div class="chat-hd-name">
                                    {{ selectedPartner ? selectedPartner.name : 'TutorConnect Chat' }}
                                </div>

                                <div class="chat-hd-sub">
                                    <span v-if="selectedTutorProfile">
                                        {{ selectedTutorProfile.price_per_hour }} € / Stunde ·
                                        {{ getPaymentMethods(selectedTutorProfile) }}
                                    </span>

                                    <span v-else-if="selectedPartner">
                                        Normales Nutzerkonto
                                    </span>

                                    <span v-else>
                                        Suche einen Tutor und starte einen Chat.
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- CHATLISTE / SUCHE -->
                        <div v-if="!selectedPartner" class="chat-msgs">
                            <div v-if="conversations.length === 0" class="req-form-card" style="margin-bottom:14px">
                                <div class="rfc-h">Tutor suchen</div>

                                <div class="rf-fld">
                                    <label>Name oder Fach</label>

                                    <input
                                        v-model.trim="search"
                                        type="text"
                                        placeholder="z.B. Abdullah oder Analysis"
                                        @keyup.enter="searchTutors"
                                    >
                                </div>

                                <button class="btn-anfrage" type="button" @click="searchTutors">
                                    Suchen
                                </button>
                            </div>

                            <div v-if="conversations.length === 0 && loading" class="tutors-sub">
                                Wird geladen...
                            </div>

                            <div v-if="errorMessage" class="tutors-sub" style="color:#dc2626">
                                {{ errorMessage }}
                            </div>

                            <div v-if="conversations.length === 0 && tutorResults.length > 0" class="req-form-card">
                                <div class="rfc-h">Gefundene Tutoren</div>

                                <div
                                    v-for="tutor in tutorResults"
                                    :key="tutor.id"
                                    class="tc-top"
                                    style="cursor:pointer;margin-bottom:12px"
                                    @click="openTutor(tutor)"
                                >
                                    <div class="tc-av">
                                        {{ getInitials(tutor.name) }}
                                    </div>

                                    <div>
                                        <div class="tc-name">{{ tutor.name }}</div>

                                        <div class="tc-price">
                                            {{ tutor.price_per_hour }} € / Stunde ·
                                            {{ getPaymentMethods(tutor) }}
                                        </div>

                                        <div class="tc-tags" style="margin-top:6px">
                                            <span
                                                v-for="subject in tutor.subjects.slice(0, 3)"
                                                :key="subject.id"
                                                class="tc-tag"
                                            >
                                                {{ subject.name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="conversations.length > 0" class="req-form-card">
                                <div class="rfc-h">Alte Chats</div>

                                <div
                                    v-for="conversation in conversations"
                                    :key="conversation.partner.id"
                                    class="tc-top"
                                    style="cursor:pointer;margin-bottom:12px"
                                    @click="openConversation(conversation)"
                                >
                                    <div class="tc-av">
                                        {{ getInitials(conversation.partner.name) }}
                                    </div>

                                    <div>
                                        <div class="tc-name">
                                            {{ conversation.partner.name }}
                                            <span v-if="conversation.partner.tutor_profile" class="mini-badge">Tutor</span>
                                        </div>

                                        <div class="tutors-sub">
                                            {{ conversation.last_message.message }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="!loading && conversations.length === 0 && tutorResults.length === 0"
                                class="tutors-sub"
                            >
                                Noch kein Chat vorhanden. Suche oben nach einem Tutor.
                            </div>
                        </div>

                        <!-- NACHRICHTEN -->
                        <div v-else ref="messagesBox" class="chat-msgs chat-scroll">
                            <button
                                type="button"
                                class="tutors-back"
                                style="border:0;background:transparent;cursor:pointer;margin-bottom:14px"
                                @click="closeChat"
                            >
                                ← Zurück zu Chats
                            </button>

                            <div v-if="loadingMessages" class="tutors-sub">
                                Nachrichten werden geladen...
                            </div>

                            <div v-else-if="messages.length === 0" class="tutors-sub">
                                Noch keine Nachrichten. Schreib die erste Nachricht.
                            </div>

                            <div
                                v-for="message in messages"
                                :key="message.id"
                                :class="message.is_mine ? 'msg-m' : 'msg-t'"
                            >
                                <div :class="message.is_mine ? 'bbl-m' : 'bbl-t'">
                                    <template v-if="message.booking">
                                        <div class="booking-msg-card">
                                            <div class="booking-msg-head">
                                                <div>
                                                    <div class="booking-msg-kicker">Nachhilfe-Anfrage</div>
                                                    <div class="booking-msg-title">
                                                        {{ message.booking.subject_name || 'Fach nicht angegeben' }}
                                                    </div>
                                                </div>

                                                <span class="booking-msg-status">
                                                    {{ message.booking.status_label || 'Offen' }}
                                                </span>
                                            </div>

                                            <div class="booking-msg-grid">
                                                <div>
                                                    <span>Anfragender</span>
                                                    <strong>{{ message.booking.student_name }}</strong>
                                                </div>

                                                <div>
                                                    <span>Tutor</span>
                                                    <strong>{{ message.booking.tutor_name }}</strong>
                                                </div>

                                                <div>
                                                    <span>Datum</span>
                                                    <strong>{{ formatBookingDate(message.booking.requested_date) }}</strong>
                                                </div>

                                                <div>
                                                    <span>Uhrzeit</span>
                                                    <strong>{{ formatBookingTimeRange(message.booking) }}</strong>
                                                </div>

                                                <div>
                                                    <span>Modus</span>
                                                    <strong>{{ message.booking.mode_label }}</strong>
                                                </div>

                                                <div>
                                                    <span>Art</span>
                                                    <strong>{{ message.booking.request_type_label }}</strong>
                                                </div>

                                                <div>
                                                    <span>Zahlung / Tausch</span>
                                                    <strong>{{ message.booking.payment_method_name || 'Keine Auswahl' }}</strong>
                                                </div>
                                            </div>

                                            <div v-if="hasText(message.booking.message)" class="booking-msg-note">
                                                <span>Thema / Nachricht</span>
                                                <p>{{ message.booking.message }}</p>
                                            </div>

                                            <div v-if="hasText(message.booking.exchange_offer)" class="booking-msg-note booking-msg-note-exchange">
                                                <span>Tauschvorschlag</span>
                                                <p>{{ message.booking.exchange_offer }}</p>
                                            </div>

                                            <RouterLink
                                                v-if="canManageBookingMessage(message)"
                                                class="booking-dashboard-btn"
                                                :to="{ name: 'dashboard' }"
                                            >
                                                Im Tutor-Dashboard verwalten
                                            </RouterLink>
                                        </div>
                                    </template>

                                    <template v-else>
                                        {{ message.message }}
                                    </template>
                                </div>

                                <div class="msg-ts">
                                    {{ formatTime(message.created_at) }}
                                </div>
                            </div>
                        </div>

                        <!-- EINGABE -->
                        <form
                            v-if="selectedPartner"
                            class="chat-inp-wrap"
                            @submit.prevent="sendMessage"
                        >
                            <input
                                v-model.trim="newMessage"
                                class="chat-inp"
                                placeholder="Nachricht eingeben..."
                                type="text"
                            >

                            <button class="chat-sbtn" type="submit" :disabled="sendingMessage">
                                {{ sendingMessage ? '...' : 'SENDEN' }}
                            </button>
                        </form>
                    </div>

                    <!-- RECHTE SEITE -->
                    <div v-if="selectedPartner || conversations.length > 0" class="chat-side">
                        <div v-if="!selectedPartner" class="req-form-card">
                            <div class="rfc-h">Neuen Tutor suchen</div>

                            <div class="rf-fld">
                                <label>Name oder Fach</label>

                                <input
                                    v-model.trim="search"
                                    type="text"
                                    placeholder="z.B. Abdullah oder Analysis"
                                >
                            </div>

                            <div v-if="loading" class="tutors-sub">
                                Suche läuft...
                            </div>

                            <div v-if="errorMessage" class="tutors-sub" style="color:#dc2626">
                                {{ errorMessage }}
                            </div>

                            <div
                                v-for="tutor in tutorResults"
                                :key="tutor.id"
                                class="tc-top"
                                style="cursor:pointer;margin-bottom:12px"
                                @click="openTutor(tutor)"
                            >
                                <div class="tc-av">
                                    {{ getInitials(tutor.name) }}
                                </div>

                                <div>
                                    <div class="tc-name">{{ tutor.name }}</div>

                                    <div class="tc-price">
                                        {{ tutor.price_per_hour }} € / Stunde
                                    </div>

                                    <div class="tc-tags" style="margin-top:6px">
                                        <span
                                            v-for="subject in tutor.subjects.slice(0, 2)"
                                            :key="subject.id"
                                            class="tc-tag"
                                        >
                                            {{ subject.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="search.length >= 2 && !loading && tutorResults.length === 0" class="tutors-sub">
                                Kein Tutor gefunden.
                            </div>
                        </div>

                        <template v-else-if="selectedTutorProfile">
                            <!-- TUTOR-KARTE -->
                            <div class="ts-card">
                                <div class="ts-av">
                                    {{ getInitials(selectedPartner.name) }}
                                </div>

                                <div class="ts-name">
                                    {{ selectedPartner.name }}
                                </div>

                                <div class="ts-sem">
                                    {{ getModeLabel(selectedTutorProfile.teaching_mode) }}
                                </div>

                                <div class="ts-price">
                                    {{ selectedTutorProfile.price_per_hour }} € / Stunde
                                </div>

                                <div style="display:flex;gap:6px;justify-content:center;margin-bottom:10px;flex-wrap:wrap">
                                    <span
                                        v-for="method in selectedTutorProfile.payment_methods"
                                        :key="method.id"
                                        style="background:#fff8e7;color:#92400e;font-size:11px;font-weight:700;padding:3px 9px;border-radius:6px;border:1px solid #f59e0b"
                                    >
                                        {{ method.name }}
                                    </span>
                                </div>

                                <div class="ts-tl">Fächer:</div>

                                <div class="ts-tags">
                                    <span
                                        v-for="subject in selectedTutorProfile.subjects"
                                        :key="subject.id"
                                        class="tc-tag"
                                    >
                                        {{ subject.name }}
                                    </span>
                                </div>

                                <RouterLink
                                    class="btn-anfrage"
                                    style="display:block;text-align:center;margin-top:14px;text-decoration:none"
                                    :to="{ name: 'tutor-profile', params: { id: selectedTutorProfile.id } }"
                                >
                                    Profil öffnen
                                </RouterLink>
                            </div>

                            <!-- ANFRAGEFORMULAR -->
                            <div class="req-form-card">
                                <div class="rfc-h">Nachhilfe-Session anfragen</div>

                                <p v-if="bookingSuccess" class="auth-message auth-success">
                                    {{ bookingSuccess }}
                                </p>

                                <p v-if="bookingError" class="auth-message auth-error">
                                    {{ bookingError }}
                                </p>

                                <div class="rf-fld">
                                    <label>Fach</label>

                                    <select v-model="bookingForm.subject_id">
                                        <option value="">Fach auswählen</option>

                                        <option
                                            v-for="subject in selectedTutorProfile.subjects"
                                            :key="subject.id"
                                            :value="subject.id"
                                        >
                                            {{ subject.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="rf-fld">
                                    <label>Durchführung</label>

                                    <select
                                        v-model="bookingForm.mode"
                                        :disabled="availableModeOptions.length === 0"
                                    >
                                        <option
                                            v-for="modeOption in availableModeOptions"
                                            :key="modeOption.value"
                                            :value="modeOption.value"
                                        >
                                            {{ modeOption.label }}
                                        </option>
                                    </select>
                                </div>

                                <div class="rf-fld">
                                    <label>Anfrageart</label>

                                    <select v-model="bookingForm.request_type">
                                        <option v-if="canUsePaid" value="paid">
                                            Nachhilfe bezahlen
                                        </option>

                                        <option v-if="canUseExchange" value="exchange">
                                            Tauschleistung vorschlagen
                                        </option>

                                        <option v-if="canUseFree" value="free">
                                            Kostenlos anfragen
                                        </option>
                                    </select>
                                </div>

                                <p v-if="exchangeInfoText" class="tutors-sub">
                                    {{ exchangeInfoText }}
                                </p>

                                <div class="rf-fld">
                                    <label>Datum</label>

                                    <select
                                        v-model="bookingForm.requested_date"
                                        :disabled="loadingBookingOptions || availableDates.length === 0"
                                    >
                                        <option value="">
                                            {{ loadingBookingOptions ? 'Termine werden geladen...' : 'Datum auswählen' }}
                                        </option>

                                        <option
                                            v-for="dateOption in availableDates"
                                            :key="dateOption.date"
                                            :value="dateOption.date"
                                        >
                                            {{ dateOption.label }}
                                        </option>
                                    </select>

                                    <p
                                        v-if="!loadingBookingOptions && selectedTutorProfile && availableDates.length === 0"
                                        class="tutors-sub"
                                        style="margin-top:6px"
                                    >
                                        Für diese Auswahl gibt es in den nächsten 60 Tagen keinen passenden freien Termin.
                                    </p>
                                </div>

                                <div class="rf-fld">
                                    <label>Uhrzeit</label>

                                    <select
                                        v-model="selectedSlotKey"
                                        :disabled="!bookingForm.requested_date || availableSlots.length === 0"
                                    >
                                        <option value="">Uhrzeit auswählen</option>

                                        <option
                                            v-for="slot in availableSlots"
                                            :key="slot.start_time + slot.end_time"
                                            :value="slot.start_time + '|' + slot.end_time"
                                        >
                                            {{ slot.label }}
                                        </option>
                                    </select>
                                </div>

                                <div class="rf-fld">
                                    <label>Zahlungsart / Tausch</label>

                                    <select v-model="bookingForm.payment_method_id">
                                        <option value="">Keine Auswahl</option>

                                        <option
                                            v-for="method in filteredPaymentMethods"
                                            :key="method.id"
                                            :value="method.id"
                                        >
                                            {{ method.name }}
                                        </option>
                                    </select>
                                </div>

                                <div v-if="bookingForm.request_type === 'exchange' && canUseExchange" class="rf-fld">
                                    <label>Dein Tauschvorschlag</label>

                                    <textarea
                                        v-model.trim="bookingForm.exchange_offer"
                                        placeholder="z.B. Ich kann dir bei Programmierung helfen ..."
                                    />
                                </div>

                                <div class="rf-fld">
                                    <label>Thema / Beschreibung</label>

                                    <textarea
                                        v-model.trim="bookingForm.message"
                                        placeholder="z.B. Ableitungsregeln, Grenzwerte ..."
                                    />
                                </div>

                                <button
                                    class="btn-anfrage"
                                    type="button"
                                    :disabled="sendingBooking"
                                    @click="sendBookingRequest"
                                >
                                    {{ sendingBooking ? 'WIRD GESENDET...' : 'ANFRAGE SENDEN' }}
                                </button>
                            </div>
                        </template>

                        <template v-else>
                            <div class="ts-card">
                                <div class="ts-av">
                                    {{ getInitials(selectedPartner.name) }}
                                </div>

                                <div class="ts-name">
                                    {{ selectedPartner.name }}
                                </div>

                                <div class="ts-sem">
                                    Normales Nutzerkonto
                                </div>

                                <p class="tutors-sub" style="margin-top:12px;">
                                    Dieser Nutzer hat kein Tutorprofil. Du kannst hier nur den bestehenden Chat fortsetzen.
                                </p>
                            </div>

                            <div class="req-form-card">
                                <div class="rfc-h">Keine Nachhilfe-Anfrage möglich</div>

                                <p class="tutors-sub">
                                    Anfragen können nur an Nutzer mit Tutorprofil gesendet werden.
                                </p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import Topbar from '../Components/Topbar.vue'
import api from '../api'

const route = useRoute()

const conversations = ref([])
const tutorResults = ref([])
const selectedPartner = ref(null)
const currentUser = ref(null)
const messages = ref([])
const messagesBox = ref(null)
const availableDates = ref([])

const search = ref('')
let searchTimeout = null
let messageRefreshInterval = null
let bookingOptionsRequestCounter = 0
const newMessage = ref('')
const selectedSlotKey = ref('')

const loading = ref(false)
const loadingMessages = ref(false)
const sendingMessage = ref(false)
const sendingBooking = ref(false)
const loadingBookingOptions = ref(false)

const errorMessage = ref('')
const bookingSuccess = ref('')
const bookingError = ref('')

const bookingForm = ref({
    tutor_profile_id: '',
    subject_id: '',
    payment_method_id: '',
    requested_date: '',
    start_time: '',
    end_time: '',
    mode: 'online',
    request_type: 'paid',
    exchange_offer: '',
    message: '',
})

const selectedTutorProfile = computed(() => {
    return selectedPartner.value?.tutor_profile ?? null
})

const availableModeOptions = computed(() => {
    if (!selectedTutorProfile.value) {
        return []
    }

    const modes = new Set()
    const tutorAvailabilities = selectedTutorProfile.value.availabilities ?? []

    tutorAvailabilities.forEach((availability) => {
        if (availability.is_available === false) {
            return
        }

        const mode = normalizeFrontendMode(availability.mode)

        if (mode === 'both') {
            modes.add('online')
            modes.add('presence')
            return
        }

        if (mode === 'online' || mode === 'presence') {
            modes.add(mode)
        }
    })

    if (modes.size === 0) {
        const profileMode = normalizeFrontendMode(selectedTutorProfile.value.teaching_mode)

        if (profileMode === 'both') {
            modes.add('online')
            modes.add('presence')
        } else if (profileMode === 'online' || profileMode === 'presence') {
            modes.add(profileMode)
        }
    }

    return [
        ...(modes.has('online') ? [{ value: 'online', label: 'Online' }] : []),
        ...(modes.has('presence') ? [{ value: 'presence', label: 'Vor Ort' }] : []),
    ]
})

const availableSlots = computed(() => {
    if (!bookingForm.value.requested_date) {
        return []
    }

    const selectedDate = availableDates.value.find(
        (dateOption) => dateOption.date === bookingForm.value.requested_date
    )

    return selectedDate?.slots ?? []
})

const selectedSlot = computed(() => {
    if (!selectedSlotKey.value) {
        return null
    }

    const [startTime, endTime] = selectedSlotKey.value.split('|')

    return {
        start_time: startTime,
        end_time: endTime,
    }
})

const currentUserHasTutorProfile = computed(() => {
    return Boolean(currentUser.value?.tutor_profile)
})

const selectedTutorHasExchange = computed(() => {
    return hasPaymentMethod('tausch')
})

const exchangeMatchingSubjects = computed(() => {
    const mySubjects = currentUser.value?.tutor_profile?.subjects ?? []
    const otherHelpSubjects = selectedTutorProfile.value?.help_subjects ?? []

    const mySubjectIds = new Set(mySubjects.map((subject) => String(subject.id)))

    return otherHelpSubjects.filter((subject) => mySubjectIds.has(String(subject.id)))
})

const canUseExchange = computed(() => {
    return currentUserHasTutorProfile.value
        && selectedTutorHasExchange.value
        && exchangeMatchingSubjects.value.length > 0
})

const exchangeInfoText = computed(() => {
    if (!selectedTutorProfile.value || !selectedTutorHasExchange.value) {
        return ''
    }

    if (!currentUserHasTutorProfile.value) {
        return 'Tauschleistung ist hier versteckt, weil du selbst kein Tutorprofil hast.'
    }

    if (exchangeMatchingSubjects.value.length === 0) {
        return 'Tauschleistung ist hier versteckt, weil du kein Fach unterrichtest, in dem diese Person Hilfe braucht.'
    }

    return `Tausch möglich für: ${exchangeMatchingSubjects.value.map((subject) => subject.name).join(', ')}`
})

const canUseFree = computed(() => {
    return hasPaymentMethod('kostenlos')
})

const canUsePaid = computed(() => {
    if (!selectedTutorProfile.value) {
        return false
    }

    const methods = selectedTutorProfile.value.payment_methods ?? []

    return methods.some((method) => {
        const name = String(method.name ?? '').toLowerCase()
        return !name.includes('tausch') && !name.includes('kostenlos')
    })
})

const filteredPaymentMethods = computed(() => {
    if (!selectedTutorProfile.value?.payment_methods) {
        return []
    }

    if (bookingForm.value.request_type === 'exchange') {
        return selectedTutorProfile.value.payment_methods.filter((method) =>
            String(method.name ?? '').toLowerCase().includes('tausch')
        )
    }

    if (bookingForm.value.request_type === 'free') {
        return selectedTutorProfile.value.payment_methods.filter((method) =>
            String(method.name ?? '').toLowerCase().includes('kostenlos')
        )
    }

    return selectedTutorProfile.value.payment_methods.filter((method) => {
        const name = String(method.name ?? '').toLowerCase()
        return !name.includes('tausch') && !name.includes('kostenlos')
    })
})

onMounted(async () => {
    await loadCurrentUser()
    await loadConversations()

    if (route.query.tutor_profile_id) {
        await openTutorById(route.query.tutor_profile_id)
    }
})

watch(search, () => {
    clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        searchTutors()
    }, 300)
})

watch(selectedSlot, (slot) => {
    if (!slot) {
        bookingForm.value.start_time = ''
        bookingForm.value.end_time = ''
        return
    }

    bookingForm.value.start_time = slot.start_time
    bookingForm.value.end_time = slot.end_time
})

watch(
    [() => bookingForm.value.mode, () => bookingForm.value.request_type],
    () => {
        loadBookingOptions()
    }
)

watch(
    () => bookingForm.value.requested_date,
    () => {
        selectedSlotKey.value = ''
        bookingForm.value.start_time = ''
        bookingForm.value.end_time = ''
    }
)

watch([canUsePaid, canUseExchange, canUseFree], () => {
    if (!selectedTutorProfile.value) {
        return
    }

    if (bookingForm.value.request_type === 'exchange' && !canUseExchange.value) {
        bookingForm.value.request_type = getDefaultRequestType()
    }

    if (bookingForm.value.request_type === 'free' && !canUseFree.value) {
        bookingForm.value.request_type = getDefaultRequestType()
    }

    if (bookingForm.value.request_type === 'paid' && !canUsePaid.value) {
        bookingForm.value.request_type = getDefaultRequestType()
    }
})

watch(filteredPaymentMethods, (methods) => {
    if (methods.length === 0) {
        bookingForm.value.payment_method_id = ''
        return
    }

    const exists = methods.some((method) => String(method.id) === String(bookingForm.value.payment_method_id))

    if (!exists) {
        bookingForm.value.payment_method_id = methods[0].id
    }
})

async function loadCurrentUser() {
    try {
        const response = await api.get('/api/chat/me')
        currentUser.value = response.data
    } catch (error) {
        console.error(error)
        currentUser.value = null
    }
}

async function loadConversations() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get('/api/chat/conversations')
        conversations.value = response.data
    } catch (error) {
        console.error(error)
        errorMessage.value = 'Chats konnten nicht geladen werden.'
    } finally {
        loading.value = false
    }
}

async function searchTutors() {
    errorMessage.value = ''
    tutorResults.value = []

    if (!search.value || search.value.length < 2) {
        return
    }

    loading.value = true

    try {
        const response = await api.get('/api/chat/tutors', {
            params: {
                search: search.value,
            },
        })

        tutorResults.value = response.data
    } catch (error) {
        console.error(error)
        errorMessage.value = 'Tutoren konnten nicht gesucht werden.'
    } finally {
        loading.value = false
    }
}

async function openTutorById(tutorProfileId) {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await api.get(`/api/chat/tutors/${tutorProfileId}`)
        await openTutor(response.data)
    } catch (error) {
        console.error(error)
        errorMessage.value = 'Tutor konnte nicht geöffnet werden.'
    } finally {
        loading.value = false
    }
}

async function openTutor(tutorProfile) {
    selectedPartner.value = {
        id: tutorProfile.user_id,
        name: tutorProfile.name,
        vorname: tutorProfile.vorname,
        nachname: tutorProfile.nachname,
        tutor_profile: tutorProfile,
    }

    resetBookingForm()
    await loadBookingOptions()
    await loadMessages()
    startMessagePolling()
}

async function openConversation(conversation) {
    selectedPartner.value = conversation.partner
    resetBookingForm()
    await loadBookingOptions()
    await loadMessages()
    startMessagePolling()
}

function resetBookingForm() {
    messages.value = []
    bookingSuccess.value = ''
    bookingError.value = ''

    if (selectedTutorProfile.value) {
        bookingForm.value.tutor_profile_id = selectedTutorProfile.value.id
        bookingForm.value.subject_id = selectedTutorProfile.value.subjects?.[0]?.id ?? ''
        bookingForm.value.request_type = getDefaultRequestType()
    } else {
        bookingForm.value.tutor_profile_id = ''
        bookingForm.value.subject_id = ''
        bookingForm.value.request_type = 'paid'
    }

    bookingForm.value.payment_method_id = ''
    bookingForm.value.exchange_offer = ''
    bookingForm.value.message = ''
    bookingForm.value.requested_date = ''
    bookingForm.value.start_time = ''
    bookingForm.value.end_time = ''
    bookingForm.value.mode = getDefaultMode()

    selectedSlotKey.value = ''
    availableDates.value = []
}

async function loadMessages(showLoading = true, onlyNew = false) {
    if (!selectedPartner.value) {
        return
    }

    const shouldAutoScroll = !onlyNew || isMessagesBoxNearBottom()

    if (showLoading) {
        loadingMessages.value = true
    }

    try {
        const params = {}

        if (onlyNew) {
            const lastId = getLastMessageId()

            if (lastId > 0) {
                params.after_id = lastId
            }
        }

        const response = await api.get(`/api/chat/users/${selectedPartner.value.id}/messages`, {
            params,
        })

        if (onlyNew) {
            addNewMessages(response.data)
        } else {
            messages.value = response.data
            sortMessages()
        }

        if (response.data.length > 0) {
            if (shouldAutoScroll) {
                scrollMessagesToBottom()
            }

            await loadConversations()
        }
    } catch (error) {
        console.error(error)

        if (showLoading) {
            errorMessage.value = 'Nachrichten konnten nicht geladen werden.'
        }
    } finally {
        if (showLoading) {
            loadingMessages.value = false
        }
    }
}

function isMessagesBoxNearBottom(distance = 120) {
    if (!messagesBox.value) {
        return true
    }

    const box = messagesBox.value
    return box.scrollHeight - box.scrollTop - box.clientHeight <= distance
}

function scrollMessagesToBottom() {
    nextTick(() => {
        if (messagesBox.value) {
            messagesBox.value.scrollTop = messagesBox.value.scrollHeight
        }
    })
}

function getLastMessageId() {
    if (messages.value.length === 0) {
        return 0
    }

    return Math.max(...messages.value.map((message) => Number(message.id)))
}

function addNewMessages(newMessages) {
    const existingIds = new Set(messages.value.map((message) => Number(message.id)))

    newMessages.forEach((message) => {
        if (!existingIds.has(Number(message.id))) {
            messages.value.push(message)
        }
    })

    sortMessages()
}

function sortMessages() {
    messages.value.sort((a, b) => Number(a.id) - Number(b.id))
}

async function sendMessage() {
    if (!newMessage.value || !selectedPartner.value) {
        return
    }

    const messageText = newMessage.value

    sendingMessage.value = true
    newMessage.value = ''

    try {
        await api.post(`/api/chat/users/${selectedPartner.value.id}/messages`, {
            message: messageText,
        })

        await loadMessages(false, false)
        await loadConversations()
        scrollMessagesToBottom()
    } catch (error) {
        console.error(error)
        errorMessage.value = 'Nachricht konnte nicht gesendet werden.'
        newMessage.value = messageText
    } finally {
        sendingMessage.value = false
    }
}

async function loadBookingOptions() {
    const requestId = ++bookingOptionsRequestCounter

    availableDates.value = []
    selectedSlotKey.value = ''
    bookingForm.value.requested_date = ''
    bookingForm.value.start_time = ''
    bookingForm.value.end_time = ''

    if (
        !selectedTutorProfile.value
        || !bookingForm.value.mode
        || !bookingForm.value.request_type
    ) {
        return
    }

    loadingBookingOptions.value = true
    bookingError.value = ''

    try {
        const response = await api.get(
            `/api/chat/tutors/${selectedTutorProfile.value.id}/booking-options`,
            {
                params: {
                    mode: bookingForm.value.mode,
                    request_type: bookingForm.value.request_type,
                },
            }
        )

        if (requestId !== bookingOptionsRequestCounter) {
            return
        }

        availableDates.value = response.data?.dates ?? []
    } catch (error) {
        if (requestId !== bookingOptionsRequestCounter) {
            return
        }

        console.error(error)
        bookingError.value = error.response?.data?.message
            || 'Verfügbare Termine konnten nicht geladen werden.'
    } finally {
        if (requestId === bookingOptionsRequestCounter) {
            loadingBookingOptions.value = false
        }
    }
}

async function sendBookingRequest() {
    bookingSuccess.value = ''
    bookingError.value = ''

    if (!selectedTutorProfile.value) {
        bookingError.value = 'Anfragen können nur an Tutorprofile gesendet werden.'
        return
    }

    if (!bookingForm.value.subject_id) {
        bookingError.value = 'Bitte wähle ein Fach aus.'
        return
    }

    if (!bookingForm.value.requested_date || !selectedSlot.value) {
        bookingError.value = 'Bitte wähle Datum und Uhrzeit aus.'
        return
    }

    if (bookingForm.value.request_type === 'exchange' && !canUseExchange.value) {
        bookingError.value = 'Tauschleistung ist nur möglich, wenn du Tutor in einem Fach bist, in dem diese Person Hilfe braucht.'
        return
    }

    if (bookingForm.value.request_type === 'exchange' && !bookingForm.value.exchange_offer) {
        bookingError.value = 'Bitte beschreibe deine Tauschleistung.'
        return
    }

    sendingBooking.value = true

    try {
        await api.post('/api/bookings', {
            tutor_profile_id: selectedTutorProfile.value.id,
            subject_id: bookingForm.value.subject_id,
            payment_method_id: bookingForm.value.payment_method_id || null,
            requested_date: bookingForm.value.requested_date,
            start_time: bookingForm.value.start_time,
            end_time: bookingForm.value.end_time,
            mode: bookingForm.value.mode,
            request_type: bookingForm.value.request_type,
            exchange_offer: bookingForm.value.exchange_offer || null,
            message: bookingForm.value.message || null,
        })

        bookingSuccess.value = 'Anfrage wurde erfolgreich gesendet.'
        bookingError.value = ''

        bookingForm.value.message = ''
        bookingForm.value.exchange_offer = ''
        selectedSlotKey.value = ''

        await loadBookingOptions()
        await loadMessages()
        await loadConversations()
    } catch (error) {
        console.error(error)
        bookingError.value = error.response?.data?.message || 'Anfrage konnte nicht gesendet werden.'
    } finally {
        sendingBooking.value = false
    }
}

function hasPaymentMethod(searchName) {
    if (!selectedTutorProfile.value?.payment_methods) {
        return false
    }

    return selectedTutorProfile.value.payment_methods.some((method) =>
        String(method.name ?? '').toLowerCase().includes(searchName)
    )
}

function getDefaultMode() {
    return availableModeOptions.value[0]?.value ?? 'online'
}

function normalizeFrontendMode(mode) {
    if (mode === 'in_person') return 'presence'
    if (mode === 'presence') return 'presence'
    if (mode === 'both') return 'both'
    if (mode === 'online') return 'online'

    return ''
}

function getDefaultRequestType() {
    if (canUsePaid.value) {
        return 'paid'
    }

    if (canUseFree.value) {
        return 'free'
    }

    if (canUseExchange.value) {
        return 'exchange'
    }

    return 'paid'
}

function startMessagePolling() {
    stopMessagePolling()

    messageRefreshInterval = setInterval(() => {
        if (selectedPartner.value) {
            loadMessages(false, true)
        }
    }, 3000)
}

function stopMessagePolling() {
    if (messageRefreshInterval) {
        clearInterval(messageRefreshInterval)
        messageRefreshInterval = null
    }
}

onUnmounted(() => {
    stopMessagePolling()
})

function closeChat() {
    stopMessagePolling()

    selectedPartner.value = null
    messages.value = []
    newMessage.value = ''
    bookingSuccess.value = ''
    bookingError.value = ''
}

function getInitials(name) {
    if (!name) {
        return 'TC'
    }

    return name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()
}

function getPaymentMethods(tutorProfile) {
    if (!tutorProfile.payment_methods || tutorProfile.payment_methods.length === 0) {
        return 'Keine Zahlungsart'
    }

    return tutorProfile.payment_methods.map((method) => method.name).join(', ')
}

function getModeLabel(mode) {
    if (mode === 'online') return 'Online'
    if (mode === 'in_person' || mode === 'presence') return 'Vor Ort'
    if (mode === 'both') return 'Online & vor Ort'

    return 'Flexibel'
}

function canManageBookingMessage(message) {
    return Number(message?.booking?.tutor_user_id) === Number(currentUser.value?.id)
}

function hasText(value) {
    return value !== null && value !== undefined && String(value).trim() !== ''
}

function formatBookingDate(value) {
    if (!value) {
        return 'Datum nicht angegeben'
    }

    const datePart = String(value).slice(0, 10)
    const [year, month, day] = datePart.split('-').map(Number)

    if (!year || !month || !day) {
        return String(value)
    }

    return new Date(year, month - 1, day).toLocaleDateString('de-DE', {
        weekday: 'short',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    })
}

function formatShortTime(value) {
    if (!value) {
        return '--:--'
    }

    return String(value).slice(0, 5)
}

function formatBookingTimeRange(booking) {
    return `${formatShortTime(booking.start_time)} - ${formatShortTime(booking.end_time)} Uhr`
}

function formatTime(value) {
    if (!value) {
        return ''
    }

    return new Date(value).toLocaleTimeString('de-DE', {
        hour: '2-digit',
        minute: '2-digit',
    })
}
</script>

<style scoped>
.mini-badge {
    display: inline-block;
    margin-left: 6px;
    padding: 2px 7px;
    border-radius: 999px;
    background: #d8f3dc;
    color: #2d6a4f;
    font-size: 11px;
    font-weight: 800;
}

.chat-outer {
    height: calc(100vh - 72px);
    overflow: hidden;
}

.chat-layout {
    height: 100%;
    align-items: stretch;
}

.chat-layout.no-conversations {
    grid-template-columns: 1fr;
}

.chat-layout.no-conversations .chat-box {
    width: 100%;
}

.chat-box {
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.chat-hd {
    flex-shrink: 0;
}

.chat-scroll {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding-bottom: 18px;
}

.chat-inp-wrap {
    flex-shrink: 0;
}

.chat-side {
    max-height: 100%;
    overflow-y: auto;
}

.booking-msg-card {
    min-width: 280px;
    max-width: 520px;
    color: #111827;
}

.booking-msg-head {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: flex-start;
    padding-bottom: 12px;
    margin-bottom: 12px;
    border-bottom: 1px solid rgba(15, 23, 42, .12);
}

.booking-msg-kicker {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-weight: 900;
    color: #92400e;
}

.booking-msg-title {
    margin-top: 3px;
    font-size: 16px;
    font-weight: 900;
    line-height: 1.25;
}

.booking-msg-status {
    flex-shrink: 0;
    padding: 4px 10px;
    border-radius: 999px;
    background: #fff7ed;
    color: #9a3412;
    border: 1px solid #fed7aa;
    font-size: 11px;
    font-weight: 900;
}

.booking-msg-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.booking-msg-grid div {
    background: rgba(255, 255, 255, .72);
    border: 1px solid rgba(15, 23, 42, .08);
    border-radius: 10px;
    padding: 9px 10px;
}

.booking-msg-grid span,
.booking-msg-note span {
    display: block;
    color: #6b7280;
    font-size: 11px;
    font-weight: 800;
    margin-bottom: 3px;
}

.booking-msg-grid strong {
    display: block;
    font-size: 13px;
    line-height: 1.3;
}

.booking-msg-note {
    margin-top: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    background: rgba(255, 255, 255, .78);
    border: 1px solid rgba(15, 23, 42, .08);
}

.booking-msg-note-exchange {
    background: #fff7ed;
    border-color: #fed7aa;
}

.booking-msg-note p {
    margin: 0;
    font-size: 13px;
    line-height: 1.45;
}

.booking-dashboard-btn {
    display: block;
    margin-top: 12px;
    text-align: center;
    padding: 10px 12px;
    border-radius: 999px;
    background: #111827;
    color: white;
    text-decoration: none;
    font-size: 12px;
    font-weight: 900;
}

.booking-dashboard-btn:hover {
    filter: brightness(1.08);
}

@media (max-width: 760px) {
    .booking-msg-card {
        min-width: 0;
    }

    .booking-msg-grid {
        grid-template-columns: 1fr;
    }
}

</style>
