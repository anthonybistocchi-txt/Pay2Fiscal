# Modelo de dados fiscal (emitentes, produto e transação)

Este documento explica as tabelas `emitters`, `product_fiscal` e `transaction_fiscal_data` para quem **não tem formação em tributação brasileira**, mas precisa **ler ou alterar código** que persiste ou usa esses campos.

## Contexto em uma frase

A **Nota Fiscal eletrônica (NF-e)** exige códigos padronizados (NCM, CFOP, tributos, regime da empresa). Essas tabelas guardam **dados cadastrais do emitente**, **defaults fiscais por produto** e **cópia dos dados usados numa venda** (transação), além do **resultado** quando o sistema fiscal processa a operação.

---

## Glossário rápido

| Termo | O que é |
|--------|--------|
| **Emitente** | Empresa que **emite** a nota (vendedor). Na NF-e, identificação e endereço do emitente vêm desse cadastro. |
| **Regime tributário** | Conjunto de regras que define **como** a empresa paga impostos (Simples Nacional vs. demais regimes para PJ). |
| **CRT (Código de Regime Tributário)** | **Código numérico oficial** usado na NF-e para informar o regime. Valores usados no Brasil: **1** = Simples Nacional; **2** = Simples Nacional com **excesso de sublimite** de receita; **3** = Regime Normal (Lucro Presumido / Lucro Real, entre outros — fora do Simples). |
| **SIMPLES vs NORMAL** (campo `tax_regime`) | **Macroclassificação** no nosso modelo: **SIMPLES** = optante pelo Simples; **NORMAL** = não está no Simples (tratamento “normal” de PJ). Deve ser **coerente** com o CRT quando ambos estiverem preenchidos. |
| **NCM** | Código de **8 dígitos** que classifica a mercadoria internacionalmente. Obrigatório na NF-e para a maioria dos produtos. Pode ter zeros à esquerda — por isso guardamos como **string**. |
| **CEST** | Código de **7 dígitos** ligado a substituição tributária em algumas cadeias (ex.: bebidas). Pode ter zeros à esquerda — **string**. |
| **CFOP** | Código de **4 dígitos** que descreve a **natureza da operação** (venda, devolução, remessa, etc.). Afeta regra fiscal e texto legal da nota. |
| **Origem da mercadoria** | Código **0 a 8** (tabela da NF-e): nacional, estrangeira importação direta, etc. Define regras de ICMS junto com CST/CSOSN. |
| **CST** | **Código de Situação Tributária** — situação do ICMS, PIS ou COFINS para aquele item (tributado, isento, etc.). |
| **CSOSN** | Usado no **Simples Nacional**: código de situação **substitutivo** do CST de ICMS para optantes do Simples. Na prática, um único campo no banco (`icms_cst_csosn`) pode guardar **CST** (regime normal) ou **CSOSN** (Simples), conforme o produto/empresa. |
| **PIS / COFINS CST** | CST específico para PIS e para COFINS (dois impostos federais; cada um com seu CST). |

Regra prática: **não invente códigos**. Valores válidos vêm de tabelas oficiais (manual da NF-e, Convênio ICMS, etc.). Erros geram **rejeição na SEFAZ** ou cálculo incorreto.

---

## Tabela `emitters`

Cadastro da **empresa emissora** (quem vende e emite NF-e).

| Campo | Tipo / restrição | Função |
|--------|------------------|--------|
| `legal_name` | string, obrigatório | Razão social. |
| `trade_name` | string, opcional | Nome fantasia. |
| `cnpj` | 14 caracteres, único | CNPJ sem máscara (só dígitos). |
| `ie` | string, opcional | Inscrição estadual (quando aplicável). |
| `im` | string, opcional | Inscrição municipal (quando aplicável, ex.: alguns serviços ou ISS). |
| `tax_regime` | enum `SIMPLES` \| `NORMAL`, opcional | Visão simplificada do regime no sistema. |
| `crt` | enum `'1'` \| `'2'` \| `'3'`, opcional | CRT oficial na NF-e: **1** Simples Nacional; **2** Simples com excesso de sublimite; **3** Regime Normal. |
| Endereço (`street`, `number`, `complement`, `neighborhood`, `city`, `state`, `zip_code`, `country`) | `country` default `BR` | Endereço do emitente exigido na NF-e. |
| `email`, `phone` | opcionais | Contato. |

**Coerência:** `tax_regime = SIMPLES` costuma ir junto de CRT **1** ou **2**. `tax_regime = NORMAL` costuma ir junto de CRT **3**.

---

## Tabela `product_fiscal`

**Um registro por produto** (`product_id` único): são os **defaults fiscais** do item no catálogo (origem, NCM, CEST, CFOP padrão, CST/CSOSN de ICMS, CST de PIS e COFINS).

| Campo | Função |
|--------|--------|
| `origin_id` | Origem da mercadoria (0–8). |
| `ncm`, `cest` | Classificação fiscal do produto (strings com zeros à esquerda se necessário). |
| `cfop` | CFOP **padrão**; em algumas operações ou UFs pode ser sobrescrito na transação. |
| `icms_cst_csosn` | CST de ICMS **ou** CSOSN, conforme o caso. |
| `pis_cst`, `cofins_cst` | CST de PIS e de COFINS. |

Alterações aqui afetam **novas** operações que copiarem esses dados para a transação (conforme a regra de negócio do app).

---

## Tabela `transaction_fiscal_data`

**Um registro por transação** (`transaction_id` único): **snapshot fiscal** da operação no momento da venda — espelha em grande parte o que veio do produto/regra da época, para **auditoria** e emissão sem depender do cadastro do produto mudar depois.

| Campo | Função |
|--------|--------|
| `origin_id`, `ncm`, `cfop`, `cest` | Valores usados **nesta** transação. |
| `icms_cst_csosn`, `pis_cst`, `cofins_cst` | Idem. |
| `fiscal_response_code` | Código numérico de retorno do processamento fiscal (convênio interno do sistema; alinhar com o microserviço/cliente que emite/consulta NF-e). |
| `fiscal_request_id` | Identificador único do pedido/processamento (rastreio, idempotência). |
| `failure_reason` | Texto explicando falha (validação, SEFAZ, timeout, etc.), quando houver. |

Comentário de domínio: o comentário na migration menciona “1 item por transação por enquanto” — o modelo atual assume **um conjunto de códigos fiscais por transação**; se no futuro houver **vários itens** por nota, o desenho pode precisar de tabela de itens filhos.

---

## Regras importantes para desenvolvimento

1. **Unicidade:** `transaction_id`, `product_id` e `cnpj` (emitente) são únicos onde indicado — não duplicar vínculos.
2. **Integridade:** exclusão do produto ou da transação em cascata remove o fiscal associado (conforme `onDelete` nas FKs).
3. **Strings numéricas:** NCM, CEST, CFOP, CSTs — manter **padding** e formato esperado pelo integrador/NF-e (muitas vezes só dígitos, tamanho fixo).
4. **CRT e `tax_regime`:** manter alinhamento (ex.: NORMAL + CRT 3) reduz inconsistência na emissão.

---

## Onde aprofundar (oficial)

- Manual de Orientação do Contribuinte (MOC) da **NF-e** (Receita/SEFAZ).
- Tabelas de **CST/CSOSN**, **origem**, **CFOP** nos anexos do leiaute em uso.

Este README descreve o **modelo deste repositório**, não substitui assessoria tributária.

**ATUALIZADO EM 29/04/2026**
