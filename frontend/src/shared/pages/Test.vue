<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-bg font-sans text-text p-4 relative">
    <!-- Main Centered Card Container -->
    <main class="w-full max-w-[400px]">
      <!-- Level 1 Card -->
      <div class="bg-surface border border-border rounded-lg p-6 mb-6">
        <!-- Header Section -->
        <div class="flex flex-col items-center mb-8">
          <div class="w-16 h-16 rounded-full bg-bg flex items-center justify-center mb-4 border border-border text-accent">
            <svg class="w-8 h-8 fill-current" viewBox="0 0 24 24">
              <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zm0 2.67L18.09 9 12 12.33 5.91 9 12 5.67zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z" />
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-text text-center tracking-tight">Exam Management System</h1>
          <p class="text-xs text-text opacity-60 mt-1">Institutional Login</p>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Email Input Group -->
          <div class="flex flex-col space-y-1">
            <label class="text-xs font-bold text-text" for="email">Email Address</label>
            <div class="relative flex items-center">
              <div class="absolute left-3 pointer-events-none text-text opacity-40">
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                  <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
                </svg>
              </div>
              <input
                id="email"
                v-model="email"
                type="email"
                required
                placeholder="admin@university.edu"
                class="w-full pl-10 pr-3 py-2 text-sm text-text bg-bg border border-border rounded focus:outline-none focus:border-accent focus:ring-2 focus:ring-accent/20 transition-all" />
            </div>
          </div>

          <!-- Password Input Group -->
          <div class="flex flex-col space-y-1">
            <label class="text-xs font-bold text-text" for="password">Password</label>
            <div class="relative flex items-center">
              <div class="absolute left-3 pointer-events-none text-text opacity-40">
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                  <path
                    d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z" />
                </svg>
              </div>
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="••••••••"
                :class="[
                  'w-full pl-10 pr-10 py-2 text-sm text-text bg-bg border rounded focus:outline-none transition-all',
                  errorMessage ? 'border-red-600 focus:ring-2 focus:ring-red-600/20' : 'border-border focus:border-accent focus:ring-2 focus:ring-accent/20',
                ]" />
              <!-- Password Toggle Eye Button -->
              <button
                type="button"
                @click="togglePassword"
                class="absolute right-3 text-text opacity-40 hover:opacity-100 focus:outline-none"
                aria-label="Toggle password visibility">
                <svg v-if="!showPassword" class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                  <path
                    d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-2.24-5-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                </svg>
                <svg v-else class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                  <path
                    d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.44-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.17c0-1.66-1.34-3-3-3l-.17.02z" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Error Message State -->
          <div v-if="errorMessage" class="flex items-center space-x-1.5 text-red-600">
            <svg class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
            </svg>
            <span class="text-xs font-bold">{{ errorMessage }}</span>
          </div>

          <!-- Action Button -->
          <button
            type="submit"
            class="w-full h-12 py-2 bg-accent hover:bg-accent-hover text-white font-semibold rounded-lg flex items-center justify-center space-x-2 transition-colors focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
            <span>Log In</span>
            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
              <path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z" />
            </svg>
          </button>
        </form>

        <!-- Links Section -->
        <div class="mt-4 text-center">
          <a href="#" class="text-xs text-accent hover:underline focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-1 rounded-sm"> Forgot password? </a>
        </div>
      </div>
    </main>

    <!-- Minimal Footer -->
    <footer class="w-full text-center py-4 absolute bottom-0">
      <p class="text-xs text-text opacity-60">© {{ currentYear }} University Name. All rights reserved.</p>
    </footer>
  </div>
</template>

<script>
export default {
  name: 'LoginForm',
  data() {
    return {
      email: '',
      password: '',
      showPassword: false,
      errorMessage: 'Invalid credentials',
      currentYear: new Date().getFullYear(),
    };
  },
  methods: {
    togglePassword() {
      this.showPassword = !this.showPassword;
    },
    handleSubmit() {
      console.log('Logging in with:', this.email, this.password);
    },
  },
};
</script>
