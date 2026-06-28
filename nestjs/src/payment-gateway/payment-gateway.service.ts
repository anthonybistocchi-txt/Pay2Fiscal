import { Injectable } from '@nestjs/common';

@Injectable()
export class PaymentsService {}

function dispatchPayment(payload: Record<string, unknown>) {
  console.log('dispatchPayment', payload);
}