/**
 * CSV Export Utility
 * Generates and downloads CSV files from structured data
 */

/**
 * Convert array of objects to CSV string
 */
export function toCSV(data: Record<string, unknown>[], columns?: string[]): string {
  if (data.length === 0) return '';

  const headers = columns || Object.keys(data[0]);
  const rows = data.map((row) =>
    headers.map((col) => {
      const value = row[col];
      const str = value === null || value === undefined ? '' : String(value);
      // Escape CSV special characters
      if (str.includes(',') || str.includes('"') || str.includes('\n')) {
        return `"${str.replace(/"/g, '""')}"`;
      }
      return str;
    }).join(',')
  );

  return [headers.join(','), ...rows].join('\n');
}

/**
 * Download data as a CSV file
 */
export function downloadCSV(
  data: Record<string, unknown>[],
  filename: string,
  columns?: string[]
): void {
  const csv = toCSV(data, columns);
  const BOM = '\uFEFF'; // UTF-8 BOM for Excel compatibility
  const blob = new Blob([BOM + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);

  const link = document.createElement('a');
  link.href = url;
  link.download = `${filename}.csv`;
  link.style.display = 'none';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

/**
 * Export students list
 */
export function exportStudents(students: Array<{
  student_code: string;
  full_name: string;
  gender: string;
  phone: string;
  class_name: string;
  discount_percent: number;
  status: string;
  registration_date: string;
}>) {
  downloadCSV(
    students as unknown as Record<string, unknown>[],
    `students_${new Date().toISOString().split('T')[0]}`,
    ['student_code', 'full_name', 'gender', 'phone', 'class_name', 'discount_percent', 'status', 'registration_date']
  );
}

/**
 * Export financial transactions
 */
export function exportTransactions(transactions: Array<{
  date: string;
  type: string;
  category: string;
  description: string;
  amount: number;
  operator: string;
}>) {
  downloadCSV(
    transactions as unknown as Record<string, unknown>[],
    `transactions_${new Date().toISOString().split('T')[0]}`,
    ['date', 'type', 'category', 'description', 'amount', 'operator']
  );
}

/**
 * Export attendance report
 */
export function exportAttendance(records: Array<{
  date: string;
  class_name: string;
  student_name: string;
  student_code: string;
  status: string;
}>) {
  downloadCSV(
    records as unknown as Record<string, unknown>[],
    `attendance_${new Date().toISOString().split('T')[0]}`,
    ['date', 'class_name', 'student_name', 'student_code', 'status']
  );
}
