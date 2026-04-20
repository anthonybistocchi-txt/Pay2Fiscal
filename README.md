## English Version

### Project Intent
The Pay2Fiscal is a distributed backend ecosystem designed to demonstrate how to handle the critical lifecycle of a financial transaction. Instead of a single monolith, this project uses a polyglot architecture where each service is chosen for its specific strengths, ensuring high availability, data integrity, and resilience.

### The Flow
- **Orchestration (Laravel)**: Acts as the entry point. It manages customer subscriptions and triggers the billing cycle by dispatching events.

- **Payment Processing (Go)**: Consumes billing events to execute transactions. It focuses on atomic precision, using database locks and idempotency keys to ensure financial safety.

- **Fiscal Issuance (Fastify)**: Once payment is confirmed, this service handles the asynchronous communication with government APIs to issue the Fiscal Invoice (NF-e), implementing retry strategies if external services fail.

### Why this Stack?
- **Laravel (PHP)**: Excellent for rapid development of complex business logic and robust API management.

- **Go (Golang)**: Provides ultra-fast execution and safe concurrency for the "Money Move" part, where performance and types matter most.

- **Fastify (Node.js)**: A lightweight, high-performance framework ideal for I/O-intensive tasks, such as waiting for slow external fiscal APIs.

## Versão em Português

### Intuito do Projeto
O Pay2Fiscal é um ecossistema de backend distribuído projetado para demonstrar como lidar com o ciclo de vida crítico de uma transação financeira. Em vez de um único monólito, este projeto utiliza uma arquitetura onde cada serviço é escolhido por suas forças específicas, garantindo alta disponibilidade, integridade de dados e resiliência.

### O Fluxo
- **Orquestração (Laravel)**: Atua como o ponto de entrada. Gerencia as assinaturas dos clientes e inicia o ciclo de cobrança através do disparo de eventos.

- **Processamento de Pagamento (Go)**: Consome os eventos de cobrança para executar as transações. Foca na precisão atômica, utilizando locks de banco de dados e chaves de idempotência para garantir a segurança financeira.

- **Emissão Fiscal (Fastify)**: Assim que o pagamento é confirmado, este serviço cuida da comunicação assíncrona com APIs governamentais para emitir a Nota Fiscal (NF-e), implementando estratégias de retentativa caso os serviços externos falhem.

### Por que esta Stack?
- **Laravel (PHP)**: Excelente para o desenvolvimento rápido de lógicas de negócio complexas e gestão robusta de APIs.

- **Go (Golang)**: Oferece execução ultra rápida e concorrência segura para a parte de movimentação de dinheiro, onde performance e tipagem são cruciais.

- **Fastify (Node.js)**: Um framework leve e de alta performance, ideal para tarefas intensivas de I/O, como aguardar o retorno de APIs fiscais externas lentas.