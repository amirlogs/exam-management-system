<script setup lang="ts">
import { onMounted, computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { CheckCircle2, RefreshCw } from 'lucide-vue-next'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import ImportProcessingStep from '../components/ImportProcessingStep.vue'
import ImportReviewTable from '../components/ImportReviewTable.vue'
import { useImportWizard } from '../composables/useImportWizard'
import { IMPORT_TYPE_CONFIG } from '../config/importTypes'
import ConfirmModal from '@/shared/components/ConfirmModal.vue'
import ImportQuestionsReviewTable from '../components/ImportQuestionsReviewTable.vue'
const route = useRoute()
const router = useRouter()
const id = computed(() => Number(route.params.id))

const { record, isConfirming, isCancelling, savingRow, loadExisting, updateRow, deleteRow, confirm, cancel } = useImportWizard('students')

const loading = ref(false)
const showConfirmModal = ref(false)
const showCancelModal = ref(false)

async function load() {
    loading.value = true
    await loadExisting(id.value)
    loading.value = false
}
onMounted(load)

const typeLabel = computed(() => (record.value ? IMPORT_TYPE_CONFIG[record.value.type].label : ''))

async function confirmFinal() {
    try {
        await confirm()
        showConfirmModal.value = false
    } catch { /* toast already shown */ }
}
async function cancelFinal() {
    await cancel()
    showCancelModal.value = false
    router.push({ name: 'admin-imports' })
}
</script>

<template>
    <div class="mx-auto w-full max-w-260 space-y-6 px-6 py-6">
        <div class="flex items-start justify-between gap-4">
            <div v-if="record">
                <p class="text-xs uppercase tracking-wide text-text/50">Import #{{ record.id }} · {{ typeLabel }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-text">
                    {{ record.status === 'ready_for_review' ? 'Review data'
                        : record.status === 'confirmed' ? 'Import complete'
                            : record.status === 'failed' ? 'Import failed'
                                : 'Processing' }}
                </h1>
            </div>
            <BaseButton variant="secondary" :disabled="loading" @click="load">
                <template #icon>
                    <RefreshCw :class="['h-4 w-4', loading && 'animate-spin']" />
                </template>
                Refresh
            </BaseButton>
        </div>

        <ImportProcessingStep v-if="record && (record.status === 'pending' || record.status === 'processing')"
            :total-rows="record.total_rows" />

        <div v-else-if="record?.status === 'ready_for_review'" class="space-y-6">
            <component :is="record.type === 'questions' ? ImportQuestionsReviewTable : ImportReviewTable"
                :rows="record.validated_data ?? {}" :saving-row="savingRow"
                @update-row="({ row, data }) => updateRow(row, data)" @delete-row="deleteRow" />
            <div class="flex justify-between border-t border-border pt-4">
                <BaseButton variant="secondary" @click="showCancelModal = true">Cancel import</BaseButton>
                <BaseButton :disabled="record.error_count > 0" @click="showConfirmModal = true">
                    {{ record.error_count > 0 ? `Resolve ${record.error_count} invalid rows` : 'Confirm import' }}
                </BaseButton>
            </div>
        </div>

        <div v-else-if="record?.status === 'confirmed'" class="space-y-6">
            <div class="flex items-center gap-3 rounded-md border border-success/30 bg-success/5 p-5">
                <CheckCircle2 class="h-6 w-6 shrink-0 text-success" />
                <div>
                    <h3 class="font-semibold text-text">Import confirmed</h3>
                    <p class="text-sm text-text/60">{{ record.valid_count }} {{ typeLabel.toLowerCase() }} added on {{
                        record.updated_at.slice(0, 10) }}.</p>
                </div>
            </div>
            <component :is="record.type === 'questions' ? ImportQuestionsReviewTable : ImportReviewTable"
                :rows="record.validated_data ?? {}" :saving-row="null" readonly />
        </div>

        <div v-else-if="record?.status === 'failed'"
            class="rounded-md border border-error/30 bg-error/5 p-8 text-center">
            <p class="text-sm font-medium text-error">This import failed to process.</p>
            <p class="mt-1 text-sm text-text/60">Try cancelling it and uploading the file again.</p>
            <BaseButton class="mt-4" variant="secondary" @click="showCancelModal = true">Cancel import</BaseButton>
        </div>
    </div>

    <ConfirmModal :show="showConfirmModal" title="Confirm this import?"
        :description="`This will create ${record?.valid_count ?? 0} new ${typeLabel.toLowerCase()} records. This action cannot be undone.`"
        confirm-text="Confirm import" variant="danger" :icon="CheckCircle2" :loading="isConfirming"
        @close="showConfirmModal = false" @confirm="confirmFinal" />
    <ConfirmModal :show="showCancelModal" title="Cancel this import?"
        description="This will discard the uploaded file and all reviewed rows. This cannot be undone."
        confirm-text="Cancel import" variant="danger" :loading="isCancelling" @close="showCancelModal = false"
        @confirm="cancelFinal" />
</template>
