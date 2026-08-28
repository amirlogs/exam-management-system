<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';
import { Plus, ShieldCheck, Archive } from 'lucide-vue-next';
import BaseDialog from '@/shared/components/ui/BaseDialog.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseInput from '@/shared/components/ui/BaseInput.vue';
import PermissionEditor from '../components/PermissionEditor.vue';
import { useResourceForm } from '@/shared/composables/useResourceForm';
import { roleSchema } from '../schemas/access.schema';
import { getRoles, createRole, updateRole, deleteRole } from '../api/roles';
import { handleApiError } from '@/shared/utils/apiError';
import type { Role } from '../types/role';
import { useUiStore } from '@/stores/ui';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';

const uiStore = useUiStore();
const roles = ref<Role[]>([]);
const loading = ref(false);
const selectedRoleId = ref<number | null>(null);
const selectedRole = computed(() => roles.value.find((r) => r.id === selectedRoleId.value) ?? null);

const showFormModal = ref(false);
const showDeleteModal = ref(false);
const editing = ref<Role | null>(null);
const saving = ref(false);
const deleting = ref(false);

const { form, errors, validate, reset, applyServerErrors } = useResourceForm(roleSchema, { name: '', description: '' });

async function load() {
  loading.value = true;
  try {
    const res = await getRoles(1, 100);
    roles.value = res.data;
    if (!selectedRoleId.value && roles.value.length) selectedRoleId.value = roles.value[0].id;
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to load roles.');
  } finally {
    loading.value = false;
  }
}
onMounted(load);

function openCreate() {
  editing.value = null;
  reset({ name: '', description: '' });
  showFormModal.value = true;
}
function openEdit(role: Role) {
  editing.value = role;
  reset({ name: role.name, description: role.description });
  showFormModal.value = true;
}
function closeFormModal() {
  showFormModal.value = false;
  editing.value = null;
}

async function submitForm() {
  if (!validate()) return;

  const isEditing = !!editing.value;

  saving.value = true;

  try {
    if (isEditing) {
      const updated = await updateRole(editing.value!.id, form);

      const idx = roles.value.findIndex((r) => r.id === updated.id);

      if (idx !== -1) {
        roles.value[idx] = updated;
      }

      uiStore.showToast('Role updated.', 'success');
    } else {
      const created = await createRole(form);

      const newRole = {
        ...created,
        permissions: created.permissions ?? [],
      };

      roles.value = [newRole, ...roles.value];

      selectedRoleId.value = newRole.id;

      uiStore.showToast('Role created.', 'success');
    }

    closeFormModal();
  } catch (err: any) {
    if (err?.response?.status === 422) {
      applyServerErrors(err.response.data?.errors);
      uiStore.showToast('Please fix the errors below.', 'error');
    } else {
      uiStore.showToast(err?.response?.data?.message || 'Failed to save role.', 'error');
    }
  } finally {
    saving.value = false;
  }
}
function openDelete(role: Role) {
  editing.value = role;
  showDeleteModal.value = true;
}
async function confirmDelete() {
  if (!editing.value) return;
  deleting.value = true;
  try {
    await deleteRole(editing.value.id);
    showDeleteModal.value = false;
    deleting.value = false;
    uiStore.showToast('Role deleted.', 'success');
    const deletedId = editing.value.id;
    roles.value = roles.value.filter((r) => r.id !== deletedId);
    if (selectedRoleId.value === deletedId) selectedRoleId.value = roles.value[0]?.id ?? null;
  } catch (err: any) {
    deleting.value = false;
    uiStore.showToast(err?.response?.data?.message || 'Failed to delete role.', 'error');
  }
}

function onPermissionsUpdated(role: Role) {
  const idx = roles.value.findIndex((r) => r.id === role.id);
  if (idx !== -1) roles.value[idx] = role;
}
</script>

<template>
  <div class="mx-auto flex w-full max-w-360 flex-col xl:flex-row gap-6 px-4 sm:px-6 py-6 min-h-screen">
    <div class="flex-1 space-y-4 min-w-0">
      <div class="flex items-end justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold tracking-tight text-text">Roles & permissions</h1>
          <p class="mt-1 text-sm text-text/60">Manage access roles and what each one can do.</p>
        </div>
        <BaseButton variant="secondary" @click="openCreate">
          <template #icon><Plus class="h-4 w-4" /></template>
          New role
        </BaseButton>
      </div>

      <div v-if="loading" class="py-16 text-center text-sm text-text/50">Loading roles...</div>
      <div v-else-if="!roles.length" class="py-16 text-center text-sm text-text/50">No roles yet.</div>
      <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <button
          v-for="role in roles"
          :key="role.id"
          type="button"
          class="rounded-md border p-4 text-left transition-colors"
          :class="selectedRoleId === role.id ? 'border-accent bg-accent/5' : 'border-border bg-surface hover:border-accent/50'"
          @click="selectedRoleId = role.id">
          <div class="mb-2 flex items-start justify-between">
            <h3 class="font-display text-base text-text">{{ role.name }}</h3>
            <ShieldCheck class="h-4 w-4 shrink-0 text-accent" />
          </div>
          <p class="mb-3 line-clamp-2 text-xs text-text/55">{{ role.description || '—' }}</p>
          <div class="flex items-center justify-between text-xs">
            <span class="rounded bg-bg px-2 py-0.5 font-mono text-text/60">{{ role.permissions.length }} permissions</span>
            <div class="flex gap-2">
              <button type="button" class="text-text/50 hover:text-accent" @click.stop="openEdit(role)">Edit</button>
              <button type="button" class="text-text/50 hover:text-error" @click.stop="openDelete(role)">
                <Archive class="h-3.5 w-3.5" />
              </button>
            </div>
          </div>
        </button>
      </div>
    </div>

    <PermissionEditor v-if="selectedRole" :role="selectedRole" class="w-full xl:w-120 shrink-0 xl:sticky xl:top-6" @updated="onPermissionsUpdated" />
  </div>

  <BaseDialog :model-value="showFormModal" :title="editing ? 'Edit role' : 'New role'" @update:model-value="closeFormModal">
    <div class="space-y-4">
      <BaseInput v-model="form.name" label="Role name" :error="errors.name" autocomplete="off" />
      <div>
        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-text/70">Description</label>
        <textarea
          v-model="form.description"
          rows="3"
          class="w-full resize-none rounded-md border bg-bg px-4 py-3 text-sm text-text outline-none focus:border-accent"
          :class="errors.description ? 'border-error' : 'border-border'" />
        <p v-if="errors.description" class="mt-1 text-xs text-error">{{ errors.description }}</p>
      </div>
    </div>
    <template #footer>
      <div class="flex justify-end gap-2">
        <BaseButton variant="secondary" @click="closeFormModal">Cancel</BaseButton>
        <BaseButton :loading="saving" @click="submitForm">{{ editing ? 'Save changes' : 'Create role' }}</BaseButton>
      </div>
    </template>
  </BaseDialog>

  <ConfirmModal
    :show="showDeleteModal"
    title="Delete this role?"
    :description="`This will delete ${editing?.name}. If any users currently hold this role, deletion may be blocked.`"
    confirm-text="Delete role"
    variant="danger"
    :icon="Archive"
    :loading="deleting"
    @close="showDeleteModal = false"
    @confirm="confirmDelete" />
</template>
