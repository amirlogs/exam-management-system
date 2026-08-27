<template>
  <div class="min-h-[80vh] flex items-center justify-center">
    <div class="w-full max-w-md bg-surface border border-border rounded-xl p-10 text-center">
      <div class="w-16 h-16 mx-auto mb-6 rounded-xl bg-bg flex items-center justify-center">
        <SearchX class="w-7 h-7 text-text/50" />
      </div>

      <h1 class="text-2xl font-bold text-text mb-3">Page Not Found</h1>

      <p class="text-text/60 text-sm leading-relaxed mb-6">
        The requested resource could not be located in the EduExam system. It may have been moved, deleted, or you may not have the necessary permissions to view it.
      </p>

      <div class="flex gap-3">
        <BaseButton class="flex-1" @click="$router.push('/admin/dashboard')"> Return to Dashboard </BaseButton>
        <BaseButton variant="secondary" class="flex-1" @click="$router.push('/support')"> Report a Broken Link </BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { SearchX } from 'lucide-vue-next';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
</script>
