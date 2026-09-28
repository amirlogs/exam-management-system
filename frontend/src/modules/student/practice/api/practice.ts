import api from '@/api/axios';
import type {
  PracticeExam,
  PracticeQuestion,
  PracticeQuestionHistory,
  CreatePracticeExamPayload,
  GeneratePracticeQuestionsPayload,
  SubmitPracticeAnswerPayload,
  SubmitPracticeAnswerResponse,
  QuestionGuidance,
  AskGuidancePayload,
} from '../types/practice';

interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
  errors?: any;
}

interface PaginatedResponse<T> {
  success: boolean;
  message: string;
  data: T[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
}

// ----------------- Practice Exams -----------------
export async function getPracticeExams(
  page = 1,
  perPage = 15,
  filters: { status?: string; search?: string } = {}
) {
  const response = await api.get<PaginatedResponse<PracticeExam>>('/practice-exams', {
    params: {
      page,
      per_page: perPage,
      ...filters,
    },
  });
  return response.data;
}

export async function createPracticeExam(payload: CreatePracticeExamPayload) {
  const response = await api.post<ApiResponse<PracticeExam>>('/practice-exams', payload);
  return response.data;
}

export async function getPracticeExam(id: number) {
  const response = await api.get<ApiResponse<PracticeExam>>(`/practice-exams/${id}`);
  return response.data;
}

export async function updatePracticeExam(id: number, payload: Partial<CreatePracticeExamPayload>) {
  const response = await api.patch<ApiResponse<PracticeExam>>(`/practice-exams/${id}`, payload);
  return response.data;
}

export async function deletePracticeExam(id: number) {
  const response = await api.delete<ApiResponse<null>>(`/practice-exams/${id}`);
  return response.data;
}

export async function startPracticeExam(id: number) {
  const response = await api.post<ApiResponse<PracticeExam>>(`/practice-exams/${id}/start`);
  return response.data;
}

export async function completePracticeExam(id: number) {
  const response = await api.post<ApiResponse<PracticeExam>>(`/practice-exams/${id}/complete`);
  return response.data;
}

export async function retakePracticeExam(id: number) {
  const response = await api.post<ApiResponse<PracticeExam>>(`/practice-exams/${id}/retake`);
  return response.data;
}

// ----------------- Questions & Answering -----------------
export async function getPracticeExamQuestions(
  examId: number,
  page = 1,
  perPage = 100,
  filters: { status?: string; type?: string; difficulty?: string } = {}
) {
  const response = await api.get<PaginatedResponse<PracticeQuestion>>(
    `/practice-exams/${examId}/questions`,
    {
      params: {
        page,
        per_page: perPage,
        ...filters,
      },
    }
  );
  return response.data;
}

export async function submitPracticeAnswer(
  examId: number,
  questionId: number,
  payload: SubmitPracticeAnswerPayload
) {
  const response = await api.post<ApiResponse<SubmitPracticeAnswerResponse>>(
    `/practice-exams/${examId}/questions/${questionId}/answers`,
    payload
  );
  return response.data;
}

// ----------------- AI Question Generation Pipeline -----------------
export async function generatePracticeQuestions(
  examId: number,
  payload: GeneratePracticeQuestionsPayload
) {
  const response = await api.post<ApiResponse<PracticeQuestionHistory>>(
    `/practice-exams/${examId}/questions`,
    payload
  );
  return response.data;
}

export async function getPracticeHistory(historyId: number) {
  const response = await api.get<ApiResponse<PracticeQuestionHistory>>(
    `/practice-exams-history/${historyId}`
  );
  return response.data;
}

export async function confirmPracticeQuestions(historyId: number) {
  const response = await api.post<ApiResponse<PracticeQuestionHistory>>(
    `/practice-exams-history/${historyId}/confirm`
  );
  return response.data;
}

export async function deleteGeneratedPracticeQuestion(historyId: number, index: number) {
  const response = await api.delete<ApiResponse<PracticeQuestionHistory>>(
    `/practice-exams-history/${historyId}/questions/${index}`
  );
  return response.data;
}

// ----------------- AI Question Guidance -----------------
export async function getQuestionGuidance(examId: number, questionId: number) {
  const response = await api.get<ApiResponse<QuestionGuidance[]>>(
    `/practice-exams/${examId}/questions/${questionId}/guidance`
  );
  return response.data;
}

export async function askQuestionGuidance(
  examId: number,
  questionId: number,
  payload: AskGuidancePayload
) {
  const response = await api.post<ApiResponse<QuestionGuidance>>(
    `/practice-exams/${examId}/questions/${questionId}/guidance`,
    payload
  );
  return response.data;
}
