import { jsPDF } from 'jspdf';
import 'jspdf-autotable';
import { format } from 'date-fns';

interface Institution {
  name: string;
  address: string;
  phone: string;
  email: string;
}

interface Receipt {
  receiptNumber: string;
  studentName: string;
  studentId: string;
  amount: string;
  discount: string;
  paymentType: string;
  paymentMethod: string;
  paymentDate: Date;
  generatedDate: Date;
  signedByName: string;
}

export const generateReceiptPdf = (receipt: Receipt, institution: Institution): jsPDF => {
  const doc = new jsPDF();
  
  // Add header
  doc.setFontSize(20);
  doc.setFont('helvetica', 'bold');
  doc.text(institution.name, doc.internal.pageSize.width / 2, 20, { align: 'center' });
  
  doc.setFontSize(12);
  doc.setFont('helvetica', 'normal');
  doc.text(institution.address, doc.internal.pageSize.width / 2, 28, { align: 'center' });
  doc.text(`Phone: ${institution.phone} | Email: ${institution.email}`, doc.internal.pageSize.width / 2, 35, { align: 'center' });
  
  // Add line
  doc.setLineWidth(0.5);
  doc.line(14, 40, doc.internal.pageSize.width - 14, 40);
  
  // Receipt title
  doc.setFontSize(16);
  doc.setFont('helvetica', 'bold');
  doc.text('RECEIPT', doc.internal.pageSize.width / 2, 50, { align: 'center' });
  
  // Receipt details
  doc.setFontSize(10);
  doc.setFont('helvetica', 'bold');
  doc.text(`Receipt No: ${receipt.receiptNumber}`, 14, 60);
  doc.text(`Date: ${format(receipt.generatedDate, 'MMM dd, yyyy')}`, doc.internal.pageSize.width - 14, 60, { align: 'right' });
  
  // Student details
  doc.setFontSize(12);
  doc.text(`Student: ${receipt.studentName}`, 14, 70);
  doc.setFontSize(10);
  doc.setFont('helvetica', 'normal');
  doc.text(`ID: ${receipt.studentId}`, 14, 76);
  
  // Payment details
  (doc as any).autoTable({
    startY: 85,
    head: [['Description', 'Amount']],
    body: [
      [receipt.paymentType, `$${receipt.amount}`],
      receipt.discount && parseFloat(receipt.discount) > 0 ? ['Discount', `-$${receipt.discount}`] : ['', ''],
      ['', ''],
      ['Total', `$${(parseFloat(receipt.amount) - parseFloat(receipt.discount || '0')).toFixed(2)}`]
    ],
    theme: 'grid',
    headStyles: { fillColor: [59, 130, 246], textColor: 255, fontStyle: 'bold' },
    foot: [[`Payment Method: ${receipt.paymentMethod}`, `Payment Date: ${format(receipt.paymentDate, 'MMM dd, yyyy')}`]],
    footStyles: { fontStyle: 'italic' },
    styles: { fontSize: 10 }
  });
  
  // Signature
  const finalY = (doc as any).lastAutoTable.finalY + 20;
  doc.setFontSize(10);
  doc.text('Signed by:', 14, finalY);
  doc.setFont('helvetica', 'bold');
  doc.text(receipt.signedByName, 14, finalY + 5);
  
  // Digital signature image would go here in a real app
  // For this demo, just add a line
  doc.line(14, finalY + 15, 60, finalY + 15);
  
  // Add footer
  doc.setFont('helvetica', 'italic');
  doc.setFontSize(8);
  doc.text('This is an electronically generated receipt and does not require a physical signature.', doc.internal.pageSize.width / 2, doc.internal.pageSize.height - 10, { align: 'center' });
  
  return doc;
};

export const downloadReceipt = (receipt: Receipt, institution: Institution) => {
  const doc = generateReceiptPdf(receipt, institution);
  doc.save(`Receipt-${receipt.receiptNumber}.pdf`);
};

export const printReceipt = (receipt: Receipt, institution: Institution) => {
  const doc = generateReceiptPdf(receipt, institution);
  doc.autoPrint();
  doc.output('dataurlnewwindow');
};
