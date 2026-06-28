import { Module } from '@nestjs/common';
import { PaymentsController } from './payment-gateway.controller';
import { PaymentsService } from './payment-gateway.service';

@Module({
  controllers: [PaymentsController],
  providers: [PaymentsService]
})
export class PaymentsModule {}
