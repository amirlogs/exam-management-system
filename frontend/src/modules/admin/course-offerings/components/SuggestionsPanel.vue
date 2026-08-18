<script setup lang="ts">
import { Sparkles, Plus, X } from 'lucide-vue-next'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import type { OfferingSuggestion } from '../types/courseOffering'

defineProps<{ suggestions: OfferingSuggestion[]; creatingKey: string | null }>()
const emit = defineEmits<{ create: [OfferingSuggestion]; close: [] }>()

function key(s: OfferingSuggestion) {
    return `${s.course_id}-${s.program_id}`
}
</script>

<template>
    <div class="rounded-md border border-accent/30 bg-accent/5 p-5">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <Sparkles class="h-4 w-4 text-accent" />
                <h3 class="font-semibold text-text">{{ suggestions.length }} missing course offerings found</h3>
            </div>
            <button type="button" class="text-text/40 hover:text-text" @click="emit('close')">
                <X class="h-4 w-4" />
            </button>
        </div>

        <div v-if="!suggestions.length" class="py-6 text-center text-sm text-text/55">
            All active-curriculum courses for this semester already have offerings.
        </div>

        <div v-else class="space-y-2">
            <div v-for="s in suggestions" :key="key(s)"
                class="flex items-center justify-between gap-4 rounded-md border border-border bg-surface px-4 py-2.5">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-text">{{ s.course_code }} — {{ s.course_name }}</p>
                    <p class="text-xs text-text/50">{{ s.program_name }} · Year {{ s.year_level }}</p>
                </div>
                <BaseButton variant="secondary" :loading="creatingKey === key(s)" @click="emit('create', s)">
                    <template #icon>
                        <Plus class="h-4 w-4" />
                    </template>Create
                </BaseButton>
            </div>
        </div>
    </div>
</template>
