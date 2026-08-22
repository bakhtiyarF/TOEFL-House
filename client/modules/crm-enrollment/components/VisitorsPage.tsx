/**
 * Visitors Page — CRM & Enrollment Module
 */

import { Card, CardContent, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { Input } from '@shared/components/ui/input';
import { Users, Plus, Search, Phone, ArrowRight } from 'lucide-react';

const stageColors: Record<string, string> = {
  lead: 'bg-gray-100 text-gray-800',
  inquiry: 'bg-blue-100 text-blue-800',
  follow_up: 'bg-yellow-100 text-yellow-800',
  placement_booking: 'bg-purple-100 text-purple-800',
  placement_completed: 'bg-green-100 text-green-800',
  registration: 'bg-indigo-100 text-indigo-800',
  enrollment: 'bg-emerald-100 text-emerald-800',
  lost: 'bg-red-100 text-red-800',
};

const mockVisitors = [
  { id: '1', name: 'Sara Mohammadi', phone: '+93 700 111 111', stage: 'lead', source: 'friend', visitDate: '2026-08-20' },
  { id: '2', name: 'Hassan Rezai', phone: '+93 700 222 222', stage: 'follow_up', source: 'social', visitDate: '2026-08-19' },
  { id: '3', name: 'Maryam Karimi', phone: '+93 700 333 333', stage: 'placement_completed', source: 'ads', visitDate: '2026-08-18' },
  { id: '4', name: 'Reza Ahmadi', phone: '+93 700 444 444', stage: 'inquiry', source: 'referral', visitDate: '2026-08-18' },
  { id: '5', name: 'Nadia Faizi', phone: '+93 700 555 555', stage: 'registration', source: 'organic', visitDate: '2026-08-17' },
];

export function VisitorsPage() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <Users className="h-8 w-8" />
            Visitors & Leads
          </h1>
          <p className="text-muted-foreground">Manage visitor pipeline and enrollment conversion</p>
        </div>
        <Button>
          <Plus className="h-4 w-4 me-2" />
          Add Visitor
        </Button>
      </div>

      {/* Pipeline Summary */}
      <div className="grid gap-2 md:grid-cols-4 lg:grid-cols-6">
        {['lead', 'inquiry', 'follow_up', 'placement_completed', 'registration', 'enrollment'].map((stage) => {
          const count = mockVisitors.filter((v) => v.stage === stage).length;
          return (
            <Card key={stage} className="text-center p-3">
              <p className="text-2xl font-bold">{count}</p>
              <p className="text-xs text-muted-foreground capitalize">{stage.replace('_', ' ')}</p>
            </Card>
          );
        })}
      </div>

      {/* Visitors Table */}
      <Card>
        <CardHeader>
          <div className="relative">
            <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input placeholder="Search visitors..." className="ps-10" />
          </div>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b">
                  <th className="text-start py-3 px-4 font-medium">Name</th>
                  <th className="text-start py-3 px-4 font-medium">Phone</th>
                  <th className="text-start py-3 px-4 font-medium">Source</th>
                  <th className="text-start py-3 px-4 font-medium">Stage</th>
                  <th className="text-start py-3 px-4 font-medium">Visit Date</th>
                  <th className="text-end py-3 px-4 font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {mockVisitors.map((visitor) => (
                  <tr key={visitor.id} className="border-b hover:bg-muted/50">
                    <td className="py-3 px-4 font-medium">{visitor.name}</td>
                    <td className="py-3 px-4">
                      <div className="flex items-center gap-1">
                        <Phone className="h-3 w-3 text-muted-foreground" />
                        {visitor.phone}
                      </div>
                    </td>
                    <td className="py-3 px-4 capitalize">{visitor.source}</td>
                    <td className="py-3 px-4">
                      <Badge className={stageColors[visitor.stage]}>
                        {visitor.stage.replace('_', ' ')}
                      </Badge>
                    </td>
                    <td className="py-3 px-4 text-muted-foreground">{visitor.visitDate}</td>
                    <td className="py-3 px-4 text-end">
                      <Button variant="ghost" size="sm">
                        Convert <ArrowRight className="h-3 w-3 ms-1" />
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
