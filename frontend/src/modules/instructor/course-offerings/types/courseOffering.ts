export interface CourseOffering {
  id: number;
  course: { id: number; code: string; name: string } | null;
  semester: { id: number; name: string; academic_year?: string } | null;
  status: 'draft' | 'pending_approval' | 'approved' | 'rejected' | 'cancelled';
  my_role?: 'lead' | 'instructor';
  created_at: string;
}
