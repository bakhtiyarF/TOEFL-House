/**
 * Classes Page — Academic Module
 */

import { Card, CardContent, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { Input } from '@shared/components/ui/input';
import { ClipboardList, Plus, Search, Users, Calendar } from 'lucide-react';

const mockClasses = [
  { id: '1', name: 'General English - Level 3', teacher: 'Mr. Ahmed', students: 15, capacity: 20, schedule: 'Sun/Tue 09:00-11:00', status: 'active' },
  { id: '2', name: 'TOEFL Preparation - Level 2', teacher: 'Ms. Sarah', students: 12, capacity: 15, schedule: 'Mon/Wed 14:00-16:00', status: 'active' },
  { id: '3', name: 'IELTS Advanced', teacher: 'Mr. Karim', students: 8, capacity: 12, schedule: 'Sat 10:00-13:00', status: 'active' },
  { id: '4', name: 'General English - Level 1', teacher: 'Ms. Fatima', students: 18, capacity: 20, schedule: 'Sun/Tue 16:00-18:00', status: 'active' },
  { id: '5', name: 'Business English', teacher: 'Mr. Ali', students: 0, capacity: 15, schedule: 'Mon/Wed 09:00-11:00', status: 'cancelled' },
];

export function ClassesPage() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <ClipboardList className="h-8 w-8" />
            Classes
          </h1>
          <p className="text-muted-foreground">Manage class schedules and assignments</p>
        </div>
        <Button>
          <Plus className="h-4 w-4 me-2" />
          Create Class
        </Button>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {mockClasses.map((cls) => (
          <Card key={cls.id} className={cls.status === 'cancelled' ? 'opacity-60' : ''}>
            <CardHeader className="pb-3">
              <div className="flex items-center justify-between">
                <CardTitle className="text-base">{cls.name}</CardTitle>
                <Badge variant={cls.status === 'active' ? 'default' : 'secondary'}>
                  {cls.status}
                </Badge>
              </div>
              <p className="text-sm text-muted-foreground">{cls.teacher}</p>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex items-center gap-2 text-sm">
                <Users className="h-4 w-4 text-muted-foreground" />
                <span>{cls.students}/{cls.capacity} students</span>
              </div>
              <div className="flex items-center gap-2 text-sm">
                <Calendar className="h-4 w-4 text-muted-foreground" />
                <span>{cls.schedule}</span>
              </div>
              <div className="pt-2">
                <Button variant="outline" size="sm" className="w-full">
                  View Details
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
