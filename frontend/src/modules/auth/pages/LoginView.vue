<template>
    <div class="w-full max-w-5xl">
        <!-- Header -->
        <div class="text-center mb-10">
            <div class="w-16 h-16 mx-auto mb-5 rounded-xl bg-accent/10 flex items-center justify-center">
                <Landmark class="w-7 h-7 text-accent" />
            </div>
            <h1 class="text-3xl font-bold text-text">Select Workspace</h1>
            <p class="text-text/60 mt-2 max-w-md mx-auto">
                Choose your destination for this session. You can change this later from the top navigation.
            </p>
        </div>

        <!-- Cards -->
        <div class="flex flex-wrap justify-center gap-6">
            <div v-for="ws in workspaces" :key="ws.id"
                class="w-full max-w-[360px] bg-surface border border-border rounded-xl p-6 flex flex-col">
                <div class="w-11 h-11 rounded-lg bg-accent/10 flex items-center justify-center mb-5 text-accent">
                    <component :is="ws.icon" class="w-5 h-5" />
                </div>

                <h3 class="text-xl font-bold text-text mb-2">{{ ws.title }}</h3>
                <p class="text-text/60 text-sm flex-1">{{ ws.description }}</p>

                <hr class="border-border my-5" />

                <label class="flex items-center gap-2 mb-4 cursor-pointer select-none">
                    <input type="checkbox" :checked="defaultWorkspaceId === ws.id" @change="setDefault(ws.id)"
                        class="w-4 h-4 rounded border-border text-accent focus:ring-accent" />
                    <span class="text-sm text-text/70">Set as default workspace</span>
                </label>

                <BaseButton variant="primary" block @click="$emit('enter-workspace', ws.id)">
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
import { ref } from 'vue'
import { Landmark, ShieldUser, BookOpen, ClipboardList, ArrowRight } from 'lucide-vue-next'
import BaseButton from '@/shared/components/ui/BaseButton.vue'

defineEmits<{ (e: 'enter-workspace', id: string): void }>()

const year = new Date().getFullYear()

const workspaces = [
    { id: 'admin', title: 'Administration', description: 'System oversight, user management, and institutional audit logs.', icon: ShieldUser },
    { id: 'teaching', title: 'Teaching', description: 'Course management, question banks, and grading workflows.', icon: BookOpen },
    { id: 'student', title: 'My Studies', description: 'Personal exam schedules, results, and academic records.', icon: ClipboardList },
]

// Single source of truth for "which one is default" — only one can be true at a time
const defaultWorkspaceId = ref<string | null>(null)

function setDefault(id: string) {
    // Clicking the already-default one unchecks it (goes back to "no default").
    // Clicking a different one switches the default to that one.
    defaultWorkspaceId.value = defaultWorkspaceId.value === id ? null : id
}
</script>
