import { sql } from 'drizzle-orm';
import { db } from './server/db.js';

async function migrate() {
  console.log("Starting database migration...");
  
  try {
    // Add section column
    await db.execute(sql`
      ALTER TABLE student_enrollments 
      ADD COLUMN IF NOT EXISTS section TEXT
    `);
    
    // Rename course to grade
    await db.execute(sql`
      ALTER TABLE student_enrollments 
      RENAME COLUMN course TO grade
    `);
    
    console.log("Migration completed successfully!");
  } catch (error) {
    console.error("Migration failed:", error);
  }
  
  process.exit(0);
}

migrate();
