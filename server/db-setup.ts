import { db } from './db';
import { academicYears, insertAcademicYearSchema, insertUserSchema, users } from '@shared/schema';
import bcrypt from 'bcrypt';
import { eq } from 'drizzle-orm';

export async function setupDatabase() {
  try {
    console.log('Setting up the database...');

    // Create default admin user if it doesn't exist
    const adminUser = await db.query.users.findFirst({
      where: eq(users.username, 'admin'),
    });

    if (!adminUser) {
      const hashedPassword = await bcrypt.hash('password', 10);
      await db.insert(users).values({
        username: 'admin',
        password: hashedPassword,
        email: 'admin@uic-iq.com',
        name: 'Administrator',
        role: 'admin',
      });
      console.log('Created default admin user');
    }

    // Create current academic year if it doesn't exist
    const currentYear = await db.query.academicYears.findFirst({
      where: eq(academicYears.isCurrentYear, true),
    });

    if (!currentYear) {
      const currentDate = new Date();
      const currentYearNumber = currentDate.getFullYear();
      const nextYearNumber = currentYearNumber + 1;
      
      const academicYearName = `${currentYearNumber}-${nextYearNumber}`;
      
      await db.insert(academicYears).values({
        name: academicYearName,
        startDate: `${currentYearNumber}-09-01`, // September 1st of current year
        endDate: `${nextYearNumber}-08-31`, // August 31st of next year
        isCurrentYear: true,
        status: 'active',
      });
      console.log(`Created current academic year: ${academicYearName}`);
    }

    console.log('Database setup completed!');
  } catch (error) {
    console.error('Database setup error:', error);
  }
}