import { reactive, ref } from 'vue';

export function useResourceFilters<F extends Record<string, any>>(defaultFilters: F) {
  const search = ref('');
  const activeView = ref<'active' | 'archived'>('active');
  const filters = reactive<F>({ ...defaultFilters });
  const refreshing = ref(false);

  function toQuery(page = 1) {
    return {
      search: search.value || undefined,
      archived: activeView.value === 'archived',
      page,
      filter: { ...filters },
    };
  }

  return { search, activeView, filters, refreshing, toQuery };
}
