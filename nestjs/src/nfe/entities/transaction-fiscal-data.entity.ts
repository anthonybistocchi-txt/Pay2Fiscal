import {
  Column,
  CreateDateColumn,
  Entity,
  JoinColumn,
  OneToOne,
  PrimaryGeneratedColumn,
  UpdateDateColumn,
} from 'typeorm';
import { FiscalStatus } from '../enums/fiscal-status.enum';
import { Transaction } from './transaction.entity';

@Entity('transaction_fiscal_data')
export class TransactionFiscalData {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ name: 'transaction_id', type: 'int', unique: true })
  transactionId: number;

  @Column({
    name: 'fiscal_status',
    type: 'enum',
    enum: FiscalStatus,
    default: FiscalStatus.PENDING,
  })
  fiscalStatus: FiscalStatus;

  @Column({ name: 'origin_product', type: 'smallint', nullable: true })
  originProduct: number | null;

  @Column({ type: 'varchar', length: 8, nullable: true })
  ncm: string | null;

  @Column({ type: 'varchar', length: 4, nullable: true })
  cfop: string | null;

  @Column({ type: 'varchar', length: 7, nullable: true })
  cest: string | null;

  @Column({ name: 'icms_cst_csosn', type: 'varchar', length: 4, nullable: true })
  icmsCstCsosn: string | null;

  @Column({ name: 'pis_cst', type: 'varchar', length: 2, nullable: true })
  pisCst: string | null;

  @Column({ name: 'cofins_cst', type: 'varchar', length: 2, nullable: true })
  cofinsCst: string | null;

  @Column({ name: 'fiscal_request_id', type: 'varchar', unique: true, nullable: true })
  fiscalRequestId: string | null;

  @Column({ name: 'failure_reason', type: 'text', nullable: true })
  failureReason: string | null;

  @Column({ name: 'error_code', type: 'smallint', nullable: true })
  errorCode: number | null;

  @Column({ name: 'emitted_at', type: 'timestamp', nullable: true })
  emittedAt: Date | null;

  @OneToOne(() => Transaction, (transaction) => transaction.fiscalData, {
    onDelete: 'CASCADE',
  })
  @JoinColumn({ name: 'transaction_id' })
  transaction: Transaction;

  @CreateDateColumn({ name: 'created_at' })
  createdAt: Date;

  @UpdateDateColumn({ name: 'updated_at' })
  updatedAt: Date;
}
