import { FiscalStatus } from '../enums/fiscal-status.enum';

export const FISCAL_DISPATCHABLE_STATUSES: FiscalStatus[] = [
  FiscalStatus.PENDING,
  FiscalStatus.PROCESSING,
];

/** NCMs reservados para simulação (somente fora de production). */
export const SIMULATION_NCM = {
  PROCESSING: '99999997',
  REJECTED: '99999998',
  DENIED: '99999999',
  ERROR: '99999996',
} as const;

export const FISCAL_FAILURE_MESSAGES = {
  REJECTED:
    'Documento rejeitado pela Sefaz: dados inconsistentes ou erro de preenchimento.',
  DENIED:
    'Documento denegado pela Sefaz: irregularidade fiscal do emitente ou destinatário. Numeração inutilizável.',
  ERROR: 'Falha interna ao processar o documento fiscal.',
} as const;
