import React from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { ArrowUp, ArrowDown } from 'lucide-react';
import { cn } from '@/lib/utils';

interface StatCardProps {
  title: string;
  value: string | number;
  icon: React.ReactNode;
  change?: string | number;
  changeType?: 'increase' | 'decrease';
  iconBgColor?: string;
  iconColor?: string;
}

export function StatCard({
  title,
  value,
  icon,
  change,
  changeType = 'increase',
  iconBgColor = 'bg-primary-100',
  iconColor = 'text-primary-600'
}: StatCardProps) {
  return (
    <Card>
      <CardContent className="p-5">
        <div className="flex items-center">
          <div className={cn("flex-shrink-0 rounded-md p-3", iconBgColor)}>
            {React.cloneElement(icon as React.ReactElement, { 
              className: cn("h-5 w-5", iconColor) 
            })}
          </div>
          <div className="ml-5 w-0 flex-1">
            <dl>
              <dt className="text-sm font-medium text-gray-500 truncate">{title}</dt>
              <dd className="flex items-baseline">
                <div className="text-2xl font-semibold text-gray-900">{value}</div>
                {change && (
                  <div className={cn(
                    "ml-2 flex items-baseline text-sm font-semibold",
                    changeType === 'increase' ? 'text-green-600' : 'text-red-600'
                  )}>
                    {changeType === 'increase' ? (
                      <ArrowUp className="h-3 w-3" />
                    ) : (
                      <ArrowDown className="h-3 w-3" />
                    )}
                    <span className="sr-only">
                      {changeType === 'increase' ? 'Increased' : 'Decreased'} by
                    </span>
                    {change}
                  </div>
                )}
              </dd>
            </dl>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
