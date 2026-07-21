import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  const theme = ref(localStorage.getItem('app-theme') || 'oxford')
  const isDark = ref(localStorage.getItem('app-dark') === 'true')

  function apply() {
    document.documentElement.setAttribute('data-theme', theme.value)
    document.documentElement.classList.toggle('dark', isDark.value)
  }

  watch(theme, (val) => {
    localStorage.setItem('app-theme', val)
    apply()
  })

  watch(isDark, (val) => {
    localStorage.setItem('app-dark', String(val))
    apply()
  })

  // Actions
  function setTheme(name) {
    theme.value = name
  }
  function toggleDark() {
    isDark.value = !isDark.value
  }

  apply()

  return { theme, isDark, setTheme, toggleDark }
})
