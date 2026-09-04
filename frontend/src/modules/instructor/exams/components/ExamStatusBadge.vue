<script setup lang="ts">
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';

import type { ExamStatus } from '../types/exam';

defineProps<{
  status: ExamStatus;
}>();
</script>

<template>
  <BaseBadge
    :variant="
      status === 'approved'
        ? 'success'
        : status === 'active'
          ? 'success'
          : status === 'scheduled'
            ? 'info'
            : status === 'pending_approval'
              ? 'warning'
              : status === 'rejected'
                ? 'danger'
                : status === 'cancelled'
                  ? 'danger'
                  : status === 'completed'
                    ? 'success'
                    : 'default'
    ">
    {{ status.replaceAll('_', ' ') }}
  </BaseBadge>
</template>
