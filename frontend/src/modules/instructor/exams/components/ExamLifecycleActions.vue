<script setup lang="ts">
import { computed, ref } from 'vue';
import { Archive, CalendarClock, Check, Clock, Clock3, Play, RotateCcw, Send, Square, X, XCircle } from 'lucide-vue-next';

import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import CalendarSelector from '@/shared/components/ui/CalendarSelector.vue';
import DurationSelector from './DurationSelector.vue';

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
  schedule: [{ scheduled_start: string; duration_minutes?: number }];
  publish: [];
  end: [];
  cancel: [];
  archive: [];
  extendTime: [number];
}>();

const showReject = ref(false);
const rejectReason = ref('');

const showSchedule = ref(false);
const scheduleStart = ref('');
const scheduleDuration = ref<number>(props.exam.duration_minutes || 90);

const showExtend = ref(false);
const extendMinutes = ref<number>(15);

const status = computed(() => props.exam.status);

function submitReject() {
  const reason = rejectReason.value.trim();
  if (!reason) return;

  emit('reject', reason);
  rejectReason.value = '';
  showReject.value = false;
}

function openSchedule() {
  scheduleDuration.value = props.exam.duration_minutes || 90;
  // Default to tomorrow 09:00 if not set
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  tomorrow.setHours(9, 0, 0, 0);
  const pad = (n: number) => String(n).padStart(2, '0');
  const defaultIso = `${tomorrow.getFullYear()}-${pad(tomorrow.getMonth() + 1)}-${pad(tomorrow.getDate())}T09:00`;
  scheduleStart.value = defaultIso;
  showSchedule.value = true;
}

function submitSchedule() {
  if (!scheduleStart.value) return;

  emit('schedule', {
    scheduled_start: scheduleStart.value,
    duration_minutes: Number(scheduleDuration.value),
  });
  showSchedule.value = false;
}

function submitExtend() {
  if (!extendMinutes.value || extendMinutes.value < 1) return;

  emit('extendTime', Number(extendMinutes.value));
  showExtend.value = false;
}
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <!-- Draft State Actions -->
    <BaseButton v-if="status === 'draft'" v-can="'exam.submit'" :loading="loading" @click="emit('submit')">
      <template #icon>
        <Send class="h-4 w-4" />
      </template>
      Submit for approval
    </BaseButton>

    <!-- Pending Approval State Actions -->
    <template v-if="status === 'pending_approval'">
      <BaseButton v-can="'exam.approve'" variant="primary" :loading="loading" @click="emit('approve')">
        <template #icon>
          <Check class="h-4 w-4" />
        </template>
        Approve
      </BaseButton>

      <BaseButton v-can="'exam.revert'" variant="secondary" :loading="loading" @click="emit('revert')">
        <template #icon>
          <RotateCcw class="h-4 w-4" />
        </template>
        Revert to draft
      </BaseButton>

      <button
        v-can="'exam.reject'"
        type="button"
        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-error/30 px-3 text-xs font-medium text-error hover:bg-error/10 transition-colors"
        @click="showReject = true">
        <XCircle class="h-3.5 w-3.5" />
        Reject
      </button>
    </template>

    <!-- Approved State Actions -->
    <template v-if="status === 'approved'">
      <BaseButton v-can="'exam.schedule'" :loading="loading" @click="openSchedule">
        <template #icon>
          <Clock3 class="h-4 w-4" />
        </template>
        Schedule exam
      </BaseButton>

      <button
        v-can="'exam.cancel'"
        type="button"
        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-error/30 px-3 text-xs font-medium text-error hover:bg-error/10 transition-colors"
        @click="emit('cancel')">
        <XCircle class="h-3.5 w-3.5" />
        Cancel exam
      </button>
    </template>

    <!-- Scheduled State Actions -->
    <template v-if="status === 'scheduled'">
      <BaseButton v-can:any="['exam.publish', 'exam.activate']" :loading="loading" @click="emit('publish')">
        <template #icon>
          <Play class="h-4 w-4" />
        </template>
        Publish & activate
      </BaseButton>

      <BaseButton v-can="'exam.schedule.update'" variant="secondary" :loading="loading" @click="openSchedule">
        <template #icon>
          <CalendarClock class="h-4 w-4" />
        </template>
        Reschedule
      </BaseButton>

      <button
        v-can="'exam.cancel'"
        type="button"
        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-error/30 px-3 text-xs font-medium text-error hover:bg-error/10 transition-colors"
        @click="emit('cancel')">
        <XCircle class="h-3.5 w-3.5" />
        Cancel exam
      </button>
    </template>

    <!-- Active State Actions -->
    <template v-if="status === 'active'">
      <BaseButton v-can="'exam.end'" variant="danger" :loading="loading" @click="emit('end')">
        <template #icon>
          <Square class="h-4 w-4" />
        </template>
        End exam
      </BaseButton>

      <BaseButton v-can="'exam.extend'" variant="secondary" :loading="loading" @click="showExtend = true">
        <template #icon>
          <Clock class="h-4 w-4" />
        </template>
        Extend time
      </BaseButton>
    </template>

    <!-- Completed State Actions -->
    <template v-if="status === 'completed'">
      <button
        v-can="'exam.archive'"
        type="button"
        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-border px-3 text-xs font-medium text-text/70 hover:border-accent/40 hover:text-text transition-colors"
        @click="emit('archive')">
        <Archive class="h-3.5 w-3.5" />
        Archive exam
      </button>
    </template>

    <!-- Schedule / Reschedule Modal -->
    <div v-if="showSchedule" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
      <div class="w-full max-w-lg rounded-2xl border border-border bg-surface p-6 shadow-2xl max-h-[90vh] flex flex-col">
        <div class="flex items-start justify-between">
          <div>
            <h3 class="font-display text-base font-bold text-text">
              {{ status === 'scheduled' ? 'Reschedule Exam' : 'Schedule Exam' }}
            </h3>
            <p class="mt-1 text-xs text-text/50">Specify the starting date and time for students to sit for this exam.</p>
          </div>
          <button type="button" class="rounded p-1 text-text/40 hover:text-text cursor-pointer" @click="showSchedule = false">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="mt-4 space-y-4 overflow-y-auto pr-1 flex-1">
          <CalendarSelector v-model="scheduleStart" label="Examination Start Window" />

          <DurationSelector v-if="status === 'scheduled'" v-model="scheduleDuration" :min="30" label="Adjust Duration" />
        </div>

        <div class="mt-6 flex justify-end gap-2">
          <BaseButton variant="secondary" @click="showSchedule = false"> Cancel </BaseButton>
          <BaseButton :disabled="!scheduleStart" :loading="loading" @click="submitSchedule">
            <template #icon>
              <Check class="h-4 w-4" />
            </template>
            Confirm Schedule
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Extend Time Modal -->
    <div v-if="showExtend" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
      <div class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl">
        <div class="flex items-start justify-between">
          <div>
            <h3 class="font-display text-base font-bold text-text">Extend Ongoing Exam</h3>
            <p class="mt-1 text-xs text-text/50">Add extra time to the active examination window.</p>
          </div>
          <button type="button" class="rounded p-1 text-text/40 hover:text-text" @click="showExtend = false">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="mt-4 space-y-3">
          <div>
            <label class="block mb-1.5 text-xs font-bold uppercase tracking-wide text-text/70">Quick Add</label>
            <div class="grid grid-cols-4 gap-2">
              <button
                v-for="mins in [10, 15, 30, 45]"
                :key="mins"
                type="button"
                class="rounded-lg border py-1.5 text-xs font-semibold transition cursor-pointer"
                :class="extendMinutes === mins ? 'border-accent bg-accent/10 text-accent' : 'border-border bg-bg/50 text-text/75 hover:border-text/30 hover:bg-bg'"
                @click="extendMinutes = mins">
                +{{ mins }}m
              </button>
            </div>
          </div>

          <BaseInput v-model="extendMinutes" type="number" min="1" label="Additional minutes" placeholder="e.g. 15" />
        </div>

        <div class="mt-6 flex justify-end gap-2">
          <BaseButton variant="secondary" @click="showExtend = false"> Cancel </BaseButton>
          <BaseButton :disabled="!extendMinutes || extendMinutes < 1" :loading="loading" @click="submitExtend">
            <template #icon>
              <Check class="h-4 w-4" />
            </template>
            Extend Time
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="showReject" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-xs p-4">
      <div class="w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl">
        <div class="flex items-start justify-between">
          <div>
            <h3 class="font-display text-base font-bold text-text">Reject Exam</h3>
            <p class="mt-1 text-xs text-text/50">Provide the reason to be saved with this rejection review.</p>
          </div>
          <button type="button" class="rounded p-1 text-text/40 hover:text-text" @click="showReject = false">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="mt-4">
          <BaseInput v-model="rejectReason" label="Rejection Reason" placeholder="e.g. Needs more medium difficulty questions" />
        </div>

        <div class="mt-6 flex justify-end gap-2">
          <BaseButton variant="secondary" @click="showReject = false"> Cancel </BaseButton>
          <BaseButton :disabled="!rejectReason.trim()" :loading="loading" @click="submitReject">
            <template #icon>
              <Check class="h-4 w-4" />
            </template>
            Confirm Rejection
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
