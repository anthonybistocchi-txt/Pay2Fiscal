import { Controller, Body, Post } from '@nestjs/common';
import { DispatchNfeDto } from './dto/dispatch-nfe.dto';
import { NfeService } from './nfe.service';
@Controller('nfe')
export class NfeController {
  constructor(private readonly nfeService: NfeService) {}

  @Post()
  dispatch(@Body() payload: DispatchNfeDto) {
    return this.nfeService.dispatch(payload);
  }
}

