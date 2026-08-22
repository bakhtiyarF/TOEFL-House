/**
 * Settings Page — Platform Services Module
 * System configuration, rule engine admin, and audit log
 */

import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { Input } from '@shared/components/ui/input';
import { Label } from '@shared/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@shared/components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@shared/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@shared/components/ui/dialog';
import {
  Settings, Shield, Bell, Database, Workflow, Cpu, Play, Eye,
  AlertTriangle, CheckCircle2, Code, Users, Building2, Globe,
} from 'lucide-react';

interface RuleDefinition {
  id: string;
  name: string;
  category: string;
  priority: number;
  is_active: boolean;
  conditions_summary: string;
  actions_summary: string;
}

const defaultRules: RuleDefinition[] = [
  { id: '1', name: 'Discount Cap Enforcement', category: 'discount', priority: 200, is_active: true, conditions_summary: 'discountPercent > 30', actions_summary: 'Cap at 30%, warn' },
  { id: '2', name: 'Placement Test Fee — First Attempt', category: 'fee', priority: 100, is_active: true, conditions_summary: 'isFirstPlacementTest == true', actions_summary: 'Set fee = 300 AFN' },
  { id: '3', name: 'Placement Test Fee — Retake', category: 'fee', priority: 100, is_active: true, conditions_summary: 'isFirstPlacementTest == false', actions_summary: 'Set fee = 0 AFN' },
  { id: '4', name: 'Friend Referral Discount', category: 'discount', priority: 80, is_active: true, conditions_summary: 'leadSource == "friend"', actions_summary: 'Add 10% discount' },
  { id: '5', name: 'Early Registration Discount', category: 'discount', priority: 70, is_active: true, conditions_summary: 'daysBeforeClassStart >= 14', actions_summary: 'Add 5% discount' },
  { id: '6', name: 'Attendance Warning', category: 'attendance', priority: 100, is_active: true, conditions_summary: 'attendanceRate < 85', actions_summary: 'Warn + SMS parent' },
  { id: '7', name: 'Attendance Critical', category: 'attendance', priority: 150, is_active: true, conditions_summary: 'attendanceRate < 60', actions_summary: 'Critical warning' },
  { id: '8', name: 'Automatic Savings — 5%', category: 'finance', priority: 100, is_active: true, conditions_summary: 'type == income, amount > 0', actions_summary: 'Calculate 5% savings' },
  { id: '9', name: 'Profit Withdrawal Block', category: 'finance', priority: 200, is_active: true, conditions_summary: 'reserveFundMet == false', actions_summary: 'BLOCK withdrawal' },
  { id: '10', name: 'Minimum Class Size Warning', category: 'academic', priority: 100, is_active: true, conditions_summary: 'enrolled < 6, class active', actions_summary: 'Warn: consider merging' },
  { id: '11', name: 'Payroll Multiplier — Below Min', category: 'payroll', priority: 100, is_active: true, conditions_summary: 'enrolledCount < 6', actions_summary: '0.75x multiplier' },
  { id: '12', name: 'Payroll Multiplier — Standard', category: 'payroll', priority: 100, is_active: true, conditions_summary: 'enrolledCount >= 6', actions_summary: '1.0x multiplier' },
];

const categoryColors: Record<string, string> = {
  fee: 'bg-yellow-100 text-yellow-800',
  discount: 'bg-green-100 text-green-800',
  attendance: 'bg-blue-100 text-blue-800',
  finance: 'bg-purple-100 text-purple-800',
  academic: 'bg-orange-100 text-orange-800',
  payroll: 'bg-teal-100 text-teal-800',
  promotion: 'bg-pink-100 text-pink-800',
  scholarship: 'bg-indigo-100 text-indigo-800',
};

export function SettingsPage() {
  const [selectedRule, setSelectedRule] = useState<RuleDefinition | null>(null);
  const [ruleFilter, setRuleFilter] = useState('all');

  const filteredRules = defaultRules.filter((r) =>
    ruleFilter === 'all' || r.category === ruleFilter
  );

  const auditLogs = [
    { action: 'User login', operator: 'Ahmad Rahimi', date: '2026-08-22', time: '09:15', ip: '192.168.1.10' },
    { action: 'Student created', operator: 'Reception', date: '2026-08-22', time: '09:22', ip: '192.168.1.5' },
    { action: 'Payment recorded', operator: 'Reception', date: '2026-08-22', time: '09:45', ip: '192.168.1.5' },
    { action: 'Rule updated: Discount Cap', operator: 'Admin', date: '2026-08-21', time: '14:30', ip: '192.168.1.1' },
    { action: 'Teacher salary processed', operator: 'Finance', date: '2026-08-21', time: '16:00', ip: '192.168.1.3' },
    { action: 'Branch created: Herat', operator: 'Owner', date: '2026-08-20', time: '10:00', ip: '192.168.1.1' },
  ];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold flex items-center gap-2">
          <Settings className="h-8 w-8" />
          Settings
        </h1>
        <p className="text-muted-foreground">System configuration, rules engine, and administration</p>
      </div>

      <Tabs defaultValue="rules">
        <TabsList>
          <TabsTrigger value="rules" className="gap-1"><Cpu className="h-4 w-4" /> Business Rules</TabsTrigger>
          <TabsTrigger value="organization" className="gap-1"><Building2 className="h-4 w-4" /> Organization</TabsTrigger>
          <TabsTrigger value="security" className="gap-1"><Shield className="h-4 w-4" /> Security</TabsTrigger>
          <TabsTrigger value="audit" className="gap-1"><Eye className="h-4 w-4" /> Audit Log</TabsTrigger>
          <TabsTrigger value="system" className="gap-1"><Database className="h-4 w-4" /> System</TabsTrigger>
        </TabsList>

        {/* Rule Engine Tab */}
        <TabsContent value="rules">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="flex items-center gap-2">
                    <Cpu className="h-5 w-5" />
                    Rule Engine
                  </CardTitle>
                  <CardDescription>
                    {defaultRules.length} rules configured · Evaluates in priority order (highest first) · Single forward pass
                  </CardDescription>
                </div>
                <Button size="sm">
                  <Play className="h-4 w-4 me-1" />
                  Add Rule
                </Button>
              </div>
              <div className="flex gap-2 flex-wrap mt-2">
                {['all', 'fee', 'discount', 'attendance', 'finance', 'academic', 'payroll'].map((cat) => (
                  <Button
                    key={cat}
                    variant={ruleFilter === cat ? 'default' : 'outline'}
                    size="sm"
                    onClick={() => setRuleFilter(cat)}
                  >
                    {cat === 'all' ? 'All' : cat.charAt(0).toUpperCase() + cat.slice(1)}
                  </Button>
                ))}
              </div>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Rule Name</TableHead>
                    <TableHead>Category</TableHead>
                    <TableHead className="text-center">Priority</TableHead>
                    <TableHead>Condition</TableHead>
                    <TableHead>Action</TableHead>
                    <TableHead className="text-center">Status</TableHead>
                    <TableHead className="text-end">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredRules.map((rule) => (
                    <TableRow key={rule.id}>
                      <TableCell className="font-medium">{rule.name}</TableCell>
                      <TableCell>
                        <Badge className={categoryColors[rule.category] || 'bg-gray-100'}>
                          {rule.category}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-center font-mono">{rule.priority}</TableCell>
                      <TableCell className="text-xs text-muted-foreground font-mono max-w-[200px] truncate">{rule.conditions_summary}</TableCell>
                      <TableCell className="text-xs text-muted-foreground">{rule.actions_summary}</TableCell>
                      <TableCell className="text-center">
                        {rule.is_active ? (
                          <CheckCircle2 className="h-4 w-4 text-green-600 mx-auto" />
                        ) : (
                          <AlertTriangle className="h-4 w-4 text-orange-600 mx-auto" />
                        )}
                      </TableCell>
                      <TableCell className="text-end">
                        <div className="flex items-center justify-end gap-1">
                          <Button variant="ghost" size="icon" onClick={() => setSelectedRule(rule)}>
                            <Code className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="icon">
                            <Play className="h-4 w-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Organization Tab */}
        <TabsContent value="organization">
          <div className="grid gap-6 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg"><Building2 className="h-5 w-5" /> Organization</CardTitle>
                <CardDescription>Basic organization settings</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Label>Organization Name</Label>
                  <Input defaultValue="TOEFL House" />
                </div>
                <div className="space-y-2">
                  <Label>Default Currency</Label>
                  <Input defaultValue="AFN (Afghan Afghani)" disabled />
                </div>
                <div className="space-y-2">
                  <Label>Timezone</Label>
                  <Input defaultValue="Asia/Kabul (UTC+4:30)" disabled />
                </div>
                <div className="space-y-2">
                  <Label>Date Format</Label>
                  <Input defaultValue="Gregorian ISO (YYYY-MM-DD)" disabled />
                </div>
                <Button>Save Changes</Button>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg"><Globe className="h-5 w-5" /> Branches</CardTitle>
                <CardDescription>Manage campus branches</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3">
                {[
                  { name: 'Main Campus - Kabul', status: 'active', users: 12 },
                  { name: 'Herat Branch', status: 'active', users: 5 },
                  { name: 'Mazar-i-Sharif', status: 'inactive', users: 0 },
                ].map((branch, i) => (
                  <div key={i} className="flex items-center justify-between p-3 rounded-lg border">
                    <div>
                      <p className="font-medium text-sm">{branch.name}</p>
                      <p className="text-xs text-muted-foreground">{branch.users} users</p>
                    </div>
                    <Badge variant={branch.status === 'active' ? 'default' : 'secondary'}>{branch.status}</Badge>
                  </div>
                ))}
                <Button variant="outline" className="w-full">Add Branch</Button>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Security Tab */}
        <TabsContent value="security">
          <div className="grid gap-6 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg"><Users className="h-5 w-5" /> Roles</CardTitle>
                <CardDescription>10 system roles defined</CardDescription>
              </CardHeader>
              <CardContent className="space-y-2">
                {[
                  { name: 'Owner', perms: '~85', scope: 'Organization' },
                  { name: 'General Manager', perms: '~50', scope: 'Branch+' },
                  { name: 'Head of Department', perms: '~25', scope: 'Department' },
                  { name: 'Finance Manager', perms: '~20', scope: 'Branch' },
                  { name: 'Receptionist', perms: '~15', scope: 'Branch' },
                  { name: 'Counselor', perms: '~5', scope: 'Branch' },
                  { name: 'Teacher', perms: '10', scope: 'Own/Class' },
                  { name: 'Data Entry', perms: '~8', scope: 'Branch' },
                  { name: 'Designer', perms: '~3', scope: 'Branch' },
                  { name: 'Donor Manager', perms: '~12', scope: 'Mixed' },
                ].map((role, i) => (
                  <div key={i} className="flex items-center justify-between p-2 rounded border text-sm">
                    <span className="font-medium">{role.name}</span>
                    <div className="flex items-center gap-2">
                      <span className="text-xs text-muted-foreground">{role.perms} perms</span>
                      <Badge variant="outline" className="text-xs">{role.scope}</Badge>
                    </div>
                  </div>
                ))}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg"><Bell className="h-5 w-5" /> Notifications</CardTitle>
                <CardDescription>Notification preferences</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {[
                  { event: 'New student registered', sms: true, email: false, inApp: true },
                  { event: 'Payment received', sms: false, email: true, inApp: true },
                  { event: 'Attendance below threshold', sms: true, email: false, inApp: true },
                  { event: 'Expense approval needed', sms: false, email: true, inApp: true },
                ].map((notif, i) => (
                  <div key={i} className="flex items-center justify-between p-3 rounded border text-sm">
                    <span>{notif.event}</span>
                    <div className="flex gap-2">
                      {notif.sms && <Badge variant="outline" className="text-xs">SMS</Badge>}
                      {notif.email && <Badge variant="outline" className="text-xs">Email</Badge>}
                      {notif.inApp && <Badge variant="outline" className="text-xs">In-App</Badge>}
                    </div>
                  </div>
                ))}
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Audit Log Tab */}
        <TabsContent value="audit">
          <Card>
            <CardHeader>
              <CardTitle>Audit Log</CardTitle>
              <CardDescription>System activity history (read-only)</CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Action</TableHead>
                    <TableHead>Operator</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead>Time</TableHead>
                    <TableHead>IP Address</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {auditLogs.map((log, i) => (
                    <TableRow key={i}>
                      <TableCell className="font-medium">{log.action}</TableCell>
                      <TableCell>{log.operator}</TableCell>
                      <TableCell className="text-muted-foreground">{log.date}</TableCell>
                      <TableCell className="text-muted-foreground">{log.time}</TableCell>
                      <TableCell className="font-mono text-xs text-muted-foreground">{log.ip}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>

        {/* System Tab */}
        <TabsContent value="system">
          <div className="grid gap-6 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Financial Settings</CardTitle>
                <CardDescription>Global financial parameters</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Label>Daily Saving Percent</Label>
                  <div className="flex items-center gap-2">
                    <Input type="number" defaultValue={5} className="w-24" />
                    <span className="text-sm text-muted-foreground">% of income auto-saved</span>
                  </div>
                </div>
                <div className="space-y-2">
                  <Label>Saving Balance</Label>
                  <Input defaultValue="125,000 AFN" disabled />
                </div>
                <div className="space-y-2">
                  <Label>Main Account Balance</Label>
                  <Input defaultValue="890,000 AFN" disabled />
                </div>
                <Button>Update Settings</Button>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="text-lg">System Information</CardTitle>
                <CardDescription>Environment & version info</CardDescription>
              </CardHeader>
              <CardContent className="space-y-2 text-sm">
                <div className="flex justify-between"><span className="text-muted-foreground">App Version</span><span className="font-mono">v3.0.0</span></div>
                <div className="flex justify-between"><span className="text-muted-foreground">PHP Version</span><span className="font-mono">8.3+</span></div>
                <div className="flex justify-between"><span className="text-muted-foreground">Laravel</span><span className="font-mono">11.x</span></div>
                <div className="flex justify-between"><span className="text-muted-foreground">React</span><span className="font-mono">19.x</span></div>
                <div className="flex justify-between"><span className="text-muted-foreground">Database</span><span className="font-mono">MySQL 8</span></div>
                <div className="flex justify-between"><span className="text-muted-foreground">Cache/Queue</span><span className="font-mono">Redis 7</span></div>
                <div className="flex justify-between"><span className="text-muted-foreground">Tables</span><span className="font-mono">73</span></div>
                <div className="flex justify-between"><span className="text-muted-foreground">Modules</span><span className="font-mono">8 active</span></div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>

      {/* Rule Detail Dialog */}
      <Dialog open={!!selectedRule} onOpenChange={() => setSelectedRule(null)}>
        <DialogContent className="max-w-lg">
          {selectedRule && (
            <>
              <DialogHeader>
                <DialogTitle className="flex items-center gap-2">
                  <Code className="h-5 w-5" />
                  {selectedRule.name}
                </DialogTitle>
                <DialogDescription>
                  Category: {selectedRule.category} · Priority: {selectedRule.priority}
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-4">
                <div>
                  <Label className="text-xs text-muted-foreground">Conditions</Label>
                  <div className="bg-muted p-3 rounded-md font-mono text-sm mt-1">
                    {selectedRule.conditions_summary}
                  </div>
                </div>
                <div>
                  <Label className="text-xs text-muted-foreground">Actions</Label>
                  <div className="bg-muted p-3 rounded-md font-mono text-sm mt-1">
                    {selectedRule.actions_summary}
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Badge className={categoryColors[selectedRule.category]}>{selectedRule.category}</Badge>
                  <Badge variant={selectedRule.is_active ? 'default' : 'secondary'}>
                    {selectedRule.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                  <Badge variant="outline">Priority: {selectedRule.priority}</Badge>
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setSelectedRule(null)}>Close</Button>
                <Button variant="outline"><Play className="h-4 w-4 me-1" /> Dry Run</Button>
                <Button>Edit Rule</Button>
              </DialogFooter>
            </>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
