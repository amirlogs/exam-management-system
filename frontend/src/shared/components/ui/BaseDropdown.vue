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
const triggerEl = ref<HTMLElement | null>(null)

const menuStyle = ref<{ top?: string; bottom?: string; left: string; width: string }>({
    left: '0px',
    width: '0px',
})

function toggleOpen() {
    if (isOpen.value) {
        isOpen.value = false
        return
    }
    if (triggerEl.value) {
        const rect = triggerEl.value.getBoundingClientRect()
        const spaceBelow = window.innerHeight - rect.bottom
        const openUpward = spaceBelow < 220 && rect.top > 220

        menuStyle.value = openUpward
            ? { bottom: `${window.innerHeight - rect.top + 4}px`, left: `${rect.left}px`, width: `${rect.width}px` }
            : { top: `${rect.bottom + 4}px`, left: `${rect.left}px`, width: `${rect.width}px` }
    }
    isOpen.value = true
}

function select(value: string) {
    emit('update:modelValue', value)
    isOpen.value = false
}

function handleClickOutside(e: MouseEvent) {
    const target = e.target as HTMLElement
    if (!target.closest('[data-dropdown-trigger]') && !target.closest('[data-dropdown-menu]')) {
        isOpen.value = false
    }
}
function handleScroll() {
    isOpen.value = false
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    window.addEventListener('scroll', handleScroll, true)
})
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('scroll', handleScroll, true)
})

const selectedLabel = () => props.options.find((o) => o.value === props.modelValue)?.label ?? props.placeholder
</script>

<template>
    <div class="relative">
        <button ref="triggerEl" type="button" data-dropdown-trigger @click.stop="toggleOpen"
            class="flex w-full items-center justify-between gap-2 px-4 py-2 rounded-md border border-border bg-surface text-sm font-medium text-text hover:border-accent transition-colors">
            {{ selectedLabel() }}
            <ChevronDown class="w-4 h-4 text-text/50 shrink-0" :class="isOpen ? 'rotate-180' : ''" />
        </button>

        <Teleport to="body">
            <div v-if="isOpen" data-dropdown-menu
                class="fixed z-[100] max-h-60 overflow-y-auto rounded-lg border border-border bg-surface py-1 shadow-lg"
                :style="menuStyle" @click.stop>
                <button v-for="opt in options" :key="opt.value" type="button" @click="select(opt.value)"
                    class="w-full flex items-center px-3 py-2 text-sm text-left transition-colors"
                    :class="opt.value === modelValue ? 'text-accent font-semibold bg-accent/5' : 'text-text hover:bg-bg'">
                    {{ opt.label }}
                </button>
            </div>
        </Teleport>
    </div>
</template>
