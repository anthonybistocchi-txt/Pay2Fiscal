import {
  Column,
  CreateDateColumn,
  Entity,
  PrimaryGeneratedColumn,
  UpdateDateColumn,
} from 'typeorm';
import { EmitterCrt } from '../enums/emitter-crt.enum';
import { TaxRegime } from '../enums/tax-regime.enum';

@Entity('emitters')
export class Emitter {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ name: 'legal_name', type: 'varchar' })
  legalName: string;

  @Column({ name: 'trade_name', type: 'varchar', nullable: true })
  tradeName: string | null;

  @Column({ type: 'varchar', length: 14, unique: true })
  cnpj: string;

  @Column({ type: 'varchar', nullable: true })
  ie: string | null;

  @Column({ type: 'varchar', nullable: true })
  im: string | null;

  @Column({ name: 'tax_regime', type: 'enum', enum: TaxRegime, nullable: true })
  taxRegime: TaxRegime | null;

  @Column({ type: 'enum', enum: EmitterCrt, nullable: true })
  crt: EmitterCrt | null;

  @Column({ type: 'varchar' })
  street: string;

  @Column({ type: 'varchar' })
  number: string;

  @Column({ type: 'varchar', nullable: true })
  complement: string | null;

  @Column({ type: 'varchar', nullable: true })
  neighborhood: string | null;

  @Column({ type: 'varchar' })
  city: string;

  @Column({ type: 'varchar', length: 2 })
  state: string;

  @Column({ name: 'zip_code', type: 'varchar', length: 8 })
  zipCode: string;

  @Column({ type: 'varchar', length: 2, default: 'BR' })
  country: string;

  @Column({ type: 'varchar', nullable: true })
  email: string | null;

  @Column({ type: 'varchar', nullable: true })
  phone: string | null;

  @CreateDateColumn({ name: 'created_at' })
  createdAt: Date;

  @UpdateDateColumn({ name: 'updated_at' })
  updatedAt: Date;
}
