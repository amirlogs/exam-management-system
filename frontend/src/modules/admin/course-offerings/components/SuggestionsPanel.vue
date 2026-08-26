<script setup lang="ts">
import { computed } from 'vue'
import { Sparkles, Plus, X } from 'lucide-vue-next'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import type { OfferingSuggestion } from '../types/courseOffering'

const props = defineProps<{
    suggestions: OfferingSuggestion[]
    creatingKey: string | null
}>()

const emit = defineEmits<{
    create: [suggestion: OfferingSuggestion]
    close: []
}>()

const grouped = computed(() => {
    const map = new Map<number, {
        course_id: number
        course_code: string
        course_name: string
        targets: Array<{
            program_id: number
            program_name: string
            year_level: number
        }>
    }>()

    for (const suggestion of props.suggestions) {
        if (!map.has(suggestion.course_id)) {
            map.set(suggestion.course_id, {
                course_id: suggestion.course_id,
                course_code: suggestion.course_code,
                course_name: suggestion.course_name,
                targets: [],
            })
        }

        map.get(suggestion.course_id)!.targets.push({
            program_id: suggestion.program_id,
            program_name: suggestion.program_name,
            year_level: suggestion.year_level,
        })
    }

    return [...map.values()]
})

function key(courseId: number) {
    return String(courseId)
}
</script>

<template>
    <div class="rounded-md border border-accent/30 bg-accent/5 p-5">
        <div class="mb-4 flex items-start justify-between gap-4">
            <div class="flex items-start gap-2.5">
                <Sparkles class="mt-0.5 h-4 w-4 shrink-0 text-accent" />

                <div>
                    <h3 class="font-semibold text-text">
                        {{ grouped.length }} course offerings suggested
                    </h3>

                    <p class="mt-0.5 text-xs text-text/50">
                        Suggestions come from active curricula for the selected semester.
                    </p>
                </div>
            </div>

            <button type="button" class="rounded-md p-1 text-text/40 transition-colors hover:bg-text/5 hover:text-text"
                @click="emit('close')">
                <X class="h-4 w-4" />
            </button>
        </div>

        <div v-if="!grouped.length"
            class="rounded-md border border-dashed border-border py-8 text-center text-sm text-text/50">
            All curriculum-required courses already have offerings for this semester.
        </div>

        <div v-else class="space-y-3">
            <div v-for="course in grouped" :key="course.course_id" class="rounded-md border border-border bg-surface">
                <div class="flex items-start justify-between gap-4 p-4">
                    <div class="min-w-0">
                        <p class="font-mono text-xs text-text/45">
                            {{ course.course_code }}
                        </p>

                        <p class="mt-0.5 text-sm font-semibold text-text">
                            {{ course.course_name }}
                        </p>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <span v-for="target in course.targets" :key="`${target.program_id}-${target.year_level}`"
                                class="rounded-full border border-border bg-bg px-2.5 py-1 text-[11px] text-text/65">
                                {{ target.program_name }} · Year {{ target.year_level }}
                            </span>
                        </div>
                    </div>

                    <BaseButton v-can="'course_offering.create'" variant="secondary"
                        :loading="creatingKey === key(course.course_id)" @click="emit('create', {
                            course_id: course.course_id,
                            course_code: course.course_code,
                            course_name: course.course_name,
                            program_id: course.targets[0]?.program_id ?? 0,
                            program_name: course.targets[0]?.program_name ?? '',
                            year_level: course.targets[0]?.year_level ?? 0,
                        })">
                        <template #icon>
                            <Plus class="h-4 w-4" />
                        </template>

                        Create offering
                    </BaseButton>
                </div>
            </div>
        </div>
    </div>
</template>
