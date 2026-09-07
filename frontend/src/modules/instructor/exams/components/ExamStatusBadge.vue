<script setup lang="ts">
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import type { ExamStatus } from '../types/exam';

const props = defineProps<{
  status: ExamStatus;
}>();

function formatStatus(status: string) {
  if (!status) return '—';
  return status
    .split('_')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

function getVariant(status: ExamStatus): 'neutral' | 'info' | 'danger' | 'warning' | 'success' | 'dark' {
  switch (status) {
    case 'approved':
    case 'active':
    case 'completed':
      return 'success';
    case 'scheduled':
      return 'info';
    case 'pending_approval':
      return 'warning';
    case 'rejected':
    case 'cancelled':
      return 'danger';
    case 'draft':
    default:
      return 'neutral';
  }
}
</script>

<template>
  <BaseBadge :variant="getVariant(props.status)">
    {{ formatStatus(props.status) }}
  </BaseBadge>
</template>
