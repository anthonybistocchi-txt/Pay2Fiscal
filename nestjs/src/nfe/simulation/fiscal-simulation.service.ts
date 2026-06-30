import { Injectable } from '@nestjs/common';
import { SIMULATION_NCM } from '../constants/fiscal-dispatch.constants';
import { SimulationOutcome } from '../types/nfe-dispatch.types';

@Injectable()
export class FiscalSimulationService {
  resolveOutcome(ncm: string | null): SimulationOutcome {
    if (process.env.NODE_ENV === 'production' || ncm === null) {
      return 'EMITTED';
    }

    switch (ncm) {
      case SIMULATION_NCM.PROCESSING:
        return 'PROCESSING';
      case SIMULATION_NCM.REJECTED:
        return 'REJECTED';
      case SIMULATION_NCM.DENIED:
        return 'DENIED';
      case SIMULATION_NCM.ERROR:
        return 'ERROR';
      default:
        return 'EMITTED';
    }
  }
}
