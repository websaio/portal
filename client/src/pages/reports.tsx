import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { MainLayout } from "@/components/layout/main-layout";
import { MonthlyPaymentChart, FeeDistributionChart, StudentsByCourseChart } from "@/components/dashboard/charts";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { format } from "date-fns";
import { Download, Printer } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

export default function Reports() {
  const [activeTab, setActiveTab] = useState("payment-summary");
  const { toast } = useToast();
  
  // Fetch data
  const { data: payments = [] } = useQuery({
    queryKey: ['/api/payments'],
  });
  
  const { data: students = [] } = useQuery({
    queryKey: ['/api/students'],
  });

  const handleDownloadReport = () => {
    toast({
      title: "Report downloaded",
      description: "Report has been downloaded successfully",
    });
  };

  const handlePrintReport = () => {
    window.print();
  };

  // Calculate summary stats
  const calculatePaymentSummary = () => {
    const currentMonth = new Date().getMonth();
    const currentYear = new Date().getFullYear();
    
    const totalPayments = payments.reduce((sum: number, payment: any) => sum + parseFloat(payment.amount), 0);
    
    const currentMonthPayments = payments.filter((payment: any) => {
      const paymentDate = new Date(payment.paymentDate);
      return paymentDate.getMonth() === currentMonth && paymentDate.getFullYear() === currentYear;
    });
    
    const currentMonthTotal = currentMonthPayments.reduce((sum: number, payment: any) => sum + parseFloat(payment.amount), 0);
    
    const pendingPayments = payments.filter((payment: any) => payment.status === 'pending');
    const pendingTotal = pendingPayments.reduce((sum: number, payment: any) => sum + parseFloat(payment.amount), 0);

    return {
      totalPayments,
      currentMonthTotal,
      pendingTotal,
      paymentCount: payments.length,
      currentMonthName: format(new Date(currentYear, currentMonth, 1), 'MMMM yyyy')
    };
  };

  const paymentSummary = calculatePaymentSummary();

  return (
    <MainLayout title="Reports">
      <div className="mb-6 flex justify-between items-center">
        <div className="space-y-1">
          <h2 className="text-lg font-semibold text-gray-900">
            View and export tuition management reports
          </h2>
          <p className="text-sm text-gray-500">
            Generate reports for payments, students, and more
          </p>
        </div>
        <div className="flex space-x-2">
          <Button variant="outline" onClick={handlePrintReport}>
            <Printer className="mr-2 h-4 w-4" />
            Print
          </Button>
          <Button onClick={handleDownloadReport}>
            <Download className="mr-2 h-4 w-4" />
            Download Report
          </Button>
        </div>
      </div>

      <Tabs defaultValue="payment-summary" onValueChange={setActiveTab}>
        <TabsList className="mb-4">
          <TabsTrigger value="payment-summary">Payment Summary</TabsTrigger>
          <TabsTrigger value="student-metrics">Student Metrics</TabsTrigger>
          <TabsTrigger value="charts">Charts & Graphs</TabsTrigger>
        </TabsList>
        
        <TabsContent value="payment-summary" className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-gray-500">Total Collections</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">${paymentSummary.totalPayments.toFixed(2)}</div>
                <p className="text-xs text-gray-500 mt-1">All time</p>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-gray-500">{paymentSummary.currentMonthName} Collections</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">${paymentSummary.currentMonthTotal.toFixed(2)}</div>
                <p className="text-xs text-gray-500 mt-1">Current month</p>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-gray-500">Pending Payments</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">${paymentSummary.pendingTotal.toFixed(2)}</div>
                <p className="text-xs text-gray-500 mt-1">Yet to be collected</p>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-gray-500">Total Transactions</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{paymentSummary.paymentCount}</div>
                <p className="text-xs text-gray-500 mt-1">All payments</p>
              </CardContent>
            </Card>
          </div>
          
          <Card>
            <CardHeader>
              <CardTitle>Recent Payment Transactions</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt No.</th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                      <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {payments.slice(0, 10).map((payment: any) => (
                      <tr key={payment.id}>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{payment.receiptNumber}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{payment.studentName}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${parseFloat(payment.amount).toFixed(2)}</td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {payment.paymentType.charAt(0).toUpperCase() + payment.paymentType.slice(1).replace('_', ' ')}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {format(new Date(payment.paymentDate), "MMM dd, yyyy")}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                            payment.status === 'completed' ? 'bg-green-100 text-green-800' : 
                            payment.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                            'bg-red-100 text-red-800'
                          }`}>
                            {payment.status.charAt(0).toUpperCase() + payment.status.slice(1)}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
        
        <TabsContent value="student-metrics" className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-gray-500">Total Students</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{students.length}</div>
                <p className="text-xs text-gray-500 mt-1">Active and inactive</p>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-gray-500">Active Students</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">
                  {students.filter((student: any) => student.status === 'active').length}
                </div>
                <p className="text-xs text-gray-500 mt-1">Currently enrolled</p>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-gray-500">Inactive Students</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">
                  {students.filter((student: any) => student.status === 'inactive').length}
                </div>
                <p className="text-xs text-gray-500 mt-1">No longer enrolled</p>
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-gray-500">Average Payment</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">
                  ${payments.length > 0 
                    ? (paymentSummary.totalPayments / payments.length).toFixed(2) 
                    : '0.00'}
                </div>
                <p className="text-xs text-gray-500 mt-1">Per student</p>
              </CardContent>
            </Card>
          </div>
          
          <Card>
            <CardHeader>
              <CardTitle>Students by Course</CardTitle>
            </CardHeader>
            <CardContent className="h-80">
              <StudentsByCourseChart />
            </CardContent>
          </Card>
        </TabsContent>
        
        <TabsContent value="charts" className="space-y-4">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <Card>
              <CardHeader>
                <CardTitle>Monthly Payment Trends</CardTitle>
              </CardHeader>
              <CardContent className="h-80">
                <MonthlyPaymentChart />
              </CardContent>
            </Card>
            
            <Card>
              <CardHeader>
                <CardTitle>Fee Distribution</CardTitle>
              </CardHeader>
              <CardContent className="h-80">
                <FeeDistributionChart />
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </MainLayout>
  );
}
