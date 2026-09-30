<template>
    <RouterView />
    <ReviewPrompt v-if="auth.isAuthenticated" />
</template>

<script setup>
import { useAuthStore } from './auth'
import ReviewPrompt from './Components/ReviewPrompt.vue'

const auth = useAuthStore()
</script>
