import { registerAs } from '@nestjs/config';
import { TypeOrmModuleOptions } from '@nestjs/typeorm';

export default registerAs(
  'database',
  (): TypeOrmModuleOptions => ({
    type: 'postgres',
    host: process.env.DATABASE_HOST ?? 'localhost',
    port: Number(process.env.DATABASE_PORT ?? 5432),
    username: process.env.DATABASE_USER ?? 'fiscal',
    password: process.env.DATABASE_PASSWORD ?? 'fiscal',
    database: process.env.DATABASE_NAME ?? 'fiscal_node',
    autoLoadEntities: true,
    synchronize: process.env.NODE_ENV !== 'production',
  }),
);
