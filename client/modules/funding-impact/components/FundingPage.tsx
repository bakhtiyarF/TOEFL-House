/**
 * Funding & Impact Page
 */

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@shared/components/ui/card';
import { Button } from '@shared/components/ui/button';
import { Badge } from '@shared/components/ui/badge';
import { Heart, Plus, Users, Target, Award, TrendingUp } from 'lucide-react';
import { formatAmount } from '@shared/lib/utils';

const fundingSummary = [
  { title: 'Total Donations', value: 1250000, icon: Heart, color: 'text-rose-600' },
  { title: 'Active Campaigns', value: 3, icon: Target, color: 'text-blue-600' },
  { title: 'Scholarships Awarded', value: 28, icon: Award, color: 'text-purple-600' },
  { title: 'Sponsored Students', value: 15, icon: Users, color: 'text-green-600' },
];

const campaigns = [
  { id: '1', name: 'Winter Scholarship Fund', target: 500000, raised: 350000, status: 'active' },
  { id: '2', name: 'New Library Books', target: 200000, raised: 200000, status: 'completed' },
  { id: '3', name: 'Teacher Training Program', target: 300000, raised: 125000, status: 'active' },
];

export function FundingPage() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <Heart className="h-8 w-8" />
            Funding & Impact
          </h1>
          <p className="text-muted-foreground">Manage donors, campaigns, and scholarships</p>
        </div>
        <Button>
          <Plus className="h-4 w-4 me-2" />
          New Campaign
        </Button>
      </div>

      {/* Summary */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {fundingSummary.map((card) => {
          const Icon = card.icon;
          return (
            <Card key={card.title}>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{card.title}</CardTitle>
                <Icon className={`h-5 w-5 ${card.color}`} />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">
                  {typeof card.value === 'number' && card.value > 1000
                    ? `${formatAmount(card.value)} AFN`
                    : card.value}
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>

      {/* Campaigns */}
      <Card>
        <CardHeader>
          <CardTitle>Active Campaigns</CardTitle>
          <CardDescription>Track fundraising progress</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {campaigns.map((campaign) => {
            const progress = Math.round((campaign.raised / campaign.target) * 100);
            return (
              <div key={campaign.id} className="space-y-2">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className="font-medium">{campaign.name}</span>
                    <Badge variant={campaign.status === 'completed' ? 'secondary' : 'default'}>
                      {campaign.status}
                    </Badge>
                  </div>
                  <span className="text-sm text-muted-foreground">
                    {formatAmount(campaign.raised)} / {formatAmount(campaign.target)} AFN
                  </span>
                </div>
                <div className="w-full bg-muted rounded-full h-2">
                  <div
                    className="bg-primary h-2 rounded-full transition-all"
                    style={{ width: `${Math.min(100, progress)}%` }}
                  />
                </div>
              </div>
            );
          })}
        </CardContent>
      </Card>
    </div>
  );
}
