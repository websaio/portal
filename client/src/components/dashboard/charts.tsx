import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Download, MoreVertical } from 'lucide-react';
import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Legend,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';

interface ChartCardProps {
  title: string;
  children: React.ReactNode;
}

export function ChartCard({ title, children }: ChartCardProps) {
  return (
    <Card>
      <CardHeader className="px-4 py-5 sm:px-6 flex justify-between items-center">
        <CardTitle className="text-lg font-medium text-gray-900">{title}</CardTitle>
        <div className="flex space-x-1">
          <Button variant="ghost" size="icon" className="h-8 w-8">
            <Download className="h-4 w-4" />
          </Button>
          <Button variant="ghost" size="icon" className="h-8 w-8">
            <MoreVertical className="h-4 w-4" />
          </Button>
        </div>
      </CardHeader>
      <CardContent className="p-4">
        {children}
      </CardContent>
    </Card>
  );
}

const monthlyData = [
  { name: 'Jan', amount: 2400 },
  { name: 'Feb', amount: 1398 },
  { name: 'Mar', amount: 9800 },
  { name: 'Apr', amount: 3908 },
  { name: 'May', amount: 4800 },
  { name: 'Jun', amount: 3800 },
  { name: 'Jul', amount: 4300 },
  { name: 'Aug', amount: 5300 },
  { name: 'Sep', amount: 4500 },
  { name: 'Oct', amount: 6300 },
  { name: 'Nov', amount: 5400 },
  { name: 'Dec', amount: 8000 },
];

const feeDistributionData = [
  { name: 'Tuition Fee', value: 75000 },
  { name: 'Registration Fee', value: 15000 },
  { name: 'Exam Fee', value: 10000 },
  { name: 'Library Fee', value: 5000 },
  { name: 'Other Fees', value: 7500 },
];

const COLORS = ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ef4444'];

export function MonthlyPaymentChart() {
  return (
    <ChartCard title="Monthly Payment Trends">
      <div className="h-64 w-full">
        <ResponsiveContainer width="100%" height="100%">
          <AreaChart
            data={monthlyData}
            margin={{ top: 10, right: 30, left: 0, bottom: 0 }}
          >
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis tickFormatter={(value) => `$${value}`} />
            <Tooltip formatter={(value) => [`$${value}`, 'Amount']} />
            <Area 
              type="monotone" 
              dataKey="amount" 
              stroke="#3b82f6" 
              fill="#3b82f6" 
              fillOpacity={0.2} 
            />
          </AreaChart>
        </ResponsiveContainer>
      </div>
    </ChartCard>
  );
}

export function FeeDistributionChart() {
  return (
    <ChartCard title="Fee Collection Stats">
      <div className="h-64 w-full">
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Pie
              data={feeDistributionData}
              cx="50%"
              cy="50%"
              labelLine={false}
              outerRadius={80}
              fill="#8884d8"
              dataKey="value"
              label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
            >
              {feeDistributionData.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
              ))}
            </Pie>
            <Tooltip formatter={(value) => [`$${value}`, 'Amount']} />
            <Legend />
          </PieChart>
        </ResponsiveContainer>
      </div>
    </ChartCard>
  );
}

const studentCourseData = [
  { name: 'Computer Science', students: 45 },
  { name: 'Business', students: 35 },
  { name: 'Engineering', students: 28 },
  { name: 'Mathematics', students: 15 },
  { name: 'Arts', students: 22 },
];

export function StudentsByCourseChart() {
  return (
    <ChartCard title="Students by Course">
      <div className="h-64 w-full">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart
            data={studentCourseData}
            margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
          >
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis />
            <Tooltip />
            <Bar dataKey="students" fill="#3b82f6" />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </ChartCard>
  );
}
