<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { ChevronDown, Search } from 'lucide-vue-next'

const props = defineProps<{
    modelValue: string
    options: { value: string; label: string }[]
    placeholder?: string
    label?: string
    error?: string
}>()
const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>()

const isOpen = ref(false)
const query = ref('')
const triggerEl = ref<HTMLElement | null>(null)
const menuStyle = ref<{ top?: string; bottom?: string; left: string; width: string }>({ left: '0px', width: '0px' })

const filteredOptions = computed(() =>
    query.value ? props.options.filter((o) => o.label.toLowerCase().includes(query.value.toLowerCase())) : props.options,
)

function toggleOpen() {
    if (isOpen.value) { isOpen.value = false; return }
    if (triggerEl.value) {
        const rect = triggerEl.value.getBoundingClientRect()
        const openUpward = window.innerHeight - rect.bottom < 260 && rect.top > 260
        menuStyle.value = openUpward
            ? { bottom: `${window.innerHeight - rect.top + 4}px`, left: `${rect.left}px`, width: `${rect.width}px` }
            : { top: `${rect.bottom + 4}px`, left: `${rect.left}px`, width: `${rect.width}px` }
    }
    query.value = ''
    isOpen.value = true
}
function select(value: string) {
    emit('update:modelValue', value)
    isOpen.value = false
}
function handleClickOutside(e: MouseEvent) {
    const t = e.target as HTMLElement
    if (!t.closest('[data-combobox-trigger]') && !t.closest('[data-combobox-menu]')) isOpen.value = false
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

const selectedLabel = () => props.options.find((o) => o.value === props.modelValue)?.label ?? props.placeholder
</script>

<template>
    <div>
        <label v-if="label" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/70">{{ label
            }}</label>
        <button ref="triggerEl" type="button" data-combobox-trigger @click.stop="toggleOpen"
            class="flex w-full items-center justify-between gap-2 rounded-md border bg-surface px-4 py-2 text-sm font-medium text-text transition-colors"
            :class="error ? 'border-error' : 'border-border hover:border-accent'">
            <span class="truncate">{{ selectedLabel() }}</span>
            <ChevronDown class="h-4 w-4 shrink-0 text-text/50" :class="isOpen ? 'rotate-180' : ''" />
        </button>
        <p v-if="error" class="mt-1.5 text-xs text-error">{{ error }}</p>

        <Teleport to="body">
            <div v-if="isOpen" data-combobox-menu
                class="fixed z-[100] flex max-h-72 flex-col rounded-lg border border-border bg-surface shadow-lg"
                :style="menuStyle" @click.stop>
                <div class="flex items-center gap-2 border-b border-border px-3 py-2">
                    <Search class="h-4 w-4 shrink-0 text-text/40" />
                    <input v-model="query" type="text" placeholder="Search..." autofocus
                        class="w-full bg-transparent text-sm text-text outline-none placeholder:text-text/40" />
                </div>
                <div class="overflow-y-auto py-1">
                    <button v-for="opt in filteredOptions" :key="opt.value" type="button" @click="select(opt.value)"
                        class="flex w-full items-center px-3 py-2 text-left text-sm transition-colors"
                        :class="opt.value === modelValue ? 'bg-accent/5 font-semibold text-accent' : 'text-text hover:bg-bg'">
                        {{ opt.label }}
                    </button>
                    <p v-if="!filteredOptions.length" class="px-3 py-4 text-center text-xs text-text/40">No matches</p>
                </div>
            </div>
        </Teleport>
    </div>
</template>
