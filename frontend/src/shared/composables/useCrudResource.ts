import { useUiStore } from '@/stores/ui';
import { onBeforeUnmount, ref } from 'vue';

export interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface ResourceQuery {
  page?: number;
  per_page?: number;
  search?: string;
}

interface CrudApi<T> {
  list: (params: ResourceQuery) => Promise<{ data: T[]; pagination: Pagination }>;
  listArchived: (params: ResourceQuery) => Promise<{ data: T[]; pagination: Pagination }>;
  remove: (id: number) => Promise<void>;
  restore: (id: number) => Promise<T>;
}

export function useCrudResource<T extends { id: number }>(api: CrudApi<T>, labelKey: keyof T) {
  const uiStore = useUiStore();

  const items = ref<T[]>([]);
  const archivedItems = ref<T[]>([]);
  const activeTab = ref<'active' | 'archived'>('active');
  const loading = ref(false);
  const error = ref<string | null>(null);
  const search = ref('');

  let searchTimer: ReturnType<typeof setTimeout> | null = null;

  const emptyPagination = (): Pagination => ({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
    from: null,
    to: null,
  });

  const activePagination = ref<Pagination>(emptyPagination());
  const archivedPagination = ref<Pagination>(emptyPagination());

  const currentPagination = () => (activeTab.value === 'active' ? activePagination.value : archivedPagination.value);
  const currentList = () => (activeTab.value === 'active' ? items.value : archivedItems.value);

  // Track if initial fetch has completed
  const isInitialLoad = ref(true);

  async function load(page = 1) {
    loading.value = true;
    error.value = null;

    try {
      if (isInitialLoad.value) {
        // Fetch BOTH active and archived on first render to get accurate initial tab counts
        const [activeRes, archivedRes] = await Promise.all([
          api.list({
            page,
            search: search.value || undefined,
          }),
          api.listArchived({
            page: 1,
            search: search.value || undefined,
          }),
        ]);

        items.value = activeRes.data;
        activePagination.value = activeRes.pagination;

        archivedItems.value = archivedRes.data;
        archivedPagination.value = archivedRes.pagination;

        isInitialLoad.value = false;
      } else {
        // Subsequent pagination / tab toggles only fetch the current tab's data
        if (activeTab.value === 'active') {
          const response = await api.list({
            page,
            search: search.value || undefined,
          });

          items.value = response.data;
          activePagination.value = response.pagination;
        } else {
          const response = await api.listArchived({
            page,
            search: search.value || undefined,
          });

          archivedItems.value = response.data;
          archivedPagination.value = response.pagination;
        }
      }
    } catch (err: any) {
      error.value =
        err?.response?.status === 401 || err?.response?.status === 403
          ? 'You are not authorized to view this resource.'
          : 'Failed to load data. Please try again.';
    } finally {
      loading.value = false;
    }
  }

  function setSearch(value: string) {
    search.value = value;

    if (searchTimer) {
      clearTimeout(searchTimer);
    }

    searchTimer = setTimeout(() => {
      load(1);
      searchTimer = null;
    }, 400);
  }

  function changeTab(tab: 'active' | 'archived') {
    if (activeTab.value === tab) return;

    activeTab.value = tab;
    load(1);
  }

  async function archive(item: T) {
    await api.remove(item.id);

    items.value = items.value.filter((i) => i.id !== item.id);
    activePagination.value.total = Math.max(0, activePagination.value.total - 1);
    archivedPagination.value.total += 1;

    uiStore.showToast(`${String(item[labelKey])} archived.`, 'success');
  }

  async function restore(item: T) {
    await api.restore(item.id);

    archivedItems.value = archivedItems.value.filter((i) => i.id !== item.id);
    archivedPagination.value.total = Math.max(0, archivedPagination.value.total - 1);
    activePagination.value.total += 1;

    uiStore.showToast(`${String(item[labelKey])} restored.`, 'success');
  }

  onBeforeUnmount(() => {
    if (searchTimer) {
      clearTimeout(searchTimer);
      searchTimer = null;
    }
  });

  return {
    items,
    archivedItems,
    activeTab,
    loading,
    error,
    search,
    activePagination,
    archivedPagination,
    currentPagination,
    currentList,
    load,
    setSearch,
    changeTab,
    archive,
    restore,
  };
}
