<script setup lang="ts">
import { ArrowLeft } from 'lucide-vue-next';
import { useRouter } from 'vue-router';
defineProps<{ fallback?: string }>();
const router = useRouter();
function goBack(fallback?: string) {
  if (window.history.state?.back) router.back();
  else if (fallback) router.push(fallback);
}
</script>
<template>
  <button @click="goBack(fallback)" class="inline-flex items-center gap-1.5 text-sm text-text/60 hover:text-accent transition-colors mb-2">
    <ArrowLeft class="w-4 h-4" /> Back
  </button>
</template>
