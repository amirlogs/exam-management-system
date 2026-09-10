<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  User,
  Lock,
  Sliders,
  Shield,
  Check,
  Save,
  KeyRound,
  Palette,
  Moon,
  Sun,
  Mail,
  Calendar,
  CheckCircle2,
  ShieldCheck,
  Building2,
  Sparkles,
  ArrowRight,
} from 'lucide-vue-next';

import ResourceToolbar from '@/shared/components/ResourceToolbar.vue';
import BaseButton from '@/shared/components/ui/BaseButton.vue';
import BaseBadge from '@/shared/components/ui/BaseBadge.vue';
import { useAuthStore } from '@/stores/auth';
import { usePermissionsStore } from '@/stores/permission';
import { useThemeStore } from '@/stores/theme';
import { useUiStore } from '@/stores/ui';
import { handleApiError } from '@/shared/utils/apiError';
import { updateProfile, changePassword } from '@/api/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const permissionsStore = usePermissionsStore();
const themeStore = useThemeStore();
const uiStore = useUiStore();

// Navigation Tabs
type TabKey = 'general' | 'security' | 'preferences' | 'permissions';
const activeTab = ref<TabKey>('general');

// Personal Info State
const firstName = ref('');
const lastName = ref('');
const email = ref('');
const isSavingProfile = ref(false);

// Password Change State
const currentPassword = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const isChangingPassword = ref(false);

// Workspace State
const isSwitchingWorkspace = ref<string | null>(null);

// Populate user data
function initUserData() {
  const user = authStore.user;
  if (!user) return;
  firstName.value = user.first_name || '';
  lastName.value = user.last_name || '';
  email.value = user.email || '';
}

onMounted(() => {
  initUserData();
  if (route.query.tab && ['general', 'security', 'preferences', 'permissions'].includes(route.query.tab as string)) {
    activeTab.value = route.query.tab as TabKey;
  }
});

watch(() => authStore.user, initUserData);

watch(
  () => route.query.tab,
  (newTab) => {
    if (newTab && ['general', 'security', 'preferences', 'permissions'].includes(newTab as string)) {
      activeTab.value = newTab as TabKey;
    }
  },
);

// Initials for avatar
const userInitials = computed(() => {
  const f = firstName.value.trim().charAt(0).toUpperCase();
  const l = lastName.value.trim().charAt(0).toUpperCase();
  return f && l ? `${f}${l}` : f || 'U';
});

const userFullName = computed(() => {
  return `${firstName.value} ${lastName.value}`.trim() || 'User Profile';
});

// Roles list
const userRoles = computed(() => {
  const roles = authStore.user?.role_name;
  if (!roles) return [];
  return Array.isArray(roles) ? roles : [roles];
});

// Accessible workspaces
const availableWorkspaces = computed(() => {
  const ws = permissionsStore.workspaces;
  if (!ws) return ['instructor'];
  const list: { key: string; label: string; desc: string }[] = [];
  if (ws.admin) list.push({ key: 'admin', label: 'Admin Workspace', desc: 'Manage university institutions, curricula, users, and audit logs' });
  if (ws.instructor) list.push({ key: 'instructor', label: 'Instructor Workspace', desc: 'Design exams, compose questions, and grade student submissions' });
  if (ws.student) list.push({ key: 'student', label: 'Student Workspace', desc: 'Take scheduled exams and review academic performance results' });
  return list;
});

// Has Profile changes
const hasProfileChanges = computed(() => {
  const user = authStore.user;
  if (!user) return false;
  return firstName.value.trim() !== (user.first_name || '') || lastName.value.trim() !== (user.last_name || '');
});

// Save Profile
async function handleSaveProfile() {
  if (!firstName.value.trim() || !lastName.value.trim()) {
    uiStore.showToast('First name and last name are required.', 'error');
    return;
  }

  isSavingProfile.value = true;
  try {
    const res = await updateProfile({
      first_name: firstName.value.trim(),
      last_name: lastName.value.trim(),
    });

    if (authStore.user) {
      authStore.user.first_name = res.data.data.first_name;
      authStore.user.last_name = res.data.data.last_name;
    }

    uiStore.showToast('Profile updated successfully.', 'success');
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to update profile.');
  } finally {
    isSavingProfile.value = false;
  }
}

// Change Password
async function handleChangePassword() {
  if (!currentPassword.value) {
    uiStore.showToast('Please enter your current password.', 'error');
    return;
  }
  if (!newPassword.value || newPassword.value.length < 8) {
    uiStore.showToast('New password must be at least 8 characters.', 'error');
    return;
  }
  if (newPassword.value !== confirmPassword.value) {
    uiStore.showToast('New passwords do not match.', 'error');
    return;
  }

  isChangingPassword.value = true;
  try {
    await changePassword({
      current_password: currentPassword.value,
      new_password: newPassword.value,
      new_password_confirmation: confirmPassword.value,
    });

    uiStore.showToast('Password changed successfully.', 'success');
    currentPassword.value = '';
    newPassword.value = '';
    confirmPassword.value = '';
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to change password.');
  } finally {
    isChangingPassword.value = false;
  }
}

// Active and Last Visited Workspace
const activeWorkspaceKey = computed(() => {
  return permissionsStore.activeWorkspace || authStore.user?.default_workspace || 'instructor';
});

async function handleSwitchWorkspace(workspace: string) {
  if (workspace === activeWorkspaceKey.value) return;
  isSwitchingWorkspace.value = workspace;
  try {
    await permissionsStore.setActiveWorkspace(workspace);
    uiStore.showToast(`Switched to ${workspace} workspace.`, 'success');
    router.push(`/${workspace}/profile?tab=preferences`);
  } catch (err) {
    handleApiError(err, uiStore, undefined, 'Failed to switch workspace.');
  } finally {
    isSwitchingWorkspace.value = null;
  }
}

// Available Themes
const themeOptions = [
  { id: 'oxford', label: 'Oxford Crimson', color: '#7a2731', desc: 'Academic heritage burgundy' },
  { id: 'forest', label: 'Forest Green', color: '#1b3a2b', desc: 'Botanical natural emerald' },
  { id: 'slate', label: 'Slate Amber', color: '#c17817', desc: 'Contemporary warm amber' },
];
</script>

<template>
  <div class="mx-auto w-full max-w-360 space-y-6 px-6 py-6 min-h-[calc(100vh-68px)]">
    <!-- Header Toolbar -->
    <ResourceToolbar
      title="User Account"
      description="Manage your profile credentials, security settings, and workspace preferences."
      :show-search="false"
      :show-filter="false"
      :show-refresh="false"
      :show-fullscreen="false"
    />

    <!-- User Identity Hero Card -->
    <div class="rounded-2xl border border-border bg-surface p-6 shadow-xs">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">
        <div class="flex items-center gap-4">
          <!-- Avatar Initials -->
          <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-accent/10 text-accent font-display text-xl font-bold ring-4 ring-accent/10">
            {{ userInitials }}
          </div>

          <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="font-display text-xl font-bold text-text">
                {{ userFullName }}
              </h2>
              <BaseBadge variant="success" class="flex items-center gap-1 text-[11px]">
                <CheckCircle2 class="h-3 w-3" />
                Active
              </BaseBadge>
            </div>

            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-text/60">
              <span class="flex items-center gap-1">
                <Mail class="h-3.5 w-3.5 text-text/40" />
                {{ email || 'No email registered' }}
              </span>
              <span v-if="authStore.user?.created_at" class="text-text/30">•</span>
              <span v-if="authStore.user?.created_at" class="flex items-center gap-1 font-mono text-[11px]">
                <Calendar class="h-3.5 w-3.5 text-text/40" />
                Joined {{ authStore.user.created_at }}
              </span>
            </div>

            <!-- Role Tags -->
            <div v-if="userRoles.length" class="flex items-center gap-1.5 pt-1 flex-wrap">
              <span
                v-for="role in userRoles"
                :key="role"
                class="inline-flex items-center rounded-md bg-text/5 px-2 py-0.5 font-mono text-[10px] font-semibold uppercase text-text/70 border border-border/60"
              >
                {{ role }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex rounded-xl bg-bg p-1 border border-border overflow-x-auto">
      <button
        type="button"
        class="flex-1 min-w-[130px] flex items-center justify-center gap-2 rounded-lg py-2.5 text-xs font-semibold transition-all cursor-pointer"
        :class="activeTab === 'general' ? 'bg-surface text-accent shadow-xs border border-border/60' : 'text-text/60 hover:text-text'"
        @click="activeTab = 'general'"
      >
        <User class="h-4 w-4" />
        <span>Personal Details</span>
      </button>

      <button
        type="button"
        class="flex-1 min-w-[130px] flex items-center justify-center gap-2 rounded-lg py-2.5 text-xs font-semibold transition-all cursor-pointer"
        :class="activeTab === 'security' ? 'bg-surface text-accent shadow-xs border border-border/60' : 'text-text/60 hover:text-text'"
        @click="activeTab = 'security'"
      >
        <Lock class="h-4 w-4" />
        <span>Security & Password</span>
      </button>

      <button
        type="button"
        class="flex-1 min-w-[130px] flex items-center justify-center gap-2 rounded-lg py-2.5 text-xs font-semibold transition-all cursor-pointer"
        :class="activeTab === 'preferences' ? 'bg-surface text-accent shadow-xs border border-border/60' : 'text-text/60 hover:text-text'"
        @click="activeTab = 'preferences'"
      >
        <Sliders class="h-4 w-4" />
        <span>Preferences & Theme</span>
      </button>

      <button
        type="button"
        class="flex-1 min-w-[130px] flex items-center justify-center gap-2 rounded-lg py-2.5 text-xs font-semibold transition-all cursor-pointer"
        :class="activeTab === 'permissions' ? 'bg-surface text-accent shadow-xs border border-border/60' : 'text-text/60 hover:text-text'"
        @click="activeTab = 'permissions'"
      >
        <ShieldCheck class="h-4 w-4" />
        <span>Roles & Access</span>
      </button>
    </div>

    <!-- TAB 1: PERSONAL DETAILS -->
    <div v-if="activeTab === 'general'" class="rounded-2xl border border-border bg-surface p-6 shadow-xs space-y-6">
      <div class="border-b border-border pb-4">
        <h3 class="font-display text-base font-bold text-text">Personal Information</h3>
        <p class="text-xs text-text/55">Update your display name and view your institutional credentials.</p>
      </div>

      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-text/65">First Name</label>
          <input
            v-model="firstName"
            type="text"
            class="w-full h-11 rounded-xl border border-border bg-bg/40 px-4 text-sm text-text placeholder:text-text/35 focus:bg-surface focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent transition-colors"
            placeholder="First name"
          />
        </div>

        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-text/65">Last Name</label>
          <input
            v-model="lastName"
            type="text"
            class="w-full h-11 rounded-xl border border-border bg-bg/40 px-4 text-sm text-text placeholder:text-text/35 focus:bg-surface focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent transition-colors"
            placeholder="Last name"
          />
        </div>
      </div>

      <!-- Institutional Email (Read-only) -->
      <div class="space-y-1.5">
        <div class="flex items-center justify-between">
          <label class="block text-xs font-bold uppercase tracking-wider text-text/65">Institutional Email Address</label>
          <span class="text-[11px] text-text/40 flex items-center gap-1">
            <Lock class="h-3 w-3" /> Managed by Administrator
          </span>
        </div>
        <div class="flex h-11 w-full items-center justify-between rounded-xl border border-border/70 bg-bg/80 px-4 text-sm text-text/70">
          <span>{{ email }}</span>
          <BaseBadge variant="neutral" class="text-[10px]">Verified</BaseBadge>
        </div>
        <p class="text-[11px] text-text/45">To update your official email address, contact your university system administrator.</p>
      </div>

      <div class="pt-2 flex justify-end border-t border-border">
        <BaseButton
          variant="primary"
          :loading="isSavingProfile"
          :disabled="!hasProfileChanges"
          @click="handleSaveProfile"
        >
          <template #icon>
            <Save class="h-4 w-4" />
          </template>
          Save Changes
        </BaseButton>
      </div>
    </div>

    <!-- TAB 2: SECURITY & PASSWORD -->
    <div v-else-if="activeTab === 'security'" class="rounded-2xl border border-border bg-surface p-6 shadow-xs space-y-6">
      <div class="border-b border-border pb-4">
        <h3 class="font-display text-base font-bold text-text">Password & Authentication</h3>
        <p class="text-xs text-text/55">Ensure your account is protected with a strong, distinct passphrase.</p>
      </div>

      <div class="space-y-4 max-w-lg">
        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-text/65">Current Password</label>
          <input
            v-model="currentPassword"
            type="password"
            class="w-full h-11 rounded-xl border border-border bg-bg/40 px-4 text-sm text-text placeholder:text-text/35 focus:bg-surface focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent transition-colors"
            placeholder="Enter your existing password"
          />
        </div>

        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-text/65">New Password</label>
          <input
            v-model="newPassword"
            type="password"
            class="w-full h-11 rounded-xl border border-border bg-bg/40 px-4 text-sm text-text placeholder:text-text/35 focus:bg-surface focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent transition-colors"
            placeholder="Minimum 8 characters"
          />
        </div>

        <div class="space-y-1.5">
          <label class="block text-xs font-bold uppercase tracking-wider text-text/65">Confirm New Password</label>
          <input
            v-model="confirmPassword"
            type="password"
            class="w-full h-11 rounded-xl border border-border bg-bg/40 px-4 text-sm text-text placeholder:text-text/35 focus:bg-surface focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent transition-colors"
            placeholder="Re-type new password"
          />
        </div>
      </div>

      <div class="rounded-xl border border-border/80 bg-bg/40 p-4 text-xs text-text/60 space-y-1 max-w-lg">
        <p class="font-semibold text-text/80 flex items-center gap-1.5">
          <KeyRound class="h-3.5 w-3.5 text-accent" /> Password Guidelines
        </p>
        <p>Password must be at least 8 characters. We recommend including uppercase letters, numbers, and special symbols.</p>
      </div>

      <div class="pt-2 flex justify-start border-t border-border">
        <BaseButton
          variant="primary"
          :loading="isChangingPassword"
          :disabled="!currentPassword || !newPassword || !confirmPassword"
          @click="handleChangePassword"
        >
          <template #icon>
            <Lock class="h-4 w-4" />
          </template>
          Update Password
        </BaseButton>
      </div>
    </div>

    <!-- TAB 3: PREFERENCES & THEME -->
    <div v-else-if="activeTab === 'preferences'" class="space-y-6">
      <!-- Last Visited Workspace Card -->
      <div class="rounded-2xl border border-border bg-surface p-6 shadow-xs space-y-5">
        <div class="border-b border-border pb-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-display text-base font-bold text-text">Last Visited Workspace</h3>
              <p class="text-xs text-text/55">The platform automatically remembers your last visited workspace and resumes here upon sign-in.</p>
            </div>
            <BaseBadge variant="neutral" class="font-mono text-[11px]">
              Auto-Resumed
            </BaseBadge>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <div
            v-for="ws in availableWorkspaces"
            :key="ws.key"
            class="flex flex-col justify-between p-4 rounded-xl border text-left transition-all"
            :class="activeWorkspaceKey === ws.key
              ? 'border-accent bg-accent/5 ring-2 ring-accent/15'
              : 'border-border bg-bg/40 hover:bg-bg/70'"
          >
            <div>
              <div class="flex w-full items-center justify-between mb-2">
                <span class="font-bold text-xs capitalize text-text">{{ ws.label }}</span>
                <span
                  v-if="activeWorkspaceKey === ws.key"
                  class="inline-flex items-center gap-1 text-[10px] font-mono font-semibold uppercase text-accent bg-accent/10 px-1.5 py-0.5 rounded"
                >
                  <Check class="h-3 w-3 text-accent" /> Active
                </span>
              </div>
              <p class="text-[11px] text-text/55 leading-relaxed">{{ ws.desc }}</p>
            </div>

            <div v-if="activeWorkspaceKey !== ws.key" class="pt-3 border-t border-border/50 mt-3">
              <button
                type="button"
                :disabled="isSwitchingWorkspace === ws.key"
                @click="handleSwitchWorkspace(ws.key)"
                class="text-[11px] font-semibold text-accent hover:underline cursor-pointer flex items-center gap-1 disabled:opacity-50"
              >
                <span>{{ isSwitchingWorkspace === ws.key ? 'Switching...' : 'Switch workspace' }}</span>
                <ArrowRight class="h-3 w-3" />
              </button>
            </div>
            <div v-else class="pt-3 border-t border-accent/20 mt-3 text-[11px] text-accent/80 font-medium">
              Current active session
            </div>
          </div>
        </div>
      </div>

      <!-- Theme Switcher Card -->
      <div class="rounded-2xl border border-border bg-surface p-6 shadow-xs space-y-5">
        <div class="border-b border-border pb-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-display text-base font-bold text-text">Interface Appearance</h3>
              <p class="text-xs text-text/55">Select your preferred color palette and color scheme mode.</p>
            </div>
            <!-- Dark Mode Toggle -->
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-xl border border-border bg-bg px-3.5 py-2 text-xs font-semibold text-text hover:bg-surface transition-colors cursor-pointer"
              @click="themeStore.toggleDark()"
            >
              <Sun v-if="themeStore.isDark" class="h-4 w-4 text-amber-500" />
              <Moon v-else class="h-4 w-4 text-text/60" />
              <span>{{ themeStore.isDark ? 'Dark Mode' : 'Light Mode' }}</span>
            </button>
          </div>
        </div>

        <!-- Palette Options -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <button
            v-for="opt in themeOptions"
            :key="opt.id"
            type="button"
            class="flex items-start gap-3.5 p-4 rounded-xl border text-left transition-all cursor-pointer"
            :class="themeStore.theme === opt.id
              ? 'border-accent bg-accent/5 ring-2 ring-accent/15'
              : 'border-border bg-bg/40 hover:bg-bg hover:border-border/80'"
            @click="themeStore.setTheme(opt.id)"
          >
            <div
              class="h-8 w-8 rounded-lg shrink-0 shadow-xs flex items-center justify-center text-white text-xs font-bold"
              :style="{ backgroundColor: opt.color }"
            >
              <Check v-if="themeStore.theme === opt.id" class="h-4 w-4" />
            </div>
            <div class="space-y-0.5">
              <span class="font-bold text-xs text-text">{{ opt.label }}</span>
              <p class="text-[11px] text-text/50">{{ opt.desc }}</p>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 4: ROLES & ACCESS -->
    <div v-else-if="activeTab === 'permissions'" class="rounded-2xl border border-border bg-surface p-6 shadow-xs space-y-6">
      <div class="border-b border-border pb-4">
        <h3 class="font-display text-base font-bold text-text">Roles & Institutional Access</h3>
        <p class="text-xs text-text/55">Review your assigned system authorizations and granted access privileges.</p>
      </div>

      <!-- Active Roles -->
      <div class="space-y-3">
        <h4 class="text-xs font-bold uppercase tracking-wider text-text/70">Granted Roles</h4>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div
            v-for="role in userRoles"
            :key="role"
            class="flex items-center gap-3 p-4 rounded-xl border border-border bg-bg/40"
          >
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-accent/10 text-accent">
              <Shield class="h-4 w-4" />
            </div>
            <div>
              <p class="font-bold text-xs uppercase text-text">{{ role }}</p>
              <p class="text-[11px] text-text/50">Active Institutional Role</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Granted Permissions Counter & Pills -->
      <div class="space-y-3 pt-2">
        <h4 class="text-xs font-bold uppercase tracking-wider text-text/70">
          Permission Capabilities ({{ permissionsStore.permissions.length }})
        </h4>

        <div class="flex flex-wrap gap-1.5 max-h-56 overflow-y-auto rounded-xl border border-border bg-bg/40 p-4">
          <span
            v-for="perm in permissionsStore.permissions"
            :key="perm"
            class="inline-flex items-center gap-1 rounded-md bg-surface px-2.5 py-1 font-mono text-[10px] text-text/75 border border-border/60"
          >
            <Check class="h-2.5 w-2.5 text-emerald-600" />
            {{ perm }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
