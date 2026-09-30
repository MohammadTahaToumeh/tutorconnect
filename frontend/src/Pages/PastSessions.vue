<template>
    <div class="past-page">
        <RouterLink to="/dashboard" class="back-link">
            ← Zurück zum Dashboard
        </RouterLink>

        <h1>Vergangene Sitzungen</h1>

        <p class="subtitle">
            Hier findest du alle abgeschlossenen und bewerteten Nachhilfestunden.
        </p>

        <div v-if="pastBookings.length === 0" class="empty-card">
    Noch keine vergangenen Sitzungen vorhanden.
</div>

<div v-else>
    <div
        v-for="booking in pastBookings"
        :key="booking.id"
        class="empty-card"
    >
        <h3>{{ booking.subject?.name }}</h3>

        <p>
            {{ booking.requested_date }}
            {{ booking.start_time }} - {{ booking.end_time }}
        </p>

        <p>Status: Abgeschlossen</p>
    </div>
</div>
    </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { ref, computed, onMounted } from 'vue'
import api from '../api'

const bookings = ref([])

onMounted(async () => {
    const response = await api.get('/api/bookings')
    bookings.value = response.data.as_student || []
})

const pastBookings = computed(() =>
    bookings.value.filter(
        booking => booking.status === 'completed'
    )
)
</script>
<style scoped>
.past-page{
    max-width:1100px;
    margin:40px auto;
    padding:20px;
}

.back-link{
    text-decoration:none;
    color:#0d6b52;
    font-weight:bold;
}

h1{
    margin:20px 0 8px;
    color:#12352d;
}

.subtitle{
    color:#5b6d65;
    margin-bottom:30px;
}

.empty-card{
    background:white;
    border-radius:14px;
    padding:25px;
    border:1px solid #d8e7df;
}
</style>