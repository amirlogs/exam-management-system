<script setup lang="ts">
import { ref, computed } from 'vue';
import { CheckCircle2, AlertTriangle, Pencil, Trash2, X, Check, Filter } from 'lucide-vue-next';

import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';

import type { ImportRowMap } from '../types/import';

const props = defineProps<{
  rows: ImportRowMap;
  savingRow: string | null;
  readonly?: boolean;
}>();

const emit = defineEmits<{
  'update-row': [
    {
      row: string;
      data: Record<string, any>;
    },
  ];
  'delete-row': [row: string];
}>();

const filterInvalidOnly = ref(false);
const expandedRow = ref<string | null>(null);
const editingRow = ref<string | null>(null);
const draft = ref<Record<string, any>>({});

const entries = computed(() => {
  const all = Object.entries(props.rows);

  return filterInvalidOnly.value ? all.filter(([, row]) => row.status === 'invalid') : all;
});

const rowCount = computed(() => Object.keys(props.rows).length);

const validCount = computed(() => Object.values(props.rows).filter((row) => row.status === 'valid').length);

const invalidCount = computed(() => Object.values(props.rows).filter((row) => row.status === 'invalid').length);

const columns = computed(() => {
  const firstRow = Object.values(props.rows)[0];

  return firstRow ? Object.keys(firstRow.data) : [];
});

function toggleExpand(rowKey: string) {
  if (editingRow.value === rowKey) return;

  expandedRow.value = expandedRow.value === rowKey ? null : rowKey;
}

function startEdit(rowKey: string, data: Record<string, any>) {
  editingRow.value = rowKey;
  expandedRow.value = rowKey;
  draft.value = { ...data };
}

function cancelEdit() {
  editingRow.value = null;
  draft.value = {};
}

function saveEdit(rowKey: string) {
  emit('update-row', {
    row: rowKey,
    data: { ...draft.value },
  });

  editingRow.value = null;
  draft.value = {};
}
</script>

<template>
  <div class="overflow-hidden rounded-md border border-border bg-surface">
    <div class="flex items-center justify-between border-b border-border bg-bg/50 px-5 py-3">
      <div class="flex items-center gap-3 text-xs">
        <span class="font-semibold uppercase tracking-wide text-text/60"> {{ rowCount }} rows </span>

        <span class="text-border">|</span>

        <span class="font-medium text-success"> {{ validCount }} valid </span>

        <span v-if="invalidCount > 0" class="font-medium text-error"> {{ invalidCount }} invalid </span>
      </div>

      <button
        v-if="!readonly && invalidCount > 0"
        type="button"
        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-semibold transition-colors"
        :class="filterInvalidOnly ? 'border-error/30 bg-error/10 text-error' : 'border-border bg-surface text-text/70 hover:bg-bg'"
        @click="filterInvalidOnly = !filterInvalidOnly">
        <Filter class="h-3.5 w-3.5" />
        {{ filterInvalidOnly ? 'Showing invalid only' : 'Filter invalid rows' }}
      </button>
    </div>

    <div class="divide-y divide-border">
      <div v-for="[rowKey, row] in entries" :key="rowKey" :class="row.status === 'invalid' ? 'bg-error/5' : ''">
        <div class="flex cursor-pointer items-center justify-between gap-4 p-4 hover:bg-text/2" @click="toggleExpand(rowKey)">
          <div class="flex min-w-0 flex-1 items-center gap-3">
            <span class="w-8 shrink-0 font-mono text-xs font-semibold text-text/40"> #{{ rowKey }} </span>

            <CheckCircle2 v-if="row.status === 'valid'" class="h-4 w-4 shrink-0 text-success" />

            <AlertTriangle v-else class="h-4 w-4 shrink-0 text-error" />

            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-text">
                {{ Object.values(row.data)[0] || '(empty)' }}
              </p>

              <p class="mt-0.5 text-xs text-text/40">{{ Object.keys(row.data).length }} fields</p>
            </div>
          </div>

          <div class="flex shrink-0 items-center gap-2" @click.stop>
            <BaseBadge :variant="row.status === 'valid' ? 'success' : 'danger'">
              {{ row.status === 'valid' ? 'Valid' : `${row.errors.length} issue(s)` }}
            </BaseBadge>

            <template v-if="!readonly">
              <button type="button" class="rounded-md bg-accent/10 px-2.5 py-1 text-xs font-semibold text-accent hover:bg-accent/20" @click="startEdit(rowKey, row.data)">
                <Pencil class="mr-1 inline h-3.5 w-3.5" />
                Edit
              </button>

              <button type="button" class="rounded-md bg-error/10 px-2.5 py-1 text-xs font-semibold text-error hover:bg-error/20" @click="emit('delete-row', rowKey)">
                <Trash2 class="mr-1 inline h-3.5 w-3.5" />
                Delete
              </button>
            </template>

            <button type="button" class="text-text/40 hover:text-text" @click="toggleExpand(rowKey)">
              {{ expandedRow === rowKey ? '▲' : '▼' }}
            </button>
          </div>
        </div>

        <div v-if="expandedRow === rowKey" class="border-t border-border bg-bg/20 p-5">
          <div v-if="row.errors.length" class="mb-5 rounded-md border border-error/20 bg-error/10 p-3 text-xs">
            <div class="mb-1 flex items-center gap-1.5 font-bold text-error">
              <AlertTriangle class="h-3.5 w-3.5" />
              Needs correction
            </div>

            <ul class="list-inside list-disc space-y-0.5 text-error/90">
              <li v-for="(error, index) in row.errors" :key="index">
                {{ error }}
              </li>
            </ul>
          </div>

          <div v-if="editingRow !== rowKey" class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div v-for="column in columns" :key="column" class="rounded-md border border-border bg-surface p-3">
              <p class="text-[10px] font-bold uppercase tracking-wide text-text/40">
                {{ column }}
              </p>

              <p class="mt-1 break-words text-sm text-text">
                {{ row.data[column] === null || row.data[column] === '' ? 'N/A' : String(row.data[column]) }}
              </p>
            </div>
          </div>

          <div v-else class="space-y-4 rounded-md border border-accent/30 bg-surface p-5">
            <div class="flex items-center justify-between border-b border-border pb-3">
              <h4 class="text-xs font-bold uppercase tracking-wider text-accent">Edit row</h4>

              <span class="text-xs text-text/50"> Row #{{ rowKey }} </span>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div v-for="column in columns" :key="column">
                <label class="mb-1 block text-xs font-semibold text-text/60">
                  {{ column }}
                </label>

                <input v-model="draft[column]" type="text" class="w-full rounded-md border border-border bg-bg px-3 py-2 text-sm text-text outline-none focus:border-accent" />
              </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-border pt-3">
              <BaseButton variant="secondary" @click="cancelEdit">
                <template #icon>
                  <X class="h-4 w-4" />
                </template>
                Cancel
              </BaseButton>

              <BaseButton :loading="savingRow === rowKey" @click="saveEdit(rowKey)">
                <template #icon>
                  <Check class="h-4 w-4" />
                </template>
                Save row
              </BaseButton>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
