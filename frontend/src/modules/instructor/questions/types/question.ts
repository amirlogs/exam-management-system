export type QuestionType = 'mcq' | 'true_false' | 'short_answer' | 'essay';

export type QuestionDifficulty = 'easy' | 'medium' | 'hard';

export interface QuestionCourse {
  id: number;
  code: string;
  name: string;
  credit_hours: number;
}

export interface QuestionOption {
  id: number;
  option_text: string;
  is_correct: boolean;
}

export interface Question {
  id: number;
  course_id: number;
  course?: QuestionCourse | null;

  type: QuestionType;
  chapter: string | null;
  content: string;

  difficulty: QuestionDifficulty;
  status: string;

  options: QuestionOption[];

  created_at: string | null;
  updated_at: string | null;

  exam_id: number | string | null;
  marks: number | string | null;
}

export interface QuestionFilters {
  search?: string;
  course_id?: number | string;
  type?: QuestionType | '';
  chapter?: string;
  difficulty?: QuestionDifficulty | '';
}

export interface QuestionPayload {
  type: QuestionType;
  chapter?: string | null;
  content: string;
  difficulty: QuestionDifficulty;
  options?: string[];
  correct_answer?: string | null;
}

export interface QuestionListResponse {
  data: Question[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
}
