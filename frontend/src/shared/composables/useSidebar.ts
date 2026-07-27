import { ref } from 'vue'

const isCollapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true')
const isMobileOpen = ref(false)

function toggleCollapse() {
  isCollapsed.value = !isCollapsed.value
  localStorage.setItem('sidebar_collapsed', String(isCollapsed.value))
}

function toggleMobile() {
  isMobileOpen.value = !isMobileOpen.value
}

function closeMobile() {
  isMobileOpen.value = false
}

export function useSidebar() {
  return { isCollapsed, isMobileOpen, toggleCollapse, toggleMobile, closeMobile }
}
