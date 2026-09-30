
<template>
    <div class="app">
        <header
            class="topbar solid"
            aria-label="Anmeldenavigation"
        >
            <RouterLink
                class="tb-logo"
                to="/"
            >
                <span
                    class="tb-logo-ic"
                    aria-hidden="true"
                >
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.4"
                    >
                        <path d="M12 2L2 7l10 5 10-5-10-5z" />
                        <path d="M2 17l10 5 10-5" />
                        <path d="M2 12l10 5 10-5" />
                    </svg>
                </span>

                <span class="tb-logo-name">
                    TutorConnect
                </span>
            </RouterLink>

            <div></div>

            <RouterLink
                class="tb-link"
                to="/"
            >
                ← Zurück
            </RouterLink>
        </header>

        <main class="login-outer">
            <section class="login-card">
                <div class="login-logo-wrap">
                    <div class="login-logo-sq">
                        <svg
                            width="28"
                            height="28"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="white"
                            stroke-width="2.5"
                        >
                            <path d="M12 2L2 7l10 5 10-5-10-5z" />
                            <path d="M2 17l10 5 10-5" />
                            <path d="M2 12l10 5 10-5" />
                        </svg>
                    </div>

                    <div class="login-title">
                        TutorConnect
                    </div>

                    <div class="login-sub">
                        HS Bochum · Fachbereich Informatik
                    </div>
                </div>

                <div class="auth-tabs">
                    <button
                        type="button"
                        class="auth-tab"
                        :class="{ active: activeTab === 'login' }"
                        @click="activeTab = 'login'"
                    >
                        Anmelden
                    </button>

                    <button
                        type="button"
                        class="auth-tab"
                        :class="{ active: activeTab === 'register' }"
                        @click="activeTab = 'register'"
                    >
                        Registrieren
                    </button>
                </div>

                <p
                    v-if="errorMessage"
                    class="auth-message auth-error"
                >
                    {{ errorMessage }}
                </p>

                <p
                    v-if="successMessage"
                    class="auth-message auth-success"
                >
                    {{ successMessage }}
                </p>

                <form
                    v-if="activeTab === 'login'"
                    @submit.prevent="submitLogin"
                >
                    <div class="fld">
                        <label for="login-email">
                            Studentische Hochschul-E-Mail
                        </label>

                        <input
                            id="login-email"
                            v-model.trim="loginForm.email"
                            type="email"
                            autocomplete="email"
                            placeholder="vorname.nachname@stud.hs-bochum.de"
                            required
                        >
                    </div>

                    <div class="fld">
                        <label for="login-password">
                            Passwort
                        </label>

                        <input
                            id="login-password"
                            v-model="loginForm.password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="••••••••"
                            required
                        >
                    </div>

                    <label class="remember-row">
                        <input
                            v-model="loginForm.remember"
                            type="checkbox"
                        >

                        <span>
                            Angemeldet bleiben
                        </span>
                    </label>

                    <button
                        type="submit"
                        class="btn-login"
                        :disabled="submitting"
                    >
                        {{
                            submitting
                                ? 'Anmelden …'
                                : 'Anmelden'
                        }}
                    </button>
                </form>

                <form
                    v-else
                    @submit.prevent="sendRegistrationCode"
                >
                    <div class="fld">
                        <label for="first-name">
                            Vorname
                        </label>

                        <input
                            id="first-name"
                            v-model.trim="registerForm.vorname"
                            type="text"
                            required
                        >
                    </div>

                    <div class="fld">
                        <label for="last-name">
                            Nachname
                        </label>

                        <input
                            id="last-name"
                            v-model.trim="registerForm.nachname"
                            type="text"
                            required
                        >
                    </div>

                    <div class="fld">
                        <label for="register-email">
                            Studentische Hochschul-E-Mail
                        </label>

                        <input
                            id="register-email"
                            v-model.trim="registerForm.email"
                            type="email"
                            autocomplete="email"
                            placeholder="vorname.nachname@stud.hs-bochum.de"
                            required
                        >
                    </div>

                    <div class="fld">
                        <label for="register-password">
                            Passwort
                        </label>

                        <input
                            id="register-password"
                            v-model="registerForm.password"
                            type="password"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >
                    </div>

                    <div class="fld">
                        <label for="register-password-confirmation">
                            Passwort bestätigen
                        </label>

                        <input
                            id="register-password-confirmation"
                            v-model="registerForm.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >
                    </div>

                    <div class="fld">
                        <label for="study-program">
                            Studiengang
                        </label>

                        <select
                            id="study-program"
                            v-model="registerForm.studiengang"
                            required
                        >
                            <option
                                value=""
                                disabled
                            >
                                Studiengang auswählen
                            </option>

                            <option
                                v-for="program in studyPrograms"
                                :key="program.id"
                                :value="program.name"
                            >
                                {{ program.name }}
                            </option>
                        </select>
                    </div>

                    <div class="fld">
                        <label for="semester">
                            Semester
                        </label>

                        <select
                            id="semester"
                            v-model="registerForm.semester"
                            required
                        >
                            <option
                                value=""
                                disabled
                            >
                                Semester auswählen
                            </option>

                            <option
                                v-for="semester in 14"
                                :key="semester"
                                :value="String(semester)"
                            >
                                {{ semester }}
                            </option>
                        </select>
                    </div>

                    <div class="fld">
                        <label for="role">
                            Ich möchte
                        </label>

                        <select
                            id="role"
                            v-model="registerForm.rolle"
                            required
                        >
                            <option value="Nachhilfe bekommen">
                                Nachhilfe bekommen
                            </option>

                            <option value="Nachhilfe anbieten">
                                Nachhilfe anbieten
                            </option>

                            <option value="Beides">
                                Beides
                            </option>
                        </select>
                    </div>

                    <button
                        type="submit"
                        class="btn-login"
                        :disabled="submitting"
                    >
                        {{
                            submitting
                                ? 'Code wird gesendet …'
                                : 'Account erstellen'
                        }}
                    </button>
                </form>
            </section>
        </main>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api'
import { useAuthStore } from '../auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const activeTab = ref(
    route.query.tab === 'register'
        ? 'register'
        : 'login'
)

const submitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const studyPrograms = ref([])

const loginForm = reactive({
    email: '',
    password: '',
    remember: true,
})

const registerForm = reactive({
    vorname: '',
    nachname: '',
    email: '',
    password: '',
    password_confirmation: '',
    studiengang: '',
    semester: '',
    rolle: 'Nachhilfe bekommen',
})

onMounted(async () => {
    try {
        const response = await api.get(
            '/api/auth/register/options'
        )

        studyPrograms.value =
            response.data.study_programs
    } catch {
        studyPrograms.value = []
    }
})

async function submitLogin() {
    resetMessages()
    submitting.value = true

    try {
        await auth.login(
            {
                email: loginForm.email,
                password: loginForm.password,
            },
            loginForm.remember
        )
    } catch (error) {
        console.error('Login-Fehler:', error)

        errorMessage.value = getErrorMessage(
            error,
            'E-Mail oder Passwort ist falsch.'
        )

        submitting.value = false
        return
    }

    try {
        await router.push(
            String(route.query.redirect || '/')
        )
    } catch (error) {
        console.error('Weiterleitungs-Fehler:', error)

        errorMessage.value =
            'Login war erfolgreich, aber die Weiterleitung hat nicht geklappt.'

        submitting.value = false
        return
    }

    submitting.value = false
}

async function sendRegistrationCode() {
    resetMessages()

    if (
        registerForm.password
        !== registerForm.password_confirmation
    ) {
        errorMessage.value =
            'Die Passwortbestätigung stimmt nicht überein.'

        return
    }

    submitting.value = true

    try {
        const response = await api.post(
            '/api/auth/register/send-code',
            registerForm
        )

        sessionStorage.setItem(
            'pending_registration_email',
            response.data.email
        )

        await router.push({
            name: 'verify-code',
            query: {
                email: response.data.email,
            },
        })
    } catch (error) {
        errorMessage.value = getErrorMessage(
            error,
            'Der Bestätigungscode konnte nicht gesendet werden.'
        )
    } finally {
        submitting.value = false
    }
}

function resetMessages() {
    errorMessage.value = ''
    successMessage.value = ''
}

function getErrorMessage(error, fallback) {
    const errors = error.response?.data?.errors

    if (errors) {
        return Object.values(errors).flat()[0]
            || fallback
    }

    return error.response?.data?.message
        || fallback
}
</script>

<style scoped>
.auth-message {
    margin-bottom: 14px;
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 13px;
    text-align: center;
}

.auth-error {
    color: #991b1b;
    background: #fee2e2;
}

.auth-success {
    color: #065f46;
    background: #d1fae5;
}

.remember-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 2px 0 14px;
    color: #5a6b63;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.remember-row input {
    width: 16px;
    height: 16px;
    accent-color: #0d6b52;
}

button:disabled {
    cursor: wait;
    opacity: 0.65;
}
</style>

