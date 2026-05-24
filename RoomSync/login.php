<?php
session_start();
require 'conexao.php';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND senha = ?");
    $stmt->execute([$email, $senha]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $_SESSION['logado'] = true;
        $_SESSION['usuario_id'] = $usuario['id']; // Salvamos o ID para usar nas reservas!
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_papel'] = $usuario['papel'];
        header("Location: index.php");
        exit;
    } else {
        $erro = "<div class='alert alert-danger'>E-mail ou senha incorretos.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>RoomSync - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark d-flex justify-content-center align-items-center vh-100">
    <div class="card p-4 shadow" style="width: 350px;">
        <h2 class="text-center mb-4">Login RoomSync</h2>
        <?= $erro ?>
        <form method="POST">
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Senha</label>
                <input type="password" name="senha" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Entrar</button>
        </form>
        <div class="text-center mt-3">
            <a href="cadastro.php" class="text-decoration-none">Criar nova conta</a>
        </div>
    </div>
</body>
</html>