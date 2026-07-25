<script setup lang="ts">
import { ref } from 'vue'
import BaseCard from '@/shared/components/ui/BaseCard.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import { Flag } from 'lucide-vue-next'
import type { QuestionRow } from '../types'

defineProps<{ rows: QuestionRow[] }>()
const emit = defineEmits<{ flag: [{ questionId: number; comment: string }] }>()

const openCommentFor = ref<number | null>(null)
const comment = ref('')

function submitFlag(targetId: number | undefined, rowIndex: number) {
    const finalId = targetId ?? rowIndex
    if (!comment.value.trim()) return
    emit('flag', { questionId: finalId, comment: comment.value.trim() })
    openCommentFor.value = null
    comment.value = ''
}
</script>

<template>
    <BaseCard :padded="false">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-bg border-b border-border">
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-text/60">Question</th>
                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-text/60">Type</th>
                    <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-text/60">Action</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="row in rows" :key="row.row">
                    <tr class="border-b border-border last:border-0">
                        <td class="px-4 py-3 text-text max-w-md truncate">{{ row.data.text }}</td>
                        <td class="px-4 py-3 text-text/70 uppercase text-xs font-semibold">{{ row.data.type }}</td>
                        <td class="px-4 py-3 text-right">
                            <button
                                class="inline-flex items-center gap-1.5 text-sm text-text/60 hover:text-red-600 transition-colors"
                                @click="openCommentFor = openCommentFor === row.row ? null : row.row">
                                <Flag class="w-4 h-4" /> Flag
                            </button>
                        </td>
                    </tr>
                    <tr v-if="openCommentFor === row.row" class="bg-bg">
                        <td colspan="3" class="px-4 py-4">
                            <textarea v-model="comment" rows="2" placeholder="What's wrong with this question?"
                                class="w-full px-3 py-2 rounded-md border border-border bg-surface text-sm focus:outline-none focus:border-accent" />
                            <div class="flex justify-end gap-2 mt-2">
                                <BaseButton variant="secondary" @click="openCommentFor = null">Cancel</BaseButton>
                                <BaseButton @click="submitFlag(row.id, row.row)">Submit Flag</BaseButton>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </BaseCard>
</template>
