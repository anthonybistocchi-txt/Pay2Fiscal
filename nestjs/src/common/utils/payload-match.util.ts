import { BadRequestException } from '@nestjs/common';

export function assertPayloadMatch(
  field: string,
  payloadValue: unknown,
  entityValue: unknown,
): void {
  if (valuesMatch(payloadValue, entityValue)) {
    return;
  }

  throw new BadRequestException(`${field} mismatch`);
}

export function valuesMatch(
  payloadValue: unknown,
  entityValue: unknown,
): boolean {
  if (payloadValue === entityValue) {
    return true;
  }

  if (payloadValue == null && entityValue == null) {
    return true;
  }

  if (payloadValue == null || entityValue == null) {
    return false;
  }

  if (entityValue instanceof Date) {
    return new Date(String(payloadValue)).getTime() === entityValue.getTime();
  }

  return String(payloadValue) === String(entityValue);
}
