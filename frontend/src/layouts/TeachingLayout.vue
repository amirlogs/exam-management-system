<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { LayoutGrid, BookOpen, HelpCircle, CheckSquare, Flag } from 'lucide-vue-next'
import AppSidebar from './partials/AppSidebar.vue'
import AppTopbar from './partials/AppTopbar.vue'
import WorkspaceSwitcherDropdown from './partials/WorkspaceSwitcherDropdown.vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const navItems = [
    { label: 'Dashboard', to: '/teaching/dashboard', icon: LayoutGrid },
    { label: 'My Courses', to: '/teaching/courses', icon: BookOpen },
    { label: 'Question Bank', to: '/teaching/question-bank', icon: HelpCircle },
    { label: 'Grading Queue', to: '/teaching/grading', icon: CheckSquare },
    { label: 'Flags', to: '/teaching/flags', icon: Flag },
]
</script>

<template>
    <div class="flex min-h-screen bg-bg">
        <AppSidebar brand-name="University Exam" brand-subtitle="Management Portal" :items="navItems" :active-to="route.path" />

        <div class="flex-1 flex flex-col">
            <AppTopbar :user-name="authStore.user?.name" :user-role="authStore.user?.roleLabel"
                :user-avatar="authStore.user?.avatar">
                <template #workspace-switcher>
                    <WorkspaceSwitcherDropdown active-workspace="teaching"
                        @change="id => router.push(`/${id}/dashboard`)" />
                </template>
            </AppTopbar>

            <main class="p-8 flex-1">
                <router-view />
            </main>
        </div>
    </div>
</template>
