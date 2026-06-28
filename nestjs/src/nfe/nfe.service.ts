import {
  BadRequestException,
  Injectable,
  NotFoundException,
  UnprocessableEntityException,
} from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
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

  async dispatch(payload: DispatchNfeDto) {
    const [transaction, fiscalData, emitter] = await Promise.all([
      this.getTransactionValidated(payload),
      this.getTransactionFiscalDataValidated(payload),
      this.getEmitterValidated(payload.emitter),
    ]);

    this.assertFiscalDataBelongsToTransaction(fiscalData, transaction);
    this.assertTransactionPayloadMatches(transaction, payload);

    return {
      transaction_uuid: transaction.transactionUuid,
      fiscal_status: fiscalData.fiscalStatus,
      emitter_cnpj: emitter.cnpj,
      status: 'validated',
    };
  }

  private assertTransactionPayloadMatches(
    transaction: Transaction,
    payload: DispatchNfeDto,
  ): void {
    this.assertMatch('user_id', payload.user_id, transaction.userId);
    this.assertMatch('product_id', payload.product_id, transaction.productId);
    this.assertMatch('quantity', payload.quantity, transaction.quantity);
    this.assertMatch(
      'payment_amount',
      payload.payment_amount,
      transaction.paymentAmount,
    );
    this.assertMatch(
      'payment_method',
      payload.payment_method,
      transaction.paymentMethod,
    );
    this.assertMatch(
      'payment_status',
      payload.payment_status,
      transaction.paymentStatus,
    );
    this.assertMatch('card_brand', payload.card_brand, transaction.cardBrand);
    this.assertMatch(
      'last_4_digits_card',
      payload.last_4_digits_card,
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

    if (payload.idempotency_key !== transaction.idempotencyKey) {
      throw new BadRequestException('Transaction idempotency key mismatch');
    }

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

    this.assertMatch(
      'origin_product',
      payloadFiscal.origin_product,
      fiscalData.originProduct,
    );
    this.assertMatch('ncm', payloadFiscal.ncm, fiscalData.ncm);
    this.assertMatch('cfop', payloadFiscal.cfop, fiscalData.cfop);
    this.assertMatch('cest', payloadFiscal.cest, fiscalData.cest);
    this.assertMatch(
      'icms_cst_csosn',
      payloadFiscal.icms_cst_csosn,
      fiscalData.icmsCstCsosn,
    );
    this.assertMatch('pis_cst', payloadFiscal.pis_cst, fiscalData.pisCst);
    this.assertMatch(
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

    this.assertMatch('emitter.legal_name', emitter.legal_name, emitterEntity.legalName);
    this.assertMatch('emitter.trade_name', emitter.trade_name, emitterEntity.tradeName);
    this.assertMatch('emitter.ie', emitter.ie, emitterEntity.ie);
    this.assertMatch('emitter.im', emitter.im, emitterEntity.im);
    this.assertMatch('emitter.tax_regime', emitter.tax_regime, emitterEntity.taxRegime);
    this.assertMatch('emitter.crt', emitter.crt, emitterEntity.crt);
    this.assertMatch('emitter.email', emitter.email, emitterEntity.email);
    this.assertMatch('emitter.phone', emitter.phone, emitterEntity.phone);
    this.assertMatch(
      'emitter.address.street',
      emitter.address.street,
      emitterEntity.street,
    );
    this.assertMatch(
      'emitter.address.number',
      emitter.address.number,
      emitterEntity.number,
    );
    this.assertMatch(
      'emitter.address.complement',
      emitter.address.complement,
      emitterEntity.complement,
    );
    this.assertMatch(
      'emitter.address.neighborhood',
      emitter.address.neighborhood,
      emitterEntity.neighborhood,
    );
    this.assertMatch('emitter.address.city', emitter.address.city, emitterEntity.city);
    this.assertMatch('emitter.address.state', emitter.address.state, emitterEntity.state);
    this.assertMatch(
      'emitter.address.zip_code',
      emitter.address.zip_code,
      emitterEntity.zipCode,
    );
    this.assertMatch(
      'emitter.address.country',
      emitter.address.country,
      emitterEntity.country,
    );

    return emitterEntity;
  }

  private assertMatch(
    field: string,
    payloadValue: unknown,
    entityValue: unknown,
  ): void {
    if (this.valuesMatch(payloadValue, entityValue)) {
      return;
    }

    throw new BadRequestException(`${field} mismatch`);
  }

  private valuesMatch(payloadValue: unknown, entityValue: unknown): boolean {
    if (payloadValue === entityValue) {
      return true;
    }

    if (payloadValue == null && entityValue == null) {
      return true;
    }

    if (payloadValue == null || entityValue == null) {
      return false;
    }

    return String(payloadValue) === String(entityValue);
  }
}
