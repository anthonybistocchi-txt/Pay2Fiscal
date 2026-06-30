import {
  HttpException,
  HttpStatus,
  Injectable,
  InternalServerErrorException,
  UnprocessableEntityException,
} from '@nestjs/common';
import { randomUUID } from 'node:crypto';
import { FISCAL_FAILURE_MESSAGES } from '../constants/fiscal-dispatch.constants';
import { FiscalStatus } from '../enums/fiscal-status.enum';
import { FiscalSimulationService } from '../simulation/fiscal-simulation.service';
import {
  DispatchNfeResponseBody,
  NfeDispatchContext,
  SimulationOutcome,
} from '../types/nfe-dispatch.types';

@Injectable()
export class FiscalOutcomeHandler {
  constructor(private readonly simulationService: FiscalSimulationService) {}

  handle(context: NfeDispatchContext): DispatchNfeResponseBody {
    const outcome = this.simulationService.resolveOutcome(context.fiscalData.ncm);
    const requestId = randomUUID();

    return this.applyOutcome(outcome, context, requestId);
  }

  private applyOutcome(
    outcome: SimulationOutcome,
    context: NfeDispatchContext,
    requestId: string,
  ): DispatchNfeResponseBody {
    const { transaction, emitter } = context;

    switch (outcome) {
      case 'EMITTED': {
        const emittedAt = new Date();

        return {
          request_id: requestId,
          transaction_uuid: transaction.transactionUuid,
          status: 'EMITTED',
          fiscal_status: FiscalStatus.EMITTED,
          emitter_cnpj: emitter.cnpj,
          emitted_at: emittedAt.toISOString(),
        };
      }

      case 'PROCESSING':
        throw new HttpException(
          {
            request_id: requestId,
            transaction_uuid: transaction.transactionUuid,
            status: 'PROCESSING',
            fiscal_status: FiscalStatus.PROCESSING,
            emitter_cnpj: emitter.cnpj,
          },
          HttpStatus.ACCEPTED,
        );

      case 'REJECTED':
        return this.failWithStatus(
          context,
          requestId,
          'REJECTED',
          FISCAL_FAILURE_MESSAGES.REJECTED,
          UnprocessableEntityException,
        );

      case 'DENIED':
        return this.failWithStatus(
          context,
          requestId,
          'DENIED',
          FISCAL_FAILURE_MESSAGES.DENIED,
          UnprocessableEntityException,
        );

      case 'ERROR':
        return this.failWithStatus(
          context,
          requestId,
          'ERROR',
          FISCAL_FAILURE_MESSAGES.ERROR,
          InternalServerErrorException,
        );

      default: {
        const exhaustiveCheck: never = outcome;
        throw new HttpException(
          `Unhandled fiscal outcome: ${String(exhaustiveCheck)}`,
          HttpStatus.INTERNAL_SERVER_ERROR,
        );
      }
    }
  }

  private failWithStatus(
    context: NfeDispatchContext,
    requestId: string,
    status: 'REJECTED' | 'DENIED' | 'ERROR',
    failureReason: string,
    ExceptionClass: typeof UnprocessableEntityException | typeof InternalServerErrorException,
  ): never {
    const { transaction, emitter } = context;

    throw new ExceptionClass({
      request_id: requestId,
      transaction_uuid: transaction.transactionUuid,
      status,
      fiscal_status: FiscalStatus[status],
      emitter_cnpj: emitter.cnpj,
      failure_reason: failureReason,
    });
  }
}
