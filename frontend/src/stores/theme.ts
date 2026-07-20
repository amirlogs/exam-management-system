import { defineStore } from 'pinia'
import { ref } from 'vue'

const useThemeStore = defineStore('theme', () => {
  const theme = ref('light')
  const htmlElement = document.documentElement

  function toggleTheme() {
    if (htmlElement.getAttribute('data-theme') === 'dark') {
      htmlElement.removeAttribute('data-theme')
    } else {
      htmlElement.setAttribute('data-theme', 'dark')
    }
  }
  return {
    theme,
    toggleTheme,
  }
})

export default useThemeStore
