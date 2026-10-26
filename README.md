# Disco Burguer SG

Site institucional e sistema de gestão para a hamburgueria **Disco Burguer SG**. O projeto reúne uma vitrine pública (cardápio, sobre, localização e feedback de clientes) e um painel interno para a operação do restaurante: pedidos de mesa, cozinha, estoque, financeiro e equipe.

Desenvolvido como projeto do curso Técnico em Desenvolvimento de Sistemas (ETEC).

---

## Funcionalidades

### Site público
- Cardápio organizado por categorias, com navegação automática por seção
- Páginas institucionais: **Sobre**, **Quem somos**, **Localização** e **Feedback**
- Formulário de feedback dos clientes, salvo no banco de dados

### Painel administrativo
- Login com redefinição de senha por e-mail (via Brevo)
- Gestão de **produtos**, incluindo combos
- Controle de **mesas** e **pedidos**, com registro de ocorrências
- **Pagamentos** de pedidos, integrados ao fluxo de caixa
- **Estoque**: lotes, movimentações, receitas e alertas de estoque baixo
- Cadastro de **fornecedores** e **funcionários**
- **Financeiro**: fluxo de caixa, contas a pagar, caixas diários e categorias
- Criação de usuários administradores

### Operação do salão
- **Tela do garçom** para lançar pedidos por mesa
- **Tela da cozinha** para acompanhar os pedidos em preparo

---

## Tecnologias

| Camada | Tecnologia |
|---|---|
| Back-end | PHP 8+ com PDO |
| Banco de dados | MySQL 8 / MariaDB 10.4+ |
| Front-end | HTML5, CSS3, JavaScript, Bootstrap 5 |
| Geração de PDF | [dompdf](https://github.com/dompdf/dompdf) 3.x |
| E-mail | Brevo (recuperação de senha) |
| Fontes | Google Fonts |

---

## Estrutura do projeto

```
disco_burguer_sg/
├── index.php                # Página inicial
├── config.php               # BASE_URL e BASE_PATH do projeto
├── discoburguer.sql         # Criação e carga inicial do banco
├── composer.json            # Dependências PHP
│
├── php/
│   ├── conexao.php          # Conexão PDO (local ou produção)
│   └── login.php
│
├── php_html/                # Páginas, organizadas por área
│   ├── divulgacao/          # Site público: cardápio, sobre, feedback, local
│   ├── sistema_garcom/      # Telas de pedido (garçom) e cozinha
│   └── sistema_web/         # Painel administrativo
│
├── controllers/             # Reservado para regras de negócio (ainda vazio)
├── includes/                # Cabeçalho, rodapé e modal do cardápio
├── css/                     # Estilos por área
├── js/                      # Scripts do cardápio, carrossel, modal e comentários
├── assets/                  # Estilos do painel administrativo
├── img/                     # Imagens do site e dos produtos
└── vendor/                  # Dependências do Composer (não versionar)
```

> **Nota:** a lógica de negócio e as consultas SQL estão, por enquanto, dentro das próprias páginas em `php_html/`. A pasta `controllers/` foi criada para separar essa lógica no futuro.

---

## Banco de dados

O arquivo `discoburguer.sql` cria o banco `discoburguer` e suas tabelas:

| Módulo | Tabelas |
|---|---|
| Cardápio | `produtos`, `itens_combo` |
| Estoque | `estoque`, `lotes_estoque`, `movimentacoes_estoque`, `receitas`, `fornecedores` |
| Mesas e pedidos | `mesas`, `pedidos`, `itens_pedidos`, `customizacoes_item_pedido`, `pagamentos_pedido`, `ocorrencias_pedido` |
| Equipe | `funcionarios` |
| Financeiro | `fluxo_caixa`, `contas_a_pagar`, `caixas_diarios`, `categorias_financeiras` |
| Feedback | `feedbacks` |

---

## Como rodar localmente

### Pré-requisitos
- PHP 8.0 ou superior, com as extensões `pdo_mysql` e `mbstring`
- MySQL 8 ou MariaDB 10.4 ou superior
- [Composer](https://getcomposer.org/)
- Servidor local, como XAMPP, WAMP, Laragon ou o servidor embutido do PHP

### Passo a passo

1. **Clone ou copie o projeto para a pasta do servidor.** No XAMPP, use `htdocs/Disco_Burger_SG/`. O nome da pasta importa, pois `config.php` define `BASE_URL` como `/Disco_Burger_SG/`.

   ```bash
   git clone https://github.com/Deivid-Barreto/disco_burguer_sg.git Disco_Burger_SG
   ```

2. **Instale as dependências do PHP:**

   ```bash
   composer install
   ```

3. **Crie o banco de dados.** No phpMyAdmin (ou outro cliente MySQL), importe o arquivo `discoburguer.sql`.

4. **Confira a conexão.** Em `php/conexao.php`, o ambiente local usa `root` com senha vazia. Ajuste se o seu MySQL for diferente.

5. **Acesse no navegador:**
   - Site: `http://localhost/Disco_Burger_SG/`
   - Painel: `http://localhost/Disco_Burger_SG/php_html/sistema_web/login.php`

> A conexão escolhe o banco automaticamente pelo domínio: `localhost`, `127.0.0.1` e domínios `.test` usam o banco local. Qualquer outro domínio usa o banco de produção.

---

## Segurança

**Atenção:** as credenciais do banco de produção estão escritas diretamente em `php/conexao.php`, e esse arquivo já está no histórico do Git. Antes de tornar o repositório público ou fazer deploy:

- [ ] Trocar a senha do banco de produção
- [ ] Mover as credenciais para um arquivo `.env` ou variáveis de ambiente
- [ ] Adicionar `vendor/` e arquivos sensíveis ao `.gitignore`
- [ ] Revisar consultas SQL e entradas de formulário quanto a injeção de SQL e XSS

---

## Status do desenvolvimento

- Banco de dados estruturado
- Cardápio e páginas institucionais
- Recuperação de senha com Brevo
- Testes completos do fluxo de pedidos
- Módulo de pagamentos de pedidos (`pagamentos_pedido.php`)
- Módulo de ocorrências de pedidos
- Revisão de produtos e mesas

---

## Autoria

Repositório: [github.com/Deivid-Barreto/disco_burguer_sg](https://github.com/Deivid-Barreto/disco_burguer_sg)

Colaboradores:

- [Emilly da Silva](https://github.com/emilly-evan)
- [Giuliascruz](https://github.com/Giuliascruz)
- [raphaelroque332](https://github.com/raphaelroque332)
- [victoroliver05](https://github.com/victoroliver05)