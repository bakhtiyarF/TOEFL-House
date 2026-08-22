/**
 * Students Page — Academic Module
 * Manages student records, enrollment, and academic progress
 */

import { Card, CardContent, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { Input } from '@shared/components/ui/input';
import { GraduationCap, Plus, Search, Filter } from 'lucide-react';

// Mock data for demonstration
const mockStudents = [
  { id: '1', name: 'Ahmad Rahimi', code: 'STU-001', status: 'active', class: 'General English L3', phone: '+93 700 123 456' },
  { id: '2', name: 'Fatima Ahmadi', code: 'STU-002', status: 'active', class: 'TOEFL Prep L2', phone: '+93 700 234 567' },
  { id: '3', name: 'Mohammad Karimi', code: 'STU-003', status: 'graduated', class: 'IELTS Advanced', phone: '+93 700 345 678' },
  { id: '4', name: 'Zahra Noori', code: 'STU-004', status: 'active', class: 'General English L1', phone: '+93 700 456 789' },
  { id: '5', name: 'Ali Hussaini', code: 'STU-005', status: 'inactive', class: 'General English L4', phone: '+93 700 567 890' },
];

const statusColors: Record<string, string> = {
  active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
  inactive: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
  graduated: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
  suspended: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
};

export function StudentsPage() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <GraduationCap className="h-8 w-8" />
            Students
          </h1>
          <p className="text-muted-foreground">Manage student records and enrollment</p>
        </div>
        <Button>
          <Plus className="h-4 w-4 me-2" />
          Add Student
        </Button>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center gap-4">
            <div className="flex-1 relative">
              <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input placeholder="Search students..." className="ps-10" />
            </div>
            <Button variant="outline" size="icon">
              <Filter className="h-4 w-4" />
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b">
                  <th className="text-start py-3 px-4 font-medium">Student Code</th>
                  <th className="text-start py-3 px-4 font-medium">Name</th>
                  <th className="text-start py-3 px-4 font-medium">Class</th>
                  <th className="text-start py-3 px-4 font-medium">Phone</th>
                  <th className="text-start py-3 px-4 font-medium">Status</th>
                  <th className="text-end py-3 px-4 font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {mockStudents.map((student) => (
                  <tr key={student.id} className="border-b hover:bg-muted/50">
                    <td className="py-3 px-4 font-mono text-xs">{student.code}</td>
                    <td className="py-3 px-4 font-medium">{student.name}</td>
                    <td className="py-3 px-4">{student.class}</td>
                    <td className="py-3 px-4 text-muted-foreground">{student.phone}</td>
                    <td className="py-3 px-4">
                      <Badge className={statusColors[student.status]}>
                        {student.status}
                      </Badge>
                    </td>
                    <td className="py-3 px-4 text-end">
                      <Button variant="ghost" size="sm">
                        View
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
