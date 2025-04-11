import type { Express, Request, Response } from "express";
import { createServer, type Server } from "http";
import { storage } from "./storage";
import bcrypt from 'bcrypt';
import jwt from 'jsonwebtoken';
import { z } from "zod";
import { insertStudentSchema, insertPaymentSchema, insertUserSchema } from '@shared/schema';

const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key';

// Auth middleware
const authenticateToken = (req: Request, res: Response, next: any) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) return res.status(401).json({ message: 'Authentication required' });

  jwt.verify(token, JWT_SECRET, (err: any, user: any) => {
    if (err) return res.status(403).json({ message: 'Invalid or expired token' });
    
    req.body.user = user;
    next();
  });
};

// Check admin role middleware
const isAdmin = (req: Request, res: Response, next: any) => {
  if (req.body.user.role !== 'admin') {
    return res.status(403).json({ message: 'Admin privileges required' });
  }
  next();
};

export async function registerRoutes(app: Express): Promise<Server> {
  // Auth Routes
  app.post('/api/auth/login', async (req: Request, res: Response) => {
    try {
      const { email, password } = req.body;
      
      if (!email || !password) {
        return res.status(400).json({ message: 'Email and password are required' });
      }
      
      const user = await storage.getUserByEmail(email);
      
      if (!user) {
        return res.status(401).json({ message: 'Invalid email or password' });
      }
      
      const isPasswordValid = await bcrypt.compare(password, user.password);
      
      if (!isPasswordValid) {
        return res.status(401).json({ message: 'Invalid email or password' });
      }
      
      // Update last login timestamp
      await storage.updateUser(user.id, { lastLogin: new Date() });
      
      // Generate token
      const token = jwt.sign(
        { id: user.id, email: user.email, role: user.role, name: user.name }, 
        JWT_SECRET, 
        { expiresIn: '24h' }
      );
      
      return res.json({ 
        token,
        user: {
          id: user.id,
          name: user.name,
          email: user.email,
          role: user.role
        }
      });
    } catch (error) {
      console.error('Login error:', error);
      return res.status(500).json({ message: 'An error occurred during login' });
    }
  });

  // Dashboard Routes
  app.get('/api/dashboard/stats', authenticateToken, async (req: Request, res: Response) => {
    try {
      const students = await storage.getAllStudents();
      const payments = await storage.getAllPayments();
      const receipts = await storage.getAllReceipts();
      
      // Calculate total payments amount
      const totalPaymentsAmount = payments.reduce((sum, payment) => {
        return sum + Number(payment.amount);
      }, 0);
      
      // Calculate pending dues (a simple calculation for demo)
      const pendingDues = payments
        .filter(payment => payment.status === 'pending')
        .reduce((sum, payment) => sum + Number(payment.amount), 0);
      
      const stats = {
        totalStudents: students.length,
        totalPayments: totalPaymentsAmount,
        pendingDues: pendingDues,
        generatedReceipts: receipts.length
      };
      
      return res.json(stats);
    } catch (error) {
      console.error('Dashboard stats error:', error);
      return res.status(500).json({ message: 'Failed to fetch dashboard statistics' });
    }
  });
  
  app.get('/api/dashboard/recent-payments', authenticateToken, async (req: Request, res: Response) => {
    try {
      const recentPayments = await storage.getRecentPayments(5);
      const enhancedPayments = await Promise.all(recentPayments.map(async (payment) => {
        const student = await storage.getStudent(payment.studentId);
        const createdBy = await storage.getUser(payment.createdBy);
        
        return {
          ...payment,
          studentName: student ? `${student.firstName} ${student.lastName}` : 'Unknown',
          studentId: student?.studentId || 'Unknown',
          createdByName: createdBy?.name || 'Unknown'
        };
      }));
      
      return res.json(enhancedPayments);
    } catch (error) {
      console.error('Recent payments error:', error);
      return res.status(500).json({ message: 'Failed to fetch recent payments' });
    }
  });
  
  app.get('/api/dashboard/recent-students', authenticateToken, async (req: Request, res: Response) => {
    try {
      const students = await storage.getAllStudents();
      const recentStudents = students
        .sort((a, b) => b.createdAt.getTime() - a.createdAt.getTime())
        .slice(0, 5);
      
      return res.json(recentStudents);
    } catch (error) {
      console.error('Recent students error:', error);
      return res.status(500).json({ message: 'Failed to fetch recent students' });
    }
  });

  // Student Routes
  app.get('/api/students', authenticateToken, async (req: Request, res: Response) => {
    try {
      const students = await storage.getAllStudents();
      return res.json(students);
    } catch (error) {
      console.error('Get students error:', error);
      return res.status(500).json({ message: 'Failed to fetch students' });
    }
  });
  
  app.get('/api/students/:id', authenticateToken, async (req: Request, res: Response) => {
    try {
      const id = parseInt(req.params.id);
      if (isNaN(id)) {
        return res.status(400).json({ message: 'Invalid student ID' });
      }
      
      const student = await storage.getStudent(id);
      if (!student) {
        return res.status(404).json({ message: 'Student not found' });
      }
      
      return res.json(student);
    } catch (error) {
      console.error('Get student error:', error);
      return res.status(500).json({ message: 'Failed to fetch student details' });
    }
  });
  
  app.post('/api/students', authenticateToken, async (req: Request, res: Response) => {
    try {
      const validatedData = insertStudentSchema.parse(req.body);
      
      // Check if student with same email already exists
      const existingStudent = await storage.getStudentByEmail(validatedData.email);
      if (existingStudent) {
        return res.status(409).json({ message: 'A student with this email already exists' });
      }
      
      const student = await storage.createStudent(validatedData);
      return res.status(201).json(student);
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ message: 'Validation error', errors: error.errors });
      }
      console.error('Create student error:', error);
      return res.status(500).json({ message: 'Failed to create student' });
    }
  });
  
  app.put('/api/students/:id', authenticateToken, async (req: Request, res: Response) => {
    try {
      const id = parseInt(req.params.id);
      if (isNaN(id)) {
        return res.status(400).json({ message: 'Invalid student ID' });
      }
      
      const student = await storage.getStudent(id);
      if (!student) {
        return res.status(404).json({ message: 'Student not found' });
      }
      
      // Manually validate the update payload
      const { firstName, lastName, email, phone, address, course, joiningDate, status } = req.body;
      
      // Check if email is being changed and if it belongs to another student
      if (email && email !== student.email) {
        const existingStudent = await storage.getStudentByEmail(email);
        if (existingStudent && existingStudent.id !== id) {
          return res.status(409).json({ message: 'Another student with this email already exists' });
        }
      }
      
      const updatedStudent = await storage.updateStudent(id, {
        firstName, lastName, email, phone, address, course, joiningDate, status
      });
      
      return res.json(updatedStudent);
    } catch (error) {
      console.error('Update student error:', error);
      return res.status(500).json({ message: 'Failed to update student' });
    }
  });
  
  app.delete('/api/students/:id', authenticateToken, async (req: Request, res: Response) => {
    try {
      const id = parseInt(req.params.id);
      if (isNaN(id)) {
        return res.status(400).json({ message: 'Invalid student ID' });
      }
      
      // Check if student exists
      const student = await storage.getStudent(id);
      if (!student) {
        return res.status(404).json({ message: 'Student not found' });
      }
      
      const success = await storage.deleteStudent(id);
      if (!success) {
        return res.status(500).json({ message: 'Failed to delete student' });
      }
      
      return res.status(204).send();
    } catch (error) {
      console.error('Delete student error:', error);
      return res.status(500).json({ message: 'Failed to delete student' });
    }
  });

  // Payment Routes
  app.get('/api/payments', authenticateToken, async (req: Request, res: Response) => {
    try {
      const payments = await storage.getAllPayments();
      const enhancedPayments = await Promise.all(payments.map(async (payment) => {
        const student = await storage.getStudent(payment.studentId);
        return {
          ...payment,
          studentName: student ? `${student.firstName} ${student.lastName}` : 'Unknown',
          studentId: student?.studentId || 'Unknown'
        };
      }));
      
      return res.json(enhancedPayments);
    } catch (error) {
      console.error('Get payments error:', error);
      return res.status(500).json({ message: 'Failed to fetch payments' });
    }
  });
  
  app.get('/api/payments/:id', authenticateToken, async (req: Request, res: Response) => {
    try {
      const id = parseInt(req.params.id);
      if (isNaN(id)) {
        return res.status(400).json({ message: 'Invalid payment ID' });
      }
      
      const payment = await storage.getPayment(id);
      if (!payment) {
        return res.status(404).json({ message: 'Payment not found' });
      }
      
      const student = await storage.getStudent(payment.studentId);
      const enhancedPayment = {
        ...payment,
        studentName: student ? `${student.firstName} ${student.lastName}` : 'Unknown',
        studentId: student?.studentId || 'Unknown'
      };
      
      return res.json(enhancedPayment);
    } catch (error) {
      console.error('Get payment error:', error);
      return res.status(500).json({ message: 'Failed to fetch payment details' });
    }
  });
  
  app.post('/api/payments', authenticateToken, async (req: Request, res: Response) => {
    try {
      // Add the current user ID as createdBy
      const paymentData = {
        ...req.body,
        createdBy: req.body.user.id
      };
      
      const validatedData = insertPaymentSchema.parse(paymentData);
      
      // Verify student exists
      const student = await storage.getStudent(validatedData.studentId);
      if (!student) {
        return res.status(404).json({ message: 'Student not found' });
      }
      
      const payment = await storage.createPayment(validatedData);
      
      // Fetch and return the enhanced payment
      const enhancedPayment = {
        ...payment,
        studentName: `${student.firstName} ${student.lastName}`,
        studentId: student.studentId
      };
      
      return res.status(201).json(enhancedPayment);
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ message: 'Validation error', errors: error.errors });
      }
      console.error('Create payment error:', error);
      return res.status(500).json({ message: 'Failed to create payment' });
    }
  });
  
  app.put('/api/payments/:id', authenticateToken, async (req: Request, res: Response) => {
    try {
      const id = parseInt(req.params.id);
      if (isNaN(id)) {
        return res.status(400).json({ message: 'Invalid payment ID' });
      }
      
      const payment = await storage.getPayment(id);
      if (!payment) {
        return res.status(404).json({ message: 'Payment not found' });
      }
      
      const { amount, paymentDate, paymentType, paymentMethod, discount, status, notes } = req.body;
      
      const updatedPayment = await storage.updatePayment(id, {
        amount, paymentDate, paymentType, paymentMethod, discount, status, notes
      });
      
      return res.json(updatedPayment);
    } catch (error) {
      console.error('Update payment error:', error);
      return res.status(500).json({ message: 'Failed to update payment' });
    }
  });
  
  app.get('/api/payments/student/:studentId', authenticateToken, async (req: Request, res: Response) => {
    try {
      const studentId = parseInt(req.params.studentId);
      if (isNaN(studentId)) {
        return res.status(400).json({ message: 'Invalid student ID' });
      }
      
      const payments = await storage.getPaymentsByStudentId(studentId);
      return res.json(payments);
    } catch (error) {
      console.error('Get student payments error:', error);
      return res.status(500).json({ message: 'Failed to fetch student payments' });
    }
  });

  // Receipt Routes
  app.get('/api/receipts', authenticateToken, async (req: Request, res: Response) => {
    try {
      const receipts = await storage.getAllReceipts();
      const enhancedReceipts = await Promise.all(receipts.map(async (receipt) => {
        const student = await storage.getStudent(receipt.studentId);
        const signedBy = await storage.getUser(receipt.signedBy);
        const payment = await storage.getPayment(receipt.paymentId);
        
        return {
          ...receipt,
          studentName: student ? `${student.firstName} ${student.lastName}` : 'Unknown',
          studentId: student?.studentId || 'Unknown',
          signedByName: signedBy?.name || 'Unknown',
          paymentType: payment?.paymentType || 'Unknown'
        };
      }));
      
      return res.json(enhancedReceipts);
    } catch (error) {
      console.error('Get receipts error:', error);
      return res.status(500).json({ message: 'Failed to fetch receipts' });
    }
  });
  
  app.get('/api/receipts/:id', authenticateToken, async (req: Request, res: Response) => {
    try {
      const id = parseInt(req.params.id);
      if (isNaN(id)) {
        return res.status(400).json({ message: 'Invalid receipt ID' });
      }
      
      const receipt = await storage.getReceipt(id);
      if (!receipt) {
        return res.status(404).json({ message: 'Receipt not found' });
      }
      
      const student = await storage.getStudent(receipt.studentId);
      const signedBy = await storage.getUser(receipt.signedBy);
      const payment = await storage.getPayment(receipt.paymentId);
      
      const enhancedReceipt = {
        ...receipt,
        studentName: student ? `${student.firstName} ${student.lastName}` : 'Unknown',
        studentId: student?.studentId || 'Unknown',
        signedByName: signedBy?.name || 'Unknown',
        paymentType: payment?.paymentType || 'Unknown',
        paymentMethod: payment?.paymentMethod || 'Unknown',
        discount: payment?.discount || '0'
      };
      
      return res.json(enhancedReceipt);
    } catch (error) {
      console.error('Get receipt error:', error);
      return res.status(500).json({ message: 'Failed to fetch receipt details' });
    }
  });
  
  app.post('/api/receipts/send-email/:id', authenticateToken, async (req: Request, res: Response) => {
    try {
      const id = parseInt(req.params.id);
      if (isNaN(id)) {
        return res.status(400).json({ message: 'Invalid receipt ID' });
      }
      
      const receipt = await storage.getReceipt(id);
      if (!receipt) {
        return res.status(404).json({ message: 'Receipt not found' });
      }
      
      // In a real application, we would send an email here
      // For this demo, we'll just mark it as sent
      
      const updatedReceipt = await storage.updateReceipt(id, { emailSent: true });
      
      return res.json(updatedReceipt);
    } catch (error) {
      console.error('Send receipt email error:', error);
      return res.status(500).json({ message: 'Failed to send receipt email' });
    }
  });

  // User Routes (Admin only)
  app.get('/api/users', authenticateToken, isAdmin, async (req: Request, res: Response) => {
    try {
      const users = await storage.getAllUsers();
      
      // Don't send password information
      const sanitizedUsers = users.map(user => ({
        id: user.id,
        username: user.username,
        email: user.email,
        name: user.name,
        role: user.role,
        createdAt: user.createdAt,
        lastLogin: user.lastLogin
      }));
      
      return res.json(sanitizedUsers);
    } catch (error) {
      console.error('Get users error:', error);
      return res.status(500).json({ message: 'Failed to fetch users' });
    }
  });
  
  app.post('/api/users', authenticateToken, isAdmin, async (req: Request, res: Response) => {
    try {
      const userData = insertUserSchema.parse(req.body);
      
      // Check if user with same email or username already exists
      const existingUserByEmail = await storage.getUserByEmail(userData.email);
      if (existingUserByEmail) {
        return res.status(409).json({ message: 'A user with this email already exists' });
      }
      
      const existingUserByUsername = await storage.getUserByUsername(userData.username);
      if (existingUserByUsername) {
        return res.status(409).json({ message: 'A user with this username already exists' });
      }
      
      // Hash password
      const hashedPassword = await bcrypt.hash(userData.password, 10);
      
      const user = await storage.createUser({
        ...userData,
        password: hashedPassword
      });
      
      // Don't send password in response
      const sanitizedUser = {
        id: user.id,
        username: user.username,
        email: user.email,
        name: user.name,
        role: user.role,
        createdAt: user.createdAt
      };
      
      return res.status(201).json(sanitizedUser);
    } catch (error) {
      if (error instanceof z.ZodError) {
        return res.status(400).json({ message: 'Validation error', errors: error.errors });
      }
      console.error('Create user error:', error);
      return res.status(500).json({ message: 'Failed to create user' });
    }
  });

  // Settings Routes
  app.get('/api/settings', authenticateToken, async (req: Request, res: Response) => {
    try {
      const settings = await storage.getAllSettings();
      return res.json(settings);
    } catch (error) {
      console.error('Get settings error:', error);
      return res.status(500).json({ message: 'Failed to fetch settings' });
    }
  });
  
  app.put('/api/settings/:name', authenticateToken, isAdmin, async (req: Request, res: Response) => {
    try {
      const { name } = req.params;
      const { value } = req.body;
      
      if (!value) {
        return res.status(400).json({ message: 'Setting value is required' });
      }
      
      const setting = await storage.getSetting(name);
      if (!setting) {
        return res.status(404).json({ message: 'Setting not found' });
      }
      
      const updatedSetting = await storage.updateSetting(name, value);
      return res.json(updatedSetting);
    } catch (error) {
      console.error('Update setting error:', error);
      return res.status(500).json({ message: 'Failed to update setting' });
    }
  });

  const httpServer = createServer(app);
  return httpServer;
}
