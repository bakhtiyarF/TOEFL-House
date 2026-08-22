/**
 * Settings Page — Platform Services Module
 */

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Input } from '@shared/components/ui/input';
import { Label } from '@shared/components/ui/label';
import { Settings, Shield, Bell, Database, Workflow } from 'lucide-react';

export function SettingsPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold flex items-center gap-2">
          <Settings className="h-8 w-8" />
          Settings
        </h1>
        <p className="text-muted-foreground">System configuration and administration</p>
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        {/* Organization */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <Database className="h-5 w-5" />
              Organization
            </CardTitle>
            <CardDescription>Manage organization, campuses, and branches</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label>Organization Name</Label>
              <Input defaultValue="TOEFL House" />
            </div>
            <Button variant="outline" className="w-full">Manage Branches</Button>
          </CardContent>
        </Card>

        {/* Security */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <Shield className="h-5 w-5" />
              Security
            </CardTitle>
            <CardDescription>Roles, permissions, and user management</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <Button variant="outline" className="w-full">Manage Users</Button>
            <Button variant="outline" className="w-full">Roles & Permissions</Button>
            <Button variant="outline" className="w-full">Audit Log</Button>
          </CardContent>
        </Card>

        {/* Notifications */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <Bell className="h-5 w-5" />
              Notifications
            </CardTitle>
            <CardDescription>Configure notification preferences</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <Button variant="outline" className="w-full">Notification Settings</Button>
            <Button variant="outline" className="w-full">Notification History</Button>
          </CardContent>
        </Card>

        {/* Workflows & Rules */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <Workflow className="h-5 w-5" />
              Business Rules
            </CardTitle>
            <CardDescription>Rule engine and workflow configuration</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <Button variant="outline" className="w-full">Rule Definitions</Button>
            <Button variant="outline" className="w-full">Workflows</Button>
            <Button variant="outline" className="w-full">System Settings</Button>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
