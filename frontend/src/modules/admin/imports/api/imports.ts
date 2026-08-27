import api from '@/api/axios';
import type { Pagination } from '@/shared/composables/useCrudResource';
import type { ImportHistory, ImportRow, ImportRowMap, ImportStatus, ImportType } from '../types/import';

interface ListResponse<T> {
  data: T[];
  pagination: Pagination;
}

function normalizeRows(rows: unknown): ImportRowMap | null {
  if (!rows) return null;

  if (Array.isArray(rows)) {
    const result: ImportRowMap = {};

    rows.forEach((row: ImportRow) => {
      if (row.row_number !== undefined) {
        result[String(row.row_number)] = {
          row_number: row.row_number,
          data: row.data ?? {},
          status: row.status,
          errors: row.errors ?? [],
        };
      }
    });

    return result;
  }

  if (typeof rows === 'object') {
    return rows as ImportRowMap;
  }

  return null;
}

function normalizeImport(importHistory: ImportHistory): ImportHistory {
  return {
    ...importHistory,
    validated_data: normalizeRows(importHistory.validated_data),
  };
}

export async function listImports(type?: ImportType, page = 1, perPage = 12, status?: ImportStatus): Promise<ListResponse<ImportHistory>> {
  const res = await api.get('/imports', {
    params: {
      type,
      status,
      page,
      per_page: perPage,
    },
  });

  return {
    ...res.data,
    data: res.data.data.map(normalizeImport),
  };
}
export async function getImport(id: number): Promise<ImportHistory> {
  const res = await api.get(`/imports/${id}`);

  return normalizeImport(res.data.data);
}

export async function startImport(type: ImportType, file: File, context: Record<string, any>): Promise<ImportHistory> {
  const form = new FormData();

  form.append('file', file);
  form.append('context', JSON.stringify(context));

  const res = await api.post(`/imports/${type}`, form, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });

  return normalizeImport(res.data.data);
}

export async function updateImportRow(importId: number, rowIndex: string | number, data: Record<string, any>): Promise<ImportHistory> {
  const res = await api.patch(`/imports/${importId}/rows/${rowIndex}`, data);

  return normalizeImport(res.data.data);
}

export async function deleteImportRow(importId: number, rowIndex: string | number): Promise<ImportHistory> {
  const res = await api.delete(`/imports/${importId}/rows/${rowIndex}`);

  return normalizeImport(res.data.data);
}

export async function confirmImport(importId: number): Promise<ImportHistory> {
  const res = await api.post(`/imports/${importId}/confirm`);

  return normalizeImport(res.data.data);
}

export async function cancelImport(importId: number): Promise<void> {
  await api.delete(`/imports/${importId}`);
}
