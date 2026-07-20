import {
  IsEnum,
  IsNumber,
  IsObject,
  IsOptional,
  IsString,
  IsUUID,
  ValidateNested,
} from 'class-validator';
import { Type } from 'class-transformer';
import { PaymentMethod } from '../enums/payment-method.enum';
import { PaymentStatus } from '../enums/payment-status.enum';

export class TransactionFiscalDataDto {
  @IsNumber({}, { message: 'origin_product must be a number.' })
  origin_product: number;

  @IsString({ message: 'ncm must be a string.' })
  ncm: string;

  @IsString({ message: 'cfop must be a string.' })
  cfop: string;

  @IsOptional()
  @IsString({ message: 'cest must be a string.' })
  cest?: string | null;

  @IsString({ message: 'icms_cst_csosn must be a string.' })
  icms_cst_csosn: string;

  @IsString({ message: 'pis_cst must be a string.' })
  pis_cst: string;

  @IsString({ message: 'cofins_cst must be a string.' })
  cofins_cst: string;
}

export class EmitterAddressDto {
  @IsString({ message: 'address.street must be a string.' })
  street: string;

  @IsString({ message: 'address.number must be a string.' })
  number: string;

  @IsOptional()
  @IsString({ message: 'address.complement must be a string.' })
  complement?: string | null;

  @IsOptional()
  @IsString({ message: 'address.neighborhood must be a string.' })
  neighborhood?: string | null;

  @IsString({ message: 'address.city must be a string.' })
  city: string;

  @IsString({ message: 'address.state must be a string.' })
  state: string;

  @IsString({ message: 'address.zip_code must be a string.' })
  zip_code: string;

  @IsString({ message: 'address.country must be a string.' })
  country: string;
}

export class EmitterDto {
  @IsString({ message: 'emitter.legal_name must be a string.' })
  legal_name: string;

  @IsOptional()
  @IsString({ message: 'emitter.trade_name must be a string.' })
  trade_name?: string | null;

  @IsString({ message: 'emitter.cnpj must be a string.' })
  cnpj: string;

  @IsOptional()
  @IsString({ message: 'emitter.ie must be a string.' })
  ie?: string | null;

  @IsOptional()
  @IsString({ message: 'emitter.im must be a string.' })
  im?: string | null;

  @IsString({ message: 'emitter.tax_regime must be a string.' })
  tax_regime: string;

  @IsString({ message: 'emitter.crt must be a string.' })
  crt: string;

  @ValidateNested({ message: 'emitter.address must be a valid object.' })
  @Type(() => EmitterAddressDto)
  address: EmitterAddressDto;

  @IsOptional()
  @IsString({ message: 'emitter.email must be a string.' })
  email?: string | null;

  @IsOptional()
  @IsString({ message: 'emitter.phone must be a string.' })
  phone?: string | null;
}

export class DispatchNfeDto {
  @IsUUID(undefined, { message: 'transaction_uuid must be a valid UUID.' })
  transaction_uuid: string;

  @IsUUID(undefined, { message: 'idempotency_key must be a valid UUID.' })
  idempotency_key: string;

  @IsNumber({}, { message: 'user_id must be a number.' })
  user_id: number;

  @IsNumber({}, { message: 'product_id must be a number.' })
  product_id: number;

  @IsNumber({}, { message: 'quantity must be a number.' })
  quantity: number;

  @IsNumber({}, { message: 'payment_amount must be a number.' })
  payment_amount: number;

  @IsEnum(PaymentMethod, { message: 'payment_method must be a valid payment method.' })
  payment_method: PaymentMethod;

  @IsEnum(PaymentStatus, { message: 'payment_status must be a valid payment status.' })
  payment_status: PaymentStatus;

  @IsOptional()
  @IsString({ message: 'card_brand must be a string.' })
  card_brand?: string | null;

  @IsOptional()
  @IsString({ message: 'last_4_digits_card must be a string.' })
  last_4_digits_card?: string | null;

  @IsObject({ message: 'transaction_fiscal_data must be an object.' })
  @ValidateNested({ message: 'transaction_fiscal_data must be a valid object.' })
  @Type(() => TransactionFiscalDataDto)
  transaction_fiscal_data: TransactionFiscalDataDto;

  @ValidateNested({ message: 'emitter must be a valid object.' })
  @Type(() => EmitterDto)
  emitter: EmitterDto;
}
