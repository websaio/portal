import { pgTable, text, serial, integer, boolean, timestamp, varchar, decimal, date } from "drizzle-orm/pg-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod";

// Academic Year schema
export const academicYears = pgTable("academic_years", {
  id: serial("id").primaryKey(),
  name: text("name").notNull().unique(), // e.g. "2023-2024", "2024-2025"
  startDate: date("start_date").notNull(),
  endDate: date("end_date").notNull(),
  isCurrentYear: boolean("is_current_year").default(false),
  status: text("status").notNull().default("active"), // active, closed
  createdAt: timestamp("created_at").defaultNow(),
});

export const insertAcademicYearSchema = createInsertSchema(academicYears).omit({
  id: true,
  createdAt: true,
});

// User schema
export const users = pgTable("users", {
  id: serial("id").primaryKey(),
  username: text("username").notNull().unique(),
  password: text("password").notNull(),
  email: text("email").notNull().unique(),
  name: text("name").notNull(),
  role: text("role").notNull().default("staff"), // admin or staff
  createdAt: timestamp("created_at").defaultNow(),
  lastLogin: timestamp("last_login"),
});

export const insertUserSchema = createInsertSchema(users).omit({
  id: true,
  createdAt: true,
  lastLogin: true,
});

// Students schema (base student info that doesn't change between years)
export const students = pgTable("students", {
  id: serial("id").primaryKey(),
  studentId: varchar("student_id", { length: 10 }).notNull().unique(),
  firstName: text("first_name").notNull(),
  lastName: text("last_name").notNull(),
  email: text("email").notNull(),
  phone: text("phone").notNull(),
  address: text("address"),
  createdAt: timestamp("created_at").defaultNow(),
});

export const insertStudentSchema = createInsertSchema(students).omit({
  id: true,
  studentId: true,
  createdAt: true,
});

// Student Enrollments schema (year-specific student data)
export const studentEnrollments = pgTable("student_enrollments", {
  id: serial("id").primaryKey(),
  studentId: integer("student_id").notNull().references(() => students.id),
  academicYearId: integer("academic_year_id").notNull().references(() => academicYears.id),
  grade: text("grade").notNull(), // nursery, kg1, kg2, grade1-12
  section: text("section"), // A, B, C, etc. (optional)
  joiningDate: timestamp("joining_date").notNull(),
  tuitionFee: decimal("tuition_fee", { precision: 10, scale: 2 }),
  status: text("status").notNull().default("active"), // active, inactive, pending
  createdAt: timestamp("created_at").defaultNow(),
});

export const insertStudentEnrollmentSchema = createInsertSchema(studentEnrollments).omit({
  id: true,
  createdAt: true,
});

// Payments schema
export const payments = pgTable("payments", {
  id: serial("id").primaryKey(),
  studentId: integer("student_id").notNull().references(() => students.id),
  academicYearId: integer("academic_year_id").notNull().references(() => academicYears.id),
  amount: decimal("amount", { precision: 10, scale: 2 }).notNull(),
  paymentDate: timestamp("payment_date").notNull(),
  paymentType: text("payment_type").notNull(), // tuition, registration, exam, library, etc.
  paymentMethod: text("payment_method").notNull(), // cash, credit_card, bank_transfer, check
  receiptNumber: varchar("receipt_number", { length: 15 }),
  discount: decimal("discount", { precision: 10, scale: 2 }).default("0"),
  status: text("status").notNull().default("completed"), // completed, pending, failed
  notes: text("notes"),
  createdBy: integer("created_by").notNull().references(() => users.id),
  createdAt: timestamp("created_at").defaultNow(),
});

export const insertPaymentSchema = createInsertSchema(payments).omit({
  id: true,
  receiptNumber: true,
  createdAt: true,
});

// Receipts schema
export const receipts = pgTable("receipts", {
  id: serial("id").primaryKey(),
  receiptNumber: varchar("receipt_number", { length: 15 }).notNull().unique(),
  paymentId: integer("payment_id").notNull().references(() => payments.id),
  studentId: integer("student_id").notNull().references(() => students.id),
  academicYearId: integer("academic_year_id").notNull().references(() => academicYears.id),
  amount: decimal("amount", { precision: 10, scale: 2 }).notNull(),
  generatedDate: timestamp("generated_date").notNull(),
  signedBy: integer("signed_by").notNull().references(() => users.id),
  emailSent: boolean("email_sent").default(false),
  createdAt: timestamp("created_at").defaultNow(),
});

export const insertReceiptSchema = createInsertSchema(receipts).omit({
  id: true,
  createdAt: true,
});

// Settings schema
export const settings = pgTable("settings", {
  id: serial("id").primaryKey(),
  name: text("name").notNull().unique(),
  value: text("value").notNull(),
  description: text("description"),
  updatedAt: timestamp("updated_at").defaultNow(),
});

export const insertSettingsSchema = createInsertSchema(settings).omit({
  id: true,
  updatedAt: true,
});

// Type definitions
export type AcademicYear = typeof academicYears.$inferSelect;
export type InsertAcademicYear = z.infer<typeof insertAcademicYearSchema>;

export type User = typeof users.$inferSelect;
export type InsertUser = z.infer<typeof insertUserSchema>;

export type Student = typeof students.$inferSelect;
export type InsertStudent = z.infer<typeof insertStudentSchema>;

export type StudentEnrollment = typeof studentEnrollments.$inferSelect;
export type InsertStudentEnrollment = z.infer<typeof insertStudentEnrollmentSchema>;

export type Payment = typeof payments.$inferSelect;
export type InsertPayment = z.infer<typeof insertPaymentSchema>;

export type Receipt = typeof receipts.$inferSelect;
export type InsertReceipt = z.infer<typeof insertReceiptSchema>;

export type Setting = typeof settings.$inferSelect;
export type InsertSetting = z.infer<typeof insertSettingsSchema>;
