import { useEffect, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { MainLayout } from "@/components/layout/main-layout";
import { StatCard } from "@/components/dashboard/stat-card";
import { MonthlyPaymentChart, FeeDistributionChart } from "@/components/dashboard/charts";
import { DataTable } from "@/components/ui/data-table";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { 
  Users,
  CreditCard,
  AlertCircle,
  FileText,
} from "lucide-react";
import { format } from "date-fns";
import { Badge } from "@/components/ui/badge";

interface DashboardStats {
  totalStudents: number;
  totalPayments: number;
  pendingDues: number;
  generatedReceipts: number;
}

interface Payment {
  id: number;
  studentName: string;
  studentId: string;
  amount: string;
  paymentType: string;
  paymentDate: string;
  status: string;
}

interface Student {
  id: number;
  firstName: string;
  lastName: string;
  email: string;
  studentId: string;
  course: string;
  status: string;
}

export default function Dashboard() {
  const [stats, setStats] = useState<DashboardStats>({
    totalStudents: 0,
    totalPayments: 0,
    pendingDues: 0,
    generatedReceipts: 0
  });

  // Fetch dashboard stats
  const { data: statsData } = useQuery({
    queryKey: ['/api/dashboard/stats'],
  });

  // Fetch recent payments
  const { data: recentPaymentsData } = useQuery({
    queryKey: ['/api/dashboard/recent-payments'],
  });

  // Fetch recent students
  const { data: recentStudentsData } = useQuery({
    queryKey: ['/api/dashboard/recent-students'],
  });

  useEffect(() => {
    if (statsData) {
      setStats(statsData);
    }
  }, [statsData]);

  // Payment columns
  const paymentColumns = [
    {
      accessorKey: "studentName",
      header: "Student",
      cell: ({ row }: any) => {
        const payment = row.original;
        const initials = payment.studentName
          .split(' ')
          .map((name: string) => name[0])
          .join('')
          .toUpperCase();
        
        return (
          <div className="flex items-center">
            <div className="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 text-sm font-medium">
              {initials}
            </div>
            <div className="ml-4">
              <div className="text-sm font-medium text-gray-900">{payment.studentName}</div>
              <div className="text-sm text-gray-500">ID: {payment.studentId}</div>
            </div>
          </div>
        );
      },
    },
    {
      accessorKey: "amount",
      header: "Amount",
      cell: ({ row }: any) => {
        const payment = row.original;
        return (
          <div>
            <div className="text-sm text-gray-900">${Number(payment.amount).toFixed(2)}</div>
            <div className="text-sm text-gray-500">{payment.paymentType.replace('_', ' ').charAt(0).toUpperCase() + payment.paymentType.replace('_', ' ').slice(1)}</div>
          </div>
        );
      },
    },
    {
      accessorKey: "paymentDate",
      header: "Date",
      cell: ({ row }: any) => (
        <span className="text-sm text-gray-500">
          {format(new Date(row.original.paymentDate), "MMM dd, yyyy")}
        </span>
      ),
    },
    {
      accessorKey: "status",
      header: "Status",
      cell: ({ row }: any) => {
        const status = row.original.status;
        let badgeClass = "";
        
        switch (status) {
          case "completed":
            badgeClass = "bg-green-100 text-green-800";
            break;
          case "pending":
            badgeClass = "bg-yellow-100 text-yellow-800";
            break;
          default:
            badgeClass = "bg-gray-100 text-gray-800";
        }
        
        return (
          <Badge variant="outline" className={badgeClass}>
            {status.charAt(0).toUpperCase() + status.slice(1)}
          </Badge>
        );
      },
    },
  ];

  // Student columns
  const studentColumns = [
    {
      accessorKey: "name",
      header: "Name",
      cell: ({ row }: any) => {
        const student = row.original;
        const fullName = `${student.firstName} ${student.lastName}`;
        const initials = fullName
          .split(' ')
          .map((name: string) => name[0])
          .join('')
          .toUpperCase();
        
        return (
          <div className="flex items-center">
            <div className="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 text-sm font-medium">
              {initials}
            </div>
            <div className="ml-4">
              <div className="text-sm font-medium text-gray-900">{fullName}</div>
              <div className="text-sm text-gray-500">{student.email}</div>
            </div>
          </div>
        );
      },
    },
    {
      accessorKey: "studentId",
      header: "ID",
      cell: ({ row }: any) => (
        <span className="text-sm text-gray-500">
          {row.original.studentId}
        </span>
      ),
    },
    {
      accessorKey: "course",
      header: "Course",
      cell: ({ row }: any) => (
        <span className="text-sm text-gray-500">
          {row.original.course}
        </span>
      ),
    },
    {
      accessorKey: "status",
      header: "Status",
      cell: ({ row }: any) => {
        const status = row.original.status;
        let badgeClass = "";
        
        switch (status) {
          case "active":
            badgeClass = "bg-green-100 text-green-800";
            break;
          case "pending":
            badgeClass = "bg-yellow-100 text-yellow-800";
            break;
          case "inactive":
            badgeClass = "bg-red-100 text-red-800";
            break;
          default:
            badgeClass = "bg-gray-100 text-gray-800";
        }
        
        return (
          <Badge variant="outline" className={badgeClass}>
            {status.charAt(0).toUpperCase() + status.slice(1)}
          </Badge>
        );
      },
    },
  ];

  return (
    <MainLayout title="Dashboard">
      {/* Stats */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          title="Total Students"
          value={stats.totalStudents}
          icon={<Users />}
          change="12%"
          changeType="increase"
        />
        
        <StatCard
          title="Total Payments"
          value={`$${stats.totalPayments.toLocaleString()}`}
          icon={<CreditCard />}
          change="8.2%"
          changeType="increase"
          iconBgColor="bg-green-100"
          iconColor="text-green-600"
        />
        
        <StatCard
          title="Pending Dues"
          value={`$${stats.pendingDues.toLocaleString()}`}
          icon={<AlertCircle />}
          change="3.2%"
          changeType="decrease"
          iconBgColor="bg-purple-100"
          iconColor="text-purple-600"
        />
        
        <StatCard
          title="Generated Receipts"
          value={stats.generatedReceipts}
          icon={<FileText />}
          change="5.4%"
          changeType="increase"
          iconBgColor="bg-teal-100"
          iconColor="text-teal-600"
        />
      </div>
      
      {/* Charts */}
      <div className="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <MonthlyPaymentChart />
        <FeeDistributionChart />
      </div>
      
      {/* Tables */}
      <div className="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader className="px-6 py-4">
            <div className="flex justify-between items-center">
              <CardTitle className="text-lg font-medium text-gray-900">Recent Payments</CardTitle>
              <a href="/payments" className="text-sm font-medium text-primary-600 hover:text-primary-500">
                View all
              </a>
            </div>
          </CardHeader>
          <CardContent className="p-0">
            <DataTable 
              columns={paymentColumns} 
              data={recentPaymentsData || []} 
            />
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="px-6 py-4">
            <div className="flex justify-between items-center">
              <CardTitle className="text-lg font-medium text-gray-900">Recent Students</CardTitle>
              <a href="/students" className="text-sm font-medium text-primary-600 hover:text-primary-500">
                View all
              </a>
            </div>
          </CardHeader>
          <CardContent className="p-0">
            <DataTable 
              columns={studentColumns} 
              data={recentStudentsData || []} 
            />
          </CardContent>
        </Card>
      </div>
    </MainLayout>
  );
}
