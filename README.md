## English Version

### What is Pay2Fiscal?
Pay2Fiscal is a distributed backend project that demonstrates the full lifecycle of a purchase:
from **creating an order**, to **processing a payment**, and finally **issuing a Brazilian fiscal invoice (NF-e)**.
The system is intentionally designed around **asynchronous processing** so that API requests stay fast and resilient
even when external services (payment gateways and government/fiscal APIs) are slow or unstable.

### Core Goals
- **Fast requests**: the API does not block waiting for external providers.
- **Data integrity**: the database is the source of truth for the transaction state.
- **Resilience**: retries, idempotency, and safe state transitions across the workflow.
- **Clear ownership**: each service has a well-defined responsibility.

### Services and Responsibilities
- **Payments + Orchestration (Laravel)**
  - Receives purchase requests (entry point).
  - Persists the transaction and its fiscal-related snapshot (product fiscal data, emitter data, etc.).
  - Calls the payment gateway asynchronously (via queue jobs).
  - Receives and validates payment gateway webhooks.
  - Updates the transaction state in the database (source of truth).
  - Triggers the fiscal issuance workflow only after payment is confirmed.

- **Fiscal Issuance (NestJS)**
  - Receives the transaction payload from Laravel (after payment approval).
  - Performs the asynchronous communication with fiscal/government providers for NF-e issuance.
  - Implements retry/backoff strategies and failure handling suited for unstable external APIs.
  - Reports results back (success/failure) so Laravel can reflect the fiscal outcome.

### End-to-End Flow (High Level)
1. **Client creates a purchase** (Laravel API).
2. Laravel stores a new **Transaction** with initial status (e.g. `PENDING`) and an **idempotency key**.
3. After DB commit, Laravel enqueues a **payment job** that talks to the gateway (no request blocking).
4. The payment gateway sends **webhooks** (which may be duplicated/out-of-order).
5. Laravel validates webhook signature, applies **idempotency**, and safely updates the transaction status
   (e.g. `PAID/APPROVED` or `FAILED`).
6. Only when payment is **approved**, Laravel dispatches a job to **send the transaction to NestJS**.
7. NestJS issues the **NF-e** asynchronously and returns the outcome.
8. Laravel stores the final fiscal state (e.g. `FISCAL_ISSUED` or `FISCAL_ERROR`) for clients to query.

### Reliability Patterns Used
- **Idempotency keys**: prevents duplicated charges and duplicated side effects.
- **After-commit dispatch**: jobs are triggered only if DB state was successfully persisted.
- **Queues + retries**: external I/O is done in workers with backoff and failure handling.
- **Monotonic state transitions**: payment/fiscal states should move forward safely, never “rewind”.

---

## Versão em Português

### O que é o Pay2Fiscal?
O Pay2Fiscal é um projeto de backend distribuído que demonstra o ciclo completo de uma compra:
desde **criar um pedido**, **processar o pagamento**, até **emitir a Nota Fiscal eletrônica (NF-e)**.
O sistema é propositalmente orientado a **processamento assíncrono**, para manter as requests rápidas e resilientes
mesmo quando serviços externos (gateway de pagamento e APIs fiscais/governamentais) estiverem lentos ou instáveis.

### Objetivos Principais
- **Requests rápidas**: a API não fica “travada” aguardando provedores externos.
- **Integridade dos dados**: o banco é a fonte de verdade do estado da transação.
- **Resiliência**: retentativas, idempotência e transições seguras de estado.
- **Responsabilidades claras**: cada serviço faz uma parte bem definida do fluxo.

### Serviços e Responsabilidades
- **Pagamentos + Orquestração (Laravel)**
  - Recebe as requisições de compra (ponto de entrada).
  - Persiste a transação e o “snapshot” fiscal necessário (dados fiscais do produto, emissor etc.).
  - Chama o gateway de pagamento de forma assíncrona (via jobs/filas).
  - Recebe e valida webhooks do gateway.
  - Atualiza o estado da transação no banco (fonte de verdade).
  - Dispara a emissão fiscal somente após a confirmação do pagamento.

- **Emissão Fiscal (NestJS)**
  - Recebe do Laravel os dados da transação (após aprovação do pagamento).
  - Faz a comunicação assíncrona com provedores fiscais/APIs governamentais para emitir NF-e.
  - Implementa estratégias de retentativa/backoff e tratamento de falhas típicas de APIs instáveis.
  - Reporta o resultado (sucesso/erro) para o Laravel refletir o status fiscal.

### Fluxo de Ponta a Ponta (Alto Nível)
1. **Cliente cria uma compra** (API do Laravel).
2. O Laravel grava uma **Transaction** com status inicial (ex.: `PENDING`) e uma **chave de idempotência**.
3. Após o commit no banco, o Laravel enfileira um **job de pagamento** que conversa com o gateway (sem bloquear a request).
4. O gateway envia **webhooks** (podem vir duplicados e fora de ordem).
5. O Laravel valida a assinatura, aplica **idempotência**, e atualiza o status de forma segura
   (ex.: `PAID/APPROVED` ou `FAILED`).
6. Somente quando o pagamento for **aprovado**, o Laravel dispara um job para **enviar a transação ao NestJS**.
7. O NestJS emite a **NF-e** de forma assíncrona e devolve o resultado.
8. O Laravel persiste o estado final fiscal (ex.: `FISCAL_ISSUED` ou `FISCAL_ERROR`) para consulta pelos clientes.

### Padrões de Confiabilidade Utilizados
- **Chaves de idempotência**: evitam cobranças duplicadas e efeitos colaterais duplicados.
- **Disparo após commit**: jobs só rodam quando o estado foi persistido com sucesso.
- **Filas + retentativas**: I/O externo acontece em workers com backoff e tratamento de falhas.
- **Transições monotônicas de estado**: estados de pagamento/fiscal evoluem com segurança (sem “voltar”).