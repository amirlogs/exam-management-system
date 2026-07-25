<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Flag, CheckCircle2, ArrowLeft } from 'lucide-vue-next'
import * as api from './question-bank/api'
import BaseCard from '@/shared/components/ui/BaseCard.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'
import type { QuestionFlag } from './question-bank/types'

const route = useRoute()
const router = useRouter()
const questionId = Number(route.params.questionId)

const flags = ref<QuestionFlag[]>([])
const isLoading = ref(true)
const loadError = ref<string | null>(null)
const newComment = ref('')
const isSubmitting = ref(false)
const resolvingId = ref<number | null>(null)

async function load() {
    isLoading.value = true
    loadError.value = null
    try {
        flags.value = await api.getQuestionFlags(questionId)
    } catch (err: any) {
        loadError.value = err?.response?.data?.message || 'Could not load flags.'
    } finally {
        isLoading.value = false
    }
}

async function submitFlag() {
    if (!newComment.value.trim()) return
    isSubmitting.value = true
    try {
        await api.flagQuestion(questionId, newComment.value.trim())
        newComment.value = ''
        await load()
    } finally {
        isSubmitting.value = false
    }
}

async function resolve(flag: QuestionFlag) {
    resolvingId.value = flag.id
    try {
        const updated = await api.resolveFlag(flag.id)
        const idx = flags.value.findIndex(f => f.id === flag.id)
        if (idx !== -1) flags.value[idx] = updated
    } finally {
        resolvingId.value = null
    }
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
            <h1 class="font-display text-2xl text-accent">Question Flags</h1>
            <p class="text-sm text-text/60 mt-1">Question #{{ questionId }}</p>
        </div>

        <BaseCard>
            <label class="text-xs font-bold uppercase tracking-wide text-text/50 mb-2 block">Flag this question</label>
            <textarea v-model="newComment" rows="2" placeholder="What's wrong with this question?"
                class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm focus:outline-none focus:border-accent mb-3" />
            <div class="flex justify-end">
                <BaseButton :disabled="isSubmitting || !newComment.trim()" @click="submitFlag">
                    <template #icon>
                        <Flag class="w-4 h-4" />
                    </template>
                    {{ isSubmitting ? 'Submitting…' : 'Submit Flag' }}
                </BaseButton>
            </div>
        </BaseCard>

        <div v-if="isLoading" class="bg-surface border border-border rounded-lg p-12 text-center text-text/50">Loading…
        </div>
        <div v-else-if="loadError" class="bg-surface border border-border rounded-lg p-12 text-center text-red-600">{{
            loadError
            }}</div>

        <BaseCard v-else-if="flags.length > 0" :padded="false">
            <div v-for="flag in flags" :key="flag.id"
                class="p-5 border-b border-border last:border-0 flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <Flag class="w-4 h-4 text-red-500 mt-1 shrink-0" />
                    <div>
                        <p class="text-sm text-text">{{ flag.comment }}</p>
                        <p class="text-xs text-text/40 mt-1">{{ new Date(flag.created_at).toLocaleString() }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <BaseBadge :variant="flag.status === 'open' ? 'danger' : 'success'">{{ flag.status }}</BaseBadge>
                    <BaseButton v-if="flag.status === 'open'" variant="secondary" :disabled="resolvingId === flag.id"
                        @click="resolve(flag)">
                        <template #icon>
                            <CheckCircle2 class="w-4 h-4" />
                        </template>
                        {{ resolvingId === flag.id ? 'Resolving…' : 'Resolve' }}
                    </BaseButton>
                </div>
            </div>
        </BaseCard>

        <div v-else class="bg-surface border border-border rounded-lg p-12 text-center text-text/50">No flags yet on
            this
            question.</div>
    </div>
</template>
