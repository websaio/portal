import { useState } from "react";
import { useParams } from "wouter";
import { useQuery } from "@tanstack/react-query";
import { MainLayout } from "@/components/layout/main-layout";
import { DataTable } from "@/components/ui/data-table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { format } from "date-fns";
import { FileText, Eye, Download, Mail } from "lucide-react";
import { ReceiptTemplate } from "@/components/receipts/receipt-template";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/queryClient";

interface Receipt {
  id: number;
  receiptNumber: string;
  studentName: string;
  studentId: string;
  amount: string;
  paymentId: number;
  generatedDate: string;
  emailSent: boolean;
}

export default function Receipts() {
  const params = useParams();
  const { toast } = useToast();
  const [viewReceiptId, setViewReceiptId] = useState<number | null>(null);
  const [openViewDialog, setOpenViewDialog] = useState(false);
  
  // Open receipt detail directly if ID is in URL
  const receiptId = params.id ? parseInt(params.id) : null;

  // Fetch receipts
  const { data: receipts = [], isLoading } = useQuery({
    queryKey: ['/api/receipts'],
  });

  // If receipt ID is in URL, set it to view
  useState(() => {
    if (receiptId) {
      setViewReceiptId(receiptId);
      setOpenViewDialog(true);
    }
  });

  const handleViewReceipt = (id: number) => {
    setViewReceiptId(id);
    setOpenViewDialog(true);
  };

  const handleViewDialogClose = () => {
    setViewReceiptId(null);
    setOpenViewDialog(false);
    
    // Clear the URL if receipt ID is in it
    if (receiptId) {
      window.history.pushState({}, '', '/receipts');
    }
  };

  const handleSendEmail = async (id: number) => {
    try {
      await apiRequest('POST', `/api/receipts/send-email/${id}`, undefined);
      
      toast({
        title: "Email sent",
        description: "Receipt has been sent to the student's email",
      });
    } catch (error) {
      console.error('Send email error:', error);
      toast({
        title: "Error",
        description: "Failed to send email. Please try again.",
        variant: "destructive",
      });
    }
  };

  const columns = [
    {
      accessorKey: "receiptNumber",
      header: "Receipt No.",
      cell: ({ row }: any) => (
        <span className="text-sm font-medium text-gray-900">
          {row.original.receiptNumber}
        </span>
      ),
    },
    {
      accessorKey: "studentName",
      header: "Student",
      cell: ({ row }: any) => {
        const receipt = row.original;
        const initials = receipt.studentName
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
              <div className="text-sm font-medium text-gray-900">{receipt.studentName}</div>
              <div className="text-sm text-gray-500">ID: {receipt.studentId}</div>
            </div>
          </div>
        );
      },
    },
    {
      accessorKey: "amount",
      header: "Amount",
      cell: ({ row }: any) => (
        <span className="text-sm text-gray-900">
          ${Number(row.original.amount).toFixed(2)}
        </span>
      ),
    },
    {
      accessorKey: "generatedDate",
      header: "Generated Date",
      cell: ({ row }: any) => (
        <span className="text-sm text-gray-500">
          {format(new Date(row.original.generatedDate), "MMM dd, yyyy")}
        </span>
      ),
    },
    {
      accessorKey: "emailSent",
      header: "Email Status",
      cell: ({ row }: any) => {
        const emailSent = row.original.emailSent;
        
        return (
          <Badge variant="outline" className={emailSent ? "bg-green-100 text-green-800" : "bg-gray-100 text-gray-800"}>
            {emailSent ? "Sent" : "Not Sent"}
          </Badge>
        );
      },
    },
    {
      id: "actions",
      header: "Actions",
      cell: ({ row }: any) => {
        const receipt = row.original;
        
        return (
          <div className="flex space-x-2">
            <Button 
              variant="outline" 
              size="sm" 
              onClick={() => handleViewReceipt(receipt.id)}
              className="h-8 px-2"
            >
              <Eye className="h-4 w-4" />
              <span className="sr-only">View</span>
            </Button>
            
            <Button 
              variant="outline" 
              size="sm" 
              onClick={() => handleSendEmail(receipt.id)}
              className="h-8 px-2"
              disabled={receipt.emailSent}
            >
              <Mail className="h-4 w-4" />
              <span className="sr-only">Send Email</span>
            </Button>
          </div>
        );
      },
    },
  ];

  return (
    <MainLayout title="Receipts">
      <DataTable 
        columns={columns} 
        data={receipts} 
        searchColumn="receiptNumber"
        searchPlaceholder="Search by receipt number..."
      />
      
      {/* View Receipt Dialog */}
      <Dialog open={openViewDialog} onOpenChange={setOpenViewDialog}>
        <DialogContent className="sm:max-w-[750px] max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Receipt Details</DialogTitle>
          </DialogHeader>
          {viewReceiptId && <ReceiptTemplate receiptId={viewReceiptId} />}
        </DialogContent>
      </Dialog>
    </MainLayout>
  );
}
