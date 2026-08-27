export type ExamType = 'MIDTERM' | 'FINAL';
export type ExamStatus = 'draft' | 'pending_approval' | 'approved' | 'rejected' | 'scheduled' | 'active' | 'completed' | 'cancelled';
export type QuestionType = 'mcq' | 'true_false' | 'short_answer' | 'essay';
export type Difficulty = 'easy' | 'medium' | 'hard';

export interface CompositionEntry {
  marks_each?: number;
}
export type Composition = Partial<Record<'mcq' | 'true_false', CompositionEntry>>;

export interface Exam {
  id: number;
  title: string;
  course_offering_id: number;
  type: ExamType;
  duration_minutes: number;
  composition: Composition;
  status: ExamStatus;
  scheduled_start: string | null;
  scheduled_end: string | null;
  review_cycle: number;
  current_review_id: number | null;
  grading_status: string;
  total_marks: number;
  total_questions: number;
  creator: { id: number; name: string; email: string };
  created_at: string;
  updated_at: string;
}

export interface QuestionOption {
  id: number;
  option_text: string;
  is_correct: boolean;
}

export interface Question {
  id: number;
  course_id: number;
  type: QuestionType;
  chapter: string | null;
  content: string;
  difficulty: Difficulty;
  status: 'active' | 'archived';
  options: QuestionOption[];
  created_at: string;
  updated_at?: string;
  exam_id?: number;
  marks?: number;
}

export interface ImportRow {
  data: Record<string, any>;
  status: 'valid' | 'invalid';
  errors: string[];
}

export interface ImportHistory {
  id: number;
  uploaded_by: number;
  type: string;
  file_path: string;
  context: { course_id?: number; exam_id?: number; uploaded_by?: number };
  total_rows: number;
  valid_count: number;
  error_count: number;
  validated_data: Record<string, ImportRow> | null;
  status: 'pending' | 'ready_for_review' | 'confirmed' | 'failed';
  created_at: string;
  updated_at: string;
}
