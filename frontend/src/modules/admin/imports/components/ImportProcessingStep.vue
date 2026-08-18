<script setup lang="ts">
defineProps<{ totalRows: number }>()
</script>

<template>
    <div class="flex flex-col items-center rounded-md border border-border bg-surface p-12 text-center">
        <div class="mb-6 h-16 w-16 animate-spin rounded-full border-4 border-border border-t-accent" />
        <h3 class="mb-1 font-display text-xl text-text">Processing your file...</h3>
        <p class="max-w-xs text-sm text-text/60">Validating rows against institutional rules. This page updates
            automatically.</p>
        <p v-if="totalRows > 0" class="mt-4 font-mono text-xs text-text/50">{{ totalRows }} rows detected</p>
    </div>
</template>
