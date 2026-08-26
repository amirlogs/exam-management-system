<script setup lang="ts">
import { computed } from 'vue'
import { Plus, Trash2 } from 'lucide-vue-next'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'

const props = defineProps<{ form: any; errors: Record<string, string> }>()
const emit = defineEmits<{ 'update:form': [any] }>()

const typeOptions = [
    { value: 'mcq', label: 'Multiple Choice (MCQ)' },
    { value: 'true_false', label: 'True / False' },
    { value: 'short_answer', label: 'Short Answer' },
    { value: 'essay', label: 'Essay' },
]
const difficultyOptions = [
    { value: 'easy', label: 'Easy' }, { value: 'medium', label: 'Medium' }, { value: 'hard', label: 'Hard' },
]
const isChoiceType = computed(() => props.form.type === 'mcq' || props.form.type === 'true_false')

function addOption() { props.form.options.push('') }
function removeOption(i: number) { props.form.options.splice(i, 1) }
function setTrueFalse() { props.form.options = ['True', 'False'] }
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/70">Type</label>
            <BaseSelect v-model="form.type" :options="typeOptions"
                @update:model-value="(v) => { if (v === 'true_false') setTrueFalse() }" />
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/70">Content *</label>
            <textarea v-model="form.content" rows="3"
                class="w-full px-3 py-2.5 rounded-lg border bg-bg text-sm focus:outline-none focus:border-accent"
                :class="errors.content ? 'border-red-400' : 'border-border'" />
            <p v-if="errors.content" class="text-xs text-red-600 mt-1">{{ errors.content }}</p>
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/70">Chapter</label>
            <input v-model="form.chapter" class="w-full px-3 py-2.5 rounded-lg border border-border bg-bg text-sm"
                placeholder="e.g. Propositional Logic" />
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/70">Difficulty</label>
            <BaseSelect v-model="form.difficulty" :options="difficultyOptions" />
            <p v-if="errors.difficulty" class="text-xs text-red-600 mt-1">{{ errors.difficulty }}</p>
        </div>

        <div v-if="isChoiceType" class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-xs font-bold uppercase tracking-wide text-text/70">Options & Correct Answer</label>
                <button v-if="form.type === 'mcq'" type="button"
                    class="inline-flex items-center gap-1 text-xs text-accent font-semibold" @click="addOption">
                    <Plus class="h-3.5 w-3.5" /> Add option
                </button>
            </div>
            <div v-for="(_, i) in form.options" :key="i" class="flex items-center gap-2">
                <input type="radio" :checked="form.correct_answer === form.options[i]" class="accent-accent"
                    @change="form.correct_answer = form.options[i]" />
                <input v-model="form.options[i]" :disabled="form.type === 'true_false'"
                    class="flex-1 px-3 py-1.5 rounded-md border border-border bg-bg text-sm disabled:opacity-60" />
                <button v-if="form.type === 'mcq' && form.options.length > 2" type="button"
                    class="text-text/40 hover:text-red-500" @click="removeOption(i)">
                    <Trash2 class="h-4 w-4" />
                </button>
            </div>
            <p v-if="errors.correct_answer" class="text-xs text-red-600">{{ errors.correct_answer }}</p>
        </div>
    </div>
</template>
