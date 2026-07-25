<script setup lang="ts">
import { FileText, Flag, CheckSquare } from 'lucide-vue-next'
import StatCard from '@/shared/components/ui/StatCard.vue'
import GradingQueueCard from '../components/GradingQueueCard.vue'
import IntegrityFeedCard from '../components/IntegrityFeedCard.vue'
import PerformanceDistributionCard from '../components/PerformanceDistributionCard.vue'

// Placeholder data — replace with real API calls
const gradingQueue = [
    { courseCode: 'CS401', studentId: '#294012', examType: 'Mid-term', status: 'UNGRADED' },
    { courseCode: 'MATH202', studentId: '#294105', examType: 'Final', status: 'FLAGGED' },
    { courseCode: 'HIST101', studentId: '#294558', examType: 'Short Essay', status: 'UNGRADED' },
    { courseCode: 'CS401', studentId: '#294022', examType: 'Mid-term', status: 'AI PENDING' },
    { courseCode: 'ECON305', studentId: '#293991', examType: 'Open Book', status: 'UNGRADED' },
]

const integrityFeed = [
    { dot: 'bg-red-500', title: 'Anomalous Activity', detail: 'Student #294105 window-switched 12 times during CS401 Final.', time: '2 mins ago' },
    { dot: 'bg-blue-500', title: 'Question Flagged', detail: 'Question 4 in MATH202 reported for broken image asset.', time: '15 mins ago' },
    { dot: 'bg-accent', title: 'AI Grading Complete', detail: 'Batch #A4-CS401 processed. 45 exams ready for review.', time: '1 hour ago' },
]

const gradeDistribution = [
    { grade: 'F', pct: 5 },
    { grade: 'D', pct: 10 },
    { grade: 'C', pct: 22 },
    { grade: 'B', pct: 45 },
    { grade: 'A', pct: 18 },
]
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-display font-semibold text-text">Teaching Dashboard</h1>
            <p class="text-text/60 mt-1">Operational overview for the Fall 2024 Examination Period.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <StatCard label="Active Courses" value="12" tag="Live" tag-variant="danger" subtext="Across 3 departments"
                icon-bg="bg-accent/10" icon-color="text-accent">
                <template #icon>
                    <FileText class="w-5 h-5 text-accent" />
                </template>
            </StatCard>
            <StatCard label="Students Flagged" value="08" tag="Urgent" tag-variant="danger"
                subtext="Plagiarism or identity checks" icon-bg="bg-red-50">
                <template #icon>
                    <Flag class="w-5 h-5 text-red-600" />
                </template>
            </StatCard>
            <StatCard label="Grading Progress" value="422 / 650" tag="65% Done" tag-variant="neutral" :progress="65"
                icon-bg="bg-emerald-50">
                <template #icon>
                    <CheckSquare class="w-5 h-5 text-emerald-600" />
                </template>
            </StatCard>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <GradingQueueCard :rows="gradingQueue" class="lg:col-span-2" />
            <IntegrityFeedCard :items="integrityFeed" />
        </div>

        <PerformanceDistributionCard :distribution="gradeDistribution" />
    </div>
</template>
