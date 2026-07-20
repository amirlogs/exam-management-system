<script lang="ts" setup>
import { ref } from 'vue';
import useThemeStore from './stores/theme';
const themeStore = useThemeStore();

</script>




<template>
    <!-- // add a toggleer to set dark mode and white mode  -->
    <div class="flex justify-center items-center h-screen dark:bg-gray-900">
        <button @click="themeStore.toggleTheme()"
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded dark:bg-gray-800 dark:hover:bg-gray-700">
            Toggle Theme
        </button>
    </div>
</template>
