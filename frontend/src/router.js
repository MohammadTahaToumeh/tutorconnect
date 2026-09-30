
import { createRouter, createWebHistory } from 'vue-router'
import pinia from './pinia'
import { useAuthStore } from './auth'

import Home from './Pages/Home.vue'
import Login from './Pages/Login.vue'
import VerifyCode from './Pages/VerifyCode.vue'
import StudyPrograms from './Pages/StudyPrograms.vue'
import Subjects from './Pages/Subjects.vue'
import Tutors from './Pages/Tutors.vue'
import Profile from './Pages/Profile.vue'
import Chat from './Pages/Chat.vue'
import Dashboard from './Pages/Dashboard.vue'
import PastSessions from './Pages/PastSessions.vue'
import TutorProfile from './Pages/TutorProfile.vue'

const routes = [
    {
        path: '/',
        name: 'home',
        component: Home,
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { auth_required: false },
    },
    {
        path: '/verify-code',
        name: 'verify-code',
        component: VerifyCode,
        meta: { auth_required: false },
    },
    {
        path: '/study-programs',
        name: 'study-programs',
        component: StudyPrograms,
        meta: { auth_required: true },
    },
    {
        path: '/subjects',
        name: 'subjects',
        component: Subjects,
        meta: { auth_required: true },
    },
    {
        path: '/tutors',
        name: 'tutors',
        component: Tutors,
        meta: { auth_required: true },
    },
    {
    path: '/tutors/:id',
    name: 'tutor-profile',
    component: TutorProfile,
    meta: { auth_required: true },
    },
    {
        path: '/profile',
        name: 'profile',
        component: Profile,
        meta: { auth_required: true },
    },
    {
        path: '/chat',
        name: 'chat',
        component: Chat,
        meta: { auth_required: true },
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: Dashboard,
        meta: { auth_required: true },
    },
    {
    path: '/past-sessions',
    name: 'past-sessions',
    component: PastSessions,
    meta: { auth_required: true },
},
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
    
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to) => {
    const auth = useAuthStore(pinia)

    if (to.meta.auth_required && !auth.isAuthenticated) {
        return {
            name: 'login',
            query: {
                redirect: to.fullPath,
            },
        }
    }

    if (
        (
            to.name === 'login'
            || to.name === 'verify-code'
        )
        && auth.isAuthenticated
    ) {
        return {
            name: 'home',
        }
    }

    return true
})

export default router

