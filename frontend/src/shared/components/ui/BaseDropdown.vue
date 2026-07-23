<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { ChevronDown } from 'lucide-vue-next'

const props = defineProps<{
    modelValue: string
    options: { value: string; label: string }[]
    placeholder?: string
}>()
const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>()

const isOpen = ref(false)
const el = ref<HTMLElement | null>(null)

function select(value: string) {
    emit('update:modelValue', value)
    isOpen.value = false
}

function handleClickOutside(e: MouseEvent) {
    if (el.value && !el.value.contains(e.target as Node)) isOpen.value = false
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

const selectedLabel = () => props.options.find(o => o.value === props.modelValue)?.label ?? props.placeholder
</script>

<template>
    <div ref="el" class="relative">
        <button type="button" @click="isOpen = !isOpen"
            class="flex items-center gap-2 px-4 py-2 rounded-md border border-border bg-surface text-sm font-medium text-text hover:border-accent transition-colors">
            {{ selectedLabel() }}
            <ChevronDown class="w-4 h-4 text-text/50" :class="isOpen ? 'rotate-180' : ''" />
        </button>

        <div v-if="isOpen"
            class="absolute right-0 mt-1 w-48 bg-surface border border-border rounded-lg shadow-lg py-1 z-10">
            <button v-for="opt in options" :key="opt.value" @click="select(opt.value)"
                class="w-full flex items-center px-3 py-2 text-sm text-left transition-colors"
                :class="opt.value === modelValue ? 'text-accent font-semibold bg-accent/5' : 'text-text hover:bg-bg'">
                {{ opt.label }}
            </button>
        </div>
    </div>
</template>
