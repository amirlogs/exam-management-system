<script setup lang="ts">
import { computed } from 'vue';
import { School, User, AlertCircle } from 'lucide-vue-next';
import type { CourseOffering } from '../types/courseOffering';

const props = defineProps<{ offering: CourseOffering }>();
defineEmits<{ click: [] }>();

const sectionCount = computed(() => props.offering.sections?.length ?? 0);
const instructorCount = computed(() => props.offering.instructors?.length ?? 0);
const isReady = computed(() => sectionCount.value > 0 && instructorCount.value > 0);
</script>

<template>
  <div
    class="cursor-pointer rounded-md border bg-surface p-4 transition-colors hover:border-accent"
    :class="[offering.status === 'cancelled' ? 'opacity-50 border-border' : 'border-border', offering.status === 'rejected' ? 'border-l-4 border-l-error' : '']"
    @click="$emit('click')">
    <div class="mb-2 flex items-start justify-between gap-2">
      <div>
        <span class="font-mono text-xs text-text/50">{{ offering.course?.code }}</span>
        <h4 class="font-display text-base leading-tight text-text">{{ offering.course?.name }}</h4>
      </div>
      <AlertCircle v-if="offering.status === 'draft' && !isReady" class="h-4 w-4 shrink-0 text-warning" />
    </div>

    <div v-if="offering.status !== 'cancelled'" class="flex items-center gap-4 border-y border-border/50 py-2 text-xs">
      <div class="flex items-center gap-1.5" :class="sectionCount > 0 ? 'text-success' : 'text-text/30'">
        <School class="h-3.5 w-3.5" />
        <span class="font-mono">{{ sectionCount }} section{{ sectionCount === 1 ? '' : 's' }}</span>
      </div>
      <div class="flex items-center gap-1.5" :class="instructorCount > 0 ? 'text-success' : 'text-text/30'">
        <User class="h-3.5 w-3.5" />
        <span class="font-mono">{{ instructorCount }} instructor{{ instructorCount === 1 ? '' : 's' }}</span>
      </div>
    </div>

    <p v-if="offering.status === 'rejected' && offering.rejection_reason" class="mt-2 rounded-md bg-error/10 p-2 text-xs text-error">
      {{ offering.rejection_reason }}
    </p>
  </div>
</template>
