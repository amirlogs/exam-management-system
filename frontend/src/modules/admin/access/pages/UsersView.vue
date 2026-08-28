<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';
import { Plus, MoreVertical, Edit, ShieldPlus, Ban, CheckCircle2 } from 'lucide-vue-next';
import BaseDialog from '@/shared/components/ui/BaseDialog.vue';
import AppPagination from '@/shared/components/AppPagination.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import ManageRolesModal from '../components/ManageRolesModal.vue';
import { useResourceForm } from '@/shared/composables/useResourceForm';
import { userSchema, editUserSchema } from '../schemas/access.schema';
import { getUsers, createUser, updateUser, disableUser, activateUser } from '../api/users';
import { handleApiError } from '@/shared/utils/apiError';
import type { User } from '../types/user';
import type { Pagination } from '@/shared/composables/useCrudResource';
import { useUiStore } from '@/stores/ui';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';
import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';

const uiStore = useUiStore();
const users = ref<User[]>([]);
const loading = ref(false);
const emptyPagination = (): Pagination => ({ current_page: 1, last_page: 1, per_page: 12, total: 0, from: null, to: null });
const pagination = ref<Pagination>(emptyPagination());

const openMenuId = ref<number | null>(null);
const menuPosition = ref({ top: 0, left: 0 });
const showFormModal = ref(false);
const showRolesModal = ref(false);
const showDisableModal = ref(false);
const selected = ref<User | null>(null);
const saving = ref(false);
const toggling = ref(false);
const search = ref('');
async function load(page = 1, search = '') {
  loading.value = true;
  try {
    const res = await getUsers(page, 12, search);
    users.value = res.data;
    pagination.value = res.pagination;
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to load users.');
  } finally {
    loading.value = false;
  }
}
onMounted(() => load(1));

function toggleMenu(id: number, event: MouseEvent) {
  if (openMenuId.value === id) {
    openMenuId.value = null;
    return;
  }
  const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
  const menuHeight = 180;
  const openUpward = window.innerHeight - rect.bottom < menuHeight;
  menuPosition.value = { top: openUpward ? rect.top - menuHeight - 4 : rect.bottom + 4, left: rect.right - 180 };
  openMenuId.value = id;
}
function closeMenus() {
  openMenuId.value = null;
}

// Create/edit form — same field set toggled by whether `selected` is set
const isEditing = computed(() => !!selected.value);
const activeSchema = computed(() => (isEditing.value ? editUserSchema : userSchema));
const { form, errors, validate, reset, applyServerErrors } = useResourceForm(userSchema, {
  first_name: '',
  last_name: '',
  email: '',
  password: '',
});

function openCreate() {
  selected.value = null;
  reset({ first_name: '', last_name: '', email: '', password: '' });
  showFormModal.value = true;
}

function openEdit(user: User) {
  selected.value = user;
  reset({ first_name: user.first_name, last_name: user.last_name, email: user.email, password: '' });
  showFormModal.value = true;
  openMenuId.value = null;
}

function closeFormModal() {
  showFormModal.value = false;
  selected.value = null;
}

async function submitForm() {
  const result = activeSchema.value.safeParse(form);
  if (!result.success) {
    validate();
    return;
  }
  saving.value = true;
  try {
    if (selected.value) {
      await updateUser(selected.value.id, { first_name: form.first_name, last_name: form.last_name });
    } else {
      await createUser(form);
    }
    closeFormModal();
    saving.value = false;
    uiStore.showToast(isEditing.value ? 'User updated.' : 'User created.', 'success');
await load(pagination.value.current_page, search.value);
  } catch (err: any) {
    saving.value = false;
    if (err?.response?.status === 422) {
      applyServerErrors(err.response.data?.errors);
      uiStore.showToast('Please fix the errors below.', 'error');
    } else {
      uiStore.showToast(isEditing.value ? 'Failed to update user.' : 'Failed to create user.', 'error');
    }
  }
}

function openManageRoles(user: User) {
  selected.value = user;
  showRolesModal.value = true;
  openMenuId.value = null;
}

function onRolesUpdated(updated: User) {
  const idx = users.value.findIndex((u) => u.id === updated.id);
  if (idx !== -1) users.value[idx] = updated;
}

function openToggleStatus(user: User) {
  selected.value = user;
  showDisableModal.value = true;
  openMenuId.value = null;
}

async function confirmToggleStatus() {
  if (!selected.value) return;
  toggling.value = true;
  const isActive = selected.value.is_active !== false;
  try {
    const updated = isActive ? await disableUser(selected.value.id) : await activateUser(selected.value.id);
    const idx = users.value.findIndex((u) => u.id === updated.id);
    if (idx !== -1) users.value[idx] = updated;
    showDisableModal.value = false;
    uiStore.showToast(isActive ? 'User disabled.' : 'User activated.', 'success');
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to update user status.');
  } finally {
    toggling.value = false;
  }
}

function initials(u: User) {
  const a = u.first_name?.[0] ?? '';
  const b = u.last_name?.[0] ?? '';
  const combined = (a + b).toUpperCase();
  return combined || '—';
}


const refreshing = ref(false);

//pass searchQuery.value to the serch
const activeView = ref<'active' | 'archived'>('active');


function handleSearch(query: string) {
  searchQuery.value = query;
  load(1);
}

async function handleRefresh() {
  refreshing.value = true;
  await load(pagination.value.current_page, search.value);
  refreshing.value = false;
}
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6">
      <ResourceToolbar
        title="Users"
        description="Manage institutional accounts and role assignments."
        search-placeholder="Search by name or email…"
        :search="search"
        :show-search="true"
        :show-refresh="true"
        :refreshing="refreshing"
        @update:search="(value) => {
          search = value;
          load(1, value);
        }"
        @refresh="handleRefresh">
      <template #actions>
        <BaseButton @click="openCreate">
          <template #icon><Plus class="h-4 w-4" /></template>
          Add user
        </BaseButton>
      </template>
    </ResourceToolbar>
    <div class="rounded-md border border-border bg-surface">
      <div class="overflow-x-auto">
        <table class="w-full min-w-190 border-collapse">
          <thead>
            <tr class="border-b border-border bg-text/2.5">
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">User</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Roles</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-text/50">Status</th>
              <th class="w-16 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-text/50">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody v-if="loading" class="divide-y divide-border">
            <tr v-for="row in 6" :key="row">
              <td colspan="4" class="px-4 py-4">
                <div class="h-3.5 w-full max-w-sm animate-pulse rounded bg-text/5" />
              </td>
            </tr>
          </tbody>
          <tbody v-else-if="users.length" class="divide-y divide-border">
            <tr v-for="user in users" :key="user.id" class="group hover:bg-text/2">
              <td class="px-4 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent/10 text-xs font-bold text-accent">
                    {{ initials(user) }}
                  </div>
                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-text">{{ user.first_name }} {{ user.last_name }}</p>
                    <p class="truncate text-xs text-text/50">{{ user.email }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3.5">
                <div class="flex flex-wrap gap-1">
                  <BaseBadge v-for="r in user.role_name" :key="r" variant="neutral" class="text-[10px]">{{ r }}</BaseBadge>
                  <span v-if="!user.role_name.length" class="text-xs text-text/40">No roles</span>
                </div>
              </td>
              <td class="px-4 py-3.5">
                <div class="flex items-center gap-1.5 text-xs">
                  <span class="h-2 w-2 rounded-full" :class="user.is_active === false ? 'bg-text/30' : 'bg-success'" />
                  {{ user.is_active === false ? 'Disabled' : 'Active' }}
                </div>
              </td>
              <td class="px-4 py-3.5 text-right">
                <button
                  type="button"
                  data-action-trigger
                  class="inline-flex h-8 w-8 items-center justify-center rounded-md text-text/50 hover:bg-text/5 hover:text-text"
                  @click.stop="toggleMenu(user.id, $event)">
                  <MoreVertical class="h-4 w-4" />
                </button>
                <Teleport to="body">
                  <div
                    v-if="openMenuId === user.id"
                    data-action-menu
                    class="fixed z-[100] w-48 rounded-md border border-border bg-surface py-1 shadow-lg"
                    :style="{ top: menuPosition.top + 'px', left: menuPosition.left + 'px' }"
                    @click.stop>
                    <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-text hover:bg-text/5" @click="openEdit(user)">
                      <Edit class="h-4 w-4 text-text/50" /> Edit
                    </button>
                    <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-text hover:bg-text/5" @click="openManageRoles(user)">
                      <ShieldPlus class="h-4 w-4 text-text/50" /> Manage roles
                    </button>
                    <div class="my-1 border-t border-border" />
                    <button
                      v-if="user.is_active !== false"
                      type="button"
                      class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-error hover:bg-error/5"
                      @click="openToggleStatus(user)">
                      <Ban class="h-4 w-4" /> Disable
                    </button>
                    <button v-else type="button" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-success hover:bg-success/5" @click="openToggleStatus(user)">
                      <CheckCircle2 class="h-4 w-4" /> Activate
                    </button>
                  </div>
                </Teleport>
              </td>
            </tr>
          </tbody>
          <tbody v-else>
            <tr>
              <td colspan="4" class="px-6 py-12 text-center text-sm text-text/55">No users yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <AppPagination :pagination="pagination" @change-page="(page) => load(page, search)" />
    </div>
  </div>

  <BaseDialog :model-value="showFormModal" :title="isEditing ? 'Edit user' : 'Add user'" @update:model-value="closeFormModal">
    <div class="space-y-4">
      <BaseInput v-model="form.first_name" label="First name" :error="errors.first_name" />
      <BaseInput v-model="form.last_name" label="Last name" :error="errors.last_name" />
      <BaseInput v-if="!isEditing" v-model="form.email" type="email" label="Email" :error="errors.email" />
      <BaseInput v-if="!isEditing" v-model="form.password" type="password" label="Temporary password" :error="errors.password" />
    </div>
    <template #footer>
      <div class="flex justify-end gap-2">
        <BaseButton variant="secondary" @click="closeFormModal">Cancel</BaseButton>
        <BaseButton :loading="saving" @click="submitForm">{{ isEditing ? 'Save changes' : 'Create user' }} </BaseButton>
      </div>
    </template>
  </BaseDialog>

  <ManageRolesModal v-model="showRolesModal" :user="selected" @updated="onRolesUpdated" />

  <ConfirmModal
    :show="showDisableModal"
    :title="selected?.is_active === false ? 'Activate this user?' : 'Disable this user?'"
    :description="selected?.is_active === false ? `${selected?.first_name} will regain access to the system.` : `${selected?.first_name} will no longer be able to log in.`"
    :confirm-text="selected?.is_active === false ? 'Activate' : 'Disable'"
    variant="danger"
    :icon="Ban"
    :loading="toggling"
    @close="showDisableModal = false"
    @confirm="confirmToggleStatus" />
</template>
