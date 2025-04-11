import { drizzle } from 'drizzle-orm/neon-http';
import { neon, neonConfig } from '@neondatabase/serverless';
import * as schema from '@shared/schema';

// Configure neon to work in server environment
neonConfig.fetchConnectionCache = true;

// Initialize Neon SQL connection
const sql = neon(process.env.DATABASE_URL!);

// Initialize Drizzle ORM with full schema
export const db = drizzle(sql, { schema });