import { useState } from "react";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { MainLayout } from "@/components/layout/main-layout";
import { DataTable } from "@/components/ui/data-table";
import { StudentForm, StudentFormValues } from "@/components/students/student-form";
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
import { Plus, MoreHorizontal, Pencil, Trash2 } from "lucide-react";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";

interface Student {
  id: number;
  studentId: string;
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  address: string;
  course: string;
  joiningDate: string;
  status: string;
}

export default function Students() {
  const [openAddDialog, setOpenAddDialog] = useState(false);
  const [openEditDialog, setOpenEditDialog] = useState(false);
  const [openDeleteDialog, setOpenDeleteDialog] = useState(false);
  const [selectedStudent, setSelectedStudent] = useState<Student | null>(null);
  const { toast } = useToast();
  const queryClient = useQueryClient();

  // Fetch students
  const { data: students = [], isLoading } = useQuery({
    queryKey: ['/api/students'],
  });

  const handleAddDialogOpen = () => {
    setOpenAddDialog(true);
  };

  const handleAddDialogClose = () => {
    setOpenAddDialog(false);
  };

  const handleEditDialogOpen = (student: Student) => {
    setSelectedStudent(student);
    setOpenEditDialog(true);
  };

  const handleEditDialogClose = () => {
    setSelectedStudent(null);
    setOpenEditDialog(false);
  };

  const handleDeleteDialogOpen = (student: Student) => {
    setSelectedStudent(student);
    setOpenDeleteDialog(true);
  };

  const handleDeleteDialogClose = () => {
    setSelectedStudent(null);
    setOpenDeleteDialog(false);
  };

  const handleDelete = async () => {
    if (!selectedStudent) return;

    try {
      await apiRequest('DELETE', `/api/students/${selectedStudent.id}`, undefined);
      
      toast({
        title: "Student deleted",
        description: "Student has been deleted successfully",
      });
      
      queryClient.invalidateQueries({ queryKey: ['/api/students'] });
      handleDeleteDialogClose();
    } catch (error) {
      console.error('Delete student error:', error);
      toast({
        title: "Error",
        description: "Failed to delete student. Please try again.",
        variant: "destructive",
      });
    }
  };

  const handleFormSuccess = () => {
    queryClient.invalidateQueries({ queryKey: ['/api/students'] });
    handleAddDialogClose();
    handleEditDialogClose();
  };

  const columns = [
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
        <span className="text-sm font-medium text-gray-900">
          {row.original.studentId}
        </span>
      ),
    },
    {
      accessorKey: "phone",
      header: "Phone",
      cell: ({ row }: any) => (
        <span className="text-sm text-gray-500">
          {row.original.phone}
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
      accessorKey: "joiningDate",
      header: "Joining Date",
      cell: ({ row }: any) => (
        <span className="text-sm text-gray-500">
          {format(new Date(row.original.joiningDate), "MMM dd, yyyy")}
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
    {
      id: "actions",
      cell: ({ row }: any) => {
        const student = row.original;
        
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
              <DropdownMenuItem
                onClick={() => window.location.href = `/payments/student/${student.id}`}
              >
                View Payments
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={() => handleEditDialogOpen(student)}>
                <Pencil className="mr-2 h-4 w-4" />
                Edit
              </DropdownMenuItem>
              <DropdownMenuItem 
                onClick={() => handleDeleteDialogOpen(student)}
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
    <MainLayout title="Students">
      <div className="flex justify-between items-center mb-6">
        <div>
          <Button onClick={handleAddDialogOpen}>
            <Plus className="mr-2 h-4 w-4" />
            Add Student
          </Button>
        </div>
      </div>

      <DataTable 
        columns={columns} 
        data={students} 
        searchColumn="name"
        searchPlaceholder="Search by name..."
      />
      
      {/* Add Student Dialog */}
      <Dialog open={openAddDialog} onOpenChange={setOpenAddDialog}>
        <DialogContent className="sm:max-w-[550px]">
          <DialogHeader>
            <DialogTitle>Add New Student</DialogTitle>
          </DialogHeader>
          <StudentForm onSuccess={handleFormSuccess} />
        </DialogContent>
      </Dialog>
      
      {/* Edit Student Dialog */}
      <Dialog open={openEditDialog} onOpenChange={setOpenEditDialog}>
        <DialogContent className="sm:max-w-[550px]">
          <DialogHeader>
            <DialogTitle>Edit Student</DialogTitle>
          </DialogHeader>
          {selectedStudent && (
            <StudentForm 
              initialValues={{
                firstName: selectedStudent.firstName,
                lastName: selectedStudent.lastName,
                email: selectedStudent.email,
                phone: selectedStudent.phone,
                address: selectedStudent.address,
                course: selectedStudent.course,
                joiningDate: new Date(selectedStudent.joiningDate),
                status: selectedStudent.status,
              }} 
              isEdit
              studentId={selectedStudent.id}
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
              This will permanently delete the student record. This action cannot be undone.
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
