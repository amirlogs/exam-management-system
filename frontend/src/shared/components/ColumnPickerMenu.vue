<script setup lang="ts">
defineProps<{ columns: { key: string; label: string }[]; visible: Set<string> }>();
const emit = defineEmits<{ toggle: [string] }>();
</script>
<template>
  <div class="w-56 bg-surface border border-border rounded-lg shadow-lg py-2 px-1">
    <p class="px-3 py-1.5 text-xs font-bold uppercase text-text/40">Show Column</p>
    <label v-for="col in columns" :key="col.key" class="flex items-center gap-2.5 px-3 py-2 text-sm text-text hover:bg-bg rounded-md cursor-pointer">
      <input type="checkbox" :checked="visible.has(col.key)" class="accent-accent h-4 w-4" @change="emit('toggle', col.key)" />
      {{ col.label }}
    </label>
  </div>
</template>
