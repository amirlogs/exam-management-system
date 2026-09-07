<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';

import { Download, FileText, UploadCloud, X } from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';

import { getSemesters } from '@/modules/admin/semesters/api/semesters';
import { getCourses } from '@/modules/admin/courses/api/courses';

import { IMPORT_TYPE_CONFIG } from '../config/importTypes';

import type { ImportType } from '../types/import';

const props = withDefaults(
  defineProps<{
    type: ImportType;

    uploading?: boolean;
    error?: string | null;

    courseOptions?: {
      value: string;
      label: string;
    }[];
    fixedContext?: Record<string, any>;
    fixedCourseLabel?: string;
  }>(),
  {
    uploading: false,
    error: null,
    courseOptions: undefined,
    fixedContext: undefined,
    fixedCourseLabel: undefined,
  },
);

const emit = defineEmits<{
  upload: [
    {
      file: File;
      context: Record<string, any>;
    },
  ];
}>();

const config = computed(() => IMPORT_TYPE_CONFIG[props.type]);

const context = ref<Record<string, any>>({});

const semesterOptions = ref<
  {
    value: string;
    label: string;
  }[]
>([]);

const internalCourseOptions = ref<
  {
    value: string;
    label: string;
  }[]
>([]);

const availableCourseOptions = computed(() => {
  return props.courseOptions !== undefined ? props.courseOptions : internalCourseOptions.value;
});

const isDragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const localError = ref<string | null>(null);

watch(
  () => props.type,
  () => {
    context.value = props.fixedContext ? { ...props.fixedContext } : {};
    selectedFile.value = null;
    localError.value = null;

    if (fileInput.value) {
      fileInput.value.value = '';
    }
  },
  {
    immediate: true,
  },
);

watch(
  () => props.fixedContext,
  (fc) => {
    if (fc) {
      context.value = { ...context.value, ...fc };
    }
  },
  {
    immediate: true,
    deep: true,
  },
);

watch(
  availableCourseOptions,
  (opts) => {
    if (opts && opts.length === 1 && !context.value.course_id) {
      context.value.course_id = opts[0].value;
    }
  },
  {
    immediate: true,
  },
);

onMounted(async () => {
  if (config.value?.contextFields?.some((field) => field.kind === 'semester_select')) {
    const res = await getSemesters(1, 100);

    semesterOptions.value = (res.data ?? []).map((semester) => ({
      value: String(semester.id),
      label: `Semester ${semester.name} · ${semester.academic_year}`,
    }));
  }

  if (config.value?.contextFields?.some((field) => field.kind === 'course_select') && props.courseOptions === undefined) {
    const res = await getCourses(1, 200);

    internalCourseOptions.value = (res.data ?? []).map((course) => ({
      value: String(course.id),
      label: `${course.code} - ${course.name}`,
    }));
  }
});

function validate(file: File) {
  if (!file.name.toLowerCase().endsWith('.csv')) {
    localError.value = 'Only CSV files are supported.';
    selectedFile.value = null;
    return;
  }

  localError.value = null;
  selectedFile.value = file;
}

function onDrop(event: DragEvent) {
  isDragging.value = false;

  const file = event.dataTransfer?.files?.[0];

  if (file) {
    validate(file);
  }
}

function onFileSelected(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0];

  if (file) {
    validate(file);
  }
}

function clearSelection() {
  selectedFile.value = null;

  if (fileInput.value) {
    fileInput.value.value = '';
  }
}

const canSubmit = computed(() => {
  if (!selectedFile.value || !config.value) {
    return false;
  }

  return config.value.contextFields.filter((field) => field.required).every((field) => context.value[field.key] !== undefined && context.value[field.key] !== '');
});

function submit() {
  if (!selectedFile.value || !canSubmit.value) {
    return;
  }

  emit('upload', {
    file: selectedFile.value,
    context: {
      ...context.value,
    },
  });
}

function downloadSample() {
  if (!config.value) {
    return;
  }

  const blob = new Blob([config.value.sampleContent], {
    type: 'text/csv;charset=utf-8;',
  });

  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');

  anchor.href = url;
  anchor.download = config.value.sampleFilename;

  document.body.appendChild(anchor);
  anchor.click();
  anchor.remove();

  URL.revokeObjectURL(url);
}
</script>

<template>
  <div v-if="config" class="space-y-6 rounded-md border border-border bg-surface p-8">
    <div class="flex items-start justify-between gap-4 rounded-md border border-border bg-bg p-4">
      <div class="min-w-0">
        <p class="text-xs font-bold uppercase tracking-wide text-text/50">Expected CSV columns</p>

        <p class="mt-1.5 break-words font-mono text-xs text-text/70">
          {{ config.expectedColumns.join(', ') }}
        </p>
      </div>

      <BaseButton variant="secondary" class="shrink-0" @click="downloadSample">
        <template #icon>
          <Download class="h-4 w-4" />
        </template>

        Sample CSV
      </BaseButton>
    </div>

    <div v-if="config.contextFields.length" class="grid grid-cols-1 gap-6 rounded-md border border-border bg-bg p-6 md:grid-cols-2">
      <div v-for="field in config.contextFields" :key="field.key">
        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/60">
          {{ field.label }}
        </label>

        <BaseSelect v-if="field.kind === 'semester_select'" v-model="context[field.key]" :options="semesterOptions" placeholder="Select semester" />

        <div v-else-if="field.kind === 'course_select'">
          <div v-if="props.fixedCourseLabel" class="flex h-10 w-full items-center rounded-lg border border-border bg-bg/70 px-3 text-sm font-medium text-text">
            {{ props.fixedCourseLabel }}
          </div>
          <BaseSelect v-else v-model="context[field.key]" :options="availableCourseOptions" placeholder="Select course" />
        </div>

        <BaseInput v-else v-model="context[field.key]" :type="field.kind === 'number' ? 'number' : 'text'" />
      </div>
    </div>

    <p v-else class="text-sm text-text/50">This import type needs no additional context. Just upload the CSV file.</p>

    <div>
      <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/60"> Source file </label>

      <div
        v-if="!selectedFile"
        class="flex flex-col items-center justify-center rounded-md border-2 border-dashed p-12 text-center transition-colors"
        :class="isDragging ? 'border-accent bg-accent/5' : 'border-border'"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="onDrop">
        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-accent/10 text-accent">
          <UploadCloud class="h-6 w-6" />
        </div>

        <h3 class="mb-1 font-semibold text-text">Drag and drop your CSV here</h3>

        <p class="mb-6 text-sm text-text/60">Supports .csv files up to 2 MB.</p>

        <input ref="fileInput" type="file" accept=".csv" class="hidden" @change="onFileSelected" />

        <BaseButton variant="secondary" @click="fileInput?.click()"> Select file </BaseButton>
      </div>

      <div v-else class="flex items-center justify-between gap-4 rounded-md border border-border bg-bg p-4">
        <div class="flex min-w-0 items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-accent/10 text-accent">
            <FileText class="h-5 w-5" />
          </div>

          <div class="min-w-0">
            <p class="truncate text-sm font-medium text-text">
              {{ selectedFile.name }}
            </p>

            <p class="text-xs text-text/50">
              {{ (selectedFile.size / 1024).toFixed(1) }}
              KB
            </p>
          </div>
        </div>

        <button type="button" :disabled="uploading" class="rounded-md p-1.5 text-text/50 hover:bg-text/5 hover:text-text disabled:opacity-40" @click="clearSelection">
          <X class="h-4 w-4" />
        </button>
      </div>
    </div>

    <p v-if="error || localError" class="text-sm text-error">
      {{ error || localError }}
    </p>

    <div class="flex justify-end border-t border-border pt-6">
      <BaseButton :loading="uploading" :disabled="!canSubmit" @click="submit"> Upload and process </BaseButton>
    </div>
  </div>

  <div v-else class="rounded-md border border-error/30 bg-error/5 p-5">
    <p class="text-sm font-medium text-error">Unable to load the configuration for this import type.</p>
  </div>
</template>
