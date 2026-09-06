<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import { RefreshCw, Search, SlidersHorizontal, Columns3, Maximize2, Minimize2, X, Archive, Building, RotateCcw } from 'lucide-vue-next';

const props = withDefaults(
  defineProps<{
    title: string;
    description?: string;
    searchPlaceholder?: string;
    search?: string;
    searchDebounce?: number;

    showSearch?: boolean;
    showFilter?: boolean;
    showRefresh?: boolean;
    showColumns?: boolean;
    showFullscreen?: boolean;

    refreshing?: boolean;

    showTabs?: boolean;
    activeTab?: 'active' | 'archived';
    activeCount?: number;
    archivedCount?: number;

    hasActiveFilters?: boolean;
    filterCount?: number;
  }>(),
  {
    showSearch: false,
    showFilter: false,
    showRefresh: false,
    showColumns: false,
    showFullscreen: false,
    refreshing: false,

    showTabs: false,
    activeTab: 'active',
    activeCount: 0,
    archivedCount: 0,

    hasActiveFilters: false,
    filterCount: 0,

    search: '',
    searchDebounce: 400,
  },
);

const emit = defineEmits<{
  'update:search': [string];
  refresh: [];
  columns: [];
  fullscreen: [];
  'change-tab': ['active' | 'archived'];
  'clear-filters': [];
}>();

const filterOpen = ref(false);
const isFullscreen = ref(false);
const localSearch = ref(props.search);
let searchTimer: ReturnType<typeof setTimeout> | null = null;

const filterButtonLabel = computed(() => {
  return filterOpen.value ? 'Hide Filter' : 'Filter';
});

watch(
  () => props.search,
  (value) => {
    if (value !== localSearch.value) {
      localSearch.value = value;
    }
  },
);

function handleSearch(value: string) {
  localSearch.value = value;

  if (searchTimer) {
    clearTimeout(searchTimer);
  }

  searchTimer = setTimeout(() => {
    emit('update:search', value);
    searchTimer = null;
  }, props.searchDebounce);
}

function toggleFilter() {
  filterOpen.value = !filterOpen.value;
}

function closeFilter() {
  filterOpen.value = false;
}

function resetFilters() {
  emit('clear-filters');
}

function handleClickOutside(event: MouseEvent) {
  if (!filterOpen.value) return;

  const target = event.target as Node | null;
  const filterContainer = document.querySelector('[data-resource-filter]');
  const filterButton = document.querySelector('[data-resource-filter-trigger]');

  if (target && filterContainer && filterButton && !filterContainer.contains(target) && !filterButton.contains(target)) {
    closeFilter();
  }
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && filterOpen.value) {
    closeFilter();
  }
}

async function toggleFullscreen() {
  try {
    if (!document.fullscreenElement) {
      await document.documentElement.requestFullscreen();
    } else {
      await document.exitFullscreen();
    }
  } catch (error) {
    console.error('Fullscreen error:', error);
  }
}

function handleFullscreenChange() {
  isFullscreen.value = !!document.fullscreenElement;
}

onMounted(() => {
  document.addEventListener('fullscreenchange', handleFullscreenChange);
  document.addEventListener('click', handleClickOutside);
  document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
  if (searchTimer) {
    clearTimeout(searchTimer);
    searchTimer = null;
  }

  document.removeEventListener('fullscreenchange', handleFullscreenChange);
  document.removeEventListener('click', handleClickOutside);
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0">
        <h1 class="font-display text-2xl font-bold text-text">
          {{ title }}
        </h1>

        <p v-if="description" class="mt-1 text-sm text-text/60">
          {{ description }}
        </p>
      </div>

      <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2 sm:pt-1">
        <slot name="actions" />
      </div>
    </div>

    <div v-if="showTabs" class="border-b border-border">
      <div class="flex min-w-0 items-center gap-6">
        <button
          type="button"
          class="relative flex h-10 shrink-0 items-center gap-2 text-sm font-medium transition-colors"
          :class="activeTab === 'active' ? 'text-accent' : 'text-text/60 hover:text-text'"
          @click="emit('change-tab', 'active')">
          <Building class="h-4 w-4" />

          <span>Active</span>

          <span class="rounded-full px-2 py-0.5 text-xs tabular-nums" :class="activeTab === 'active' ? 'bg-accent/10 text-accent' : 'bg-text/5 text-text/55'">
            {{ activeCount }}
          </span>

          <span v-if="activeTab === 'active'" class="absolute inset-x-0 -bottom-px h-0.5 rounded-full bg-accent" />
        </button>

        <button
          type="button"
          class="relative flex h-10 shrink-0 items-center gap-2 text-sm font-medium transition-colors"
          :class="activeTab === 'archived' ? 'text-accent' : 'text-text/60 hover:text-text'"
          @click="emit('change-tab', 'archived')">
          <Archive class="h-4 w-4" />

          <span>Archived</span>

          <span class="rounded-full px-2 py-0.5 text-xs tabular-nums" :class="activeTab === 'archived' ? 'bg-accent/10 text-accent' : 'bg-text/5 text-text/55'">
            {{ archivedCount }}
          </span>

          <span v-if="activeTab === 'archived'" class="absolute inset-x-0 -bottom-px h-0.5 rounded-full bg-accent" />
        </button>
      </div>
    </div>

    <div v-if="showSearch || showFilter || showRefresh || showColumns || showFullscreen" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div v-if="showSearch" class="relative w-full sm:w-80">
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text/35" />

        <input
          type="text"
          :value="localSearch"
          :placeholder="searchPlaceholder || 'Search…'"
          class="w-full rounded-lg border border-border bg-surface py-2 pl-9 pr-4 text-sm text-text outline-none transition placeholder:text-text/40 focus:border-accent focus:ring-2 focus:ring-accent/10"
          @input="handleSearch(($event.target as HTMLInputElement).value)" />
      </div>

      <div class="ml-auto flex shrink-0 items-center gap-2">
        <button
          v-if="showFilter"
          type="button"
          data-resource-filter-trigger
          class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg border px-3 text-sm font-medium transition-colors"
          :class="
            filterOpen
              ? 'border-accent bg-accent/5 text-accent'
              : hasActiveFilters
                ? 'border-accent/30 bg-accent/5 text-accent hover:border-accent/50 hover:bg-accent/10'
                : 'border-border bg-surface text-text/70 hover:border-accent/40 hover:text-text'
          "
          :aria-expanded="filterOpen"
          aria-haspopup="true"
          @click.stop="toggleFilter">
          <X v-if="filterOpen" class="h-4 w-4" />

          <SlidersHorizontal v-else class="h-4 w-4" />

          <span>{{ filterButtonLabel }}</span>

          <span
            v-if="hasActiveFilters && !filterOpen"
            class="flex h-4 min-w-4 items-center justify-center rounded-full bg-accent px-1 text-[10px] font-semibold leading-none text-white">
            {{ filterCount || '' }}
          </span>
        </button>

        <button
          v-if="showRefresh"
          type="button"
          :disabled="refreshing"
          class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-text/60 transition-colors hover:border-accent/40 hover:text-accent disabled:opacity-50"
          title="Refresh"
          @click="emit('refresh')">
          <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': refreshing }" />
        </button>

        <button
          v-if="showColumns"
          type="button"
          class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-text/60 transition-colors hover:border-accent/40 hover:text-accent"
          title="Show/hide columns"
          @click="emit('columns')">
          <Columns3 class="h-4 w-4" />
        </button>

        <!-- <button -->
        <!--   v-if="showFullscreen" -->
        <!--   type="button" -->
        <!--   class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-text/60 transition-colors hover:border-accent/40 hover:text-accent" -->
        <!--   :title="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'" -->
        <!--   @click="toggleFullscreen"> -->
        <!--   <Minimize2 v-if="isFullscreen" class="h-4 w-4" /> -->
        <!---->
        <!--   <Maximize2 v-else class="h-4 w-4" /> -->
        <!-- </button> -->
      </div>
    </div>

    <div v-if="showFilter && filterOpen" data-resource-filter class="overflow-hidden rounded-lg border border-border bg-surface" @click.stop>
      <div class="flex items-center justify-between gap-4 border-b border-border bg-text/[0.02] px-4 py-3">
        <div class="flex items-center gap-2">
          <SlidersHorizontal class="h-4 w-4 text-text/50" />

          <div>
            <h2 class="text-sm font-semibold text-text">Filters</h2>
          </div>
        </div>

        <button
          v-if="hasActiveFilters"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-text/60 transition-colors hover:bg-text/5 hover:text-accent"
          title="Reset all filters"
          @click="resetFilters">
          <RotateCcw class="h-3.5 w-3.5" />

          <span>Reset</span>
        </button>
      </div>

      <div class="p-4">
        <slot name="filters">
          <p class="text-sm text-text/40">No filters configured for this page yet.</p>
        </slot>
      </div>
    </div>
  </div>
</template>
