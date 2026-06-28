import { Body, Controller, Post } from '@nestjs/common';
import { DispatchNfeDto } from './dto/dispatch-nfe.dto';
import { NfeService } from './nfe.service';

@Controller('transactions')
export class NfeController {
  constructor(private readonly nfeService: NfeService) {}

  @Post('dispatch')
  dispatch(@Body() payload: DispatchNfeDto) {
    return this.nfeService.dispatch(payload);
  }
}
