<template>
    <div class="w-full max-w-6xl">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="w-16 h-16 mx-auto mb-5 rounded-xl bg-accent/10 flex items-center justify-center">
                <Landmark class="w-7 h-7 text-accent" />
            </div>
            <h1 class="text-3xl font-bold text-text">Select Workspace</h1>
            <p class="text-text/60 mt-2 max-w-md mx-auto">
                Choose your destination for this session. You can change this later from the top navigation.
            </p>
        </div>

        <!-- Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div v-for="ws in workspaces" :key="ws.id" class="group relative bg-surface border border-border rounded-2xl p-7 flex flex-col
               shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
                <!-- Overflow menu -->
                <div class="absolute top-5 right-5">
                    <button type="button" @click.stop="openMenuId = openMenuId === ws.id ? null : ws.id" class="w-8 h-8 rounded-lg flex items-center justify-center text-text/40
                   hover:text-text hover:bg-bg transition-colors">
                        <MoreVertical class="w-4 h-4" />
                    </button>

                    <div v-if="openMenuId === ws.id"
                        class="absolute right-0 mt-1 w-48 bg-surface border border-border rounded-lg shadow-lg py-1 z-10">
                        <button type="button" @click="setDefault(ws.id)"
                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-text hover:bg-bg transition-colors text-left">
                            <Star class="w-4 h-4"
                                :class="defaultWorkspaceId === ws.id ? 'text-accent' : 'text-text/40'" />
                            {{ defaultWorkspaceId === ws.id ? 'Remove as default' : 'Set as default' }}
                        </button>
                    </div>
                </div>

                <!-- Icon -->
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-5" :class="ws.iconBg">
                    <component :is="ws.icon" class="w-6 h-6" :class="ws.iconColor" />
                </div>

                <!-- Title + default badge -->
                <div class="flex items-center gap-2 mb-2 pr-6">
                    <h3 class="text-xl font-bold text-text">{{ ws.title }}</h3>
                    <span v-if="defaultWorkspaceId === ws.id"
                        class="text-[11px] font-semibold text-accent bg-accent/10 px-2 py-0.5 rounded-full">
                        Default
                    </span>
                </div>

                <p class="text-text/60 text-sm leading-relaxed flex-1">{{ ws.description }}</p>

                <BaseButton variant="primary" block class="mt-6" @click="$emit('enter-workspace', ws.id)">
                    Enter Workspace
                    <template #icon>
                        <ArrowRight class="w-4 h-4" />
                    </template>
                </BaseButton>
            </div>
        </div>

        <!-- Footer -->
        <hr class="border-border my-10" />
        <div class="text-center text-sm text-text/50">
            <p>EduExam Systems © {{ year }}. All rights reserved.</p>
            <div class="flex items-center justify-center gap-2 mt-1">
                <router-link to="/privacy" class="font-semibold text-text/70 hover:text-accent transition-colors">
                    Privacy Policy
                </router-link>
                <span class="w-1 h-1 rounded-full bg-text/30" />
                <router-link to="/terms" class="font-semibold text-text/70 hover:text-accent transition-colors">
                    Terms of Service
                </router-link>
                <span class="w-1 h-1 rounded-full bg-text/30" />
                <router-link to="/support" class="font-semibold text-text/70 hover:text-accent transition-colors">
                    Support
                </router-link>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Landmark, ShieldUser, BookOpen, ClipboardList, ArrowRight, Star, MoreVertical } from 'lucide-vue-next'
import BaseButton from '@/shared/components/ui/BaseButton.vue'

defineEmits<{ (e: 'enter-workspace', id: string): void }>()

const year = new Date().getFullYear()

const workspaces = [
    {
        id: 'admin',
        title: 'Administration',
        description: 'System oversight, user management, and institutional audit logs.',
        icon: ShieldUser,
        iconBg: 'bg-accent/10',
        iconColor: 'text-accent',
    },
    {
        id: 'teaching',
        title: 'Teaching',
        description: 'Course management, question banks, and grading workflows.',
        icon: BookOpen,
        iconBg: 'bg-blue-50',
        iconColor: 'text-blue-600',
    },
    {
        id: 'student',
        title: 'My Studies',
        description: 'Personal exam schedules, results, and academic records.',
        icon: ClipboardList,
        iconBg: 'bg-emerald-50',
        iconColor: 'text-emerald-600',
    },
]

const defaultWorkspaceId = ref<string | null>(null)
const openMenuId = ref<string | null>(null)

function setDefault(id: string) {
    defaultWorkspaceId.value = defaultWorkspaceId.value === id ? null : id
    openMenuId.value = null
}

// Close the open menu when clicking anywhere outside it
function closeMenu() {
    openMenuId.value = null
}
onMounted(() => document.addEventListener('click', closeMenu))
onUnmounted(() => document.removeEventListener('click', closeMenu))
</script>
