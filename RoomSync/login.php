<?php
session_start();

// Validação do CPF pelo algoritmo oficial (espelho do JS)
function validarCpfPHP($cpf) {
    $cpf = preg_replace('/[^\d]/', '', $cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
    $add = 0;
    for ($i = 0; $i < 9; $i++) $add += intval($cpf[$i]) * (10 - $i);
    $rev = 11 - ($add % 11);
    if ($rev >= 10) $rev = 0;
    if ($rev !== intval($cpf[9])) return false;
    $add = 0;
    for ($i = 0; $i < 10; $i++) $add += intval($cpf[$i]) * (11 - $i);
    $rev = 11 - ($add % 11);
    if ($rev >= 10) $rev = 0;
    return $rev === intval($cpf[10]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipoLogin'] ?? '';

    if ($tipo === 'admin') {
        if ($_POST['login'] === 'admin' && $_POST['senha'] === '1234') {
            $_SESSION['logado']       = true;
            $_SESSION['usuario_papel'] = 'admin';
            $_SESSION['usuario_nome']  = 'Administrador';
            header("Location: index.php");
            exit;
        } else {
            $erro = "Usuário ou senha incorretos.";
        }
    } elseif ($tipo === 'cliente') {
        $nome     = trim($_POST['nome'] ?? '');
        $cpf      = trim($_POST['cpf'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');

        if (!$nome || !$cpf || !$email || !$telefone) {
            $erro = "Preencha todos os campos para continuar.";
        } elseif (!validarCpfPHP($cpf)) {
            $erro = "CPF inválido. Verifique os números digitados.";
        } else {
            $_SESSION['logado']           = true;
            $_SESSION['usuario_papel']    = 'cliente';
            $_SESSION['usuario_nome']     = htmlspecialchars($nome);
            $_SESSION['cliente_cpf']      = htmlspecialchars($cpf);
            $_SESSION['cliente_email']    = htmlspecialchars($email);
            $_SESSION['cliente_telefone'] = htmlspecialchars($telefone);
            header("Location: index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoomSync — Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: #0f1117;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-wrapper {
            width: 100%;
            max-width: 460px;
        }

        .logo-area {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            width: 52px;
            height: 52px;
            background: #2563eb;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
        }

        .logo-icon svg { width: 28px; height: 28px; fill: white; }

        .logo-area h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #f1f5f9;
            letter-spacing: -0.02em;
        }

        .logo-area p {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .card-login {
            background: #1a1d27;
            border: 1px solid #252836;
            border-radius: 16px;
            padding: 2rem;
        }

        .tab-switcher {
            display: flex;
            background: #0f1117;
            border-radius: 10px;
            padding: 4px;
            margin-bottom: 1.75rem;
            gap: 4px;
        }

        .tab-btn {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 7px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            background: transparent;
            color: #64748b;
        }

        .tab-btn.active {
            background: #1e293b;
            color: #f1f5f9;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .tab-content-area { display: none; }
        .tab-content-area.active { display: block; }

        .form-group { margin-bottom: 1rem; }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: #94a3b8;
            margin-bottom: 0.4rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .form-group input {
            width: 100%;
            padding: 0.65rem 0.9rem;
            background: #0f1117;
            border: 1px solid #252836;
            border-radius: 8px;
            color: #f1f5f9;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            transition: border-color 0.2s;
            outline: none;
        }

        .form-group input:focus {
            border-color: #2563eb;
        }

        .form-group input::placeholder { color: #475569; }

        .hint-text {
            font-size: 0.8rem;
            color: #475569;
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }

        .btn-submit {
            width: 100%;
            padding: 0.7rem;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 0.5rem;
        }

        .btn-submit:hover { background: #1d4ed8; }

        .btn-submit.admin-btn { background: #7c3aed; }
        .btn-submit.admin-btn:hover { background: #6d28d9; }

        .alert-erro {
            background: #1f1315;
            border: 1px solid #7f1d1d;
            color: #fca5a5;
            border-radius: 8px;
            padding: 0.65rem 0.9rem;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }

        .divider {
            height: 1px;
            background: #252836;
            margin: 1.25rem 0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .msg-erro {
            display: block;
            font-size: 0.72rem;
            color: #f87171;
            margin-top: 0.3rem;
            min-height: 1rem;
        }

        .campo-invalido {
            border-color: #ef4444 !important;
            background: #1f1315 !important;
        }

        .campo-valido {
            border-color: #22c55e !important;
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="logo-area">
        <div class="logo-icon">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7zm2 0v10h14V7H5zm2 2h10v2H7V9zm0 4h6v2H7v-2z"/>
            </svg>
        </div>
        <h1>RoomSync</h1>
        <p>Sistema de reserva de salas</p>
    </div>

    <div class="card-login">
        <?php if (isset($erro)): ?>
            <div class="alert-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <div class="tab-switcher">
            <button class="tab-btn active" onclick="trocarTab('cliente', this)">Sou Cliente</button>
            <button class="tab-btn" onclick="trocarTab('admin', this)">Administrador</button>
        </div>

        <!-- ABA CLIENTE -->
        <div class="tab-content-area active" id="tab-cliente">
            <p class="hint-text">Informe seus dados para acessar a grade de horários e enviar solicitações de reserva.</p>
            <form method="POST">
                <input type="hidden" name="tipoLogin" value="cliente">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome completo</label>
                        <input type="text" name="nome" placeholder="Seu nome" required>
                    </div>
                    <div class="form-group">
                        <label>CPF</label>
                        <input type="text" id="campoCpf" name="cpf" placeholder="000.000.000-00" required>
                        <span class="msg-erro" id="erroCpf"></span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="text" id="campoEmail" name="email" placeholder="email@exemplo.com" required>
                        <span class="msg-erro" id="erroEmail"></span>
                    </div>
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="text" name="telefone" placeholder="(00) 00000-0000" required>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Acessar Grade de Horários</button>
            </form>
        </div>

        <!-- ABA ADMIN -->
        <div class="tab-content-area" id="tab-admin">
            <p class="hint-text">Acesso restrito ao administrador do sistema.</p>
            <form method="POST">
                <input type="hidden" name="tipoLogin" value="admin">
                <div class="form-group">
                    <label>Usuário</label>
                    <input type="text" name="login" placeholder="admin" required>
                </div>
                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="senha" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-submit admin-btn">Entrar como Administrador</button>
            </form>
        </div>
    </div>
</div>

<script>
// ── Troca de abas ────────────────────────────────────────────────
function trocarTab(aba, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content-area').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + aba).classList.add('active');
}

<?php if (isset($erro) && strpos($erro, 'incorretos') !== false): ?>
document.querySelector('.tab-btn:last-child').click();
<?php endif; ?>

// ── Validação de CPF (algoritmo oficial) ─────────────────────────
function validarCPF(cpf) {
    cpf = cpf.replace(/[^\d]+/g, '');
    if (cpf === '' || cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
    let add = 0;
    for (let i = 0; i < 9; i++) add += parseInt(cpf.charAt(i)) * (10 - i);
    let rev = 11 - (add % 11);
    if (rev === 10 || rev === 11) rev = 0;
    if (rev !== parseInt(cpf.charAt(9))) return false;
    add = 0;
    for (let i = 0; i < 10; i++) add += parseInt(cpf.charAt(i)) * (11 - i);
    rev = 11 - (add % 11);
    if (rev === 10 || rev === 11) rev = 0;
    return rev === parseInt(cpf.charAt(10));
}

// ── Validação de e-mail ──────────────────────────────────────────
function validarEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// ── Máscara automática de CPF ────────────────────────────────────
const campoCpf = document.getElementById('campoCpf');
if (campoCpf) {
    campoCpf.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '').slice(0, 11);
        if (v.length > 9)      v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
        else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d{1,3})/,        '$1.$2.$3');
        else if (v.length > 3) v = v.replace(/(\d{3})(\d{1,3})/,               '$1.$2');
        this.value = v;
    });

    // Feedback visual ao sair do campo
    campoCpf.addEventListener('blur', function () {
        const erro = document.getElementById('erroCpf');
        if (this.value === '') return; // Não valida campo vazio aqui
        if (validarCPF(this.value)) {
            this.classList.remove('campo-invalido');
            this.classList.add('campo-valido');
            erro.textContent = '';
        } else {
            this.classList.remove('campo-valido');
            this.classList.add('campo-invalido');
            erro.textContent = 'CPF inválido. Verifique os números.';
        }
    });

    campoCpf.addEventListener('focus', function () {
        this.classList.remove('campo-invalido', 'campo-valido');
        document.getElementById('erroCpf').textContent = '';
    });
}

// ── Feedback visual no e-mail ────────────────────────────────────
const campoEmail = document.getElementById('campoEmail');
if (campoEmail) {
    campoEmail.addEventListener('blur', function () {
        const erro = document.getElementById('erroEmail');
        if (this.value === '') return;
        if (validarEmail(this.value)) {
            this.classList.remove('campo-invalido');
            this.classList.add('campo-valido');
            erro.textContent = '';
        } else {
            this.classList.remove('campo-valido');
            this.classList.add('campo-invalido');
            erro.textContent = 'Formato de e-mail inválido.';
        }
    });

    campoEmail.addEventListener('focus', function () {
        this.classList.remove('campo-invalido', 'campo-valido');
        document.getElementById('erroEmail').textContent = '';
    });
}

// ── Intercepta envio do formulário cliente ───────────────────────
const formCliente = document.querySelector('#tab-cliente form');
if (formCliente) {
    formCliente.addEventListener('submit', function (event) {
        let bloqueado = false;

        // Valida CPF
        const cpfVal = campoCpf ? campoCpf.value : '';
        if (!validarCPF(cpfVal)) {
            event.preventDefault();
            bloqueado = true;
            campoCpf.classList.add('campo-invalido');
            document.getElementById('erroCpf').textContent = 'CPF inválido. Verifique os números.';
            campoCpf.focus();
        }

        // Valida e-mail (só se CPF passou)
        const emailVal = campoEmail ? campoEmail.value : '';
        if (!bloqueado && !validarEmail(emailVal)) {
            event.preventDefault();
            campoEmail.classList.add('campo-invalido');
            document.getElementById('erroEmail').textContent = 'Formato de e-mail inválido.';
            campoEmail.focus();
        }
    });
}
</script>
</body>
</html>