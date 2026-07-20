import {
  Column,
  CreateDateColumn,
  Entity,
  Index,
  OneToOne,
  PrimaryGeneratedColumn,
  UpdateDateColumn,
} from 'typeorm';
import { PaymentMethod } from '../enums/payment-method.enum';
import { PaymentStatus } from '../enums/payment-status.enum';
import { TransactionFiscalData } from '../entities/transaction-fiscal-data.entity';

@Entity('transactions')
@Index('transactions_user_status_idx', ['userId', 'paymentStatus'])
@Index('transactions_status_created_idx', ['paymentStatus', 'createdAt'])
export class Transaction {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ name: 'user_id', type: 'int' })
  userId: number;

  @Column({ name: 'product_id', type: 'int' })
  productId: number;

  @Column({ name: 'payment_amount', type: 'int' })
  paymentAmount: number;

  @Column({ name: 'payment_method', type: 'enum', enum: PaymentMethod })
  paymentMethod: PaymentMethod;

  @Column({ name: 'payment_status', type: 'enum', enum: PaymentStatus })
  paymentStatus: PaymentStatus;

  @Column({ name: 'idempotency_key', type: 'uuid', unique: true })
  idempotencyKey: string;

  @Column({ name: 'transaction_uuid', type: 'uuid', unique: true })
  transactionUuid: string;

  @Column({ name: 'last_4_digits_card_number', type: 'varchar', nullable: true })
  last4DigitsCardNumber: string | null;

  @Column({ name: 'card_brand', type: 'varchar', nullable: true })
  cardBrand: string | null;

  @Column({ type: 'int' })
  quantity: number;

  @Column({ name: 'payment_date', type: 'timestamp', nullable: true })
  paymentDate: Date | null;

  @OneToOne(() => TransactionFiscalData, (fiscalData) => fiscalData.transaction)
  fiscalData: TransactionFiscalData;

  @CreateDateColumn({ name: 'created_at' })
  createdAt: Date;

  @UpdateDateColumn({ name: 'updated_at' })
  updatedAt: Date;
}
