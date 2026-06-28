import {
  BadRequestException,
  Injectable,
  NotFoundException,
  UnprocessableEntityException,
} from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { randomUUID } from 'node:crypto';
import { Repository } from 'typeorm';
import { assertPayloadMatch } from '../common/utils/payload-match.util';
import { DispatchNfeDto, EmitterDto } from './dto/dispatch-nfe.dto';
import { Transaction } from './entities/transaction.entity';
import { PaymentStatus } from './enums/payment-status.enum';
import { Emitter } from './entities/emitter.entity';
import { TransactionFiscalData } from './entities/transaction-fiscal-data.entity';
import { FiscalStatus } from './enums/fiscal-status.enum';

const FISCAL_DISPATCHABLE_STATUSES: FiscalStatus[] = [
  FiscalStatus.PENDING,
  FiscalStatus.PROCESSING,
];

export type DispatchNfeSuccessResponse = {
  request_id: string;
  transaction_uuid: string;
  status: 'emitted';
  fiscal_status: FiscalStatus;
  emitter_cnpj: string;
  emitted_at: string;
};

@Injectable()
export class NfeService {
  constructor(
    @InjectRepository(Transaction)
    private readonly transactionRepository: Repository<Transaction>,
    @InjectRepository(Emitter)
    private readonly emitterRepository: Repository<Emitter>,
    @InjectRepository(TransactionFiscalData)
    private readonly transactionFiscalDataRepository: Repository<TransactionFiscalData>,
  ) {}

  async dispatch(payload: DispatchNfeDto): Promise<DispatchNfeSuccessResponse> {
    const [transaction, fiscalData, emitter] = await Promise.all([
      this.getTransactionValidated(payload),
      this.getTransactionFiscalDataValidated(payload),
      this.getEmitterValidated(payload.emitter),
    ]);

    this.assertFiscalDataBelongsToTransaction(fiscalData, transaction);
    this.assertTransactionPayloadMatches(transaction, payload);

    return this.buildSuccessResponse(transaction, fiscalData, emitter);
  }

  private buildSuccessResponse(
    transaction: Transaction,
    fiscalData: TransactionFiscalData,
    emitter: Emitter,
  ): DispatchNfeSuccessResponse {
    return {
      request_id: randomUUID(),
      transaction_uuid: transaction.transactionUuid,
      status: 'emitted',
      fiscal_status: fiscalData.fiscalStatus,
      emitter_cnpj: emitter.cnpj,
      emitted_at: new Date().toISOString(),
    };
  }

  private assertTransactionPayloadMatches(
    transaction: Transaction,
    payload: DispatchNfeDto,
  ): void {
    assertPayloadMatch('user_id', payload.user_id, transaction.userId);
    assertPayloadMatch('product_id', payload.product_id, transaction.productId);
    assertPayloadMatch('quantity', payload.quantity, transaction.quantity);
    assertPayloadMatch(
      'payment_amount',
      payload.payment_amount,
      transaction.paymentAmount,
    );
    assertPayloadMatch(
      'payment_method',
      payload.payment_method,
      transaction.paymentMethod,
    );
    assertPayloadMatch(
      'payment_status',
      payload.payment_status,
      transaction.paymentStatus,
    );
    assertPayloadMatch('card_brand', payload.card_brand ?? null, transaction.cardBrand);
    assertPayloadMatch(
      'last_4_digits_card',
      payload.last_4_digits_card ?? null,
      transaction.last4DigitsCardNumber,
    );
  }

  private async getTransactionValidated(
    payload: DispatchNfeDto,
  ): Promise<Transaction> {
    const transaction = await this.transactionRepository.findOne({
      where: {
        transactionUuid: payload.transaction_uuid,
        paymentStatus: PaymentStatus.APPROVED,
      },
    });

    if (!transaction) {
      throw new NotFoundException('Transaction not found or not approved');
    }

    assertPayloadMatch(
      'idempotency_key',
      payload.idempotency_key,
      transaction.idempotencyKey,
    );

    return transaction;
  }

  private assertFiscalDataBelongsToTransaction(
    fiscalData: TransactionFiscalData,
    transaction: Transaction,
  ): void {
    if (fiscalData.transactionId !== transaction.id) {
      throw new BadRequestException(
        'Transaction fiscal data does not belong to transaction',
      );
    }
  }

  private async getTransactionFiscalDataValidated(
    payload: DispatchNfeDto,
  ): Promise<TransactionFiscalData> {
    const fiscalData = await this.transactionFiscalDataRepository.findOne({
      where: { transaction: { transactionUuid: payload.transaction_uuid } },
    });

    if (!fiscalData) {
      throw new NotFoundException('Transaction fiscal data not found');
    }

    if (!FISCAL_DISPATCHABLE_STATUSES.includes(fiscalData.fiscalStatus)) {
      throw new UnprocessableEntityException(
        `Fiscal data is not dispatchable (status: ${fiscalData.fiscalStatus})`,
      );
    }

    const payloadFiscal = payload.transaction_fiscal_data;

    assertPayloadMatch(
      'origin_product',
      payloadFiscal.origin_product,
      fiscalData.originProduct,
    );
    assertPayloadMatch('ncm', payloadFiscal.ncm, fiscalData.ncm);
    assertPayloadMatch('cfop', payloadFiscal.cfop, fiscalData.cfop);
    assertPayloadMatch('cest', payloadFiscal.cest, fiscalData.cest);
    assertPayloadMatch(
      'icms_cst_csosn',
      payloadFiscal.icms_cst_csosn,
      fiscalData.icmsCstCsosn,
    );
    assertPayloadMatch('pis_cst', payloadFiscal.pis_cst, fiscalData.pisCst);
    assertPayloadMatch(
      'cofins_cst',
      payloadFiscal.cofins_cst,
      fiscalData.cofinsCst,
    );

    return fiscalData;
  }

  private async getEmitterValidated(emitter: EmitterDto): Promise<Emitter> {
    const emitterEntity = await this.emitterRepository.findOne({
      where: { cnpj: emitter.cnpj },
    });

    if (!emitterEntity) {
      throw new NotFoundException('Emitter not found');
    }

    assertPayloadMatch('emitter.legal_name', emitter.legal_name, emitterEntity.legalName);
    assertPayloadMatch('emitter.trade_name', emitter.trade_name ?? null, emitterEntity.tradeName);
    assertPayloadMatch('emitter.ie', emitter.ie ?? null, emitterEntity.ie);
    assertPayloadMatch('emitter.im', emitter.im ?? null, emitterEntity.im);
    assertPayloadMatch('emitter.tax_regime', emitter.tax_regime, emitterEntity.taxRegime);
    assertPayloadMatch('emitter.crt', emitter.crt, emitterEntity.crt);
    assertPayloadMatch('emitter.email', emitter.email ?? null, emitterEntity.email);
    assertPayloadMatch('emitter.phone', emitter.phone ?? null, emitterEntity.phone);
    assertPayloadMatch(
      'emitter.address.street',
      emitter.address.street,
      emitterEntity.street,
    );
    assertPayloadMatch(
      'emitter.address.number',
      emitter.address.number,
      emitterEntity.number,
    );
    assertPayloadMatch(
      'emitter.address.complement',
      emitter.address.complement ?? null,
      emitterEntity.complement,
    );
    assertPayloadMatch(
      'emitter.address.neighborhood',
      emitter.address.neighborhood ?? null,
      emitterEntity.neighborhood,
    );
    assertPayloadMatch('emitter.address.city', emitter.address.city, emitterEntity.city);
    assertPayloadMatch('emitter.address.state', emitter.address.state, emitterEntity.state);
    assertPayloadMatch(
      'emitter.address.zip_code',
      emitter.address.zip_code,
      emitterEntity.zipCode,
    );
    assertPayloadMatch(
      'emitter.address.country',
      emitter.address.country,
      emitterEntity.country,
    );

    return emitterEntity;
  }
}
