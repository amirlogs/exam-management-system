<script setup lang="ts">
import { useRoute } from 'vue-router'
import {
    LayoutGrid, Building2, Landmark, GraduationCap, BookOpen,
    ListTree, CalendarDays, Users2, Upload, ClipboardList,
    ShieldCheck, BarChart3,
} from 'lucide-vue-next'
import AppSidebar from './partials/AppSidebar.vue'
import AppTopbar from './partials/AppTopbar.vue'
import WorkspaceSwitcherDropdown from './partials/WorkspaceSwitcherDropdown.vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const authStore = useAuthStore()

const navItems = [
    { label: 'Dashboard', to: '/admin/dashboard', icon: LayoutGrid },
    { label: 'Universities', to: '/admin/universities', icon: Landmark },
    { label: 'Colleges', to: '/admin/colleges', icon: Building2 },
    { label: 'Departments', to: '/admin/departments', icon: Building2 },
    { label: 'Programs', to: '/admin/programs', icon: GraduationCap },
    { label: 'Courses', to: '/admin/courses', icon: BookOpen },
    { label: 'Curriculum', to: '/admin/curriculum', icon: ListTree },
    { label: 'Semesters', to: '/admin/semesters', icon: CalendarDays },
    { label: 'Sections', to: '/admin/sections', icon: Users2 },
    { label: 'Data Import', to: '/admin/imports', icon: Upload },
    { label: 'Course Offerings', to: '/admin/course-offerings', icon: ClipboardList },
    { label: 'Users', to: '/admin/users', icon: Users2 },
    { label: 'Roles & Permissions', to: '/admin/roles', icon: ShieldCheck },
    { label: 'Results', to: '/admin/results', icon: BarChart3 },
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
