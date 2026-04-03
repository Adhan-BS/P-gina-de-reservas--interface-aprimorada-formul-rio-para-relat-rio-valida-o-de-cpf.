<?php
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/banco.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
    // Tabela de reservas com TODAS as colunas necessárias (cpf, email, telefone incluídos)
    $pdo->exec("CREATE TABLE IF NOT EXISTS reservas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        cpf TEXT NOT NULL DEFAULT '',
        email TEXT NOT NULL DEFAULT '',
        telefone TEXT NOT NULL DEFAULT '',
        sala TEXT NOT NULL,
        data TEXT NOT NULL,
        horaInicio TEXT NOT NULL,
        horaFim TEXT NOT NULL,
        status TEXT DEFAULT 'pendente'
    )");
 
    // Tabela de usuários
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        login TEXT UNIQUE NOT NULL,
        senha TEXT NOT NULL,
        papel TEXT NOT NULL
    )");
 
    // Cria admin padrão se não existir
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO usuarios (login, senha, papel) VALUES ('admin', '1234', 'admin')");
    }
 
} catch (PDOException $e) {
    die(json_encode(['erro' => 'Erro de conexão: ' . $e->getMessage()]));
}
?>