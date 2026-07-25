<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { usePermissionsStore } from '@/stores/permission'
import { computed } from 'vue';

const props = defineProps<{ openFlagCount: number }>()
const route = useRoute()
const router = useRouter()
const permissions = usePermissionsStore()

const tabs = computed(() => [
    { key: 'questions', label: 'Questions' },
    { key: 'flags', label: `Flags${props.openFlagCount ? ` (${props.openFlagCount})` : ''}` },
    ...(permissions.can('approve_question_bank_import') ? [{ key: 'approve', label: 'Approve' }] : []),
])

const activeTab = computed(() => (route.query.tab as string) || 'questions')

function setTab(key: string) {
    router.replace({ query: { ...route.query, tab: key } })
}
</script>

<template>
    <div class="flex gap-1 border-b border-border">
        <button v-for="tab in tabs" :key="tab.key" @click="setTab(tab.key)"
            class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors" :class="activeTab === tab.key
                ? 'border-accent text-accent'
                : 'border-transparent text-text/60 hover:text-text'">
            {{ tab.label }}
        </button>
    </div>
</template>
