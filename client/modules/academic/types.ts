export interface Student {
  id: string;
  student_code: string;
  full_name: string;
  phone?: string;
  email?: string;
  gender?: 'male' | 'female';
  father_name?: string;
  tazkira_no?: string;
  whatsapp?: string;
  dob?: string;
  status: 'active' | 'inactive' | 'graduated' | 'suspended';
  discount_percent: number;
  registration_date: string;
  branch_id: string;
  class_name?: string;
}

export interface AcademicClass {
  id: string;
  name: string;
  teacher_id?: string;
  program_id?: string;
  level_id?: string;
  capacity: number;
  min_viable_size: number;
  status: string;
  fee: number;
  branch_id: string;
  enrolled_count?: number;
  fill_percent?: number;
}

export interface Session {
  id: string;
  class_id: string;
  date: string;
  start_time: string;
  end_time: string;
  topic?: string;
  status: 'scheduled' | 'completed' | 'cancelled';
  teacher_id?: string;
}

export interface Enrollment {
  id: string;
  student_id: string;
  program_id: string;
  program_version_id: string;
  class_id?: string;
  status: string;
  fee_snapshot_json: Record<string, any>;
  started_at: string;
}
