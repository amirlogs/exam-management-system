<script setup lang="ts">
defineProps<{
    title: string
    description: string
}>()
</script>

<template>
    <div class="text-center mb-10">
        <div class="w-16 h-16 mx-auto mb-5 rounded-xl bg-accent/10 flex items-center justify-center">
            <slot name="icon" />
        </div>
        <h1 class="text-3xl font-bold text-text">{{ title }}</h1>
        <p class="text-text/60 mt-2 max-w-md mx-auto">{{ description }}</p>
    </div>
</template>
