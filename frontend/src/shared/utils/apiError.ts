export function handleApiError(
  err: any,
  uiStore: { showToast: (msg: string, variant: 'success' | 'error' | 'info') => void },
  applyServerErrors?: (errors: Record<string, string[] | string>) => void,
  fallback = 'Something went wrong.',
) {
  const status = err?.response?.status;
  const data = err?.response?.data;

  if (status === 403) {
    const rawMessage = data?.errors?.message || data?.message;
    const isGenericInternalError = typeof rawMessage === 'string' && rawMessage.toLowerCase().includes('internal server error');
    const displayMsg = !isGenericInternalError && rawMessage
      ? rawMessage
      : 'You do not have permission to perform this action.';
    uiStore.showToast(displayMsg, 'error');
    return;
  }

  if (data?.errors && typeof data.errors === 'object' && applyServerErrors) {
    applyServerErrors(data.errors);
    uiStore.showToast(data.message || 'Please fix the errors below.', 'error');
  } else if (data?.message) {
    uiStore.showToast(data.message, 'error');
  } else {
    uiStore.showToast(fallback, 'error');
  }
}
