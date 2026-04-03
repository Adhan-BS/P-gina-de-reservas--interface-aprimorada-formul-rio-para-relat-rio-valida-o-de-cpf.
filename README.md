# 📅 RoomSync – Sistema Inteligente de Reservas de Salas
## 📌 Sobre o Projeto
O **RoomSync** é um sistema de reservas de salas projetado para gerenciar espaços compartilhados de forma segura e eficiente. Desenvolvido para substituir processos manuais e descentralizados, o sistema garante que não haja conflitos de horários e centraliza o controle de agendamentos.

Projeto desenvolvido como requisito para a disciplina de **Código de Alta Performance**, no 3º Período (Matutino) do centro universitário UNINORTE (Ser Educacional).

## 🔄 Novo Fluxo de Aprovação (Solicitação vs. Reserva)
Para garantir maior controle administrativo, o sistema opera sob um fluxo rigoroso de aprovação:
1. **Clientes** acessam a grade e enviam uma *Solicitação de Reserva* (os blocos ficam 🟨 **Amarelos / Pendentes**).
2. **Administradores** revisam as solicitações no painel lateral, podendo **Aprovar** (transformando em 🟥 **Ocupado**) ou **Cancelar**.
3. Administradores também possuem o poder de criar reservas diretas no sistema, furando a fila de pendências caso necessário.

## ✨ Principais Funcionalidades

* **Portal de Acesso Duplo (Dark Mode):** * *Clientes:* Entram apenas informando dados de contato (Nome, CPF, E-mail e Telefone) para documentação.
  * *Administradores:* Acesso restrito por login e senha.
* **Validação de Dados em Tempo Real:** JavaScript embutido valida a autenticidade do CPF (cálculo matemático) e o formato do E-mail antes do envio ao servidor, garantindo dados limpos.
* **Privacidade Total:** A API (Back-end) bloqueia a visualização de dados sensíveis. Clientes só enxergam "Informação Protegida" nos horários de terceiros. Apenas o Admin tem acesso ao CPF e contato de quem reservou.
* **Grade Dinâmica Visual:** Interface em blocos coloridos (Verde/Livre, Amarelo/Pendente, Vermelho/Ocupado) atualizada via *Fetch API* (sem reload da página).

## 🛠️ Arquitetura e Tecnologias
A arquitetura foi planejada para ter alta portabilidade, rodando leve em servidores locais como o XAMPP.

* **Front-end:** HTML5, CSS3, Bootstrap 5 (Modais e Grid System) e Vanilla JavaScript.
* **Back-end:** PHP (Validações de segurança, controle de sessão e API RESTful).
* **Banco de Dados:** SQLite (banco relacional contido em arquivo local, dispensando setups complexos).

## 🚀 Como Executar Localmente

1. Certifique-se de ter um servidor local instalado (ex: **XAMPP**).
2. Clone este repositório para a pasta pública do seu servidor (ex: `htdocs/roomsync`).
4. Inicie o serviço **Apache**.
5. **IMPORTANTE:** Acesse o arquivo de setup para gerar a estrutura do banco de dados (tabelas e colunas de documentação):
  == http://localhost/roomsync/setup_banco.php ==
6. Acesse o http://localhost/roomsync/
Perfis de Teste:

Admin: Login: admin / Senha: 1234

Cliente: Preencha a aba de cliente com um CPF matematicamente válido.

Nota de Segurança: O arquivo de banco de dados (banco.sqlite) está inserido no .gitignore e não sobe para o repositório público. Rodar o passo 4 é obrigatório.

👥 Equipe de Desenvolvimento
Adhan Borges de Souza (Matrícula: 3354685)

Elano Serrão (Matrícula: 03352468)

Gabriel Farias (Matrícula: 03357097)

Izabel Cristina Martins dos Santos (Matrícula: 3211302)

Luigi Gabriel Lopes dos Santos (Matrícula: 03358502)

Nicolas Alegre Ferreira Melo (Matrícula: 3356782)

Pietro (Matrícula: 03359792)
