<script lang="ts" setup>
import { ref } from 'vue';
import { userAuthStore } from './stores/auth';

const authStore = userAuthStore();

const userData = ref({
    email: '',
    password: ''
})

const submitlogin = async () => {
    authStore.login(userData.value.email, userData.value.password);
}

</script>





<template>
    <!-- //login form  with email and password -->
    {{ console.log(authStore.user) }}

    <div class="flex flex-col items-center justify-center h-screen bg-gray-100">
        <div class="w-full max-w-md">
            <form @submit.prevent="submitlogin" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Email
                    </label>
                    <input
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        id="email" type="email" placeholder="Email" v-model="userData.email">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Password
                    </label>
                    <input
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                        id="password" type="password" placeholder="******************" v-model="userData.password">
                </div>
                <div class="flex items-center justify-between">
                    <button
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                        type="submit">
                        Sign In
                    </button>
                </div>
            </form>
        </div>
    </div>
</template
