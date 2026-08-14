<script setup lang="ts">
import { CheckCircle2, XCircle, Info, X } from 'lucide-vue-next'
import { useUiStore } from '@/stores/ui'

const uiStore = useUiStore()

const iconFor = { success: CheckCircle2, error: XCircle, info: Info }
const styleFor = {
    success: 'border-success/30 bg-success/10 text-success',
    error: 'border-error/30 bg-error/10 text-error',
    info: 'border-accent/30 bg-accent/10 text-accent',
}
</script>

<template>
    <div class="fixed top-6 right-6 z-50 space-y-2 w-80">
        <transition-group name="toast">
            <div v-for="toast in uiStore.toasts" :key="toast.id"
                :class="['flex items-start gap-2 rounded-md border p-3 shadow-lg bg-surface', styleFor[toast.variant] || styleFor.info]">
                <component :is="iconFor[toast.variant] || iconFor.info" class="h-4 w-4 shrink-0 mt-0.5" />
                <p class="flex-1 text-sm text-text">{{ toast.message }}</p>
                <button @click="uiStore.removeToast(toast.id)" class="text-text/40 hover:text-text">
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>
        </transition-group>
    </div>
</template>

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
