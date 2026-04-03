<?php
/**
 * setup_banco.php
 * Execute UMA VEZ para criar/recriar o banco de dados.
 * Acesse via: http://localhost/roomsync/setup_banco.php
 */
try {
    $dbPath = __DIR__ . '/banco.sqlite';

    // Remove banco antigo se existir (para recriar limpo)
    if (file_exists($dbPath)) {
        unlink($dbPath);
        echo "<p>✓ Banco antigo removido.</p>";
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tabela de reservas com todas as colunas
    $pdo->exec("CREATE TABLE IF NOT EXISTS reservas (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        nome        TEXT NOT NULL,
        cpf         TEXT NOT NULL DEFAULT '',
        email       TEXT NOT NULL DEFAULT '',
        telefone    TEXT NOT NULL DEFAULT '',
        sala        TEXT NOT NULL,
        data        TEXT NOT NULL,
        horaInicio  TEXT NOT NULL,
        horaFim     TEXT NOT NULL,
        status      TEXT NOT NULL DEFAULT 'pendente'
    )");

    echo "<p>✓ Tabela <strong>reservas</strong> criada com sucesso.</p>";

    // Tabela de usuários
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id     INTEGER PRIMARY KEY AUTOINCREMENT,
        login  TEXT NOT NULL UNIQUE,
        senha  TEXT NOT NULL,
        papel  TEXT NOT NULL
    )");

    echo "<p>✓ Tabela <strong>usuarios</strong> criada com sucesso.</p>";

    // Insere admin padrão
    $pdo->exec("INSERT INTO usuarios (login, senha, papel) VALUES ('admin', '1234', 'admin')");

    echo "<p>✓ Usuário admin criado (login: <strong>admin</strong> / senha: <strong>1234</strong>).</p>";
    echo "<hr><p style='color:green; font-weight:bold;'>Banco configurado com sucesso! <a href='login.php'>Ir para o sistema →</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>