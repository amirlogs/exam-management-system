<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { BookOpen } from 'lucide-vue-next';
import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import * as api from '../course-offerings/api/courseOfferings';
import { useUiStore } from '@/stores/ui';
import type { CourseOffering } from '../course-offerings/types/courseOffering';

const router = useRouter();
const uiStore = useUiStore();

const offerings = ref<CourseOffering[]>([]);
const loading = ref(false);
const refreshing = ref(false);
const search = ref('');

const statusVariant: Record<string, any> = {
  approved: 'success',
  pending_approval: 'warning',
  draft: 'neutral',
  rejected: 'danger',
  cancelled: 'danger',
};

async function load() {
  loading.value = true;
  try {
    const res = await api.getCourseOfferings(1, search.value ? { search: search.value } : {});
    offerings.value = res.data;
  } catch (err: any) {
    uiStore.showToast(err?.response?.data?.message || 'Failed to load your courses.', 'error');
  } finally {
    loading.value = false;
  }
}
async function handleRefresh() {
  refreshing.value = true;
  await load();
  refreshing.value = false;
}
watch(search, load);
onMounted(load);

function openCourse(offering: CourseOffering) {
  router.push(`/instructor/course-offerings/${offering.id}`);
}
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-8 min-h-[calc(100vh-68px)]">
    <ResourceToolbar
      title="My Courses"
      description="Course offerings you're assigned to teach."
      search-placeholder="Search courses…"
      show-search
      show-refresh
      :refreshing="refreshing"
      @update:search="(v) => (search = v)"
      @refresh="handleRefresh" />

    <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="h-32 rounded-xl bg-bg animate-pulse" />
    </div>

    <div v-else-if="offerings.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="offering in offerings"
        :key="offering.id"
        class="bg-surface border border-border rounded-xl p-6 cursor-pointer hover:border-accent hover:shadow-sm transition-all"
        @click="openCourse(offering)">
        <div class="flex items-start justify-between mb-4">
          <div class="w-11 h-11 rounded-lg bg-accent/10 flex items-center justify-center">
            <BookOpen class="w-5 h-5 text-accent" />
          </div>
          <BaseBadge :variant="statusVariant[offering.status] || 'neutral'" class="text-[10px]">{{ offering.status.replace('_', ' ') }}</BaseBadge>
        </div>
        <h3 class="font-semibold text-text">{{ offering.course?.name || '—' }}</h3>
        <p class="text-sm text-text/50 mt-0.5">{{ offering.course?.code || '—' }} · {{ offering.semester?.name || '—' }}</p>
      </div>
    </div>

    <div v-else class="bg-surface border border-border rounded-xl p-16 text-center text-sm text-text/50">No course offerings assigned yet.</div>
  </div>
</template>
