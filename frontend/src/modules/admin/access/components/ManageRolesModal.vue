<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { X, Plus, Shield, ShieldPlus } from 'lucide-vue-next'
import BaseDialog from '@/shared/components/ui/BaseDialog.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'
import BaseSelect from '@/shared/components/ui/BaseSelect.vue'
import ConfirmModal from '@/shared/components/ConfirmModal.vue'
import { getRoles } from '../api/roles'
import { assignRole, removeRole } from '../api/users'
import { getUniversities } from '@/modules/admin/universities/api/universities'
import { getColleges } from '@/modules/admin/colleges/api/colleges'
import { getDepartments } from '@/modules/admin/departments/api/departments'
import type { User } from '../types/user'
import type { Role } from '../types/role'
import { useUiStore } from '@/stores/ui'

const props = defineProps<{ modelValue: boolean; user: User | null }>()
const emit = defineEmits<{ 'update:modelValue': [boolean]; updated: [User] }>()

const uiStore = useUiStore()
const local = ref<User | null>(null)

const roles = ref<Role[]>([])
const roleOptions = computed(() => roles.value.map((r) => ({ value: String(r.id), label: r.name })))
const selectedRoleId = ref('')

const universities = ref<{ value: string; label: string }[]>([])
const colleges = ref<{ value: string; label: string }[]>([])
const departments = ref<{ value: string; label: string }[]>([])
const universityId = ref('')
const collegeId = ref('')
const departmentId = ref('')

const busy = ref(false)

const showAssignConfirm = ref(false)
const showRemoveConfirm = ref(false)
const roleToRemove = ref<string | null>(null)

const selectedRoleLabel = computed(() => roleOptions.value.find(r => r.value === selectedRoleId.value)?.label ?? '')

// DIAGNOSTIC — watch selectedRoleId to confirm BaseSelect's v-model is actually updating it
watch(selectedRoleId, (val) => {
  console.log('%c[watch] selectedRoleId changed to:', 'color: orange', JSON.stringify(val))
})

watch(
  () => props.user,
  async (user) => {
    local.value = user
    selectedRoleId.value = ''
    universityId.value = ''
    collegeId.value = ''
    departmentId.value = ''
    if (!user) return
    if (!roles.value.length) {
      const res = await getRoles(1, 100)
      roles.value = res.data
      console.log('[loaded roles]', roles.value)
    }
    if (!universities.value.length) {
      const [uRes, cRes, dRes] = await Promise.all([
        getUniversities(1, 1).catch(() => ({ data: [] })),
        getColleges(1, 100).catch(() => ({ data: [] })),
        getDepartments(1, 100).catch(() => ({ data: [] })),
      ])
      universities.value = (uRes.data ?? []).map((u: any) => ({ value: String(u.id), label: u.name }))
      colleges.value = (cRes.data ?? []).map((c: any) => ({ value: String(c.id), label: c.name }))
      departments.value = (dRes.data ?? []).map((d: any) => ({ value: String(d.id), label: d.name }))
    }
  },
  { immediate: true },
)

function close() { emit('update:modelValue', false) }

function requestAssignRole() {
  console.log('%c[1] requestAssignRole called', 'color: cyan', {
    local: local.value,
    selectedRoleId: selectedRoleId.value,
  })
  if (!local.value || !selectedRoleId.value) {
    console.log('%c[1a] BLOCKED — returning early. local or selectedRoleId is falsy.', 'color: red')
    return
  }
  showAssignConfirm.value = true
  console.log('%c[1b] showAssignConfirm set to', 'color: cyan', showAssignConfirm.value)
}

async function confirmAssignRole() {
  console.log('%c[2] confirmAssignRole called', 'color: green')
  if (!local.value || !selectedRoleId.value) {
    console.log('%c[2a] BLOCKED — returning early. local or selectedRoleId is falsy.', 'color: red')
    return
  }
  console.log('%c[2b] proceeding to API call', 'color: green')
  busy.value = true
  try {
    const payload: any = { role_id: Number(selectedRoleId.value) }
    if (universityId.value) payload.university_id = Number(universityId.value)
    if (collegeId.value) payload.college_id = Number(collegeId.value)
    if (departmentId.value) payload.department_id = Number(departmentId.value)
    console.log('%c[2c] payload', 'color: green', payload)
    local.value = await assignRole(local.value.id, payload)
    console.log('%c[2d] assignRole API call SUCCEEDED', 'color: green', local.value)
    emit('updated', local.value)
    selectedRoleId.value = ''
    universityId.value = ''
    collegeId.value = ''
    departmentId.value = ''
    showAssignConfirm.value = false
    uiStore.showToast('Role assigned.', 'success')
  } catch (err: any) {
    console.log('%c[2e] assignRole API call FAILED', 'color: red', err)
    uiStore.showToast(err?.response?.data?.message || 'Failed to assign role.', 'error')
  } finally {
    busy.value = false
  }
}

function requestRemoveRole(roleName: string) {
  roleToRemove.value = roleName
  showRemoveConfirm.value = true
}

async function confirmRemoveRole() {
  if (!local.value || !roleToRemove.value) return
  const role = roles.value.find((r) => r.name === roleToRemove.value)
  if (!role) return
  busy.value = true
  try {
    local.value = await removeRole(local.value.id, role.id)
    emit('updated', local.value)
    uiStore.showToast('Role removed.', 'success')
    showRemoveConfirm.value = false
  } catch (err: any) {
    uiStore.showToast(err?.response?.data?.message || 'Failed to remove role.', 'error')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <BaseDialog :model-value="modelValue" title="Manage roles"
    :description="local ? `${local.first_name} ${local.last_name}` : ''" @update:model-value="close">
    <div v-if="local" class="space-y-6">
      <div>
        <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-text/60">Current roles</h4>
        <div class="flex flex-wrap gap-2">
          <span v-for="r in local.role_name" :key="r"
            class="flex items-center gap-1.5 rounded-full border border-border bg-bg px-3 py-1.5 text-xs text-text">
            <Shield class="h-3 w-3 text-accent" /> {{ r }}
            <button type="button" class="text-text/40 hover:text-error" @click="requestRemoveRole(r)">
              <X class="h-3 w-3" />
            </button>
          </span>
          <span v-if="!local.role_name.length" class="text-xs text-text/40">No roles assigned.</span>
        </div>
      </div>

      <div class="space-y-3 rounded-md border border-border bg-bg p-4">
        <h4 class="text-xs font-bold uppercase tracking-wide text-text/60">Add a role</h4>
        <BaseSelect v-model="selectedRoleId" :options="roleOptions" placeholder="Select role" />
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
          <BaseSelect v-model="universityId" :options="universities" placeholder="University (optional)" />
          <BaseSelect v-model="collegeId" :options="colleges" placeholder="College (optional)" />
          <BaseSelect v-model="departmentId" :options="departments" placeholder="Department (optional)" />
        </div>
        <BaseButton variant="secondary" :disabled="!selectedRoleId" @click="requestAssignRole">
          <template #icon><Plus class="h-4 w-4" /></template>Assign role
        </BaseButton>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end">
        <BaseButton variant="secondary" @click="close">Done</BaseButton>
      </div>
    </template>
  </BaseDialog>

  <ConfirmModal
    :show="showAssignConfirm"
    title="Assign this role?"
    :description="`${local?.first_name} ${local?.last_name} will be granted the '${selectedRoleLabel}' role${universityId || collegeId || departmentId ? ', scoped to the selected organizational unit' : ''}.`"
    confirm-text="Assign role"
    variant="accent"
    :icon="ShieldPlus"
    :loading="busy"
    @close="showAssignConfirm = false"
    @confirm="confirmAssignRole"
  />

  <ConfirmModal
    :show="showRemoveConfirm"
    title="Remove this role?"
    :description="`${local?.first_name} ${local?.last_name} will lose the '${roleToRemove}' role and any permissions it granted.`"
    confirm-text="Remove role"
    variant="danger"
    :icon="X"
    :loading="busy"
    @close="showRemoveConfirm = false"
    @confirm="confirmRemoveRole"
  />
</template>
