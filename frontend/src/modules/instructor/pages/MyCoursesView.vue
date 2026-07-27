<script setup lang="ts">
import { useRouter } from 'vue-router'
import { BookOpen, ChevronRight } from 'lucide-vue-next'
import { SEEDED_COURSES } from '../data/coursesSeed'

const router = useRouter()

function openCourse(courseId: number) {
    router.push(`/instructor/courses/${courseId}`)
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="font-display text-2xl text-accent">My Courses</h1>
            <p class="text-sm text-text/60 mt-1">Courses you teach this term.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div v-for="course in SEEDED_COURSES" :key="course.id"
                class="bg-surface border border-border rounded-lg p-6 cursor-pointer hover:border-accent transition-colors"
                @click="openCourse(course.id)">
                <div class="flex items-start justify-between">
                    <div class="w-11 h-11 rounded-lg bg-accent/10 flex items-center justify-center mb-4">
                        <BookOpen class="w-5 h-5 text-accent" />
                    </div>
                    <ChevronRight class="w-4 h-4 text-text/30" />
                </div>
                <h3 class="font-semibold text-text">{{ course.name }}</h3>
                <p class="text-sm text-text/50 mt-0.5">{{ course.code }} · {{ course.department }}</p>
            </div>
        </div>
    </div>
</template>
