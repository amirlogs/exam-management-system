<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { HelpCircle, FileText } from 'lucide-vue-next';
import * as api from '../api/courseOfferings';
import type { CourseOffering } from '../types/courseOffering';

const route = useRoute();
const router = useRouter();
const courseOfferingId = computed(() => Number(route.params.courseOfferingId));

const offering = ref<CourseOffering | null>(null);
const loading = ref(true);

onMounted(async () => {
  loading.value = true;
  offering.value = await api.getCourseOffering(courseOfferingId.value);
  loading.value = false;
});

const sections = computed(() => [
  {
    label: 'Question Bank',
    desc: "Create, import, and manage this course's questions.",
    icon: HelpCircle,
    to: `/instructor/courses/${courseOfferingId.value}/questions`,
  },
  {
    label: 'Exams',
    desc: 'Build, submit, and manage exams for this course offering.',
    icon: FileText,
    to: `/instructor/course-offerings/${courseOfferingId.value}/exams`,
  },
]);
</script>

<template>
  <div v-if="loading" class="p-8 text-center text-text/50">Loading…</div>
  <div v-else class="mx-auto w-full max-w-360 space-y-6 px-6 py-8 min-h-[calc(100vh-68px)]">
    <div>
      <h1 class="text-2xl font-bold font-display text-text">{{ offering?.course?.name || '—' }}</h1>
      <p class="text-sm text-text/60 mt-1">{{ offering?.course?.code || '—' }} · {{ offering?.semester?.name || '—' }}</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <router-link v-for="s in sections" :key="s.label" :to="s.to" class="bg-surface border border-border rounded-xl p-6 hover:border-accent hover:shadow-sm transition-all">
        <div class="w-11 h-11 rounded-lg bg-accent/10 flex items-center justify-center mb-4">
          <component :is="s.icon" class="w-5 h-5 text-accent" />
        </div>
        <h3 class="font-semibold text-text">{{ s.label }}</h3>
        <p class="text-sm text-text/50 mt-0.5">{{ s.desc }}</p>
      </router-link>
    </div>
  </div>
</template>
