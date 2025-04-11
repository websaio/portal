import { 
  users, type User, type InsertUser,
  students, type Student, type InsertStudent,
  payments, type Payment, type InsertPayment,
  receipts, type Receipt, type InsertReceipt,
  settings, type Setting, type InsertSetting,
  academicYears, type AcademicYear, type InsertAcademicYear,
  studentEnrollments, type StudentEnrollment, type InsertStudentEnrollment
} from "@shared/schema";
import { v4 as uuidv4 } from 'uuid';
import bcrypt from 'bcrypt';
import { and, asc, desc, eq, isNull, sql } from 'drizzle-orm';
import { db } from './db';

export interface IStorage {
  // Users
  getUser(id: number): Promise<User | undefined>;
  getUserByUsername(username: string): Promise<User | undefined>;
  getUserByEmail(email: string): Promise<User | undefined>;
  createUser(user: InsertUser): Promise<User>;
  updateUser(id: number, user: Partial<User>): Promise<User | undefined>;
  getAllUsers(): Promise<User[]>;

  // Students
  getStudent(id: number): Promise<Student | undefined>;
  getStudentByEmail(email: string): Promise<Student | undefined>;
  getStudentByStudentId(studentId: string): Promise<Student | undefined>;
  createStudent(student: InsertStudent): Promise<Student>;
  updateStudent(id: number, student: Partial<Student>): Promise<Student | undefined>;
  deleteStudent(id: number): Promise<boolean>;
  getAllStudents(): Promise<Student[]>;
  
  // Payments
  getPayment(id: number): Promise<Payment | undefined>;
  createPayment(payment: InsertPayment): Promise<Payment>;
  updatePayment(id: number, payment: Partial<Payment>): Promise<Payment | undefined>;
  deletePayment(id: number): Promise<boolean>;
  getAllPayments(): Promise<Payment[]>;
  getPaymentsByStudentId(studentId: number): Promise<Payment[]>;
  getRecentPayments(limit: number): Promise<Payment[]>;

  // Receipts
  getReceipt(id: number): Promise<Receipt | undefined>;
  getReceiptByNumber(receiptNumber: string): Promise<Receipt | undefined>;
  createReceipt(receipt: InsertReceipt): Promise<Receipt>;
  updateReceipt(id: number, receipt: Partial<Receipt>): Promise<Receipt | undefined>;
  getAllReceipts(): Promise<Receipt[]>;
  getReceiptsByStudentId(studentId: number): Promise<Receipt[]>;

  // Settings
  getSetting(name: string): Promise<Setting | undefined>;
  updateSetting(name: string, value: string): Promise<Setting | undefined>;
  getAllSettings(): Promise<Setting[]>;
}

export class MemStorage implements IStorage {
  private users: Map<number, User>;
  private students: Map<number, Student>;
  private payments: Map<number, Payment>;
  private receipts: Map<number, Receipt>;
  private settings: Map<number, Setting>;
  
  private userIdCounter: number;
  private studentIdCounter: number;
  private paymentIdCounter: number;
  private receiptIdCounter: number;
  private settingIdCounter: number;

  constructor() {
    this.users = new Map();
    this.students = new Map();
    this.payments = new Map();
    this.receipts = new Map();
    this.settings = new Map();
    
    this.userIdCounter = 1;
    this.studentIdCounter = 1;
    this.paymentIdCounter = 1;
    this.receiptIdCounter = 1;
    this.settingIdCounter = 1;

    // Initialize with default admin user
    this.createDefaultAdmin();
    this.createDefaultSettings();
    this.createSampleData();
  }

  private async createDefaultAdmin() {
    const salt = await bcrypt.hash('password', 10);
    const admin: InsertUser = {
      username: 'admin',
      password: salt,
      email: 'admin@uic-iq.com',
      name: 'Admin User',
      role: 'admin'
    };
    this.createUser(admin);
  }

  private createDefaultSettings() {
    this.createSetting({
      name: 'institution_name',
      value: 'UIC-IQ Academy',
      description: 'Institution name used in receipts and emails'
    });
    
    this.createSetting({
      name: 'receipt_prefix',
      value: 'UIC-REC-',
      description: 'Prefix used for receipt numbers'
    });

    this.createSetting({
      name: 'institution_address',
      value: '123 Education Street, Knowledge City',
      description: 'Institution address used in receipts'
    });

    this.createSetting({
      name: 'institution_phone',
      value: '+1 234 567 8901',
      description: 'Institution contact number'
    });

    this.createSetting({
      name: 'institution_email',
      value: 'info@uic-iq.com',
      description: 'Institution contact email'
    });
  }

  private createSetting(setting: { name: string, value: string, description?: string }) {
    const id = this.settingIdCounter++;
    const newSetting: Setting = {
      id,
      name: setting.name,
      value: setting.value,
      description: setting.description || null,
      updatedAt: new Date()
    };
    this.settings.set(id, newSetting);
    return newSetting;
  }

  private createSampleData() {
    // Create sample students
    const students = [
      {
        firstName: 'John',
        lastName: 'Doe',
        email: 'john.doe@example.com',
        phone: '123-456-7890',
        address: '123 Main St, Anytown',
        grade: 'Grade 5',
        section: 'A',
        joiningDate: new Date('2023-01-15'),
        status: 'active'
      },
      {
        firstName: 'Jane',
        lastName: 'Smith',
        email: 'jane.smith@example.com',
        phone: '098-765-4321',
        address: '456 Oak Ave, Anytown',
        grade: 'KG2',
        section: 'B',
        joiningDate: new Date('2023-02-20'),
        status: 'active'
      }
    ];

    students.forEach(student => {
      this.createStudent(student);
    });

    // Create a default academic year if none exists
    const defaultAcademicYear = {
      name: '2023-2024',
      startDate: new Date('2023-09-01'),
      endDate: new Date('2024-08-31'),
      isCurrentYear: true,
      status: 'active'
    };
    const academicYearId = 1;
    
    // Create sample payments
    setTimeout(() => {
      if (this.students.size > 0) {
        const payments = [
          {
            studentId: 1,
            academicYearId: academicYearId,
            amount: "1000",
            paymentDate: new Date('2023-03-15'),
            paymentType: 'tuition',
            paymentMethod: 'cash',
            discount: "0",
            status: 'completed',
            notes: 'First installment',
            createdBy: 1
          },
          {
            studentId: 2,
            academicYearId: academicYearId,
            amount: "1200",
            paymentDate: new Date('2023-03-20'),
            paymentType: 'registration',
            paymentMethod: 'credit_card',
            discount: "100",
            status: 'completed',
            notes: 'Registration fee with scholarship discount',
            createdBy: 1
          }
        ];

        payments.forEach(payment => {
          this.createPayment(payment);
        });
      }
    }, 100);
  }

  // User methods
  async getUser(id: number): Promise<User | undefined> {
    return this.users.get(id);
  }

  async getUserByUsername(username: string): Promise<User | undefined> {
    return Array.from(this.users.values()).find(
      (user) => user.username === username,
    );
  }

  async getUserByEmail(email: string): Promise<User | undefined> {
    return Array.from(this.users.values()).find(
      (user) => user.email === email,
    );
  }

  async createUser(insertUser: InsertUser): Promise<User> {
    const id = this.userIdCounter++;
    // Ensure role is set
    const userWithRole = {
      ...insertUser,
      role: insertUser.role || 'staff'
    };
    
    const user: User = { 
      ...userWithRole, 
      id,
      createdAt: new Date(),
      lastLogin: null 
    };
    this.users.set(id, user);
    return user;
  }

  async updateUser(id: number, userData: Partial<User>): Promise<User | undefined> {
    const user = this.users.get(id);
    if (!user) return undefined;
    
    const updatedUser = { ...user, ...userData };
    this.users.set(id, updatedUser);
    return updatedUser;
  }

  async getAllUsers(): Promise<User[]> {
    return Array.from(this.users.values());
  }

  // Student methods
  async getStudent(id: number): Promise<Student | undefined> {
    return this.students.get(id);
  }

  async getStudentByEmail(email: string): Promise<Student | undefined> {
    return Array.from(this.students.values()).find(
      (student) => student.email === email,
    );
  }

  async getStudentByStudentId(studentId: string): Promise<Student | undefined> {
    return Array.from(this.students.values()).find(
      (student) => student.studentId === studentId,
    );
  }

  async createStudent(insertStudent: InsertStudent): Promise<Student> {
    const id = this.studentIdCounter++;
    const studentIdPrefix = 'STD-';
    const studentId = `${studentIdPrefix}${1000 + id}`;
    
    // Ensure address is properly set
    const studentWithAddress = {
      ...insertStudent,
      address: insertStudent.address || null
    };
    
    const student: Student = { 
      ...studentWithAddress, 
      id,
      studentId,
      createdAt: new Date() 
    };
    this.students.set(id, student);
    return student;
  }

  async updateStudent(id: number, studentData: Partial<Student>): Promise<Student | undefined> {
    const student = this.students.get(id);
    if (!student) return undefined;
    
    const updatedStudent = { ...student, ...studentData };
    this.students.set(id, updatedStudent);
    return updatedStudent;
  }

  async deleteStudent(id: number): Promise<boolean> {
    return this.students.delete(id);
  }

  async getAllStudents(): Promise<Student[]> {
    return Array.from(this.students.values());
  }

  // Payment methods
  async getPayment(id: number): Promise<Payment | undefined> {
    return this.payments.get(id);
  }

  async createPayment(insertPayment: InsertPayment): Promise<Payment> {
    const id = this.paymentIdCounter++;
    const receiptPrefix = 'REC-';
    const receiptNumber = `${receiptPrefix}${100000 + id}`;
    
    // Ensure status and other fields have defaults
    const paymentWithDefaults = {
      ...insertPayment,
      status: insertPayment.status || 'completed',
      discount: insertPayment.discount || "0",
      notes: insertPayment.notes || null
    };
    
    const payment: Payment = { 
      ...paymentWithDefaults, 
      id,
      receiptNumber,
      createdAt: new Date() 
    };
    this.payments.set(id, payment);
    
    // Create receipt for this payment
    this.createReceipt({
      receiptNumber,
      paymentId: id,
      studentId: insertPayment.studentId,
      academicYearId: insertPayment.academicYearId,
      amount: insertPayment.amount,
      generatedDate: new Date(),
      signedBy: insertPayment.createdBy,
      emailSent: false
    });
    
    return payment;
  }

  async updatePayment(id: number, paymentData: Partial<Payment>): Promise<Payment | undefined> {
    const payment = this.payments.get(id);
    if (!payment) return undefined;
    
    const updatedPayment = { ...payment, ...paymentData };
    this.payments.set(id, updatedPayment);
    return updatedPayment;
  }

  async deletePayment(id: number): Promise<boolean> {
    return this.payments.delete(id);
  }

  async getAllPayments(): Promise<Payment[]> {
    return Array.from(this.payments.values());
  }

  async getPaymentsByStudentId(studentId: number): Promise<Payment[]> {
    return Array.from(this.payments.values()).filter(
      (payment) => payment.studentId === studentId,
    );
  }

  async getRecentPayments(limit: number): Promise<Payment[]> {
    return Array.from(this.payments.values())
      .sort((a, b) => {
        const aTime = a.createdAt ? a.createdAt.getTime() : 0;
        const bTime = b.createdAt ? b.createdAt.getTime() : 0;
        return bTime - aTime;
      })
      .slice(0, limit);
  }

  // Receipt methods
  async getReceipt(id: number): Promise<Receipt | undefined> {
    return this.receipts.get(id);
  }

  async getReceiptByNumber(receiptNumber: string): Promise<Receipt | undefined> {
    return Array.from(this.receipts.values()).find(
      (receipt) => receipt.receiptNumber === receiptNumber,
    );
  }

  async createReceipt(insertReceipt: InsertReceipt): Promise<Receipt> {
    const id = this.receiptIdCounter++;
    
    // Ensure emailSent is properly set
    const receiptWithDefaults = {
      ...insertReceipt,
      emailSent: insertReceipt.emailSent !== undefined ? insertReceipt.emailSent : false
    };
    
    const receipt: Receipt = { 
      ...receiptWithDefaults, 
      id,
      createdAt: new Date() 
    };
    this.receipts.set(id, receipt);
    return receipt;
  }

  async updateReceipt(id: number, receiptData: Partial<Receipt>): Promise<Receipt | undefined> {
    const receipt = this.receipts.get(id);
    if (!receipt) return undefined;
    
    const updatedReceipt = { ...receipt, ...receiptData };
    this.receipts.set(id, updatedReceipt);
    return updatedReceipt;
  }

  async getAllReceipts(): Promise<Receipt[]> {
    return Array.from(this.receipts.values());
  }

  async getReceiptsByStudentId(studentId: number): Promise<Receipt[]> {
    return Array.from(this.receipts.values()).filter(
      (receipt) => receipt.studentId === studentId,
    );
  }

  // Settings methods
  async getSetting(name: string): Promise<Setting | undefined> {
    return Array.from(this.settings.values()).find(
      (setting) => setting.name === name,
    );
  }

  async updateSetting(name: string, value: string): Promise<Setting | undefined> {
    const setting = Array.from(this.settings.values()).find(
      (setting) => setting.name === name,
    );
    
    if (!setting) return undefined;
    
    const updatedSetting = { 
      ...setting, 
      value,
      updatedAt: new Date() 
    };
    this.settings.set(setting.id, updatedSetting);
    return updatedSetting;
  }

  async getAllSettings(): Promise<Setting[]> {
    return Array.from(this.settings.values());
  }
}

export class DatabaseStorage implements IStorage {
  // User methods
  async getUser(id: number): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.id, id));
    return user;
  }

  async getUserByUsername(username: string): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.username, username));
    return user;
  }

  async getUserByEmail(email: string): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.email, email));
    return user;
  }

  async createUser(user: InsertUser): Promise<User> {
    const [createdUser] = await db.insert(users).values(user).returning();
    return createdUser;
  }

  async updateUser(id: number, userData: Partial<User>): Promise<User | undefined> {
    const [updatedUser] = await db
      .update(users)
      .set(userData)
      .where(eq(users.id, id))
      .returning();
    return updatedUser;
  }

  async getAllUsers(): Promise<User[]> {
    return await db.select().from(users).orderBy(asc(users.name));
  }

  // Academic year methods (new)
  async getCurrentAcademicYear(): Promise<AcademicYear | undefined> {
    const [currentYear] = await db
      .select()
      .from(academicYears)
      .where(eq(academicYears.isCurrentYear, true));
    return currentYear;
  }

  async getAcademicYear(id: number): Promise<AcademicYear | undefined> {
    const [year] = await db
      .select()
      .from(academicYears)
      .where(eq(academicYears.id, id));
    return year;
  }

  async getAcademicYearByName(name: string): Promise<AcademicYear | undefined> {
    const [year] = await db
      .select()
      .from(academicYears)
      .where(eq(academicYears.name, name));
    return year;
  }

  async createAcademicYear(year: InsertAcademicYear): Promise<AcademicYear> {
    // If this is set as current year, unset any existing current year
    if (year.isCurrentYear) {
      await db
        .update(academicYears)
        .set({ isCurrentYear: false })
        .where(eq(academicYears.isCurrentYear, true));
    }
    
    const [createdYear] = await db
      .insert(academicYears)
      .values(year)
      .returning();
    return createdYear;
  }

  async updateAcademicYear(id: number, yearData: Partial<AcademicYear>): Promise<AcademicYear | undefined> {
    // If this is set as current year, unset any existing current year
    if (yearData.isCurrentYear) {
      await db
        .update(academicYears)
        .set({ isCurrentYear: false })
        .where(and(
          eq(academicYears.isCurrentYear, true),
          sql`${academicYears.id} != ${id}`
        ));
    }
    
    const [updatedYear] = await db
      .update(academicYears)
      .set(yearData)
      .where(eq(academicYears.id, id))
      .returning();
    return updatedYear;
  }

  async getAllAcademicYears(): Promise<AcademicYear[]> {
    return await db
      .select()
      .from(academicYears)
      .orderBy(desc(academicYears.startDate));
  }

  // Student methods
  async getStudent(id: number): Promise<Student | undefined> {
    const [student] = await db.select().from(students).where(eq(students.id, id));
    return student;
  }

  async getStudentByEmail(email: string): Promise<Student | undefined> {
    const [student] = await db.select().from(students).where(eq(students.email, email));
    return student;
  }

  async getStudentByStudentId(studentId: string): Promise<Student | undefined> {
    const [student] = await db.select().from(students).where(eq(students.studentId, studentId));
    return student;
  }

  async createStudent(student: InsertStudent): Promise<Student> {
    // Generate student ID
    const studentIdPrefix = 'STD-';
    const allStudents = await this.getAllStudents();
    const studentIdNumber = 1000 + allStudents.length + 1;
    const studentId = `${studentIdPrefix}${studentIdNumber}`;
    
    const [createdStudent] = await db
      .insert(students)
      .values({ ...student, studentId })
      .returning();
    return createdStudent;
  }

  async updateStudent(id: number, studentData: Partial<Student>): Promise<Student | undefined> {
    const [updatedStudent] = await db
      .update(students)
      .set(studentData)
      .where(eq(students.id, id))
      .returning();
    return updatedStudent;
  }

  async deleteStudent(id: number): Promise<boolean> {
    const result = await db
      .delete(students)
      .where(eq(students.id, id));
    return !!result;
  }

  async getAllStudents(): Promise<Student[]> {
    return await db
      .select()
      .from(students)
      .orderBy(asc(students.lastName), asc(students.firstName));
  }

  // Student Enrollment methods (new)
  async getStudentEnrollment(id: number): Promise<StudentEnrollment | undefined> {
    const [enrollment] = await db
      .select()
      .from(studentEnrollments)
      .where(eq(studentEnrollments.id, id));
    return enrollment;
  }

  async getStudentEnrollmentByYearAndStudent(
    academicYearId: number, 
    studentId: number
  ): Promise<StudentEnrollment | undefined> {
    const [enrollment] = await db
      .select()
      .from(studentEnrollments)
      .where(and(
        eq(studentEnrollments.academicYearId, academicYearId),
        eq(studentEnrollments.studentId, studentId)
      ));
    return enrollment;
  }

  async createStudentEnrollment(enrollment: InsertStudentEnrollment): Promise<StudentEnrollment> {
    const [createdEnrollment] = await db
      .insert(studentEnrollments)
      .values(enrollment)
      .returning();
    return createdEnrollment;
  }

  async updateStudentEnrollment(
    id: number, 
    enrollmentData: Partial<StudentEnrollment>
  ): Promise<StudentEnrollment | undefined> {
    const [updatedEnrollment] = await db
      .update(studentEnrollments)
      .set(enrollmentData)
      .where(eq(studentEnrollments.id, id))
      .returning();
    return updatedEnrollment;
  }

  async getAllStudentEnrollmentsByYear(academicYearId: number): Promise<StudentEnrollment[]> {
    return await db
      .select()
      .from(studentEnrollments)
      .where(eq(studentEnrollments.academicYearId, academicYearId));
  }

  async getAllStudentEnrollmentsByStudent(studentId: number): Promise<StudentEnrollment[]> {
    return await db
      .select()
      .from(studentEnrollments)
      .where(eq(studentEnrollments.studentId, studentId));
  }

  // Payment methods
  async getPayment(id: number): Promise<Payment | undefined> {
    const [payment] = await db.select().from(payments).where(eq(payments.id, id));
    return payment;
  }

  async createPayment(payment: InsertPayment): Promise<Payment> {
    // Generate receipt number
    const settings = await this.getSetting('receipt_prefix');
    const receiptPrefix = settings ? settings.value : 'REC-';
    const allPayments = await this.getAllPayments();
    const receiptNumber = `${receiptPrefix}${100000 + allPayments.length + 1}`;
    
    const [createdPayment] = await db
      .insert(payments)
      .values({ ...payment, receiptNumber })
      .returning();
    
    // Create receipt for this payment
    await this.createReceipt({
      receiptNumber,
      paymentId: createdPayment.id,
      studentId: payment.studentId,
      academicYearId: payment.academicYearId,
      amount: payment.amount,
      generatedDate: new Date(),
      signedBy: payment.createdBy,
      emailSent: false
    });
    
    return createdPayment;
  }

  async updatePayment(id: number, paymentData: Partial<Payment>): Promise<Payment | undefined> {
    const [updatedPayment] = await db
      .update(payments)
      .set(paymentData)
      .where(eq(payments.id, id))
      .returning();
    return updatedPayment;
  }

  async deletePayment(id: number): Promise<boolean> {
    const result = await db
      .delete(payments)
      .where(eq(payments.id, id));
    return !!result;
  }

  async getAllPayments(): Promise<Payment[]> {
    return await db
      .select()
      .from(payments)
      .orderBy(desc(payments.paymentDate));
  }

  async getPaymentsByStudentId(studentId: number): Promise<Payment[]> {
    return await db
      .select()
      .from(payments)
      .where(eq(payments.studentId, studentId))
      .orderBy(desc(payments.paymentDate));
  }

  async getPaymentsByAcademicYear(academicYearId: number): Promise<Payment[]> {
    return await db
      .select()
      .from(payments)
      .where(eq(payments.academicYearId, academicYearId))
      .orderBy(desc(payments.paymentDate));
  }

  async getRecentPayments(limit: number): Promise<Payment[]> {
    return await db
      .select()
      .from(payments)
      .orderBy(desc(payments.createdAt))
      .limit(limit);
  }

  // Receipt methods
  async getReceipt(id: number): Promise<Receipt | undefined> {
    const [receipt] = await db.select().from(receipts).where(eq(receipts.id, id));
    return receipt;
  }

  async getReceiptByNumber(receiptNumber: string): Promise<Receipt | undefined> {
    const [receipt] = await db
      .select()
      .from(receipts)
      .where(eq(receipts.receiptNumber, receiptNumber));
    return receipt;
  }

  async createReceipt(receipt: InsertReceipt): Promise<Receipt> {
    const [createdReceipt] = await db
      .insert(receipts)
      .values(receipt)
      .returning();
    return createdReceipt;
  }

  async updateReceipt(id: number, receiptData: Partial<Receipt>): Promise<Receipt | undefined> {
    const [updatedReceipt] = await db
      .update(receipts)
      .set(receiptData)
      .where(eq(receipts.id, id))
      .returning();
    return updatedReceipt;
  }

  async getAllReceipts(): Promise<Receipt[]> {
    return await db
      .select()
      .from(receipts)
      .orderBy(desc(receipts.generatedDate));
  }

  async getReceiptsByStudentId(studentId: number): Promise<Receipt[]> {
    return await db
      .select()
      .from(receipts)
      .where(eq(receipts.studentId, studentId))
      .orderBy(desc(receipts.generatedDate));
  }

  async getReceiptsByAcademicYear(academicYearId: number): Promise<Receipt[]> {
    return await db
      .select()
      .from(receipts)
      .where(eq(receipts.academicYearId, academicYearId))
      .orderBy(desc(receipts.generatedDate));
  }

  // Settings methods
  async getSetting(name: string): Promise<Setting | undefined> {
    const [setting] = await db
      .select()
      .from(settings)
      .where(eq(settings.name, name));
    return setting;
  }

  async updateSetting(name: string, value: string): Promise<Setting | undefined> {
    const setting = await this.getSetting(name);
    
    if (!setting) {
      // Create setting if it doesn't exist
      const [newSetting] = await db
        .insert(settings)
        .values({
          name,
          value,
          description: null
        })
        .returning();
      return newSetting;
    }
    
    const [updatedSetting] = await db
      .update(settings)
      .set({ 
        value,
        updatedAt: new Date()
      })
      .where(eq(settings.id, setting.id))
      .returning();
    return updatedSetting;
  }

  async getAllSettings(): Promise<Setting[]> {
    return await db
      .select()
      .from(settings)
      .orderBy(asc(settings.name));
  }
}

// Switch to database storage
export const storage = new DatabaseStorage();
