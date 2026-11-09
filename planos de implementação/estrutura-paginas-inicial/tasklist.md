# Tasklist: Execução da Estrutura Inicial de Páginas

> **Protocolo de Execução**: Cada tarefa será executada isoladamente. Ao concluir uma tarefa, o agente solicitará a autorização do usuário para verificar o que foi feito. Após a verificação e aprovação do usuário, a tarefa será marcada como concluída `[x]` e avançaremos para a próxima.

---

### Tarefas de Implementação

- [x] **Tarefa 1: Configurar Links Permanentes e Regras de Rewrite**
  - Configurar estrutura de permalinks para `/%postname%/` via WP-CLI.
  - Gerar/atualizar o arquivo `.htaccess` e testar reescrita de rotas.

- [x] **Tarefa 2: Provisionar Páginas Nativas do WooCommerce e Página Inicial**
  - Verificar ou gerar páginas do WooCommerce (`Loja`, `Carrinho`, `Finalizar Compra`, `Minha Conta`).
  - Criar a página `Início` (Home) e defini-la como página inicial estática nas opções de leitura do WordPress.

- [x] **Tarefa 3: Criar Páginas Técnicas do Nicho de Ciclismo**
  - Criar a página `Guia de Tamanhos & Bike Fit` (`/guia-de-tamanhos`).
  - Criar a página `Entrega e Montagem da Bike` (`/entrega-e-montagem`).

- [x] **Tarefa 4: Criar Páginas Institucionais e de Atendimento**
  - Criar a página `Sobre a Bike 21` (`/sobre-nos`).
  - Criar a página `Fale Conosco` (`/contato`).
  - Criar a página `Rastreamento de Pedidos` (`/rastreio`).
  - Criar a página `Garantia, Trocas e Devoluções` (`/garantia-e-trocas`).

- [x] **Tarefa 5: Criar e Vincular Páginas Legais e Termos de Uso**
  - Criar a página `Política de Privacidade` (`/politica-de-privacidade`) e defini-la como página de privacidade do WP.
  - Criar a página `Termos e Condições de Uso` (`/termos-de-uso`) e associá-la como termos oficiais no WooCommerce (`woocommerce_terms_page_id`).

- [x] **Tarefa 6: Verificação de Integridade e Mapeamento de Rotas**
  - Testar via requisição HTTP todas as novas rotas geradas (código 200).
  - Listar o sumário com IDs, títulos, slugs e URLs finais do sistema.
