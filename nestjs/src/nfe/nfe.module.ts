import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Emitter, Transaction, TransactionFiscalData } from './entities';
import { FiscalOutcomeHandler } from './handlers/fiscal-outcome.handler';
import { NfeController } from './nfe.controller';
import { NfeService } from './nfe.service';
import { FiscalSimulationService } from './simulation/fiscal-simulation.service';
import { NfeDispatchValidator } from './validators/nfe-dispatch.validator';

@Module({
  imports: [TypeOrmModule.forFeature([Transaction, TransactionFiscalData, Emitter])],
  controllers: [NfeController],
  providers: [
    NfeService,
    NfeDispatchValidator,
    FiscalSimulationService,
    FiscalOutcomeHandler,
  ],
})
export class NfeModule {}
