import { createApp } from 'vue'

import App from './App.vue'
import pinia from './pinia'
import router from './router'
import { useAuthStore } from './auth'
import './api'
import './assets/tutorconnect.css'

const app = createApp(App)

app.use(pinia)
app.use(router)
app.mount('#app')

const auth = useAuthStore(pinia)

if (auth.token) {
    auth.fetchUser().catch(() => {
        // Ungültige oder abgelaufene Tokens werden im Store entfernt.
    })
}
