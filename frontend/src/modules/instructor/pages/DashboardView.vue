<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { BookOpen, ClipboardCheck, FileClock } from 'lucide-vue-next'
import StatCardRow from '@/shared/components/StatCardRow.vue'
// import { getInstructorDashboard } from '../api/dashboard' — endpoint TBD, see below

const stats = ref({ my_courses: 0, exams_pending_approval: 0, answers_awaiting_grading: 0 })
const upcomingExams = ref<any[]>([])
const loading = ref(true)

async function load() {
    loading.value = true
    // const res = await getInstructorDashboard()
    // stats.value = res.data.stats
    // upcomingExams.value = res.data.upcoming_exams
    loading.value = false
}
onMounted(load)
</script>

<template>
    <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-8 min-h-[calc(100vh-68px)]">
        <div>
            <h1 class="text-2xl font-bold font-display text-text">Teaching Dashboard</h1>
            <p class="mt-1.5 text-sm text-text/60">Your courses, exams, and grading at a glance.</p>
        </div>

        <StatCardRow :cards="[
            { label: 'My Courses', value: stats.my_courses, icon: BookOpen },
            { label: 'Exams Pending Approval', value: stats.exams_pending_approval, icon: FileClock, tone: 'warning' },
            { label: 'Answers Awaiting Grading', value: stats.answers_awaiting_grading, icon: ClipboardCheck, tone: 'danger' },
        ]" />

        <!-- Upcoming exams strip -->
        <div class="bg-surface border border-border rounded-xl p-5">
            <h3 class="font-semibold text-text mb-4">Upcoming Exams</h3>
            <div v-if="loading" class="flex gap-4">
                <div v-for="i in 3" :key="i" class="h-24 w-56 animate-pulse rounded-lg bg-bg" />
            </div>
            <div v-else-if="upcomingExams.length" class="flex gap-4 overflow-x-auto pb-2">
                <div v-for="exam in upcomingExams" :key="exam.id"
                    class="bg-bg border border-border rounded-lg p-4 min-w-[220px] shrink-0">
                    <p class="font-semibold text-sm text-text">{{ exam.title || '—' }}</p>
                    <p class="text-xs text-text/50 mt-1">{{ exam.course_offering?.course?.code || '—' }}</p>
                    <p class="text-xs text-text/40 mt-1">{{ exam.scheduled_at || 'Not scheduled' }}</p>
                </div>
            </div>
            <p v-else class="text-sm text-text/50 py-6 text-center">No upcoming exams.</p>
        </div>
    </div>
</template>
