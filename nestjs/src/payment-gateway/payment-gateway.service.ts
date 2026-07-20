import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { randomUUID } from 'node:crypto';
import { Repository } from 'typeorm';
import { assertPayloadMatch } from '../common/utils/payload-match.util';
import { Transaction } from '../nfe/entities/transaction.entity';
import { PaymentStatus } from '../nfe/enums/payment-status.enum';
import { DispatchPaymentDto } from './dto/dispatch-payment.dto';

export type DispatchPaymentSuccessResponse = {
  idempotency_key: string;
  transaction_uuid: string;
  status: 'approved';
  gateway_reference: string;
  processed_at: string;
};

@Injectable()
export class PaymentGatewayService {
  constructor(
    @InjectRepository(Transaction)
    private readonly transactionRepository: Repository<Transaction>,
  ) {}

  async dispatch(
    payload: DispatchPaymentDto,
  ): Promise<DispatchPaymentSuccessResponse> {
    const transaction = await this.getTransactionValidated(payload);
    this.assertPayloadMatchesTransaction(transaction, payload);

    return this.buildSuccessResponse(transaction);
  }

  private async getTransactionValidated(
    payload: DispatchPaymentDto,
  ): Promise<Transaction> {
    const transaction = await this.transactionRepository.findOne({
      where: {
        id: payload.transaction_id,
        transactionUuid: payload.transaction_uuid,
        paymentStatus: PaymentStatus.PROCESSING,
      },
    });

    if (!transaction) {
      throw new NotFoundException(
        'Transaction not found or not ready for gateway dispatch',
      );
    }

    if (payload.idempotency_key !== transaction.idempotencyKey) {
      throw new BadRequestException('Transaction idempotency key mismatch');
    }

    return transaction;
  }

  private assertPayloadMatchesTransaction(
    transaction: Transaction,
    payload: DispatchPaymentDto,
  ): void {
    assertPayloadMatch(
      'transaction_id',
      payload.transaction_id,
      transaction.id,
    );
    assertPayloadMatch(
      'transaction_uuid',
      payload.transaction_uuid,
      transaction.transactionUuid,
    );
    assertPayloadMatch(
      'payment_amount',
      payload.payment_amount,
      transaction.paymentAmount,
    );
    assertPayloadMatch(
      'payment_method',
      payload.payment_method,
      transaction.paymentMethod,
    );
    assertPayloadMatch(
      'payment_status',
      payload.payment_status,
      transaction.paymentStatus,
    );
    assertPayloadMatch(
      'payment_date',
      payload.payment_date ?? null,
      transaction.paymentDate,
    );
    assertPayloadMatch(
      'idempotency_key',
      payload.idempotency_key,
      transaction.idempotencyKey,
    );
  }

  private buildSuccessResponse(
    transaction: Transaction,
  ): DispatchPaymentSuccessResponse {
    return {
      idempotency_key: transaction.idempotencyKey,
      transaction_uuid: transaction.transactionUuid,
      status: 'approved',
      gateway_reference: randomUUID(),
      processed_at: new Date().toISOString(),
    };
  }
}
