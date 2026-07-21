<script setup lang="ts">
import { ref, computed } from 'vue'
import { Eye, EyeOff } from 'lucide-vue-next'

const props = defineProps<{
    modelValue: string
    label: string
    type?: string
    placeholder?: string
    error?: string
}>()
defineEmits(['update:modelValue'])

const showPassword = ref(false)
const isPasswordField = computed(() => props.type === 'password')

const inputType = computed(() => {
    if (!isPasswordField.value) return props.type || 'text'
    return showPassword.value ? 'text' : 'password'
})
</script>

<template>
    <div>
        <label class="block text-xs font-bold tracking-wide uppercase text-text/70 mb-2">
            {{ label }}
        </label>
        <div class="relative">
            <input :type="inputType" :value="modelValue" :placeholder="placeholder"
                @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)" class="w-full px-4 py-3 rounded-md border bg-bg text-text placeholder:text-text/40
               focus:outline-none focus:border-accent transition-colors"
                :class="[error ? 'border-red-400' : 'border-border', isPasswordField ? 'pr-11' : '']" />

            <button v-if="isPasswordField" type="button" tabindex="-1" @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-text/40 hover:text-text/70 transition-colors">
                <EyeOff v-if="showPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>
