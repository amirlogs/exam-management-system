<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import {
    Landmark,
    BookOpen,
    Users2,
    Upload,
    BarChart3,
    ArrowUpRight,
    Plus,
    CheckCircle2,
    Clock,
    AlertCircle,
    Calendar,
    ChevronRight,
} from 'lucide-vue-next'

const router = useRouter()

const stats = ref([
    { label: 'Total Universities', value: '4', change: 'Across all regions', icon: Landmark, color: 'text-blue-600 bg-blue-500/10' },
    { label: 'Active Courses', value: '1,280', change: 'Across 12 Colleges', icon: BookOpen, color: 'text-indigo-600 bg-indigo-500/10' },
    { label: 'Registered Users', value: '14,520', change: '+340 this month', icon: Users2, color: 'text-emerald-600 bg-emerald-500/10' },
    { label: 'Pending Imports', value: '3', change: 'Requires review', icon: Upload, color: 'text-amber-600 bg-amber-500/10' },
])

const recentImports = ref([
    { id: 1, filename: 'CS_Semester_2_Questions.xlsx', type: 'Course Questions', uploadedBy: 'Dr. Abebe', status: 'completed', date: '10 mins ago' },
    { id: 2, filename: 'Eng_Faculty_Students.csv', type: 'User Roster', uploadedBy: 'Admin Team', status: 'pending', date: '1 hour ago' },
    { id: 3, filename: 'Med_Curriculum_2026.xlsx', type: 'Curriculum', uploadedBy: 'S. Kebede', status: 'failed', date: '3 hours ago' },
    { id: 4, filename: 'Freshman_Course_Offerings.csv', type: 'Course Offerings', uploadedBy: 'Registrar', status: 'completed', date: 'Yesterday' },
])

const academicOverview = [
    { label: 'Colleges', count: 12 },
    { label: 'Departments', count: 48 },
    { label: 'Programs', count: 96 },
    { label: 'Active Semesters', count: 2 },
]
</script>

<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-text">Admin Dashboard</h1>
                <p class="text-sm text-text/60">Overview of academic structures, system users, and data operations.</p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    @click="router.push('/admin/imports')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-text bg-surface border border-border rounded-lg hover:bg-bg transition-colors"
                >
                    <Upload :size="16" />
                    Import Data
                </button>
                <button
                    @click="router.push('/admin/users')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-accent rounded-lg hover:opacity-90 transition-opacity"
                >
                    <Plus :size="16" />
                    Add User
                </button>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="bg-surface border border-border rounded-xl p-5 shadow-sm space-y-3"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-text/60 uppercase tracking-wider">{{ stat.label }}</span>
                    <div :class="['w-9 h-9 rounded-lg flex items-center justify-center', stat.color]">
                        <component :is="stat.icon" :size="18" />
                    </div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-text">{{ stat.value }}</div>
                    <p class="text-xs text-text/50 mt-1">{{ stat.change }}</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left 2 Columns -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Recent Imports -->
                <div class="bg-surface border border-border rounded-xl shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-border flex items-center justify-between">
                        <div>
                            <h2 class="font-bold text-base text-text">Recent Data Imports</h2>
                            <p class="text-xs text-text/60">Status of latest batch import operations</p>
                        </div>
                        <button
                            @click="router.push('/admin/imports')"
                            class="text-xs font-semibold text-accent hover:underline flex items-center gap-1"
                        >
                            View All <ChevronRight :size="14" />
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-bg text-text/60 text-xs font-semibold uppercase border-b border-border">
                                <tr>
                                    <th class="py-3 px-4">File Name</th>
                                    <th class="py-3 px-4">Type</th>
                                    <th class="py-3 px-4">Uploaded By</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="item in recentImports" :key="item.id" class="hover:bg-bg/50 transition-colors">
                                    <td class="py-3.5 px-4 font-medium text-text truncate max-w-[180px]">
                                        {{ item.filename }}
                                    </td>
                                    <td class="py-3.5 px-4 text-text/70 text-xs">{{ item.type }}</td>
                                    <td class="py-3.5 px-4 text-text/70 text-xs">{{ item.uploadedBy }}</td>
                                    <td class="py-3.5 px-4">
                                        <span
                                            v-if="item.status === 'completed'"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600"
                                        >
                                            <CheckCircle2 :size="12" /> Completed
                                        </span>
                                        <span
                                            v-else-if="item.status === 'pending'"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-500/10 text-amber-600"
                                        >
                                            <Clock :size="12" /> Pending
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-600"
                                        >
                                            <AlertCircle :size="12" /> Failed
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right text-text/50 text-xs">{{ item.date }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-surface border border-border rounded-xl shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-border flex items-center justify-between">
                        <div>
                            <h2 class="font-bold text-base text-text">Recent Data Imports</h2>
                            <p class="text-xs text-text/60">Status of latest batch import operations</p>
                        </div>
                        <button
                            @click="router.push('/admin/imports')"
                            class="text-xs font-semibold text-accent hover:underline flex items-center gap-1"
                        >
                            View All <ChevronRight :size="14" />
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-bg text-text/60 text-xs font-semibold uppercase border-b border-border">
                                <tr>
                                    <th class="py-3 px-4">File Name</th>
                                    <th class="py-3 px-4">Type</th>
                                    <th class="py-3 px-4">Uploaded By</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="item in recentImports" :key="item.id" class="hover:bg-bg/50 transition-colors">
                                    <td class="py-3.5 px-4 font-medium text-text truncate max-w-[180px]">
                                        {{ item.filename }}
                                    </td>
                                    <td class="py-3.5 px-4 text-text/70 text-xs">{{ item.type }}</td>
                                    <td class="py-3.5 px-4 text-text/70 text-xs">{{ item.uploadedBy }}</td>
                                    <td class="py-3.5 px-4">
                                        <span
                                            v-if="item.status === 'completed'"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-600"
                                        >
                                            <CheckCircle2 :size="12" /> Completed
                                        </span>
                                        <span
                                            v-else-if="item.status === 'pending'"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-500/10 text-amber-600"
                                        >
                                            <Clock :size="12" /> Pending
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-600"
                                        >
                                            <AlertCircle :size="12" /> Failed
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-right text-text/50 text-xs">{{ item.date }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Academic Hierarchy -->
                <div class="bg-surface border border-border rounded-xl p-5 shadow-sm space-y-4">
                    <h2 class="font-bold text-base text-text">Academic Hierarchy Summary</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div v-for="item in academicOverview" :key="item.label" class="p-4 bg-bg rounded-lg border border-border text-center">
                            <div class="text-xl font-bold text-text">{{ item.count }}</div>
                            <div class="text-xs text-text/60 mt-0.5">{{ item.label }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Semester Card -->
                <div class="bg-surface border border-border rounded-xl p-5 shadow-sm space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-accent/10 text-accent flex items-center justify-center shrink-0">
                            <Calendar :size="20" />
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-text">Active Semester</h3>
                            <p class="text-xs text-text/60">Academic Year 2025/2026</p>
                        </div>
                    </div>
                    <div class="space-y-2 pt-2 border-t border-border text-xs">
                        <div class="flex justify-between text-text/70">
                            <span>Current Period:</span>
                            <span class="font-semibold text-text">Semester II</span>
                        </div>
                        <div class="flex justify-between text-text/70">
                            <span>Exam Phase:</span>
                            <span class="font-semibold text-emerald-600">Active</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-surface border border-border rounded-xl p-5 shadow-sm space-y-3">
                    <h3 class="font-bold text-sm text-text">Quick Navigation</h3>
                    <div class="space-y-2">
                        <button
                            @click="router.push('/admin/courses')"
                            class="w-full flex items-center justify-between p-3 rounded-lg border border-border hover:bg-bg transition-colors text-left text-xs font-medium text-text"
                        >
                            <span>Manage Courses</span>
                            <ArrowUpRight :size="14" class="text-text/40" />
                        </button>
                        <button
                            @click="router.push('/admin/roles')"
                            class="w-full flex items-center justify-between p-3 rounded-lg border border-border hover:bg-bg transition-colors text-left text-xs font-medium text-text"
                        >
                            <span>Configure Roles</span>
                            <ArrowUpRight :size="14" class="text-text/40" />
                        </button>
                        <button
                            @click="router.push('/admin/results')"
                            class="w-full flex items-center justify-between p-3 rounded-lg border border-border hover:bg-bg transition-colors text-left text-xs font-medium text-text"
                        >
                            <span>View Results</span>
                            <BarChart3 :size="14" class="text-text/40" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>