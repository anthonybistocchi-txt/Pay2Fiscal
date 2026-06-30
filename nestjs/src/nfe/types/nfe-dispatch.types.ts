import { FiscalStatus } from '../enums/fiscal-status.enum';
import { Emitter } from '../entities/emitter.entity';
import { Transaction } from '../entities/transaction.entity';
import { TransactionFiscalData } from '../entities/transaction-fiscal-data.entity';

export type SimulationOutcome =
  | 'EMITTED'
  | 'PROCESSING'
  | 'REJECTED'
  | 'DENIED'
  | 'ERROR';

export type DispatchNfeResponseBody = {
  request_id: string;
  transaction_uuid: string;
  status: SimulationOutcome;
  fiscal_status: FiscalStatus;
  emitter_cnpj: string;
  emitted_at?: string;
  failure_reason?: string;
};

export type NfeDispatchContext = {
  transaction: Transaction;
  fiscalData: TransactionFiscalData;
  emitter: Emitter;
};
