import { defineStore } from 'pinia'
import api, { setApiToken } from './api'

function readStoredJson(key) {
    const value = localStorage.getItem(key) || sessionStorage.getItem(key)

    if (!value) {
        return null
    }

    try {
        return JSON.parse(value)
    } catch {
        return null
    }
}

function clearStoredSession() {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    sessionStorage.removeItem('token')
    sessionStorage.removeItem('user')
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token:
            localStorage.getItem('token')
            || sessionStorage.getItem('token'),
        user: readStoredJson('user'),
        remember: Boolean(localStorage.getItem('token')),
    }),

    getters: {
        isAuthenticated: (state) => Boolean(state.token),
    },

    actions: {
        setSession(userInfo, remember = true) {
            clearStoredSession()

            const storage = remember ? localStorage : sessionStorage

            this.token = userInfo.token
            this.user = userInfo.user
            this.remember = remember

            storage.setItem('token', userInfo.token)
            storage.setItem('user', JSON.stringify(userInfo.user))
            setApiToken(userInfo.token)
        },

        clearSession() {
            this.token = null
            this.user = null
            this.remember = false

            clearStoredSession()
            setApiToken(null)
        },

        async login(credentials, remember = true) {
            const response = await api.post('/api/auth/login', credentials)

            this.setSession(response.data, remember)

            return response.data
        },

        async fetchUser() {
            if (!this.token) {
                return null
            }

            try {
                const response = await api.get('/api/auth/user')

                this.user = response.data.user

                const storage = this.remember
                    ? localStorage
                    : sessionStorage

                storage.setItem(
                    'user',
                    JSON.stringify(this.user)
                )

                return this.user
            } catch (error) {
                if (error.response?.status === 401) {
                    this.clearSession()
                }

                throw error
            }
        },

        async logout() {
            try {
                if (this.token) {
                    await api.post('/api/auth/logout')
                }
            } finally {
                this.clearSession()
            }
        },
    },
})
