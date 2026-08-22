export interface Teacher {
  id: string;
  full_name: string;
  phone?: string;
  email?: string;
  salary_type: string;
  base_salary: number;
  status: string;
  specialization?: string;
  qualification?: string;
  contract_type?: string;
  joined_date: string;
  classes?: number;
  students?: number;
  performance_score?: number;
}
