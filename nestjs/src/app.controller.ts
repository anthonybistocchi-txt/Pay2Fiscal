import { randomUUID } from 'node:crypto';
import { Body, Controller, Get, Headers, Post } from '@nestjs/common';

@Controller()
export class AppController {
  @Get()
  health(): { status: string; service: string } {
    return { status: 'ok', service: 'pay2fiscal-nestjs' };
  }

  @Post('payments/dispatch')
  dispatchPayment(
    @Body() payload: Record<string, unknown>,
    @Headers('idempotency-key') idempotencyKeyHeader?: string,
  ): { idempotency_key: string; status: string } {
    const idempotencyKey =
      (typeof idempotencyKeyHeader === 'string' && idempotencyKeyHeader !== ''
        ? idempotencyKeyHeader
        : null) ??
      (typeof payload.idempotency_key === 'string' ? payload.idempotency_key : '');

    return {
      idempotency_key: idempotencyKey,
      status: 'approved',
    };
  }

  @Post('transactions/dispatch')
  dispatch(@Body() payload: Record<string, unknown>): {
    request_id: string;
    transaction_uuid: string | null;
    status: string;
  } {
    const transactionUuid =
      typeof payload.transaction_uuid === 'string' ? payload.transaction_uuid : null;

    return {
      request_id: randomUUID(),
      transaction_uuid: transactionUuid,
      status: 'accepted',
    };
  }
}
