export type ExamType = 'MIDTERM' | 'FINAL' | string;

export type ExamStatus =
  | 'draft'
  | 'pending_approval'
  | 'approved'
  | 'rejected'
  | 'scheduled'
  | 'active'
  | 'completed'
  | 'archived'
  | 'cancelled';

export type GradingStatus =
  | 'not_started'
  | 'in_progress'
  | 'submitted'
  | 'verified'
  | 'published'
  | string;

export type QuestionType = 'mcq' | 'true_false' | 'short_answer' | 'essay' | 'MCQ' | 'TRUE_FALSE' | 'SHORT_ANSWER' | 'ESSAY';

export interface ExamCompositionItem {
  marks_each?: number;
}

export type ExamComposition = Record<string, ExamCompositionItem>;

export interface ExamCreator {
  id: number;
  name: string | null;
  email: string;
}

export interface QuestionOption {
  id: number;
  option_text: string;
  is_correct: boolean;
}

export interface ExamQuestionItem {
  id: number;
  course_id?: number;
  exam_id?: number;
  exam_question_id?: number;
  type: QuestionType;
  chapter?: string | null;
  content: string;
  difficulty?: string | null;
  status: string;
  marks?: number | string;
  options?: QuestionOption[];
  created_at?: string | null;
  updated_at?: string | null;
  pivot?: {
    id: number;
    exam_id: number;
    question_id: number;
    order_number?: number;
    marks: number;
  };
  question?: {
    id: number;
    course_id: number;
    type: QuestionType;
    chapter?: string | null;
    content: string;
    difficulty?: string | null;
    status: string;
    options?: QuestionOption[];
    created_at?: string | null;
    updated_at?: string | null;
  };
}

export interface Exam {
  id: number;
  title: string;
  course_offering_id: number;
  type: ExamType;
  duration_minutes: number;
  composition: ExamComposition;
  status: ExamStatus;
  scheduled_start?: string | null;
  scheduled_end?: string | null;
  total_marks?: number;
  total_questions?: number;
  review_cycle?: number;
  current_review_id?: number | null;
  creator?: ExamCreator;
  created_by?: number;
  activated_at?: string | null;
  ended_at?: string | null;
  grading_status?: GradingStatus;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface PaginatedResponse<T> {
  success: boolean;
  message: string;
  data: T[];
  pagination: Pagination;
  errors: unknown;
}

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
  errors: unknown;
}

export interface CreateExamPayload {
  title: string;
  type: ExamType;
  duration_minutes: number;
  composition: ExamComposition;
}

export interface AddExamQuestionPayload {
  question_id: number;
  marks?: number;
}

export interface BulkExamQuestionPayload {
  questions: AddExamQuestionPayload[];
}

export interface ScheduleExamPayload {
  scheduled_start?: string;
  scheduled_start_time?: string;
}

export interface UpdateSchedulePayload {
  scheduled_start?: string;
  scheduled_start_time?: string;
  duration_minutes?: number;
}

