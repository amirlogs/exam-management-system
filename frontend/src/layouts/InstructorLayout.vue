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
    { label: 'Dashboard', to: '/instructor/dashboard', icon: LayoutGrid },
    { label: 'My Courses', to: '/instructor/courses', icon: BookOpen },
    { label: 'Question Bank', to: '/instructor/question-bank', icon: HelpCircle },
]
</script>


<template>
    <div class="flex min-h-screen bg-bg">
        <AppSidebar brand-name="University Exam" brand-subtitle="Management Portal" :items="navItems"
            :active-to="route.path" />

        <div class="flex-1 flex flex-col min-w-0">
            <AppTopbar :user-name="authStore.user?.first_name" :user-avatar="authStore.user?.avatar">
                <template #workspace-switcher>
                    <WorkspaceSwitcherDropdown />
                </template>
            </AppTopbar>

            <main class="p-8 flex-1">
                <router-view :key="$route.fullPath" />
            </main>
        </div>
    </div>
</template>
