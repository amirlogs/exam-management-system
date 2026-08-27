export interface College {
  id: number;
  name: string;
  university_id: number;
  university?: { id: number; name: string; code: string };
  created_at: string;
  updated_at: string;
}
export interface CreateCollegeData {
  name: string;
}
export interface UpdateCollegeData {
  name?: string;
}
