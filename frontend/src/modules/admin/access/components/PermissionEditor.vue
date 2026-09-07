<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { ChevronDown, ChevronUp, Save, RotateCcw, ChevronsDownUp, ChevronsUpDown } from 'lucide-vue-next';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import { getPermissions } from '../api/permissions';
import { assignPermissions, removePermissions } from '../api/roles';
import type { Role } from '../types/role';
import type { Permission } from '../types/permission';
import { useUiStore } from '@/stores/ui';

const props = defineProps<{ role: Role }>();
const emit = defineEmits<{ updated: [Role] }>();

const uiStore = useUiStore();
const allPermissions = ref<Permission[]>([]);
const selectedIds = ref<Set<number>>(new Set());
const openCategories = ref<Set<string>>(new Set());
const saving = ref(false);
const loading = ref(true);

const grouped = computed(() => {
  const map = new Map<string, Permission[]>();

  for (const p of allPermissions.value) {
    if (!map.has(p.category)) map.set(p.category, []);
    map.get(p.category)!.push(p);
  }

  return map;
});

const allCategoriesOpen = computed(() => {
  return grouped.value.size > 0 && [...grouped.value.keys()].every((category) => openCategories.value.has(category));
});

onMounted(async () => {
  loading.value = true;

  try {
    allPermissions.value = await getPermissions();

    // Open every category by default — a collapsed category reads as "missing", not "hidden for space"
    openCategories.value = new Set(allPermissions.value.map((p) => p.category));
  } finally {
    loading.value = false;
  }
});

watch(
  () => props.role,
  (role) => {
    selectedIds.value = new Set(role.permissions.map((p) => p.id));
  },
  { immediate: true },
);

const hasChanges = computed(() => {
  const original = new Set(props.role.permissions.map((p) => p.id));

  if (original.size !== selectedIds.value.size) return true;

  for (const id of selectedIds.value) {
    if (!original.has(id)) return true;
  }

  return false;
});

const changeCount = computed(() => {
  const original = new Set(props.role.permissions.map((p) => p.id));
  const added = [...selectedIds.value].filter((id) => !original.has(id)).length;
  const removed = [...original].filter((id) => !selectedIds.value.has(id)).length;

  return added + removed;
});

function toggleCategory(cat: string) {
  openCategories.value.has(cat) ? openCategories.value.delete(cat) : openCategories.value.add(cat);
}

function expandAll() {
  openCategories.value = new Set(grouped.value.keys());
}

function collapseAll() {
  openCategories.value = new Set();
}

function toggleAllCategories() {
  allCategoriesOpen.value ? collapseAll() : expandAll();
}

function toggle(id: number) {
  selectedIds.value.has(id) ? selectedIds.value.delete(id) : selectedIds.value.add(id);
}

function toggleAllInCategory(cat: string, perms: Permission[]) {
  const allSelected = perms.every((p) => selectedIds.value.has(p.id));

  for (const p of perms) {
    allSelected ? selectedIds.value.delete(p.id) : selectedIds.value.add(p.id);
  }
}

function isAllSelected(perms: Permission[]) {
  return perms.every((p) => selectedIds.value.has(p.id));
}

function isPartiallySelected(perms: Permission[]) {
  const count = perms.filter((p) => selectedIds.value.has(p.id)).length;

  return count > 0 && count < perms.length;
}

function categorySelectedCount(perms: Permission[]) {
  return perms.filter((p) => selectedIds.value.has(p.id)).length;
}

async function save() {
  const original = new Set(props.role.permissions.map((p) => p.id));
  const toAdd = [...selectedIds.value].filter((id) => !original.has(id));
  const toRemove = [...original].filter((id) => !selectedIds.value.has(id));

  if (!toAdd.length && !toRemove.length) return;

  saving.value = true;

  try {
    let updated = props.role;

    if (toAdd.length) {
      updated = await assignPermissions(props.role.id, toAdd);
    }

    if (toRemove.length) {
      updated = await removePermissions(props.role.id, toRemove);
    }

    emit('updated', updated);
    uiStore.showToast('Permissions saved.', 'success');
  } catch (err: any) {
    uiStore.showToast(err?.response?.data?.message || 'Failed to save permissions.', 'error');
  } finally {
    saving.value = false;
  }
}

function discard() {
  selectedIds.value = new Set(props.role.permissions.map((p) => p.id));
}
</script>

<template>
  <div class="flex max-h-[calc(100vh-2rem)] flex-col rounded-lg border border-border bg-surface shadow-sm xl:max-h-[calc(100vh-4rem)]">
    <!-- Header -->
    <div class="shrink-0 border-b border-border bg-bg/50 px-5 py-4">
      <div class="flex items-center justify-between gap-4">
        <div class="min-w-0">
          <h3 class="font-display text-lg text-text">{{ role.name }}</h3>
          <p class="mt-0.5 text-xs text-text/55">{{ role.description || '—' }}</p>
        </div>

        <span class="shrink-0 rounded-full border border-border bg-surface px-2.5 py-1 text-xs font-semibold text-text/50"> {{ selectedIds.size }} selected </span>
      </div>

      <div v-if="!loading && grouped.size" class="mt-3 flex justify-end">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-text/55 transition-colors hover:bg-text/5 hover:text-accent"
          @click="toggleAllCategories">
          <ChevronsUpDown v-if="!allCategoriesOpen" class="h-3.5 w-3.5" />
          <ChevronsDownUp v-else class="h-3.5 w-3.5" />

          {{ allCategoriesOpen ? 'Collapse all' : 'Expand all' }}
        </button>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="flex-1 space-y-3 p-4">
      <div v-for="i in 4" :key="i" class="h-10 animate-pulse rounded-md bg-bg" />
    </div>

    <!-- Permission groups -->
    <div v-else class="min-h-0 flex-1 space-y-3 overflow-y-auto p-4">
      <div
        v-for="[category, perms] in grouped"
        :key="category"
        class="overflow-hidden rounded-lg border border-border transition-colors"
        :class="isAllSelected(perms) ? 'border-accent/30' : ''">
        <button type="button" class="flex w-full items-center justify-between bg-bg px-4 py-3 transition-colors hover:bg-bg/70" @click="toggleCategory(category)">
          <div class="flex items-center gap-2.5">
            <span class="text-xs font-bold uppercase tracking-wide text-text/70">{{ category }}</span>
            <span class="text-[11px] font-medium text-text/40">{{ categorySelectedCount(perms) }}/{{ perms.length }}</span>
          </div>

          <div class="flex items-center gap-3">
            <label class="flex cursor-pointer items-center gap-1.5 text-xs text-text/60" @click.stop>
              <input
                type="checkbox"
                :checked="isAllSelected(perms)"
                :indeterminate="isPartiallySelected(perms)"
                class="h-3.5 w-3.5 cursor-pointer rounded accent-accent"
                @change="toggleAllInCategory(category, perms)" />

              Select all
            </label>

            <component :is="openCategories.has(category) ? ChevronUp : ChevronDown" class="h-4 w-4 text-text/40" />
          </div>
        </button>

        <div v-if="openCategories.has(category)" class="divide-y divide-border bg-surface">
          <label v-for="p in perms" :key="p.id" class="flex cursor-pointer items-center justify-between gap-3 px-4 py-3 transition-colors hover:bg-bg/50">
            <div class="min-w-0">
              <p class="text-sm text-text">{{ p.name }}</p>

              <p v-if="p.description" class="mt-0.5 text-xs text-text/50">
                {{ p.description }}
              </p>
            </div>

            <input type="checkbox" :checked="selectedIds.has(p.id)" class="h-4 w-4 shrink-0 cursor-pointer rounded accent-accent" @change="toggle(p.id)" />
          </label>
        </div>
      </div>
    </div>

    <!-- Sticky save bar -->
    <div class="flex shrink-0 items-center justify-between gap-2 border-t border-border bg-bg/70 px-4 py-3">
      <span class="text-xs font-medium text-text/60">
        <template v-if="hasChanges"> {{ changeCount }} unsaved change{{ changeCount === 1 ? '' : 's' }} </template>

        <template v-else> No unsaved changes </template>
      </span>

      <div class="flex gap-2">
        <BaseButton variant="secondary" :disabled="!hasChanges || saving" @click="discard">
          <template #icon>
            <RotateCcw class="h-4 w-4" />
          </template>

          Discard
        </BaseButton>

        <BaseButton :disabled="!hasChanges" :loading="saving" @click="save">
          <template #icon>
            <Save class="h-4 w-4" />
          </template>

          Save permissions
        </BaseButton>
      </div>
    </div>
  </div>
</template>
