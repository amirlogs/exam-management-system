<script setup lang="ts">
import { ShieldX } from 'lucide-vue-next'
import BaseCard from '@/shared/components/ui/BaseCard.vue'

defineProps<{
  title?: string
  message?: string
}>()
</script>

<template>
  <BaseCard>
    <div class="flex flex-col items-center py-10 text-center">
      <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-error/10">
        <ShieldX class="h-6 w-6 text-error" />
      </div>

      <h3 class="text-sm font-semibold text-text">
        {{ title ?? 'Access restricted' }}
      </h3>

      <p class="mt-1 max-w-sm text-sm text-text/55">
        {{ message ?? "You don't have permission to perform this action." }}
      </p>
    </div>
  </BaseCard>
</template>
