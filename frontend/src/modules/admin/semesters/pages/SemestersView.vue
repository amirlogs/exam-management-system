<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Archive, Building, Edit, Lock, LockOpen, Plus, RotateCcw } from 'lucide-vue-next';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseDialog from '@/shared/components/ui/BaseDialog.vue';
import AppPagination from '@/shared/components/AppPagination.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';

import { useResourceForm } from '@/shared/composables/useResourceForm';
import { semesterSchema } from '../schemas/semester.schema';

import { getSemesters, getArchivedSemesters, createSemester, updateSemester, openSemester, closeSemester, archiveSemester, restoreSemester, type SemesterFilters } from '../api/semesters';

import { handleApiError } from '@/shared/utils/apiError';

import type { Semester, SemesterStatus } from '../types/semester';
import type { Pagination } from '@/shared/composables/useCrudResource';

import { useUiStore } from '@/stores/ui';

const uiStore = useUiStore();

const semesters = ref<Semester[]>([]);
const activeSemester = ref<Semester | null>(null);
const loadingActive = ref(false);

const showArchived = ref(false);
const loading = ref(false);
const refreshing = ref(false);
const activeTotal = ref(0);
const archivedTotal = ref(0);

// Search & Backend filters: academic_year, status, search
const searchQuery = ref('');
const selectedStatus = ref('');
const selectedAcademicYear = ref('');

const statusFilterOptions = [
  { value: '', label: 'All statuses' },
  { value: 'upcoming', label: 'Upcoming' },
  { value: 'active', label: 'Active' },
  { value: 'completed', label: 'Completed' },
];

const hasActiveFilters = computed(() => !!selectedStatus.value || !!selectedAcademicYear.value);

const filterCount = computed(() => {
  let count = 0;
  if (selectedStatus.value) count++;
  if (selectedAcademicYear.value) count++;
  return count;
});

function clearFilters() {
  selectedStatus.value = '';
  selectedAcademicYear.value = '';
  searchQuery.value = '';
  load(1);
}

function onSearchChange(value: string) {
  searchQuery.value = value;
  load(1);
}

function onFilterChange() {
  load(1);
}

const emptyPagination = (): Pagination => ({
  current_page: 1,
  last_page: 1,
  per_page: 12,
  total: 0,
  from: null,
  to: null,
});

const pagination = ref<Pagination>(emptyPagination());

const openMenuId = ref<number | null>(null);
const menuPosition = ref({
  top: 0,
  left: 0,
});

const showFormModal = ref(false);
const showArchiveModal = ref(false);
const showToggleModal = ref(false);

const selected = ref<Semester | null>(null);
const semesterToToggle = ref<Semester | null>(null);

const saving = ref(false);
const archiving = ref(false);
const restoring = ref(false);
const toggling = ref(false);

const { form, errors, validate, reset, applyServerErrors } = useResourceForm(semesterSchema, {
  academic_year: new Date().getFullYear(),
  name: 1,
  start_date: '',
  end_date: '',
});

const nameOptions = [
  {
    value: '1',
    label: 'Semester 1',
  },
  {
    value: '2',
    label: 'Semester 2',
  },
];

function semesterTitle(semester?: Semester | null) {
  if (!semester) return '';
  return `Semester ${semester.name} · ${semester.academic_year}`;
}

function statusVariant(status: SemesterStatus) {
  if (status === 'active') return 'success';
  if (status === 'completed') return 'neutral';

  return 'info';
}

function statusLabel(status: SemesterStatus) {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

function toDateInput(val?: string | null): string {
  if (!val) return '';
  if (/^\d{4}-\d{2}-\d{2}/.test(val)) return val.slice(0, 10);
  const parsed = new Date(val);
  if (isNaN(parsed.getTime())) return '';
  return parsed.toISOString().slice(0, 10);
}

async function loadActiveSemester() {
  loadingActive.value = true;
  try {
    const res = await getSemesters(1, 1, { status: 'active' });
    activeSemester.value = res.data?.[0] ?? null;
  } catch {
    activeSemester.value = null;
  } finally {
    loadingActive.value = false;
  }
}

async function updateArchivedCount() {
  try {
    const res = await getArchivedSemesters(1, 1);
    archivedTotal.value = res.pagination?.total ?? res.data?.length ?? 0;
  } catch {}
}

async function load(page = 1) {
  loading.value = true;

  try {
    const filters: SemesterFilters = {
      status: selectedStatus.value || undefined,
      academic_year: selectedAcademicYear.value ? Number(selectedAcademicYear.value) : undefined,
      search: searchQuery.value.trim() || undefined,
    };

    const response = showArchived.value
      ? await getArchivedSemesters(page, 12, filters)
      : await getSemesters(page, 12, filters);

    semesters.value = response.data ?? [];
    pagination.value = response.pagination ?? emptyPagination();

    if (!hasActiveFilters.value && !searchQuery.value.trim()) {
      if (showArchived.value) {
        archivedTotal.value = response.pagination?.total ?? response.data?.length ?? 0;
      } else {
        activeTotal.value = response.pagination?.total ?? response.data?.length ?? 0;
      }
    }
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to load semesters.');
  } finally {
    loading.value = false;
  }
}

async function refresh() {
  refreshing.value = true;

  try {
    await Promise.all([
      loadActiveSemester(),
      load(pagination.value.current_page),
      updateArchivedCount(),
    ]);
  } finally {
    refreshing.value = false;
  }
}

function toggleArchivedView(value?: boolean) {
  showArchived.value = typeof value === 'boolean' ? value : !showArchived.value;

  openMenuId.value = null;

  load(1);
}

function toggleMenu(id: number, event: MouseEvent) {
  if (openMenuId.value === id) {
    openMenuId.value = null;
    return;
  }

  const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();

  const menuHeight = 170;

  const openUpward = window.innerHeight - rect.bottom < menuHeight;

  menuPosition.value = {
    top: openUpward ? rect.top - menuHeight - 4 : rect.bottom + 4,
    left: rect.right - 180,
  };

  openMenuId.value = id;
}

function closeMenus() {
  openMenuId.value = null;
}

function handleClickOutside(event: MouseEvent) {
  const target = event.target as HTMLElement;

  if (!target.closest('[data-action-menu]') && !target.closest('[data-action-trigger]')) {
    closeMenus();
  }
}

function openCreate() {
  selected.value = null;

  reset({
    academic_year: new Date().getFullYear(),
    name: 1,
    start_date: '',
    end_date: '',
  });

  showFormModal.value = true;
}

function openEdit(semester: Semester) {
  selected.value = semester;

  reset({
    academic_year: Number(semester.academic_year),
    name: Number(semester.name),
    start_date: toDateInput(semester.start_date),
    end_date: toDateInput(semester.end_date),
  });

  showFormModal.value = true;
  openMenuId.value = null;
}

function closeFormModal() {
  showFormModal.value = false;
  selected.value = null;
}

async function submitForm() {
  if (!validate()) return;

  saving.value = true;

  const isEditing = !!selected.value;

  try {
    if (selected.value) {
      await updateSemester(selected.value.id, form);
    } else {
      await createSemester(form);
    }

    closeFormModal();

    uiStore.showToast(isEditing ? 'Semester updated.' : 'Semester created.', 'success');

    await Promise.all([
      loadActiveSemester(),
      load(selected.value ? pagination.value.current_page : 1),
      updateArchivedCount(),
    ]);
  } catch (error) {
    handleApiError(error, uiStore, applyServerErrors, isEditing ? 'Failed to update semester.' : 'Failed to create semester.');
  } finally {
    saving.value = false;
  }
}

function askToggleOpen(semester: Semester) {
  semesterToToggle.value = semester;
  showToggleModal.value = true;
  openMenuId.value = null;
}

async function confirmToggleOpen() {
  if (!semesterToToggle.value) return;

  const semester = semesterToToggle.value;

  toggling.value = true;

  try {
    if (semester.status === 'active') {
      await closeSemester(semester.id);

      uiStore.showToast('Semester closed.', 'success');
    } else {
      await openSemester(semester.id);

      uiStore.showToast('Semester opened.', 'success');
    }

    showToggleModal.value = false;
    semesterToToggle.value = null;

    await Promise.all([
      loadActiveSemester(),
      load(pagination.value.current_page),
    ]);
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to update semester status.');
  } finally {
    toggling.value = false;
  }
}

function openArchive(semester: Semester) {
  selected.value = semester;
  showArchiveModal.value = true;
  openMenuId.value = null;
}

async function confirmArchive() {
  if (!selected.value) return;

  archiving.value = true;

  try {
    await archiveSemester(selected.value.id);

    showArchiveModal.value = false;
    selected.value = null;

    uiStore.showToast('Semester archived.', 'success');

    await Promise.all([
      loadActiveSemester(),
      load(pagination.value.current_page),
      updateArchivedCount(),
    ]);
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to archive semester.');
  } finally {
    archiving.value = false;
  }
}

async function restoreOne(semester: Semester) {
  restoring.value = true;

  try {
    await restoreSemester(semester.id);

    uiStore.showToast('Semester restored.', 'success');

    await Promise.all([
      loadActiveSemester(),
      load(pagination.value.current_page),
      updateArchivedCount(),
    ]);
  } catch (error) {
    handleApiError(error, uiStore, undefined, 'Failed to restore semester.');
  } finally {
    restoring.value = false;
    openMenuId.value = null;
  }
}

onMounted(async () => {
  await Promise.all([
    loadActiveSemester(),
    load(1),
  ]);

  updateArchivedCount();

  document.addEventListener('click', handleClickOutside);

  window.addEventListener('scroll', closeMenus, true);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);

  window.removeEventListener('scroll', closeMenus, true);
});
</script>

<template>
  <div class="mx-auto w-full max-w-260 space-y-6 px-6 py-6">
    <!-- ========================================================= -->
    <!-- ACTIVE SEMESTER HERO CARD (ABOVE)                         -->
    <!-- ========================================================= -->
    <section
      v-if="activeSemester"
      class="relative overflow-hidden rounded-2xl border border-border bg-surface p-6 sm:p-8 shadow-xs">
      <div class="flex flex-col justify-between gap-6 md:flex-row md:items-start">
        <div class="flex-1 min-w-0">
          <div class="mb-3 flex flex-wrap items-center gap-2.5">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-success/30 bg-success/10 px-3 py-1">
              <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-success"></span>
              </span>
              <span class="text-xs font-bold uppercase tracking-wider text-success">Active Semester</span>
            </span>

            <span class="text-xs font-semibold uppercase tracking-wider text-text/50">
              Academic year {{ activeSemester.academic_year }}
            </span>
          </div>

          <h2 class="font-display text-2xl sm:text-3xl font-bold text-text">
            {{ semesterTitle(activeSemester) }}
          </h2>

          <div class="mt-5 flex flex-wrap items-center gap-6 sm:gap-10">
            <div>
              <p class="mb-1 text-xs font-bold uppercase tracking-wider text-text/50">Start date</p>
              <p class="font-mono text-sm font-medium text-text">
                {{ activeSemester.start_date ? activeSemester.start_date.slice(0, 10) : '—' }}
              </p>
            </div>

            <div>
              <p class="mb-1 text-xs font-bold uppercase tracking-wider text-text/50">End date</p>
              <p class="font-mono text-sm font-medium text-text">
                {{ activeSemester.end_date ? activeSemester.end_date.slice(0, 10) : '—' }}
              </p>
            </div>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-2 pt-1">
          <BaseButton
            v-can="'semester.update'"
            variant="ghost"
            size="sm"
            @click="openEdit(activeSemester)">
            <template #icon>
              <Edit class="h-4 w-4" />
            </template>
            Edit
          </BaseButton>

          <BaseButton
            v-can="'semester.close'"
            :loading="toggling"
            variant="secondary"
            @click="askToggleOpen(activeSemester)">
            <template #icon>
              <Lock class="h-4 w-4" />
            </template>
            Close semester
          </BaseButton>
        </div>
      </div>
    </section>

    <!-- INACTIVE NOTICE (WHEN NO SEMESTER IS ACTIVE) -->
    <section
      v-else-if="!loadingActive"
      class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border border-dashed border-border bg-surface/60 p-6">
      <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border bg-bg text-text/40">
          <Building class="h-5 w-5" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-text">No Active Semester</h3>
          <p class="text-xs text-text/55 mt-0.5">
            There is currently no academic term set to active. You can open an upcoming semester below or create a new one.
          </p>
        </div>
      </div>
      <BaseButton v-can="'semester.create'" variant="secondary" size="sm" @click="openCreate">
        <template #icon>
          <Plus class="h-3.5 w-3.5" />
        </template>
        New semester
      </BaseButton>
    </section>

    <!-- ========================================================= -->
    <!-- SEMESTERS DIRECTORY (TOOLBAR, TABS, FILTERS & TABLE)      -->
    <!-- ========================================================= -->
    <div class="space-y-4">
      <ResourceToolbar
        title="Semesters"
        description="Manage academic terms, upcoming sessions, and semester records."
        :show-search="true"
        :search="searchQuery"
        search-placeholder="Search semesters..."
        :show-tabs="true"
        :active-tab="showArchived ? 'archived' : 'active'"
        :active-count="activeTotal"
        :archived-count="archivedTotal"
        :show-filter="true"
        :has-active-filters="hasActiveFilters"
        :filter-count="filterCount"
        :show-refresh="true"
        :refreshing="loading || refreshing"
        @update:search="onSearchChange"
        @change-tab="(tab) => toggleArchivedView(tab === 'archived')"
        @clear-filters="clearFilters"
        @refresh="refresh">
        <template #actions>
          <BaseButton v-if="!showArchived" v-can="'semester.create'" variant="secondary" @click="openCreate">
            <template #icon>
              <Plus class="h-4 w-4" />
            </template>
            New semester
          </BaseButton>
        </template>

        <template #filters>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-xs font-semibold text-text/60">Status</label>
              <BaseSelect
                v-model="selectedStatus"
                :options="statusFilterOptions"
                placeholder="All statuses"
                @update:model-value="onFilterChange" />
            </div>

            <div>
              <label class="mb-1.5 block text-xs font-semibold text-text/60">Academic Year</label>
              <BaseInput
                v-model="selectedAcademicYear"
                type="number"
                placeholder="e.g. 2026"
                @update:model-value="onFilterChange" />
            </div>
          </div>
        </template>
      </ResourceToolbar>

      <div class="overflow-hidden rounded-xl border border-border bg-surface">
        <table class="w-full border-collapse">
          <thead>
            <tr class="border-b border-border bg-text/2.5">
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Semester</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Academic Year</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Status</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Duration</th>
              <th class="w-16 px-4 py-3 text-right">
                <span class="sr-only"> Actions </span>
              </th>
            </tr>
          </thead>

          <tbody v-if="loading" class="divide-y divide-border">
            <tr v-for="row in 4" :key="row">
              <td colspan="5" class="px-4 py-4">
                <div class="h-3.5 w-full max-w-xs animate-pulse rounded bg-text/5" />
              </td>
            </tr>
          </tbody>

          <tbody v-else-if="semesters.length" class="divide-y divide-border">
            <tr v-for="semester in semesters" :key="semester.id" class="transition-colors hover:bg-text/2">
              <td class="px-4 py-3.5 text-sm font-medium text-text">
                Semester {{ semester.name }}
              </td>

              <td class="px-4 py-3.5 font-mono text-xs text-text/70">
                {{ semester.academic_year }}
              </td>

              <td class="px-4 py-3.5">
                <BaseBadge :variant="statusVariant(semester.status)">
                  {{ statusLabel(semester.status) }}
                </BaseBadge>
              </td>

              <td class="px-4 py-3.5 font-mono text-xs text-text/60">
                {{ semester.start_date ? semester.start_date.slice(0, 10) : '—' }}
                –
                {{ semester.end_date ? semester.end_date.slice(0, 10) : '—' }}
              </td>

              <td class="px-4 py-3.5 text-right">
                <button
                  type="button"
                  data-action-trigger
                  class="inline-flex h-8 w-8 items-center justify-center rounded-md text-text/50 transition hover:bg-text/5 hover:text-text"
                  @click.stop="toggleMenu(semester.id, $event)">
                  <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="5" r="1.5" />
                    <circle cx="12" cy="12" r="1.5" />
                    <circle cx="12" cy="19" r="1.5" />
                  </svg>
                </button>

                <Teleport to="body">
                  <div
                    v-if="openMenuId === semester.id"
                    data-action-menu
                    class="fixed z-[100] w-44 rounded-md border border-border bg-surface py-1 shadow-lg"
                    :style="{
                      top: menuPosition.top + 'px',
                      left: menuPosition.left + 'px',
                    }"
                    @click.stop>
                    <template v-if="!showArchived">
                      <button
                        v-can="'semester.update'"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-text hover:bg-text/5"
                        @click="openEdit(semester)">
                        <Edit class="h-4 w-4 text-text/50" />

                        Edit
                      </button>

                      <button
                        v-if="semester.status !== 'completed'"
                        v-can="semester.status === 'active' ? 'semester.close' : 'semester.open'"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-text hover:bg-text/5"
                        @click="askToggleOpen(semester)">
                        <Lock v-if="semester.status === 'active'" class="h-4 w-4 text-text/50" />

                        <LockOpen v-else class="h-4 w-4 text-text/50" />

                        {{ semester.status === 'active' ? 'Close' : 'Open' }}
                      </button>

                      <div class="my-1 border-t border-border" />

                      <button
                        v-can="'semester.archive'"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-error hover:bg-error/5"
                        @click="openArchive(semester)">
                        <Archive class="h-4 w-4" />

                        Archive
                      </button>
                    </template>

                    <button
                      v-else
                      v-can="'semester.restore'"
                      type="button"
                      class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-accent hover:bg-accent/5 disabled:opacity-50"
                      :disabled="restoring"
                      @click="restoreOne(semester)">
                      <RotateCcw class="h-4 w-4" />

                      Restore
                    </button>
                  </div>
                </Teleport>
              </td>
            </tr>
          </tbody>

          <tbody v-else>
            <tr>
              <td colspan="5" class="px-6 py-12 text-center text-sm text-text/55">
                {{ searchQuery.trim() || hasActiveFilters ? 'No matching semesters found.' : (showArchived ? 'No archived semesters found.' : 'No semesters found.') }}
              </td>
            </tr>
          </tbody>
        </table>

        <!-- PAGINATION -->
        <div class="border-t border-border px-4 py-3">
          <AppPagination :pagination="pagination" :loading="loading" @change-page="load" />
        </div>
      </div>
    </div>
  </div>

  <BaseDialog :model-value="showFormModal" :title="selected ? 'Edit semester' : 'New semester'" @update:model-value="closeFormModal">
    <div class="space-y-4">
      <BaseInput v-model.number="form.academic_year as any" type="number" label="Academic year" placeholder="2026" :error="errors.academic_year" />

      <BaseSelect v-model="form.name as any" label="Semester" :options="nameOptions" :error="errors.name" />

      <BaseInput v-model="form.start_date" type="date" label="Start date" :error="errors.start_date" />

      <BaseInput v-model="form.end_date" type="date" label="End date" :error="errors.end_date" />
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <BaseButton variant="secondary" @click="closeFormModal"> Cancel </BaseButton>

        <BaseButton v-can="selected ? 'semester.update' : 'semester.create'" :loading="saving" @click="submitForm">
          {{ selected ? 'Save changes' : 'Create semester' }}
        </BaseButton>
      </div>
    </template>
  </BaseDialog>

  <ConfirmModal
    :show="showToggleModal"
    :title="semesterToToggle?.status === 'active' ? 'Close this semester?' : 'Open this semester?'"
    :description="
      semesterToToggle?.status === 'active' ? 'This will mark the semester as completed.' : 'This becomes the active semester. Only one semester can be active at a time.'
    "
    :confirm-text="semesterToToggle?.status === 'active' ? 'Close semester' : 'Open semester'"
    variant="danger"
    :icon="semesterToToggle?.status === 'active' ? Lock : LockOpen"
    :loading="toggling"
    @close="showToggleModal = false"
    @confirm="confirmToggleOpen" />

  <ConfirmModal
    :show="showArchiveModal"
    title="Archive semester?"
    :description="`This will archive ${selected ? semesterTitle(selected) : ''}.`"
    confirm-text="Archive semester"
    variant="danger"
    :icon="Archive"
    :loading="archiving"
    @close="showArchiveModal = false"
    @confirm="confirmArchive" />
</template>
