import React, { useEffect, useState } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useToast } from '@/hooks/use-toast';
import { apiRequest } from '@/lib/queryClient';
import { downloadReceipt, printReceipt } from '@/lib/pdf';
import { format } from 'date-fns';
import { PrinterIcon, Download, Mail } from 'lucide-react';

interface Institution {
  name: string;
  address: string;
  phone: string;
  email: string;
}

interface Receipt {
  id: number;
  receiptNumber: string;
  studentName: string;
  studentId: string;
  amount: string;
  discount: string;
  paymentType: string;
  paymentMethod: string;
  paymentDate: string;
  generatedDate: string;
  signedByName: string;
  emailSent: boolean;
}

interface ReceiptTemplateProps {
  receiptId: number;
}

export function ReceiptTemplate({ receiptId }: ReceiptTemplateProps) {
  const { toast } = useToast();
  const [receipt, setReceipt] = useState<Receipt | null>(null);
  const [institution, setInstitution] = useState<Institution>({
    name: 'UIC-IQ Academy',
    address: '123 Education Street, Knowledge City',
    phone: '+1 234 567 8901',
    email: 'info@uic-iq.com',
  });
  const [isLoading, setIsLoading] = useState(true);
  const [isSendingEmail, setIsSendingEmail] = useState(false);

  useEffect(() => {
    const fetchReceipt = async () => {
      try {
        const response = await fetch(`/api/receipts/${receiptId}`, {
          credentials: 'include',
        });
        
        if (!response.ok) {
          throw new Error('Failed to fetch receipt');
        }
        
        const data = await response.json();
        setReceipt(data);
      } catch (error) {
        console.error('Fetch receipt error:', error);
        toast({
          title: 'Error',
          description: 'Failed to load receipt details',
          variant: 'destructive',
        });
      } finally {
        setIsLoading(false);
      }
    };
    
    const fetchSettings = async () => {
      try {
        const response = await fetch('/api/settings', {
          credentials: 'include',
        });
        
        if (response.ok) {
          const settings = await response.json();
          
          const institutionName = settings.find((s: any) => s.name === 'institution_name');
          const institutionAddress = settings.find((s: any) => s.name === 'institution_address');
          const institutionPhone = settings.find((s: any) => s.name === 'institution_phone');
          const institutionEmail = settings.find((s: any) => s.name === 'institution_email');
          
          setInstitution({
            name: institutionName?.value || 'UIC-IQ Academy',
            address: institutionAddress?.value || '123 Education Street, Knowledge City',
            phone: institutionPhone?.value || '+1 234 567 8901',
            email: institutionEmail?.value || 'info@uic-iq.com',
          });
        }
      } catch (error) {
        console.error('Fetch settings error:', error);
      }
    };
    
    fetchReceipt();
    fetchSettings();
  }, [receiptId, toast]);

  const handleDownload = () => {
    if (!receipt) return;
    
    downloadReceipt({
      ...receipt,
      paymentDate: new Date(receipt.paymentDate),
      generatedDate: new Date(receipt.generatedDate),
    }, institution);
    
    toast({
      title: 'Receipt downloaded',
      description: 'The receipt has been downloaded successfully',
    });
  };

  const handlePrint = () => {
    if (!receipt) return;
    
    printReceipt({
      ...receipt,
      paymentDate: new Date(receipt.paymentDate),
      generatedDate: new Date(receipt.generatedDate),
    }, institution);
  };

  const handleSendEmail = async () => {
    if (!receipt) return;
    
    setIsSendingEmail(true);
    
    try {
      await apiRequest('POST', `/api/receipts/send-email/${receipt.id}`, {});
      
      setReceipt({
        ...receipt,
        emailSent: true,
      });
      
      toast({
        title: 'Email sent',
        description: 'Receipt has been sent to the student\'s email',
      });
    } catch (error) {
      console.error('Send email error:', error);
      toast({
        title: 'Error',
        description: 'Failed to send email. Please try again.',
        variant: 'destructive',
      });
    } finally {
      setIsSendingEmail(false);
    }
  };

  if (isLoading) {
    return (
      <Card>
        <CardContent className="p-8 text-center">
          <p>Loading receipt...</p>
        </CardContent>
      </Card>
    );
  }

  if (!receipt) {
    return (
      <Card>
        <CardContent className="p-8 text-center">
          <p>Receipt not found</p>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex justify-end space-x-2 mb-4">
        <Button variant="outline" size="sm" onClick={handlePrint}>
          <PrinterIcon className="mr-2 h-4 w-4" />
          Print
        </Button>
        <Button variant="outline" size="sm" onClick={handleDownload}>
          <Download className="mr-2 h-4 w-4" />
          Download
        </Button>
        <Button 
          variant="outline" 
          size="sm" 
          onClick={handleSendEmail}
          disabled={isSendingEmail || receipt.emailSent}
        >
          <Mail className="mr-2 h-4 w-4" />
          {receipt.emailSent ? 'Email Sent' : isSendingEmail ? 'Sending...' : 'Send Email'}
        </Button>
      </div>
      
      <Card className="border shadow-sm">
        <CardContent className="p-8">
          <div className="text-center mb-6">
            <h1 className="text-2xl font-bold text-gray-900">{institution.name}</h1>
            <p className="text-gray-600">{institution.address}</p>
            <p className="text-gray-600">Phone: {institution.phone} | Email: {institution.email}</p>
          </div>
          
          <div className="border-t border-b border-gray-200 py-4 my-6">
            <h2 className="text-xl font-bold text-center text-gray-900">RECEIPT</h2>
          </div>
          
          <div className="flex justify-between mb-6">
            <div>
              <p className="text-sm font-semibold">Receipt No:</p>
              <p className="text-sm">{receipt.receiptNumber}</p>
            </div>
            <div>
              <p className="text-sm font-semibold">Date:</p>
              <p className="text-sm">{format(new Date(receipt.generatedDate), 'MMM dd, yyyy')}</p>
            </div>
          </div>
          
          <div className="mb-6">
            <p className="text-sm font-semibold">Student:</p>
            <p className="text-base">{receipt.studentName}</p>
            <p className="text-sm">ID: {receipt.studentId}</p>
          </div>
          
          <div className="border rounded-md overflow-hidden mb-6">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                <tr>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {receipt.paymentType.charAt(0).toUpperCase() + receipt.paymentType.slice(1)}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                    ${parseFloat(receipt.amount).toFixed(2)}
                  </td>
                </tr>
                {parseFloat(receipt.discount) > 0 && (
                  <tr>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Discount</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600">
                      -${parseFloat(receipt.discount).toFixed(2)}
                    </td>
                  </tr>
                )}
                <tr className="bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Total</td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-right text-gray-900">
                    ${(parseFloat(receipt.amount) - parseFloat(receipt.discount || '0')).toFixed(2)}
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr>
                  <td colSpan={2} className="px-6 py-4 text-sm text-gray-500">
                    Payment Method: {receipt.paymentMethod.replace('_', ' ').split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')} | 
                    Payment Date: {format(new Date(receipt.paymentDate), 'MMM dd, yyyy')}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
          
          <div className="mt-8 pt-4 border-t border-gray-200">
            <div className="flex justify-between">
              <div>
                <p className="text-sm font-semibold">Signed by:</p>
                <p className="text-sm">{receipt.signedByName}</p>
                <div className="mt-2 border-t border-gray-300 w-40"></div>
                <p className="text-xs text-gray-500 mt-1">Authorized Signature</p>
              </div>
              <div className="text-right">
                <p className="text-xs text-gray-500 italic">
                  This is an electronically generated receipt and does not require a physical signature.
                </p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
