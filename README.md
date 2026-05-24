Markdown
# RoomSync 📅

Um sistema web simples e eficiente para gerenciamento e reserva de salas, desenvolvido como projeto acadêmico.

## 🛠️ Tecnologias Utilizadas
* **Front-end:** HTML5, CSS3 (Bootstrap) e JavaScript (Vanilla).
* **Back-end:** PHP.
* **Banco de Dados:** MySQL.

## 🚀 Principais Funcionalidades
* Cadastro único de clientes e sistema de Login.
* Visualização dinâmica da grade de horários (livres e ocupados).
* Solicitação de reservas pelos clientes.
* Painel do Administrador para gestão de status (Aprovar, Concluir ou Cancelar reservas).

## ⚙️ Como rodar o projeto localmente

Siga os passos abaixo para testar o projeto na sua máquina:

**1. Banco de Dados**
* Abra o seu MySQL (pelo MySQL Workbench, XAMPP, etc.).
* Execute o script contido no arquivo `database.sql` para criar as tabelas necessárias.

**2. Configuração de Conexão**
* Abra o arquivo `conexao.php` no seu editor de código.
* Insira a sua senha do MySQL na variável `$password`.

**3. Iniciando o Servidor**
* Abra o terminal (CMD) na pasta raiz do projeto.
* Execute o servidor embutido do PHP com o comando:
  php -S localhost:8000
Abra o seu navegador e acesse: http://localhost:8000
