<?php
// -- Início: conexão com o banco de dados SQLite --
try {
    // __DIR__ garante que o caminho do banco seja sempre relativo a este arquivo,
    // independente de onde o PHP for chamado
    $pdo = new PDO('sqlite:' . __DIR__ . '/banco.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Lança exceção em caso de erro SQL
    // -- Fim: conexão com o banco --

    // -- Início: criação da tabela de reservas (se ainda não existir) --
    // Inclui todas as colunas necessárias: dados pessoais do cliente + dados da reserva
    $pdo->exec("CREATE TABLE IF NOT EXISTS reservas (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        nome        TEXT NOT NULL,
        cpf         TEXT NOT NULL DEFAULT '',      -- CPF do cliente (validado no login)
        email       TEXT NOT NULL DEFAULT '',      -- E-mail do cliente
        telefone    TEXT NOT NULL DEFAULT '',      -- Telefone do cliente
        sala        TEXT NOT NULL,                 -- Ex: 'Sala 101', 'Auditório'
        data        TEXT NOT NULL,                 -- Formato: YYYY-MM-DD
        horaInicio  TEXT NOT NULL,                 -- Formato: HH:MM
        horaFim     TEXT NOT NULL,                 -- Formato: HH:MM
        status      TEXT NOT NULL DEFAULT 'pendente' -- 'pendente', 'ativa' ou 'cancelada'
    )");
    // -- Fim: criação da tabela de reservas --

    // -- Início: criação da tabela de usuários (se ainda não existir) --
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id     INTEGER PRIMARY KEY AUTOINCREMENT,
        login  TEXT NOT NULL UNIQUE,
        senha  TEXT NOT NULL,
        papel  TEXT NOT NULL  -- 'admin' ou 'cliente'
    )");
    // -- Fim: criação da tabela de usuários --

    // -- Início: criação do usuário admin padrão (somente se a tabela estiver vazia) --
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO usuarios (login, senha, papel) VALUES ('admin', '1234', 'admin')");
    }
    // -- Fim: criação do usuário admin --

} catch (PDOException $e) {
    // Em caso de falha na conexão, retorna JSON de erro (já que este arquivo é usado pela API)
    die(json_encode(['erro' => 'Erro de conexão: ' . $e->getMessage()]));
}
?>
