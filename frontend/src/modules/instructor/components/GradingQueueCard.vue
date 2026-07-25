<script setup lang="ts">
import { ref } from 'vue'
import BaseCard from '@/shared/components/ui/BaseCard.vue'
import BaseBadge from '@/shared/components/ui/BaseBadge.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseDropdown from '@/shared/components/ui/BaseDropdown.vue'
import DataTable from '@/shared/components/ui/DataTable.vue'

defineProps<{
    rows: { courseCode: string; studentId: string; examType: string; status: string }[]
}>()

const filter = ref('all')
const filterOptions = [
    { value: 'all', label: 'All statuses' },
    { value: 'ungraded', label: 'Ungraded only' },
    { value: 'flagged', label: 'Flagged only' },
]

const columns = [
    { key: 'courseCode', label: 'Course Code' },
    { key: 'studentId', label: 'Student ID' },
    { key: 'examType', label: 'Exam Type' },
    { key: 'status', label: 'Status' },
    { key: 'action', label: 'Action', align: 'right' as const },
]

const statusVariant: Record<string, 'info' | 'danger' | 'dark'> = {
    UNGRADED: 'info',
    FLAGGED: 'danger',
    'AI PENDING': 'dark',
}
</script>

<template>
    <BaseCard :padded="false">
        <div class="flex items-center justify-between p-6 pb-4">
            <h2 class="text-xl font-bold text-text">Priority Grading Queue</h2>
            <BaseDropdown v-model="filter" :options="filterOptions" />
        </div>

        <DataTable :columns="columns" :rows="rows">
            <template #cell-status="{ value }">
                <BaseBadge :variant="statusVariant[value]">{{ value }}</BaseBadge>
            </template>
            <template #cell-action="{ row }">
                <BaseButton>{{ row.status === 'FLAGGED' ? 'Review' : 'Grade' }}</BaseButton>
            </template>
        </DataTable>

        <div class="p-4 text-right border-t border-border">
            <router-link to="/teaching/grading" class="text-sm font-semibold text-accent hover:underline">
                View Full Queue →
            </router-link>
        </div>
    </BaseCard>
</template>
