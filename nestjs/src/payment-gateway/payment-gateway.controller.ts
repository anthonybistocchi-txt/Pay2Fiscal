import { Body, Controller, Post } from '@nestjs/common';
import { DispatchPaymentDto } from './dto/dispatch-payment.dto';
import { PaymentGatewayService } from './payment-gateway.service';

@Controller('payments')
export class PaymentGatewayController {
  constructor(private readonly paymentGatewayService: PaymentGatewayService) {}

  @Post('dispatch')
  dispatch(@Body() payload: DispatchPaymentDto) {
    return this.paymentGatewayService.dispatch(payload);
  }
}
