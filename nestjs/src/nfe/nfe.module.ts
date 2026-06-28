import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { NfeController } from './nfe.controller';
import { NfeService } from './nfe.service';
import { Emitter, Transaction, TransactionFiscalData } from './entities';

@Module({
  imports: [TypeOrmModule.forFeature([Transaction, TransactionFiscalData, Emitter])],
  controllers: [NfeController],
  providers: [NfeService],
})
export class NfeModule {}
