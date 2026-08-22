/**
 * Teachers Page — People & HR Module
 */

import { Card, CardContent, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { Users, Plus, DollarSign } from 'lucide-react';

const mockTeachers = [
  { id: '1', name: 'Mr. Ahmed Karimi', salaryType: 'fixed', classes: 3, students: 45, status: 'active', phone: '+93 700 111 222' },
  { id: '2', name: 'Ms. Sarah Noori', salaryType: 'per_skill', classes: 4, students: 52, status: 'active', phone: '+93 700 222 333' },
  { id: '3', name: 'Mr. Karim Rahimi', salaryType: 'hybrid', classes: 2, students: 20, status: 'active', phone: '+93 700 333 444' },
  { id: '4', name: 'Ms. Fatima Ahmadi', salaryType: 'per_session', classes: 5, students: 68, status: 'active', phone: '+93 700 444 555' },
  { id: '5', name: 'Mr. Ali Hussaini', salaryType: 'per_level', classes: 3, students: 35, status: 'on_leave', phone: '+93 700 555 666' },
];

export function TeachersPage() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <Users className="h-8 w-8" />
            Teachers
          </h1>
          <p className="text-muted-foreground">Manage teaching staff and assignments</p>
        </div>
        <Button>
          <Plus className="h-4 w-4 me-2" />
          Add Teacher
        </Button>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {mockTeachers.map((teacher) => (
          <Card key={teacher.id}>
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <CardTitle className="text-base">{teacher.name}</CardTitle>
                <Badge variant={teacher.status === 'active' ? 'default' : 'secondary'}>
                  {teacher.status}
                </Badge>
              </div>
              <p className="text-sm text-muted-foreground">{teacher.phone}</p>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Salary Type:</span>
                <Badge variant="outline">{teacher.salaryType}</Badge>
              </div>
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Classes:</span>
                <span>{teacher.classes}</span>
              </div>
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Students:</span>
                <span>{teacher.students}</span>
              </div>
              <div className="pt-2 flex gap-2">
                <Button variant="outline" size="sm" className="flex-1">
                  Profile
                </Button>
                <Button variant="outline" size="sm" className="flex-1">
                  <DollarSign className="h-4 w-4 me-1" />
                  Salary
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
