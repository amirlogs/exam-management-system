<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import AppSidebar from './partials/AppSidebar.vue'
import AppTopbar from './partials/AppTopbar.vue'
import WorkspaceTabs from './partials/WorkspaceTabs.vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const navItems = [
    { label: 'Dashboard', to: '/teaching/dashboard' },
    { label: 'My Courses', to: '/teaching/courses' },
    { label: 'Question Bank', to: '/teaching/question-bank' },
    { label: 'Grading Queue', to: '/teaching/grading' },
    { label: 'Flags', to: '/teaching/flags' },
]
</script>

<template>
    <div class="flex min-h-screen bg-bg">
        <AppSidebar brand-name="EduExam" brand-subtitle="Teaching Portal" :items="navItems" :active-to="route.path" />

        <div class="flex-1">
            <AppTopbar :user-name="authStore.user?.name" :user-role="authStore.user?.roleLabel"
                :user-avatar="authStore.user?.avatar">
                <template #left>
                    <WorkspaceTabs active-workspace="teaching" @change="id => router.push(`/${id}/dashboard`)" />
                </template>
            </AppTopbar>

            <main class="p-8">
                <router-view />
            </main>
        </div>
    </div>
</template>
