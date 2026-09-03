<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'

import { getCourses } from '@/modules/admin/courses/api/courses'

const props = withDefaults(
    defineProps<{
        importType: string
        courseOptions?: { value: string; label: string }[]
    }>(),
    {
        courseOptions: undefined,
    },
)

const loadingCourses = ref(false)
const internalCourseOptions = ref<{ value: string; label: string }[]>([])

const availableCourseOptions = computed(() => {
    if (props.courseOptions?.length) {
        return props.courseOptions
    }

    return internalCourseOptions.value
})

async function loadCourses() {
    if (props.courseOptions) {
        return
    }

    loadingCourses.value = true

    try {
        const response = await getCourses(1, 1000)

        internalCourseOptions.value = (response.data ?? []).map((course) => ({
            value: String(course.id),
            label: `${course.code} — ${course.name}`,
        }))
    } finally {
        loadingCourses.value = false
    }
}

onMounted(loadCourses)
</script>
