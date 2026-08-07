export function handleApiError(
  err: any,
  uiStore: { showToast: (msg: string, variant: 'success' | 'error' | 'info') => void },
  applyServerErrors?: (errors: Record<string, string[] | string>) => void,
  fallback = 'Something went wrong.',
) {
  const data = err?.response?.data
  if (data?.errors && typeof data.errors === 'object' && applyServerErrors) {
    applyServerErrors(data.errors)
    uiStore.showToast(data.message || 'Please fix the errors below.', 'error')
  } else if (data?.message) {
    uiStore.showToast(data.message, 'error')
  } else {
    uiStore.showToast(fallback, 'error')
  }
}
