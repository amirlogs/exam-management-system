export interface Section {
  id: number;
  name: number | string;
  year_level: number;
  program_id: number;
  semester_id: number;
  created_at: string;
  updated_at: string;
}
export interface SaveSectionData {
  semester_id: number;
  program_id: number;
  year_level: number;
  name: number;
}
