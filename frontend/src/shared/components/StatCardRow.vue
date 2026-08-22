<script setup lang="ts">
defineProps<{ cards: { label: string; value: number | string; icon: any; tone?: 'default' | 'success' | 'warning' | 'danger' }[] }>()
</script>
<template>
  <div class="grid gap-4" :style="{ gridTemplateColumns: `repeat(${cards.length}, minmax(0, 1fr))` }">
    <div v-for="c in cards" :key="c.label" class="bg-surface border border-border rounded-xl p-5">
      <div class="flex items-center justify-between mb-2">
        <p class="text-3xl font-bold text-text">{{ c.value }}</p>
        <div class="w-9 h-9 rounded-lg flex items-center justify-center"
          :class="{
            'bg-accent/10 text-accent': !c.tone || c.tone === 'default',
            'bg-emerald-50 text-emerald-600': c.tone === 'success',
            'bg-amber-50 text-amber-600': c.tone === 'warning',
            'bg-red-50 text-red-600': c.tone === 'danger',
          }">
          <component :is="c.icon" class="w-4 h-4" />
        </div>
      </div>
      <p class="text-xs font-semibold text-text/50 uppercase tracking-wide">{{ c.label }}</p>
    </div>
  </div>
</template>
