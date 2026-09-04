<script setup lang="ts">
import { computed, ref } from 'vue';

import { Archive, Check, Clock3, Play, RotateCcw, Send, Square, XCircle } from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';

import type { Exam } from '../types/exam';

const props = defineProps<{
  exam: Exam;
  loading?: boolean;
}>();

const emit = defineEmits<{
  submit: [];
  revert: [];
  approve: [];
  reject: [string];
  schedule: [];
  publish: [];
  end: [];
  cancel: [];
  archive: [];
}>();

const showReject = ref(false);
const rejectReason = ref('');

const status = computed(() => props.exam.status);

function submitReject() {
  const reason = rejectReason.value.trim();

  if (!reason) {
    return;
  }

  emit('reject', reason);

  rejectReason.value = '';
  showReject.value = false;
}
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <BaseButton v-if="status === 'draft'" :loading="loading" @click="emit('submit')">
      <template #icon>
        <Send class="h-4 w-4" />
      </template>
      Submit for approval
    </BaseButton>

    <BaseButton v-if="status === 'pending_approval'" variant="secondary" :loading="loading" @click="emit('revert')">
      <template #icon>
        <RotateCcw class="h-4 w-4" />
      </template>
      Revert to draft
    </BaseButton>

    <BaseButton v-if="status === 'approved'" :loading="loading" @click="emit('schedule')">
      <template #icon>
        <Clock3 class="h-4 w-4" />
      </template>
      Schedule
    </BaseButton>

    <BaseButton v-if="status === 'scheduled'" :loading="loading" @click="emit('publish')">
      <template #icon>
        <Play class="h-4 w-4" />
      </template>
      Publish
    </BaseButton>

    <BaseButton v-if="status === 'active'" :loading="loading" @click="emit('end')">
      <template #icon>
        <Square class="h-4 w-4" />
      </template>
      End exam
    </BaseButton>

    <button
      v-if="status === 'pending_approval'"
      type="button"
      class="inline-flex h-9 items-center gap-2 rounded-md border border-error/20 px-3 text-sm font-medium text-error hover:bg-error/5"
      @click="showReject = true">
      <XCircle class="h-4 w-4" />
      Reject
    </button>

    <button
      v-if="status === 'completed'"
      type="button"
      class="inline-flex h-9 items-center gap-2 rounded-md border border-border px-3 text-sm font-medium text-text/70 hover:border-accent/40 hover:text-text"
      @click="emit('archive')">
      <Archive class="h-4 w-4" />
      Archive
    </button>

    <button
      v-if="status === 'approved' || status === 'scheduled'"
      type="button"
      class="inline-flex h-9 items-center gap-2 rounded-md border border-error/20 px-3 text-sm font-medium text-error hover:bg-error/5"
      @click="emit('cancel')">
      <XCircle class="h-4 w-4" />
      Cancel
    </button>

    <div v-if="showReject" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 p-4">
      <div class="w-full max-w-md rounded-lg border border-border bg-surface p-6 shadow-2xl">
        <h3 class="text-base font-semibold text-text">Reject exam</h3>

        <p class="mt-1 text-sm text-text/55">Provide the reason that should be stored with the review.</p>

        <div class="mt-5">
          <BaseInput v-model="rejectReason" label="Reason" placeholder="e.g. Insufficient question coverage" />
        </div>

        <div class="mt-6 flex justify-end gap-2">
          <BaseButton variant="secondary" @click="showReject = false"> Cancel </BaseButton>

          <BaseButton :disabled="!rejectReason.trim()" @click="submitReject">
            <template #icon>
              <Check class="h-4 w-4" />
            </template>
            Reject exam
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
