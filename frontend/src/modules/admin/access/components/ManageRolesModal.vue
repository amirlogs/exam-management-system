<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { X, Plus, Shield, ShieldPlus } from 'lucide-vue-next';
import BaseDialog from '@/shared/components/ui/BaseDialog.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseSelect from '@/shared/components/ui/BaseSelect.vue';
import ConfirmModal from '@/shared/components/ConfirmModal.vue';
import { getRoles } from '../api/roles';
import { assignRole, removeRole } from '../api/users';
import type { User } from '../types/user';
import type { Role } from '../types/role';
import { useUiStore } from '@/stores/ui';

const props = defineProps<{ modelValue: boolean; user: User | null }>();
const emit = defineEmits<{ 'update:modelValue': [boolean]; updated: [User] }>();

const uiStore = useUiStore();
const local = ref<User | null>(null);

const roles = ref<Role[]>([]);
const roleOptions = computed(() => roles.value.map((r) => ({ value: String(r.id), label: r.name })));
const selectedRoleId = ref('');

const busy = ref(false);

const showAssignConfirm = ref(false);
const showRemoveConfirm = ref(false);
const roleToRemove = ref<string | null>(null);

const selectedRoleLabel = computed(() => roleOptions.value.find((r) => r.value === selectedRoleId.value)?.label ?? '');

watch(selectedRoleId, (val) => {
  console.log('%c[watch] selectedRoleId changed to:', 'color: orange', JSON.stringify(val));
});

watch(
  () => props.user,
  async (user) => {
    local.value = user;
    selectedRoleId.value = '';

    if (!user) return;

    if (!roles.value.length) {
      const res = await getRoles(1, 100);
      roles.value = res.data;
      console.log('[loaded roles]', roles.value);
    }
  },
  { immediate: true },
);

function close() {
  emit('update:modelValue', false);
}

function requestAssignRole() {
  console.log('%c[1] requestAssignRole called', 'color: cyan', {
    local: local.value,
    selectedRoleId: selectedRoleId.value,
  });

  if (!local.value || !selectedRoleId.value) {
    console.log('%c[1a] BLOCKED — returning early. local or selectedRoleId is falsy.', 'color: red');
    return;
  }

  showAssignConfirm.value = true;

  console.log('%c[1b] showAssignConfirm set to', 'color: cyan', showAssignConfirm.value);
}

async function confirmAssignRole() {
  console.log('%c[2] confirmAssignRole called', 'color: green');

  if (!local.value || !selectedRoleId.value) {
    console.log('%c[2a] BLOCKED — returning early. local or selectedRoleId is falsy.', 'color: red');
    return;
  }

  console.log('%c[2b] proceeding to API call', 'color: green');

  busy.value = true;

  try {
    const payload = {
      role_id: Number(selectedRoleId.value),
    };

    console.log('%c[2c] payload', 'color: green', payload);

    local.value = await assignRole(local.value.id, payload);

    console.log('%c[2d] assignRole API call SUCCEEDED', 'color: green', local.value);

    emit('updated', local.value);

    selectedRoleId.value = '';
    showAssignConfirm.value = false;

    uiStore.showToast('Role assigned.', 'success');
  } catch (err: any) {
    console.log('%c[2e] assignRole API call FAILED', 'color: red', err);

    uiStore.showToast(err?.response?.data?.message || 'Failed to assign role.', 'error');
  } finally {
    busy.value = false;
  }
}

function requestRemoveRole(roleName: string) {
  roleToRemove.value = roleName;
  showRemoveConfirm.value = true;
}

async function confirmRemoveRole() {
  if (!local.value || !roleToRemove.value) return;

  const role = roles.value.find((r) => r.name === roleToRemove.value);

  if (!role) return;

  busy.value = true;

  try {
    local.value = await removeRole(local.value.id, role.id);

    emit('updated', local.value);

    uiStore.showToast('Role removed.', 'success');

    showRemoveConfirm.value = false;
  } catch (err: any) {
    uiStore.showToast(err?.response?.data?.message || 'Failed to remove role.', 'error');
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <BaseDialog
    :model-value="modelValue"
    title="Manage roles"
    :description="local ? `${local.first_name} ${local.last_name}` : ''"
    @update:model-value="close">
    <div v-if="local" class="space-y-6">
      <div>
        <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-text/60">
          Current roles
        </h4>

        <div class="flex flex-wrap gap-2">
          <span
            v-for="r in local.role_name"
            :key="r"
            class="flex items-center gap-1.5 rounded-full border border-border bg-bg px-3 py-1.5 text-xs text-text">
            <Shield class="h-3 w-3 text-accent" />

            {{ r }}

            <button
              type="button"
              class="text-text/40 hover:text-error"
              :disabled="busy"
              @click="requestRemoveRole(r)">
              <X class="h-3 w-3" />
            </button>
          </span>

          <span v-if="!local.role_name.length" class="text-xs text-text/40">
            No roles assigned.
          </span>
        </div>
      </div>

      <div class="space-y-4 rounded-lg border border-border bg-bg/50 p-4">
        <div>
          <div class="flex items-center gap-2">
            <ShieldPlus class="h-4 w-4 text-accent" />

            <h4 class="text-sm font-semibold text-text">
              Assign a role
            </h4>
          </div>

          <p class="mt-1 text-xs text-text/50">
            Choose a role to grant this user its permissions.
          </p>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-semibold uppercase tracking-wide text-text/60">
            Role
          </label>

          <BaseSelect
            v-model="selectedRoleId"
            :options="roleOptions"
            placeholder="Select a role"
            :disabled="busy" />
        </div>

        <div
          class="rounded-md border px-3 py-2.5 transition-colors"
          :class="selectedRoleId ? 'border-accent/20 bg-accent/5' : 'border-border bg-surface opacity-60'">
          <p class="text-xs font-medium text-text/50">
            Selected role
          </p>

          <p class="mt-0.5 text-sm font-medium text-text">
            {{ selectedRoleLabel || 'No role selected' }}
          </p>
        </div>

        <BaseButton
          class="w-full"
          :disabled="!selectedRoleId || busy"
          @click="requestAssignRole">
          <template #icon>
            <Plus class="h-4 w-4" />
          </template>

          {{ selectedRoleId ? 'Assign role' : 'Select a role' }}
        </BaseButton>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end">
        <BaseButton variant="secondary" @click="close">
          Done
        </BaseButton>
      </div>
    </template>
  </BaseDialog>

  <ConfirmModal
    :show="showAssignConfirm"
    title="Assign this role?"
    :description="`${local?.first_name} ${local?.last_name} will be granted the '${selectedRoleLabel}' role.`"
    confirm-text="Assign role"
    variant="accent"
    :icon="ShieldPlus"
    :loading="busy"
    @close="showAssignConfirm = false"
    @confirm="confirmAssignRole" />

  <ConfirmModal
    :show="showRemoveConfirm"
    title="Remove this role?"
    :description="`${local?.first_name} ${local?.last_name} will lose the '${roleToRemove}' role and any permissions it granted.`"
    confirm-text="Remove role"
    variant="danger"
    :icon="X"
    :loading="busy"
    @close="showRemoveConfirm = false"
    @confirm="confirmRemoveRole" />
</template>
