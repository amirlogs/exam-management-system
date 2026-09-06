<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Calendar, ChevronLeft, ChevronRight, Clock, Sun, Moon } from 'lucide-vue-next';

const props = withDefaults(
  defineProps<{
    modelValue?: string;
    label?: string;
    includeTime?: boolean;
    minDate?: Date | string | null;
  }>(),
  {
    modelValue: '',
    label: 'Start Date & Time',
    includeTime: true,
    minDate: () => new Date(),
  },
);

const emit = defineEmits<{
  'update:modelValue': [string];
}>();

const now = new Date();

// Internal state
const viewYear = ref<number>(now.getFullYear());
const viewMonth = ref<number>(now.getMonth()); // 0-indexed

// Selected parts
const selectedDate = ref<{ year: number; month: number; day: number } | null>(null);
const selectedHour = ref<number>(9); // 1-12
const selectedMinute = ref<number>(0); // 0-59
const selectedPeriod = ref<'AM' | 'PM'>('AM');

const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
];

const dayLabels = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

const parsedMinDate = computed(() => {
  if (!props.minDate) return null;
  const d = new Date(props.minDate);
  d.setHours(0, 0, 0, 0);
  return Number.isNaN(d.getTime()) ? null : d;
});

function parseIso(val: string) {
  if (!val) return;
  const d = new Date(val);
  if (Number.isNaN(d.getTime())) return;

  selectedDate.value = {
    year: d.getFullYear(),
    month: d.getMonth(),
    day: d.getDate(),
  };

  viewYear.value = d.getFullYear();
  viewMonth.value = d.getMonth();

  if (props.includeTime) {
    const rawHours = d.getHours();
    selectedPeriod.value = rawHours >= 12 ? 'PM' : 'AM';
    const h12 = rawHours % 12 || 12;
    selectedHour.value = h12;
    selectedMinute.value = d.getMinutes();
  }
}

watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      parseIso(val);
    }
  },
  { immediate: true },
);

function emitValue() {
  if (!selectedDate.value) return;

  const pad = (n: number) => String(n).padStart(2, '0');
  const year = selectedDate.value.year;
  const month = pad(selectedDate.value.month + 1);
  const day = pad(selectedDate.value.day);

  if (!props.includeTime) {
    emit('update:modelValue', `${year}-${month}-${day}`);
    return;
  }

  let h24 = selectedHour.value % 12;
  if (selectedPeriod.value === 'PM') {
    h24 += 12;
  }
  const hours = pad(h24);
  const minutes = pad(selectedMinute.value);

  emit('update:modelValue', `${year}-${month}-${day}T${hours}:${minutes}`);
}

// Calendar grid calculations
const calendarDays = computed(() => {
  const year = viewYear.value;
  const month = viewMonth.value;

  // First day of month
  const firstDay = new Date(year, month, 1);
  // Day of week: 0 is Sun, 1 is Mon... convert to Monday=0
  let startingDayOfWeek = firstDay.getDay() - 1;
  if (startingDayOfWeek === -1) startingDayOfWeek = 6;

  // Days in current month
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  // Days in previous month
  const daysInPrevMonth = new Date(year, month, 0).getDate();

  const days: {
    day: number;
    month: number;
    year: number;
    isCurrentMonth: boolean;
    isToday: boolean;
    isSelected: boolean;
    isDisabled: boolean;
  }[] = [];

  // Previous month trailing days
  for (let i = startingDayOfWeek - 1; i >= 0; i--) {
    const d = daysInPrevMonth - i;
    const m = month === 0 ? 11 : month - 1;
    const y = month === 0 ? year - 1 : year;
    const dateObj = new Date(y, m, d);
    dateObj.setHours(0, 0, 0, 0);

    const isPast = parsedMinDate.value ? dateObj < parsedMinDate.value : false;

    days.push({
      day: d,
      month: m,
      year: y,
      isCurrentMonth: false,
      isToday: false,
      isSelected: false,
      isDisabled: isPast,
    });
  }

  // Current month days
  const todayDate = new Date();
  todayDate.setHours(0, 0, 0, 0);

  for (let d = 1; d <= daysInMonth; d++) {
    const dateObj = new Date(year, month, d);
    dateObj.setHours(0, 0, 0, 0);

    const isToday = dateObj.getTime() === todayDate.getTime();
    const isSelected =
      !!selectedDate.value &&
      selectedDate.value.year === year &&
      selectedDate.value.month === month &&
      selectedDate.value.day === d;

    const isPast = parsedMinDate.value ? dateObj < parsedMinDate.value : false;

    days.push({
      day: d,
      month,
      year,
      isCurrentMonth: true,
      isToday,
      isSelected,
      isDisabled: isPast,
    });
  }

  // Next month leading days to complete the grid
  const remaining = (7 - (days.length % 7)) % 7;
  for (let d = 1; d <= remaining; d++) {
    const m = month === 11 ? 0 : month + 1;
    const y = month === 11 ? year + 1 : year;
    days.push({
      day: d,
      month: m,
      year: y,
      isCurrentMonth: false,
      isToday: false,
      isSelected: false,
      isDisabled: false,
    });
  }

  return days;
});

function prevMonth() {
  if (viewMonth.value === 0) {
    viewMonth.value = 11;
    viewYear.value--;
  } else {
    viewMonth.value--;
  }
}

function nextMonth() {
  if (viewMonth.value === 11) {
    viewMonth.value = 0;
    viewYear.value++;
  } else {
    viewMonth.value++;
  }
}

function selectDay(cell: { day: number; month: number; year: number; isDisabled: boolean }) {
  if (cell.isDisabled) return;
  selectedDate.value = {
    year: cell.year,
    month: cell.month,
    day: cell.day,
  };
  viewYear.value = cell.year;
  viewMonth.value = cell.month;
  emitValue();
}

function setQuickDate(daysFromNow: number) {
  const d = new Date();
  d.setDate(d.getDate() + daysFromNow);
  selectedDate.value = {
    year: d.getFullYear(),
    month: d.getMonth(),
    day: d.getDate(),
  };
  viewYear.value = d.getFullYear();
  viewMonth.value = d.getMonth();
  emitValue();
}

function setPresetTime(hours: number, minutes: number, period: 'AM' | 'PM') {
  selectedHour.value = hours;
  selectedMinute.value = minutes;
  selectedPeriod.value = period;
  emitValue();
}

function updateHour(val: number) {
  selectedHour.value = val;
  emitValue();
}

function updateMinute(val: number) {
  selectedMinute.value = val;
  emitValue();
}

function togglePeriod(period: 'AM' | 'PM') {
  selectedPeriod.value = period;
  emitValue();
}

// Summary formatting
const formattedSummary = computed(() => {
  if (!selectedDate.value) return 'No date selected';

  const dateObj = new Date(selectedDate.value.year, selectedDate.value.month, selectedDate.value.day);
  const dateString = new Intl.DateTimeFormat('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(dateObj);

  if (!props.includeTime) {
    return dateString;
  }

  const pad = (n: number) => String(n).padStart(2, '0');
  const timeString = `${pad(selectedHour.value)}:${pad(selectedMinute.value)} ${selectedPeriod.value}`;

  return `${dateString} at ${timeString}`;
});

const relativeLabel = computed(() => {
  if (!selectedDate.value) return '';

  const target = new Date(selectedDate.value.year, selectedDate.value.month, selectedDate.value.day);
  target.setHours(0, 0, 0, 0);

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const diffDays = Math.round((target.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));

  if (diffDays === 0) return 'Today';
  if (diffDays === 1) return 'Tomorrow';
  if (diffDays === 2) return 'In 2 days';
  if (diffDays > 2 && diffDays <= 7) return `In ${diffDays} days`;
  if (diffDays < 0) return 'Past date';
  return '';
});

// Minute presets
const minuteOptions = [0, 15, 30, 45];
</script>

<template>
  <div class="space-y-4 rounded-xl border border-border bg-surface p-4 shadow-sm">
    <!-- Header / Label -->
    <div class="flex items-center justify-between">
      <label class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-text/70">
        <Calendar class="h-3.5 w-3.5 text-accent" />
        {{ label }}
      </label>

      <!-- Relative indicator badge -->
      <span
        v-if="relativeLabel"
        class="inline-flex items-center rounded-md bg-accent/10 px-2 py-0.5 text-[11px] font-semibold text-accent">
        {{ relativeLabel }}
      </span>
    </div>

    <!-- Quick Date Shortcuts -->
    <div class="flex flex-wrap items-center gap-1.5 border-b border-border/60 pb-3">
      <span class="text-[11px] font-medium text-text/45">Quick:</span>
      <button
        type="button"
        class="rounded-md border border-border bg-bg/60 px-2 py-1 text-xs font-medium text-text/75 transition-colors hover:border-accent/40 hover:bg-accent/5 hover:text-accent cursor-pointer"
        @click="setQuickDate(0)">
        Today
      </button>
      <button
        type="button"
        class="rounded-md border border-border bg-bg/60 px-2 py-1 text-xs font-medium text-text/75 transition-colors hover:border-accent/40 hover:bg-accent/5 hover:text-accent cursor-pointer"
        @click="setQuickDate(1)">
        Tomorrow
      </button>
      <button
        type="button"
        class="rounded-md border border-border bg-bg/60 px-2 py-1 text-xs font-medium text-text/75 transition-colors hover:border-accent/40 hover:bg-accent/5 hover:text-accent cursor-pointer"
        @click="setQuickDate(2)">
        In 2 Days
      </button>
      <button
        type="button"
        class="rounded-md border border-border bg-bg/60 px-2 py-1 text-xs font-medium text-text/75 transition-colors hover:border-accent/40 hover:bg-accent/5 hover:text-accent cursor-pointer"
        @click="setQuickDate(7)">
        Next Week
      </button>
    </div>

    <!-- Month & Year Navigation Header -->
    <div class="flex items-center justify-between px-1">
      <button
        type="button"
        class="flex h-7 w-7 items-center justify-center rounded-lg border border-border bg-bg text-text/70 transition hover:border-accent/40 hover:text-accent cursor-pointer"
        title="Previous month"
        @click="prevMonth">
        <ChevronLeft class="h-4 w-4" />
      </button>

      <span class="font-display text-sm font-bold text-text">
        {{ monthNames[viewMonth] }} {{ viewYear }}
      </span>

      <button
        type="button"
        class="flex h-7 w-7 items-center justify-center rounded-lg border border-border bg-bg text-text/70 transition hover:border-accent/40 hover:text-accent cursor-pointer"
        title="Next month"
        @click="nextMonth">
        <ChevronRight class="h-4 w-4" />
      </button>
    </div>

    <!-- Day of Week Headers -->
    <div class="grid grid-cols-7 gap-1 text-center">
      <span
        v-for="d in dayLabels"
        :key="d"
        class="py-1 text-[11px] font-bold uppercase tracking-wider text-text/40">
        {{ d }}
      </span>
    </div>

    <!-- Days Matrix -->
    <div class="grid grid-cols-7 gap-1">
      <button
        v-for="(cell, idx) in calendarDays"
        :key="idx"
        type="button"
        :disabled="cell.isDisabled"
        class="relative flex h-8 w-full items-center justify-center rounded-lg text-xs font-medium transition-all"
        :class="[
          cell.isDisabled
            ? 'cursor-not-allowed opacity-25 text-text/30'
            : cell.isSelected
              ? 'bg-accent font-bold text-white shadow-sm ring-2 ring-accent/30'
              : cell.isCurrentMonth
                ? 'text-text hover:bg-accent/10 hover:text-accent cursor-pointer'
                : 'text-text/35 hover:bg-bg cursor-pointer',
          cell.isToday && !cell.isSelected ? 'border border-accent/40 font-bold text-accent' : '',
        ]"
        @click="selectDay(cell)">
        {{ cell.day }}
      </button>
    </div>

    <!-- Time Selection Section -->
    <div v-if="includeTime" class="border-t border-border/70 pt-3 space-y-3">
      <div class="flex items-center justify-between">
        <span class="flex items-center gap-1.5 text-xs font-semibold text-text/70">
          <Clock class="h-3.5 w-3.5 text-accent" />
          Time
        </span>

        <!-- Time Presets -->
        <div class="flex items-center gap-1">
          <button
            type="button"
            class="rounded border border-border px-1.5 py-0.5 text-[10px] font-medium text-text/60 hover:border-accent/40 hover:text-accent cursor-pointer"
            @click="setPresetTime(9, 0, 'AM')">
            09:00 AM
          </button>
          <button
            type="button"
            class="rounded border border-border px-1.5 py-0.5 text-[10px] font-medium text-text/60 hover:border-accent/40 hover:text-accent cursor-pointer"
            @click="setPresetTime(11, 0, 'AM')">
            11:00 AM
          </button>
          <button
            type="button"
            class="rounded border border-border px-1.5 py-0.5 text-[10px] font-medium text-text/60 hover:border-accent/40 hover:text-accent cursor-pointer"
            @click="setPresetTime(2, 0, 'PM')">
            02:00 PM
          </button>
          <button
            type="button"
            class="rounded border border-border px-1.5 py-0.5 text-[10px] font-medium text-text/60 hover:border-accent/40 hover:text-accent cursor-pointer"
            @click="setPresetTime(4, 0, 'PM')">
            04:00 PM
          </button>
        </div>
      </div>

      <div class="flex items-center justify-center gap-3 rounded-lg border border-border bg-bg/50 p-2.5">
        <!-- Hour Selector -->
        <div class="flex items-center gap-1">
          <select
            :value="selectedHour"
            class="h-8 rounded-lg border border-border bg-surface px-2 text-center font-mono text-xs font-bold text-text outline-none focus:border-accent cursor-pointer"
            @change="updateHour(Number(($event.target as HTMLSelectElement).value))">
            <option v-for="h in 12" :key="h" :value="h">
              {{ String(h).padStart(2, '0') }}
            </option>
          </select>
          <span class="font-bold text-text/50">:</span>
          <!-- Minute Selector -->
          <select
            :value="selectedMinute"
            class="h-8 rounded-lg border border-border bg-surface px-2 text-center font-mono text-xs font-bold text-text outline-none focus:border-accent cursor-pointer"
            @change="updateMinute(Number(($event.target as HTMLSelectElement).value))">
            <option v-for="m in minuteOptions" :key="m" :value="m">
              {{ String(m).padStart(2, '0') }}
            </option>
          </select>
        </div>

        <!-- AM/PM Toggle Pill -->
        <div class="flex rounded-lg border border-border bg-surface p-0.5">
          <button
            type="button"
            class="flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-bold transition-all cursor-pointer"
            :class="
              selectedPeriod === 'AM'
                ? 'bg-accent text-white shadow-xs'
                : 'text-text/60 hover:text-text'
            "
            @click="togglePeriod('AM')">
            <Sun class="h-3 w-3" />
            AM
          </button>
          <button
            type="button"
            class="flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-bold transition-all cursor-pointer"
            :class="
              selectedPeriod === 'PM'
                ? 'bg-accent text-white shadow-xs'
                : 'text-text/60 hover:text-text'
            "
            @click="togglePeriod('PM')">
            <Moon class="h-3 w-3" />
            PM
          </button>
        </div>
      </div>
    </div>

    <!-- Selected Value Banner Preview -->
    <div class="flex items-center justify-between rounded-lg border border-accent/20 bg-accent/5 px-3 py-2 text-xs">
      <div class="flex items-center gap-2">
        <Calendar class="h-3.5 w-3.5 text-accent" />
        <span class="font-medium text-text/80">Selected:</span>
        <span class="font-semibold text-text">{{ formattedSummary }}</span>
      </div>
    </div>
  </div>
</template>
