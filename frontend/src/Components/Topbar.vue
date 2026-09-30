
<template>
    <header
        class="topbar solid"
        aria-label="Hauptnavigation"
    >
        <RouterLink
            class="tb-logo"
            to="/"
            aria-label="TutorConnect Startseite"
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
                    stroke-linecap="round"
                    stroke-linejoin="round"
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

        <nav
            v-if="auth.isAuthenticated"
            class="tb-nav"
            aria-label="Seitenbereiche"
        >
            <RouterLink
                class="tb-link"
                :class="{ active: studySectionActive }"
                to="/study-programs"
            >
                Studiengänge &amp; Fächer
            </RouterLink>

            <RouterLink
                class="tb-link"
                :class="{ active: tutorsActive }"
                to="/tutors"
            >
                Alle Tutoren
            </RouterLink>

            <RouterLink
                class="tb-link"
                :class="{ active: route.path === '/chat' }"
                to="/chat"
            >
                Chat
            </RouterLink>

            <RouterLink
                class="tb-link"
                :class="{ active: route.path === '/dashboard' }"
                to="/dashboard"
            >
                Dashboard
            </RouterLink>
        </nav>

        <div
            v-else
            class="tb-nav"
        ></div>

        <div class="tb-actions">
            <template v-if="auth.isAuthenticated">
                <RouterLink
                    class="tb-profil"
                    :class="{ active: profileActive }"
                    to="/profile"
                >
                    <svg
                        width="13"
                        height="13"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.2"
                        aria-hidden="true"
                    >
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" />
                    </svg>

                    Profil
                </RouterLink>

                <button
                    type="button"
                    class="tb-logout"
                    :disabled="loggingOut"
                    @click="logout"
                >
                    {{ loggingOut ? '...' : 'Ausloggen' }}
                </button>
            </template>

            <template v-else>
                <RouterLink
                    class="tb-link"
                    to="/login"
                >
                    Anmelden
                </RouterLink>

                <RouterLink
                    class="tb-profil"
                    :to="{
                        name: 'login',
                        query: { tab: 'register' },
                    }"
                >
                    Registrieren
                </RouterLink>
            </template>
        </div>
    </header>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const loggingOut = ref(false)

const studySectionActive = computed(() =>
    route.path === '/study-programs'
    || route.path === '/subjects'
)

const tutorsActive = computed(() =>
    route.path === '/tutors'
)

const profileActive = computed(() =>
    route.path === '/profile'
)

async function logout() {
    loggingOut.value = true

    try {
        await auth.logout()
        await router.push('/')
    } finally {
        loggingOut.value = false
    }
}
</script>


