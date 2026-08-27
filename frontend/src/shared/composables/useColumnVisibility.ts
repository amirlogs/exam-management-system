import { ref, watch } from 'vue';

export function useColumnVisibility(storageKey: string, allColumns: { key: string; label: string }[]) {
  const stored = localStorage.getItem(`columns:${storageKey}`);
  const visible = ref<Set<string>>(stored ? new Set(JSON.parse(stored)) : new Set(allColumns.map((c) => c.key)));

  watch(
    visible,
    (val) => {
      localStorage.setItem(`columns:${storageKey}`, JSON.stringify([...val]));
    },
    { deep: true },
  );

  function toggle(key: string) {
    visible.value.has(key) ? visible.value.delete(key) : visible.value.add(key);
  }

  return { visible, toggle, allColumns };
}
