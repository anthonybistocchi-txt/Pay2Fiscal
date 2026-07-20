import { Injectable } from '@nestjs/common';
import { DispatchNfeDto } from './dto/dispatch-nfe.dto';
import { FiscalOutcomeHandler } from './handlers/fiscal-outcome.handler';
import { DispatchNfeResponseBody } from './types/nfe-dispatch.types';
import { NfeDispatchValidator } from './validators/nfe-dispatch.validator';

export type { DispatchNfeResponseBody } from './types/nfe-dispatch.types';

@Injectable()
export class NfeService {
  constructor(
    private readonly dispatchValidator: NfeDispatchValidator,
    private readonly outcomeHandler: FiscalOutcomeHandler,
  ) {}

  async dispatch(payload: DispatchNfeDto): Promise<DispatchNfeResponseBody> {
    const context = await this.dispatchValidator.validate(payload);
    return this.outcomeHandler.handle(context);
  }
}
