export type ImportStatus = 'pending' | 'processing' | 'ready_for_review' | 'confirmed' | 'approved' | 'failed'
export type QuestionType = 'mcq' | 'essay' | 'true_false' | 'short_answer'
export type Difficulty = 'easy' | 'medium' | 'hard'
export type RowStatus = 'valid' | 'invalid'

export interface QuestionRowData {
  type: QuestionType
  text: string | null
  difficulty: Difficulty | string
  points: number | string
  options?: string[] | string | null
  correct_answer?: string | null
}

export interface QuestionRow {
  row: number
  data: QuestionRowData
  status: RowStatus
  errors: string[] | null
}

export interface QuestionBankImport {
  import_id: number
  course_id: number
  status: ImportStatus
  uploaded_by: number
  file_path: string
  valid_count: number
  error_count: number
  failure_reason: string | null
  confirmed_by: number | null
  confirmed_at: string | null
  approved_by: number | null
  approved_at: string | null
  validated_data: QuestionRow[] | null
  created_at: string
  updated_at: string
}