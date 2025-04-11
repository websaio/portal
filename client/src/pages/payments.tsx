import { useState } from "react";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { MainLayout } from "@/components/layout/main-layout";
import { DataTable } from "@/components/ui/data-table";
import { PaymentForm } from "@/components/payments/payment-form";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { format } from "date-fns";
import { Plus, MoreHorizontal, Pencil, Trash2, FileText } from "lucide-react";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";

interface Payment {
  id: number;
  studentId: number;
  studentName: string;
  amount: string;
  discount: string;
  paymentDate: string;
  paymentType: string;
  paymentMethod: string;
  receiptNumber: string;
  status: string;
  notes: string;
}

export default function Payments() {
  const [openAddDialog, setOpenAddDialog] = useState(false);
  const [openEditDialog, setOpenEditDialog] = useState(false);
  const [openDeleteDialog, setOpenDeleteDialog] = useState(false);
  const [selectedPayment, setSelectedPayment] = useState<Payment | null>(null);
  const { toast } = useToast();
  const queryClient = useQueryClient();

  // Fetch payments
  const { data: payments = [], isLoading } = useQuery({
    queryKey: ['/api/payments'],
  });

  const handleAddDialogOpen = () => {
    setOpenAddDialog(true);
  };

  const handleAddDialogClose = () => {
    setOpenAddDialog(false);
  };

  const handleEditDialogOpen = (payment: Payment) => {
    setSelectedPayment(payment);
    setOpenEditDialog(true);
  };

  const handleEditDialogClose = () => {
    setSelectedPayment(null);
    setOpenEditDialog(false);
  };

  const handleDeleteDialogOpen = (payment: Payment) => {
    setSelectedPayment(payment);
    setOpenDeleteDialog(true);
  };

  const handleDeleteDialogClose = () => {
    setSelectedPayment(null);
    setOpenDeleteDialog(false);
  };

  const handleDelete = async () => {
    if (!selectedPayment) return;

    try {
      await apiRequest('DELETE', `/api/payments/${selectedPayment.id}`, undefined);
      
      toast({
        title: "Payment deleted",
        description: "Payment has been deleted successfully",
      });
      
      queryClient.invalidateQueries({ queryKey: ['/api/payments'] });
      handleDeleteDialogClose();
    } catch (error) {
      console.error('Delete payment error:', error);
      toast({
        title: "Error",
        description: "Failed to delete payment. Please try again.",
        variant: "destructive",
      });
    }
  };

  const handleFormSuccess = () => {
    queryClient.invalidateQueries({ queryKey: ['/api/payments'] });
    handleAddDialogClose();
    handleEditDialogClose();
  };

  const handleViewReceipt = (payment: Payment) => {
    window.location.href = `/receipts/${payment.id}`;
  };

  const columns = [
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
        const discount = parseFloat(payment.discount || '0');
        
        return (
          <div>
            <div className="text-sm text-gray-900">${Number(payment.amount).toFixed(2)}</div>
            {discount > 0 && (
              <div className="text-xs text-red-500">Discount: -${discount.toFixed(2)}</div>
            )}
          </div>
        );
      },
    },
    {
      accessorKey: "paymentType",
      header: "Type",
      cell: ({ row }: any) => {
        const type = row.original.paymentType;
        return (
          <span className="text-sm text-gray-500">
            {type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' ')}
          </span>
        );
      },
    },
    {
      accessorKey: "paymentMethod",
      header: "Method",
      cell: ({ row }: any) => {
        const method = row.original.paymentMethod;
        return (
          <span className="text-sm text-gray-500">
            {method.charAt(0).toUpperCase() + method.slice(1).replace('_', ' ')}
          </span>
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
          case "failed":
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
    {
      id: "actions",
      cell: ({ row }: any) => {
        const payment = row.original;
        
        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuLabel>Actions</DropdownMenuLabel>
              <DropdownMenuItem onClick={() => handleViewReceipt(payment)}>
                <FileText className="mr-2 h-4 w-4" />
                View Receipt
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={() => handleEditDialogOpen(payment)}>
                <Pencil className="mr-2 h-4 w-4" />
                Edit
              </DropdownMenuItem>
              <DropdownMenuItem 
                onClick={() => handleDeleteDialogOpen(payment)}
                className="text-red-600"
              >
                <Trash2 className="mr-2 h-4 w-4" />
                Delete
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
    },
  ];

  return (
    <MainLayout title="Payments">
      <div className="flex justify-between items-center mb-6">
        <div>
          <Button onClick={handleAddDialogOpen}>
            <Plus className="mr-2 h-4 w-4" />
            Record Payment
          </Button>
        </div>
      </div>

      <DataTable 
        columns={columns} 
        data={payments} 
        searchColumn="studentName"
        searchPlaceholder="Search by student name..."
      />
      
      {/* Add Payment Dialog */}
      <Dialog open={openAddDialog} onOpenChange={setOpenAddDialog}>
        <DialogContent className="sm:max-w-[550px]">
          <DialogHeader>
            <DialogTitle>Record Payment</DialogTitle>
          </DialogHeader>
          <PaymentForm onSuccess={handleFormSuccess} />
        </DialogContent>
      </Dialog>
      
      {/* Edit Payment Dialog */}
      <Dialog open={openEditDialog} onOpenChange={setOpenEditDialog}>
        <DialogContent className="sm:max-w-[550px]">
          <DialogHeader>
            <DialogTitle>Edit Payment</DialogTitle>
          </DialogHeader>
          {selectedPayment && (
            <PaymentForm 
              initialValues={{
                studentId: selectedPayment.studentId,
                amount: selectedPayment.amount,
                paymentDate: new Date(selectedPayment.paymentDate),
                paymentType: selectedPayment.paymentType,
                paymentMethod: selectedPayment.paymentMethod,
                discount: selectedPayment.discount,
                status: selectedPayment.status,
                notes: selectedPayment.notes,
              }} 
              isEdit
              paymentId={selectedPayment.id}
              onSuccess={handleFormSuccess} 
            />
          )}
        </DialogContent>
      </Dialog>
      
      {/* Delete Confirmation Dialog */}
      <AlertDialog open={openDeleteDialog} onOpenChange={setOpenDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
            <AlertDialogDescription>
              This will permanently delete the payment record. This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} className="bg-red-600 hover:bg-red-700">
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </MainLayout>
  );
}
