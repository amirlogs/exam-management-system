<script setup lang="ts">
import { computed } from 'vue';
import { Clock3, Minus, Plus } from 'lucide-vue-next';

const props = withDefaults(
  defineProps<{
    modelValue: number;
    label?: string;
    min?: number;
    max?: number;
    step?: number;
    hint?: string;
    disabled?: boolean;
  }>(),
  {
    label: 'Exam Duration',
    min: 30,
    max: 360,
    step: 15,
    hint: 'Select a standard duration or customize in 15-minute intervals.',
    disabled: false,
  },
);

const emit = defineEmits<{
  'update:modelValue': [number];
}>();

const presets = [
  { label: '30m', value: 30, tag: 'Short' },
  { label: '45m', value: 45, tag: 'Quiz' },
  { label: '1 hour', value: 60, tag: '60m' },
  { label: '1.5 hrs', value: 90, tag: '90m' },
  { label: '2 hours', value: 120, tag: '120m' },
  { label: '3 hours', value: 180, tag: 'Final' },
];

const formattedDuration = computed(() => {
  const total = Number(props.modelValue) || 0;
  if (total <= 0) return '0 min';
  const hours = Math.floor(total / 60);
  const minutes = total % 60;
  if (hours === 0) return `${minutes} minutes`;
  if (minutes === 0) return `${hours} ${hours === 1 ? 'hour' : 'hours'}`;
  return `${hours} hr ${minutes} mins`;
});

function selectPreset(val: number) {
  if (props.disabled) return;
  emit('update:modelValue', val);
}

function adjust(delta: number) {
  if (props.disabled) return;
  const current = Number(props.modelValue) || props.min;
  const next = Math.min(props.max, Math.max(props.min, current + delta));
  emit('update:modelValue', next);
}

function handleInput(event: Event) {
  const target = event.target as HTMLInputElement;
  let val = parseInt(target.value, 10);
  if (isNaN(val)) val = props.min;
  emit('update:modelValue', val);
}
</script>

<template>
  <div class="space-y-2.5">
    <!-- Header: Label + Human Readable Time Badge -->
    <div class="flex items-center justify-between">
      <label class="block text-xs font-bold tracking-wide uppercase text-text/70">
        {{ label }}
      </label>

      <span class="inline-flex items-center gap-1.5 rounded-full border border-accent/20 bg-accent/10 px-2.5 py-0.5 font-mono text-xs font-semibold text-accent">
        <Clock3 class="h-3.5 w-3.5" />
        {{ formattedDuration }}
      </span>
    </div>

    <!-- Quick Presets Row -->
    <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
      <button
        v-for="preset in presets"
        :key="preset.value"
        type="button"
        :disabled="disabled"
        class="group relative flex flex-col items-center justify-center rounded-xl border px-3 py-2 text-center transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
        :class="
          modelValue === preset.value
            ? 'border-accent bg-accent/10 text-accent font-semibold shadow-2xs'
            : 'border-border bg-bg/50 text-text/75 hover:border-text/30 hover:bg-bg hover:text-text'
        "
        @click="selectPreset(preset.value)">
        <span class="text-xs">{{ preset.label }}</span>
        <span class="mt-0.5 text-[10px] font-mono text-text/40 group-hover:text-text/60">
          {{ preset.tag }}
        </span>
      </button>
    </div>

    <!-- Fine-tuning Stepper / Custom Input -->
    <div class="flex items-center gap-2 rounded-xl border border-border bg-bg/40 p-2 sm:p-2.5">
      <button
        type="button"
        :disabled="disabled || modelValue <= min"
        class="inline-flex h-9 items-center gap-1 rounded-lg border border-border bg-surface px-2.5 text-xs font-medium text-text transition hover:border-text/30 hover:bg-text/5 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
        title="Decrease by 15 minutes"
        @click="adjust(-step)">
        <Minus class="h-3.5 w-3.5" />
        <span class="hidden sm:inline">15m</span>
      </button>

      <div class="relative flex-1">
        <input
          type="number"
          :value="modelValue"
          :min="min"
          :max="max"
          :step="step"
          :disabled="disabled"
          class="w-full rounded-lg border border-border bg-surface py-2 pl-3 pr-16 text-center font-mono text-sm font-semibold text-text outline-none transition focus:border-accent disabled:opacity-50"
          @input="handleInput" />
        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-text/40"> minutes </span>
      </div>

      <button
        type="button"
        :disabled="disabled || modelValue >= max"
        class="inline-flex h-9 items-center gap-1 rounded-lg border border-border bg-surface px-2.5 text-xs font-medium text-text transition hover:border-text/30 hover:bg-text/5 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
        title="Increase by 15 minutes"
        @click="adjust(step)">
        <Plus class="h-3.5 w-3.5" />
        <span class="hidden sm:inline">15m</span>
      </button>
    </div>

    <!-- Hint / Minimum notice -->
    <p v-if="hint" class="text-[11px] text-text/45">{{ hint }} (Minimum: {{ min }}m, Maximum: {{ max }}m)</p>
  </div>
</template>
