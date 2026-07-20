import {
  IsDateString,
  IsEnum,
  IsNumber,
  IsOptional,
  IsUUID,
} from 'class-validator';
import { PaymentMethod } from '../../nfe/enums/payment-method.enum';
import { PaymentStatus } from '../../nfe/enums/payment-status.enum';

export class DispatchPaymentDto {
  @IsNumber({}, { message: 'transaction_id must be a number.' })
  transaction_id: number;

  @IsUUID(undefined, { message: 'transaction_uuid must be a valid UUID.' })
  transaction_uuid: string;

  @IsNumber({}, { message: 'payment_amount must be a number.' })
  payment_amount: number;

  @IsEnum(PaymentMethod, { message: 'payment_method must be a valid payment method.' })
  payment_method: PaymentMethod;

  @IsEnum(PaymentStatus, { message: 'payment_status must be a valid payment status.' })
  payment_status: PaymentStatus;

  @IsOptional()
  @IsDateString({}, { message: 'payment_date must be a valid ISO date string.' })
  payment_date?: string | null;

  @IsUUID(undefined, { message: 'idempotency_key must be a valid UUID.' })
  idempotency_key: string;
}
