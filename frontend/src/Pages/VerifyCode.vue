<template>
    <div class="app">
        <header class="topbar solid" aria-label="Bestätigungsnavigation">
            <RouterLink class="tb-logo" to="/">
                <span class="tb-logo-ic">TC</span>
                <span class="tb-logo-name">TutorConnect</span>
            </RouterLink>

            <div></div>

            <RouterLink class="tb-link" to="/login">← Zurück</RouterLink>
        </header>

        <main class="login-outer">
            <section class="login-card">
                <div class="login-logo-wrap">
                    <div class="login-logo-sq">TC</div>
                    <div class="login-title">E-Mail bestätigen</div>
                    <div class="login-sub">
                        Wir haben einen Code an {{ email }} gesendet.
                    </div>
                </div>

                <p v-if="errorMessage" class="verify-message">
                    {{ errorMessage }}
                </p>

                <form @submit.prevent="verifyCode">
                    <div class="fld">
                        <label for="verification-code">Bestätigungscode</label>
                        <input
                            id="verification-code"
                            v-model.trim="code"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            placeholder="6-stelligen Code eingeben"
                            required
                        >
                    </div>

                    <button type="submit" class="btn-login" :disabled="submitting">
                        {{ submitting ? 'Code wird geprüft …' : 'Code bestätigen' }}
                    </button>
                </form>
            </section>
        </main>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api'
import { useAuthStore } from '../auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const code = ref('')
const submitting = ref(false)
const errorMessage = ref('')

const email = computed(() =>
    String(
        route.query.email
        || sessionStorage.getItem('pending_registration_email')
        || ''
    )
)

async function verifyCode() {
    errorMessage.value = ''

    if (!email.value) {
        errorMessage.value = 'Die Registrierungs-E-Mail fehlt. Bitte erneut registrieren.'
        return
    }

    submitting.value = true

    try {
        const response = await api.post('/api/auth/register/verify-code', {
            email: email.value,
            code: code.value,
        })

        auth.setSession(response.data)
        sessionStorage.removeItem('pending_registration_email')
        await router.push('/')
    } catch (error) {
        const errors = error.response?.data?.errors
        errorMessage.value = errors
            ? Object.values(errors).flat()[0]
            : error.response?.data?.message || 'Der Code ist ungültig.'
    } finally {
        submitting.value = false
    }
}
</script>

<style scoped>
.verify-message {
    margin-bottom: 14px;
    padding: 10px 12px;
    border-radius: 8px;
    color: #991b1b;
    background: #fee2e2;
    font-size: 13px;
    text-align: center;
}

button:disabled {
    cursor: wait;
    opacity: 0.65;
}
</style>
