<script setup lang="ts">
import { useUiStore } from '@/stores/ui'
const uiStore = useUiStore()
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.25s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateY(10px);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(20px);
}
</style>
<template>
    <div class="fixed bottom-6 right-6 z-50 space-y-2">
        <transition-group name="toast">
            <div v-for="toast in uiStore.toasts" :key="toast.id"
                class="px-4 py-3 rounded-md shadow-lg text-sm font-medium text-white min-w-[240px]" :class="{
                    'bg-emerald-600': toast.variant === 'success',
                    'bg-red-600': toast.variant === 'error',
                    'bg-gray-800': toast.variant === 'info',
                }">
                {{ toast.message }}
            </div>
        </transition-group>
    </div>
</template>
