<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { CheckCircle2, XCircle, Loader2, Pencil, X, Trash2 } from 'lucide-vue-next';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import AlertBanner from '@/shared/components/ui/AlertBanner.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';
import { useImportPolling } from '../../questions/composables/useImportPolling';
import * as importsApi from '../api/imports';
import * as questionsApi from '../../questions/api/questions';

const route = useRoute();
const router = useRouter();
const { importRecord, loadError, start } = useImportPolling();

const fileInput = ref<HTMLInputElement>();
const selectedFile = ref<File | null>(null);
const isUploading = ref(false);
const uploadError = ref<string | null>(null);

const courseId = computed(() => (route.query.courseId ? Number(route.query.courseId) : undefined));
const examId = computed(() => (route.query.examId ? Number(route.query.examId) : undefined));
const importId = computed(() => (route.params.importId ? Number(route.params.importId) : null));

const editingRow = ref<string | null>(null);
const draft = ref<any>(null);
const isSaving = ref(false);
const isConfirming = ref(false);
const confirmError = ref<string | null>(null);
const showCancelModal = ref(false);
const isCancelling = ref(false);

async function handleFileSelect(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (file) selectedFile.value = file;
}

async function handleUpload() {
  if (!selectedFile.value) return;
  isUploading.value = true;
  uploadError.value = null;
  try {
    const context: Record<string, any> = {};
    if (courseId.value) context.course_id = courseId.value;
    if (examId.value) context.exam_id = examId.value;
    const record = await importsApi.startImport('questions', selectedFile.value, context);
    router.replace({ params: { importId: String(record.id) }, query: route.query });
    start(record.id);
  } catch (err: any) {
    uploadError.value = err?.response?.data?.message || 'Upload failed.';
  } finally {
    isUploading.value = false;
  }
}

function startEdit(rowNum: string, row: any) {
  editingRow.value = rowNum;
  draft.value = { ...row.data, options: parseOptions(row.data.options) };
}
function parseOptions(opts: any): string[] {
  if (Array.isArray(opts)) return opts;
  if (typeof opts === 'string') {
    try {
      return JSON.parse(opts);
    } catch {
      return [];
    }
  }
  return [];
}
function cancelEdit() {
  editingRow.value = null;
  draft.value = null;
}

async function saveRow(rowNum: string) {
  if (!importRecord.value || !draft.value) return;
  isSaving.value = true;
  try {
    const isChoice = draft.value.type === 'mcq' || draft.value.type === 'true_false';
    const payload = { ...draft.value, options: isChoice ? draft.value.options : undefined };
    const updated = await importsApi.updateImportRow(importRecord.value.id, rowNum, payload);
    importRecord.value = updated;
    editingRow.value = null;
    draft.value = null;
  } catch (err: any) {
    uploadError.value = err?.response?.data?.message || 'Could not save row.';
  } finally {
    isSaving.value = false;
  }
}

async function deleteRow(rowNum: string) {
  if (!importRecord.value) return;
  try {
    importRecord.value = await importsApi.deleteImportRow(importRecord.value.id, rowNum);
  } catch (err: any) {
    uploadError.value = err?.response?.data?.message || 'Could not delete row.';
  }
}

async function handleConfirm() {
  if (!importRecord.value) return;
  isConfirming.value = true;
  confirmError.value = null;
  try {
    importRecord.value = await importsApi.confirmImport(importRecord.value.id);
  } catch (err: any) {
    confirmError.value = err?.response?.data?.message || 'Could not confirm import.';
  } finally {
    isConfirming.value = false;
  }
}

async function handleCancel() {
  if (!importRecord.value) return;
  isCancelling.value = true;
  try {
    await importsApi.cancelImport(importRecord.value.id);
    showCancelModal.value = false;
    router.back();
  } catch (err: any) {
    confirmError.value = err?.response?.data?.message || 'Could not cancel import.';
  } finally {
    isCancelling.value = false;
  }
}

const rowEntries = computed(() => Object.entries(importRecord.value?.validated_data ?? {}));

onMounted(() => {
  if (importId.value) start(importId.value);
});
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-8 min-h-[calc(100vh-68px)]">
    <div>
      <h1 class="text-2xl font-bold font-display text-text">Import Review</h1>
      <div class="flex flex-wrap items-center gap-2 mt-2">
        <span v-if="courseId" class="inline-flex items-center px-3 py-1 bg-bg border border-border rounded-full text-sm text-text/70">Course #{{ courseId }}</span>
        <span v-if="examId" class="inline-flex items-center px-3 py-1 bg-bg border border-border rounded-full text-sm text-text/70">Exam #{{ examId }}</span>
      </div>
    </div>

    <!-- No import started yet -->
    <div v-if="!importRecord" class="bg-surface border-2 border-dashed border-border rounded-xl p-12 flex flex-col items-center text-center">
      <div class="h-14 w-14 rounded-full bg-accent/10 text-accent flex items-center justify-center mb-4">
        <Loader2 v-if="isUploading" class="w-6 h-6 animate-spin" />
        <span v-else class="text-2xl">📄</span>
      </div>
      <h3 class="font-semibold text-text mb-1">Upload CSV</h3>
      <p class="text-sm text-text/60 mb-6">Supports CSV, max 20MB.</p>
      <input ref="fileInput" type="file" accept=".csv" class="hidden" @change="handleFileSelect" />
      <div v-if="!selectedFile">
        <BaseButton @click="fileInput?.click()">Choose File</BaseButton>
      </div>
      <div v-else class="flex items-center gap-3">
        <span class="text-sm text-text">{{ selectedFile.name }}</span>
        <BaseButton :disabled="isUploading" @click="handleUpload">{{ isUploading ? 'Uploading…' : 'Upload' }} </BaseButton>
      </div>
      <AlertBanner v-if="uploadError" variant="danger" class="mt-4">{{ uploadError }}</AlertBanner>
    </div>

    <!-- Processing -->
    <div v-else-if="importRecord.status === 'pending'" class="bg-surface border border-border rounded-xl p-12 flex flex-col items-center text-center">
      <Loader2 class="w-8 h-8 text-accent animate-spin mb-4" />
      <h3 class="font-semibold text-text mb-1">Processing your file…</h3>
      <p class="text-sm text-text/60">This page updates automatically once ready.</p>
    </div>

    <!-- Ready for review -->
    <template v-else-if="importRecord.status === 'ready_for_review'">
      <div class="flex items-center gap-6 bg-surface border border-border rounded-xl p-5">
        <div class="flex items-center gap-2 text-emerald-600">
          <CheckCircle2 class="w-5 h-5" /><span class="font-semibold">{{ importRecord.valid_count }} Valid</span>
        </div>
        <div v-if="importRecord.error_count > 0" class="flex items-center gap-2 text-red-600">
          <XCircle class="w-5 h-5" /><span class="font-semibold">{{ importRecord.error_count }} Errors</span>
        </div>
      </div>

      <div class="bg-surface border border-border rounded-xl overflow-hidden shadow-sm">
        <table class="w-full">
          <thead>
            <tr class="bg-bg/40 border-b border-border">
              <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45 w-16">Row</th>
              <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45">Content</th>
              <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45 w-32">Type</th>
              <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-text/45 w-32">Status</th>
              <th class="px-6 py-3.5 w-32 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <template v-for="[rowNum, row] in rowEntries" :key="rowNum">
              <tr :class="row.status === 'invalid' ? 'bg-red-500/5' : ''">
                <td class="px-6 py-4 font-mono text-xs text-text/40">{{ rowNum }}</td>
                <td class="px-6 py-4 text-sm text-text max-w-md truncate">{{ row.data.content || '—' }}</td>
                <td class="px-6 py-4 text-sm text-text/70 capitalize">{{ row.data.type }}</td>
                <td class="px-6 py-4">
                  <span v-if="row.status === 'valid'" class="inline-flex items-center gap-1 text-emerald-600 text-xs font-semibold">
                    <CheckCircle2 class="w-3.5 h-3.5" /> Valid
                  </span>
                  <span v-else class="inline-flex items-center gap-1 text-red-600 text-xs font-semibold"> <XCircle class="w-3.5 h-3.5" /> {{ row.errors.length }} issue(s) </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex justify-end gap-1">
                    <button class="p-1.5 text-text/50 hover:text-accent" @click="startEdit(rowNum, row)">
                      <Pencil class="w-4 h-4" />
                    </button>
                    <button class="p-1.5 text-text/50 hover:text-red-500" @click="deleteRow(rowNum)">
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="row.status === 'invalid'" class="bg-red-500/5">
                <td colspan="5" class="px-6 pb-3 text-xs text-red-600">{{ row.errors.join(', ') }}</td>
              </tr>
              <tr v-if="editingRow === rowNum" class="bg-bg/40">
                <td colspan="5" class="px-6 py-4">
                  <div class="bg-surface p-4 rounded-lg border border-accent/30 space-y-3">
                    <div class="grid grid-cols-2 gap-3 text-xs">
                      <div class="col-span-2">
                        <label class="font-semibold text-text/70 mb-1 block">Content</label>
                        <textarea v-model="draft.content" rows="2" class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm" />
                      </div>
                      <div>
                        <label class="font-semibold text-text/70 mb-1 block">Type</label>
                        <select v-model="draft.type" class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm">
                          <option value="mcq">MCQ</option>
                          <option value="true_false">True/False</option>
                          <option value="short_answer">Short Answer</option>
                          <option value="essay">Essay</option>
                        </select>
                      </div>
                      <div>
                        <label class="font-semibold text-text/70 mb-1 block">Difficulty</label>
                        <select v-model="draft.difficulty" class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm">
                          <option value="easy">Easy</option>
                          <option value="medium">Medium</option>
                          <option value="hard">Hard</option>
                        </select>
                      </div>
                      <div>
                        <label class="font-semibold text-text/70 mb-1 block">Chapter</label>
                        <input v-model="draft.chapter" class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm" />
                      </div>
                      <div v-if="draft.type === 'mcq' || draft.type === 'true_false'">
                        <label class="font-semibold text-text/70 mb-1 block">Options (comma-separated)</label>
                        <input
                          :value="(draft.options || []).join(', ')"
                          @input="draft.options = ($event.target as HTMLInputElement).value.split(',').map((s: string) => s.trim())"
                          class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm" />
                      </div>
                      <div v-if="draft.type === 'mcq' || draft.type === 'true_false'">
                        <label class="font-semibold text-text/70 mb-1 block">Correct Answer</label>
                        <input v-model="draft.correct_answer" class="w-full px-3 py-2 rounded-md border border-border bg-bg text-sm" />
                      </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-border">
                      <BaseButton variant="secondary" @click="cancelEdit"> <X class="w-4 h-4" /> Cancel </BaseButton>
                      <BaseButton :disabled="isSaving" @click="saveRow(rowNum)">{{ isSaving ? 'Saving…' : 'Save Row' }}</BaseButton>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <AlertBanner v-if="confirmError" variant="danger">{{ confirmError }}</AlertBanner>

      <div class="flex justify-between">
        <BaseButton variant="secondary" @click="showCancelModal = true">Cancel Import</BaseButton>
        <BaseButton :disabled="importRecord.error_count > 0 || isConfirming" @click="handleConfirm">
          {{ isConfirming ? 'Confirming…' : 'Confirm Import' }}
        </BaseButton>
      </div>
    </template>

    <!-- Confirmed -->
    <div v-else-if="importRecord.status === 'confirmed'" class="bg-surface border border-border rounded-xl p-12 flex flex-col items-center text-center">
      <CheckCircle2 class="w-10 h-10 text-emerald-600 mb-4" />
      <h3 class="font-semibold text-text mb-1">Import confirmed</h3>
      <p class="text-sm text-text/60">{{ importRecord.valid_count }} questions added.</p>
    </div>
  </div>

  <ConfirmModal
    :show="showCancelModal"
    title="Cancel this import?"
    description="This will discard the uploaded file and all parsed rows. This cannot be undone."
    confirm-text="Cancel Import"
    variant="danger"
    :icon="X"
    :loading="isCancelling"
    @close="showCancelModal = false"
    @confirm="handleCancel" />
</template>
