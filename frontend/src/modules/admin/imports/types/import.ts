export type ImportType = 'students' | 'instructors' | 'sections' | 'questions' | 'users';

export type ImportStatus = 'pending' | 'processing' | 'ready_for_review' | 'confirmed' | 'failed';

export interface ImportRow {
  row_number?: number;
  data: Record<string, any>;
  status: 'valid' | 'invalid';
  errors: string[];
}

export type ImportRowMap = Record<string, ImportRow>;

export interface ImportHistory {
  id: number;
  uploaded_by: {
    id: number;
    name: string;
    email: string;
  };
  type: ImportType;
  file_path: string;
  context: Record<string, any>;
  year_level?: number | null;
  semester_id?: number | null;
  total_rows: number;
  valid_count: number;
  error_count: number;
  validated_data: ImportRowMap | null;
  status: ImportStatus;
  created_at: string;
  updated_at: string;
}
