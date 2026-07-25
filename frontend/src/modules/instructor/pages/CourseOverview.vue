<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { HelpCircle, ClipboardCheck, Users, BarChart3 } from 'lucide-vue-next'
import { SEEDED_COURSES } from '../data/coursesSeed'
import BackButton from '@/shared/components/ui/BackButton.vue'

const route = useRoute()
const courseId = computed(() => Number(route.params.courseId))
const course = computed(() => SEEDED_COURSES.find(c => c.id === courseId.value))

const sections = [
    { label: 'Question Bank', desc: 'Import and manage questions for this course.', icon: HelpCircle, to: `question-bank`, enabled: true },
    { label: 'Grading Queue', desc: 'Review and grade student submissions.', icon: ClipboardCheck, to: `grading`, enabled: false },
    { label: 'Students', desc: 'View enrolled students and sections.', icon: Users, to: `students`, enabled: false },
    { label: 'Analytics', desc: 'Performance breakdowns and question metrics.', icon: BarChart3, to: `analytics`, enabled: false },
]
</script>

<template>
    <back-button fallback="/instructor/courses" />
    <div class="space-y-6">
        <div>
            <h1 class="font-display text-2xl text-accent">{{ course?.name }}</h1>
            <p class="text-sm text-text/60 mt-1">{{ course?.code }} · {{ course?.department }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <component :is="section.enabled ? 'router-link' : 'div'" v-for="section in sections" :key="section.label"
                :to="section.enabled ? `/instructor/courses/${courseId}/${section.to}` : undefined"
                class="bg-surface border border-border rounded-lg p-6 transition-colors"
                :class="section.enabled ? 'hover:border-accent cursor-pointer' : 'opacity-50 cursor-not-allowed'">
                <div class="w-11 h-11 rounded-lg bg-accent/10 flex items-center justify-center mb-4">
                    <component :is="section.icon" class="w-5 h-5 text-accent" />
                </div>
                <h3 class="font-semibold text-text">{{ section.label }}</h3>
                <p class="text-sm text-text/50 mt-0.5">{{ section.desc }}</p>
                <span v-if="!section.enabled" class="text-xs font-semibold text-text/40 mt-2 inline-block">Coming
                    soon</span>
            </component>
        </div>
    </div>
</template>
