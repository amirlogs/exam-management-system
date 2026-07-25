<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Flag, ArrowLeft } from 'lucide-vue-next'
import * as api from './question-bank/api'
import BaseCard from '@/shared/components/ui/BaseCard.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'

const route = useRoute()
const router = useRouter()
const importId = Number(route.params.importId)

const allQuestions = ref<any[]>([])
const isLoading = ref(true)
const loadError = ref<string | null>(null)

const confirmedQuestions = computed(() => allQuestions.value.filter(q => q.status === 'confirmed'))

function parsedOptions(options: unknown): string[] {
    if (Array.isArray(options)) return options.map(String)
    if (typeof options === 'string') {
        try {
            const parsed = JSON.parse(options)
            if (Array.isArray(parsed)) return parsed.map(String)
        } catch { }
    }
    return []
}

async function load() {
    isLoading.value = true
    loadError.value = null
    try {
        allQuestions.value = await api.getImportQuestions(importId)
    } catch (err: any) {
        loadError.value = err?.response?.data?.message || 'Could not load questions.'
    } finally {
        isLoading.value = false
    }
}

function openQuestion(questionId: number) {
    router.push(`/instructor/questions/${questionId}/flags`)
}

onMounted(load)
</script>

<template>
    <div class="space-y-6">
        <button @click="router.back()"
            class="inline-flex items-center gap-1.5 text-sm text-text/60 hover:text-accent transition-colors">
            <ArrowLeft class="w-4 h-4" /> Back
        </button>

        <div>
            <h1 class="font-display text-2xl text-accent">Confirmed Questions</h1>
            <p class="text-sm text-text/60 mt-1">Import #{{ importId }} — click a question to view or add flags.</p>
        </div>

        <div v-if="isLoading" class="bg-surface border border-border rounded-lg p-12 text-center text-text/50">Loading…
        </div>
        <div v-else-if="loadError" class="bg-surface border border-border rounded-lg p-12 text-center text-red-600">{{
            loadError }}</div>
        <div v-else-if="confirmedQuestions.length === 0"
            class="bg-surface border border-border rounded-lg p-12 text-center text-text/50">
            No confirmed questions found for this import.
        </div>

        <BaseCard v-else :padded="false">
            <div v-for="q in confirmedQuestions" :key="q.id"
                class="p-5 border-b border-border last:border-0 flex items-center justify-between gap-4 cursor-pointer hover:bg-bg/40 transition-colors"
                @click="openQuestion(q.id)">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-text truncate">{{ q.text }}</p>
                    <p class="text-xs text-text/40 mt-1">
                        {{ q.difficulty }} · {{ q.points }} pts
                        <span v-if="parsedOptions(q.options).length">· {{ parsedOptions(q.options).join(', ') }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <BaseBadge variant="neutral" class="uppercase text-[10px]">{{ q.type }}</BaseBadge>
                    <Flag class="w-4 h-4 text-text/30" />
                </div>
            </div>
        </BaseCard>
    </div>
</template>
