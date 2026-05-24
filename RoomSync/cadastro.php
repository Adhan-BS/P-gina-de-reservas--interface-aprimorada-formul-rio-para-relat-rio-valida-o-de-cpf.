<?php
require 'conexao.php';
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $senha = $_POST['senha'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, cpf, email, telefone, senha) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $cpf, $email, $telefone, $senha]);
        $mensagem = "<div class='alert alert-success'>Cadastro realizado com sucesso! <a href='login.php'>Faça login</a></div>";
    } catch (PDOException $e) {
        $mensagem = "<div class='alert alert-danger'>Erro ao cadastrar. Verifique se o CPF ou E-mail já existem.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>RoomSync - Cadastro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow" style="width: 400px;">
        <h3 class="text-center mb-3">Criar Conta</h3>
        <?= $mensagem ?>
        <form method="POST">
            <div class="mb-2"><input type="text" name="nome" class="form-control" placeholder="Nome completo" required></div>
            <div class="mb-2"><input type="text" name="cpf" class="form-control" placeholder="CPF" required></div>
            <div class="mb-2"><input type="email" name="email" class="form-control" placeholder="E-mail" required></div>
            <div class="mb-2"><input type="text" name="telefone" class="form-control" placeholder="Telefone" required></div>
            <div class="mb-3"><input type="password" name="senha" class="form-control" placeholder="Senha" required></div>
            <button type="submit" class="btn btn-primary w-100">Cadastrar</button>
        </form>
        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none">Já tem conta? Faça login</a>
        </div>
    </div>
</body>
</html>