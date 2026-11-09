# Plano de Implementação: Estrutura Inicial de Páginas da Loja Bike 21

## 1. Visão Geral e Objetivos

Este plano estabelece a infraestrutura inicial de páginas institucionais, transacionais e técnicas para o e-commerce de ciclismo **Bike 21** (bicicletas, vestuário, componentes e acessórios), baseado em WordPress, WooCommerce, Hello Elementor e Elementor.

O objetivo é provisionar as rotas, títulos, slugs canônicos, atribuição de páginas do sistema e configuração de links permanentes, preparando o ambiente para a futura diagramação visual no Elementor.

---

## 2. Escopo Arquitetural das Páginas

### 2.1. Páginas do Núcleo Transacional (WooCommerce / Core)
| Título | Slug | Tipo / Função | Template |
| :--- | :--- | :--- | :--- |
| **Início** | `/` (Home) | Vitrine principal, banners e categorias | Elementor Largura Total / Padrão |
| **Loja** | `/loja` | Catálogo geral com filtros | Padrão WooCommerce |
| **Carrinho** | `/carrinho` | Revisão de itens e cálculo prévio de frete | Padrão WooCommerce |
| **Finalizar Compra** | `/checkout` | Checkout unificado Mercado Pago | Padrão WooCommerce |
| **Minha Conta** | `/minha-conta` | Painel do cliente e rastreio de pedidos | Padrão WooCommerce |

### 2.2. Páginas de Apoio Técnico e Decisão de Compra (Nicho de Ciclismo)
| Título | Slug | Objetivo / Conteúdo Previsto |
| :--- | :--- | :--- |
| **Guia de Tamanhos & Bike Fit** | `/guia-de-tamanhos` | Tabelas de quadros (MTB e Speed por altura e cavalo) e medidas de vestuário esportivo |
| **Entrega e Montagem da Bike** | `/entrega-e-montagem` | Explicação sobre envio via Melhor Envio na caixa pré-montada e regras de revisão de garantia |

### 2.3. Páginas Institucionais e de Confiança
| Título | Slug | Objetivo / Conteúdo Previsto |
| :--- | :--- | :--- |
| **Sobre a Bike 21** | `/sobre-nos` | Apresentação da marca, autoridade técnica e equipe |
| **Fale Conosco** | `/contato` | Canais de suporte, WhatsApp e horários de atendimento |
| **Rastreamento de Pedidos** | `/rastreio` | Área rápida de consulta de postagens via Melhor Envio |
| **Garantia, Trocas e Devoluções** | `/garantia-e-trocas` | Prazos legais, garantias de quadros e componentes |

### 2.4. Páginas Legais e Compliance (LGPD)
| Título | Slug | Objetivo / Conteúdo Previsto |
| :--- | :--- | :--- |
| **Política de Privacidade** | `/politica-de-privacidade` | Conformidade LGPD sobre tratamento e dados |
| **Termos e Condições de Uso** | `/termos-de-uso` | Regras contratuais de compra e navegação no site |

---

## 3. Configurações Técnicas de Sistema

1. **Estrutura de Permalinks**:
   - Definir para `/%postname%/` para garantir URLs limpas e otimizadas para SEO.
   - Geração e validação do arquivo `.htaccess` no Apache.
2. **Leitura do WordPress**:
   - Definir página inicial estática apontando para a página **Início**.
3. **Mapeamento do WooCommerce**:
   - Conferir se `woocommerce_shop_page_id`, `woocommerce_cart_page_id`, `woocommerce_checkout_page_id`, `woocommerce_myaccount_page_id` e `woocommerce_terms_page_id` estão corretamente vinculados aos IDs das páginas criadas.

---

## 4. Critérios de Aceite

* Todas as páginas publicadas com status `publish`.
* Links permanentes configurados e respondendo com código HTTP 200 nas URLs amigáveis.
* Ausência de páginas duplicadas ou lixo no painel do WordPress.
* Estrutura pronta para edição visual com Elementor.
