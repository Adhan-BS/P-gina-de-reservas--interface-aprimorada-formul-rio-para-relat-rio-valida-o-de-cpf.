<?php
// -- Início: configuração da sessão PHP --
session_start();
// -- Fim: configuração da sessão PHP --


// -- Início: função de validação de CPF no servidor (back-end) --
// Espelha a mesma lógica do JavaScript para garantir segurança mesmo se o JS for burlado
function validarCpfPHP($cpf) {
    $cpf = preg_replace('/[^\d]/', '', $cpf); // Remove pontos e traços
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false; // Rejeita sequências repetidas (ex: 111.111.111-11)
    $add = 0;
    for ($i = 0; $i < 9; $i++) $add += intval($cpf[$i]) * (10 - $i); // Calcula 1º dígito verificador
    $rev = 11 - ($add % 11);
    if ($rev >= 10) $rev = 0;
    if ($rev !== intval($cpf[9])) return false;
    $add = 0;
    for ($i = 0; $i < 10; $i++) $add += intval($cpf[$i]) * (11 - $i); // Calcula 2º dígito verificador
    $rev = 11 - ($add % 11);
    if ($rev >= 10) $rev = 0;
    return $rev === intval($cpf[10]);
}
// -- Fim: função de validação de CPF no servidor --


// -- Início: processamento do formulário de login (POST) --
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipoLogin'] ?? '';

    // -- Início: login do administrador --
    if ($tipo === 'admin') {
        if ($_POST['login'] === 'admin' && $_POST['senha'] === '1234') {
            $_SESSION['logado']        = true;
            $_SESSION['usuario_papel'] = 'admin';
            $_SESSION['usuario_nome']  = 'Administrador';
            header("Location: index.php"); // Redireciona para o sistema principal
            exit;
        } else {
            $erro = "Usuário ou senha incorretos.";
        }
    }
    // -- Fim: login do administrador --

    // -- Início: login do cliente --
    elseif ($tipo === 'cliente') {
        $nome     = trim($_POST['nome'] ?? '');
        $cpf      = trim($_POST['cpf'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');

        // -- Início: validações dos campos do cliente --
        if (!$nome || !$cpf || !$email || !$telefone) {
            $erro = "Preencha todos os campos para continuar.";
        } elseif (!validarCpfPHP($cpf)) {
            $erro = "CPF inválido. Verifique os números digitados.";
        }
        // -- Fim: validações dos campos do cliente --

        // -- Início: salvar dados do cliente na sessão --
        else {
            $_SESSION['logado']           = true;
            $_SESSION['usuario_papel']    = 'cliente';
            $_SESSION['usuario_nome']     = htmlspecialchars($nome);     // Nome exibido na navbar
            $_SESSION['cliente_cpf']      = htmlspecialchars($cpf);      // Usado na hora de criar reserva
            $_SESSION['cliente_email']    = htmlspecialchars($email);    // Salvo junto com a reserva
            $_SESSION['cliente_telefone'] = htmlspecialchars($telefone); // Salvo junto com a reserva
            header("Location: index.php");
            exit;
        }
        // -- Fim: salvar dados do cliente na sessão --
    }
    // -- Fim: login do cliente --
}
// -- Fim: processamento do formulário de login --
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoomSync — Acesso</title>

    <!-- -- Início: frameworks externos -- -->
    <!-- Bootstrap 5.3: reset CSS e utilitários básicos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: DM Sans (textos) e DM Mono (campos numéricos) -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono&display=swap" rel="stylesheet">
    <!-- -- Fim: frameworks externos -- -->

    <style>
        /* -- Início: reset e configuração base da página -- */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            background: #0f1117; /* Fundo escuro da página de login */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        /* -- Fim: reset e configuração base da página -- */

        /* -- Início: estilo do container centralizado -- */
        .login-wrapper {
            width: 100%;
            max-width: 460px;
        }
        /* -- Fim: estilo do container centralizado -- */

        /* -- Início: estilo da área do logo e título -- */
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
        /* -- Fim: estilo da área do logo e título -- */

        /* -- Início: estilo do card principal de login -- */
        .card-login {
            background: #1a1d27;
            border: 1px solid #252836;
            border-radius: 16px;
            padding: 2rem;
        }
        /* -- Fim: estilo do card principal de login -- */

        /* -- Início: estilo dos botões de troca de aba (Cliente / Admin) -- */
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
        /* -- Fim: estilo dos botões de troca de aba -- */

        /* -- Início: controle de visibilidade das abas -- */
        .tab-content-area { display: none; }
        .tab-content-area.active { display: block; }
        /* -- Fim: controle de visibilidade das abas -- */

        /* -- Início: estilo dos campos do formulário -- */
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

        .form-group input:focus { border-color: #2563eb; }
        .form-group input::placeholder { color: #475569; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Dois campos lado a lado */
            gap: 0.75rem;
        }
        /* -- Fim: estilo dos campos do formulário -- */

        /* -- Início: estilo do texto de dica abaixo do título da aba -- */
        .hint-text {
            font-size: 0.8rem;
            color: #475569;
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }
        /* -- Fim: estilo do texto de dica -- */

        /* -- Início: estilo do botão de envio do formulário -- */
        .btn-submit {
            width: 100%;
            padding: 0.7rem;
            background: #2563eb; /* Azul para cliente */
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
        .btn-submit.admin-btn { background: #7c3aed; } /* Roxo para admin */
        .btn-submit.admin-btn:hover { background: #6d28d9; }
        /* -- Fim: estilo do botão de envio -- */

        /* -- Início: estilo do alerta de erro vindo do PHP -- */
        .alert-erro {
            background: #1f1315;
            border: 1px solid #7f1d1d;
            color: #fca5a5;
            border-radius: 8px;
            padding: 0.65rem 0.9rem;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }
        /* -- Fim: estilo do alerta de erro -- */

        /* -- Início: estilo das mensagens de erro e feedback visual dos campos CPF e e-mail -- */
        .msg-erro {
            display: block;
            font-size: 0.72rem;
            color: #f87171;
            margin-top: 0.3rem;
            min-height: 1rem; /* Reserva espaço para não deslocar o layout ao aparecer */
        }

        .campo-invalido {
            border-color: #ef4444 !important; /* Borda vermelha */
            background: #1f1315 !important;
        }

        .campo-valido {
            border-color: #22c55e !important; /* Borda verde */
        }
        /* -- Fim: estilo de feedback visual dos campos -- */
    </style>
</head>
<body>

<!-- -- Início: container principal da página de login -- -->
<div class="login-wrapper">

    <!-- -- Início: área do logo e título do sistema -- -->
    <div class="logo-area">
        <div class="logo-icon">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7zm2 0v10h14V7H5zm2 2h10v2H7V9zm0 4h6v2H7v-2z"/>
            </svg>
        </div>
        <h1>RoomSync</h1>
        <p>Sistema de reserva de salas</p>
    </div>
    <!-- -- Fim: área do logo e título -- -->

    <!-- -- Início: card de login com abas -- -->
    <div class="card-login">

        <!-- -- Início: exibição de erro do PHP (login inválido, CPF inválido, etc.) -- -->
        <?php if (isset($erro)): ?>
            <div class="alert-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <!-- -- Fim: exibição de erro do PHP -- -->

        <!-- -- Início: botões de troca de aba -- -->
        <div class="tab-switcher">
            <button class="tab-btn active" onclick="trocarTab('cliente', this)">Sou Cliente</button>
            <button class="tab-btn" onclick="trocarTab('admin', this)">Administrador</button>
        </div>
        <!-- -- Fim: botões de troca de aba -- -->

        <!-- -- Início: aba do cliente (formulário de identificação) -- -->
        <div class="tab-content-area active" id="tab-cliente">
            <p class="hint-text">Informe seus dados para acessar a grade de horários e enviar solicitações de reserva.</p>
            <form method="POST">
                <input type="hidden" name="tipoLogin" value="cliente">

                <!-- -- Início: campos Nome e CPF -- -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome completo</label>
                        <input type="text" name="nome" placeholder="Seu nome" required>
                    </div>
                    <div class="form-group">
                        <label>CPF</label>
                        <!-- id="campoCpf" é usado pelo JS para aplicar máscara e validação -->
                        <input type="text" id="campoCpf" name="cpf" placeholder="000.000.000-00" required>
                        <!-- Espaço reservado para mensagem de erro do JS -->
                        <span class="msg-erro" id="erroCpf"></span>
                    </div>
                </div>
                <!-- -- Fim: campos Nome e CPF -- -->

                <!-- -- Início: campos E-mail e Telefone -- -->
                <div class="form-row">
                    <div class="form-group">
                        <label>E-mail</label>
                        <!-- id="campoEmail" é usado pelo JS para validação de formato -->
                        <input type="text" id="campoEmail" name="email" placeholder="email@exemplo.com" required>
                        <!-- Espaço reservado para mensagem de erro do JS -->
                        <span class="msg-erro" id="erroEmail"></span>
                    </div>
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="text" name="telefone" placeholder="(00) 00000-0000" required>
                    </div>
                </div>
                <!-- -- Fim: campos E-mail e Telefone -- -->

                <button type="submit" class="btn-submit">Acessar Grade de Horários</button>
            </form>
        </div>
        <!-- -- Fim: aba do cliente -- -->

        <!-- -- Início: aba do administrador -- -->
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
        <!-- -- Fim: aba do administrador -- -->

    </div>
    <!-- -- Fim: card de login com abas -- -->

</div>
<!-- -- Fim: container principal da página de login -- -->


<script>
// -- Início: função de troca de abas (Cliente / Admin) --
function trocarTab(aba, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content-area').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + aba).classList.add('active');
}

// Reabre a aba de admin se o PHP retornou erro de credencial inválida
<?php if (isset($erro) && strpos($erro, 'incorretos') !== false): ?>
document.querySelector('.tab-btn:last-child').click();
<?php endif; ?>
// -- Fim: função de troca de abas --


// -- Início: função de validação de CPF pelo algoritmo oficial (front-end) --
function validarCPF(cpf) {
    cpf = cpf.replace(/[^\d]+/g, ''); // Remove tudo que não é dígito
    if (cpf === '' || cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
    let add = 0;
    for (let i = 0; i < 9; i++) add += parseInt(cpf.charAt(i)) * (10 - i); // 1º dígito
    let rev = 11 - (add % 11);
    if (rev === 10 || rev === 11) rev = 0;
    if (rev !== parseInt(cpf.charAt(9))) return false;
    add = 0;
    for (let i = 0; i < 10; i++) add += parseInt(cpf.charAt(i)) * (11 - i); // 2º dígito
    rev = 11 - (add % 11);
    if (rev === 10 || rev === 11) rev = 0;
    return rev === parseInt(cpf.charAt(10));
}
// -- Fim: função de validação de CPF (front-end) --


// -- Início: função de validação de formato de e-mail --
function validarEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
// -- Fim: função de validação de e-mail --


// -- Início: máscara automática de CPF (formata enquanto o usuário digita) --
const campoCpf = document.getElementById('campoCpf');
if (campoCpf) {
    campoCpf.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '').slice(0, 11);
        if (v.length > 9)      v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
        else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d{1,3})/,        '$1.$2.$3');
        else if (v.length > 3) v = v.replace(/(\d{3})(\d{1,3})/,               '$1.$2');
        this.value = v;
    });
    // -- Fim: máscara automática de CPF --

    // -- Início: feedback visual do CPF ao sair do campo --
    campoCpf.addEventListener('blur', function () {
        const erro = document.getElementById('erroCpf');
        if (this.value === '') return;
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
    // -- Fim: feedback visual do CPF ao sair do campo --

    // -- Início: limpar feedback do CPF ao focar novamente --
    campoCpf.addEventListener('focus', function () {
        this.classList.remove('campo-invalido', 'campo-valido');
        document.getElementById('erroCpf').textContent = '';
    });
    // -- Fim: limpar feedback do CPF --
}


// -- Início: feedback visual do e-mail ao sair do campo --
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

    // -- Início: limpar feedback do e-mail ao focar novamente --
    campoEmail.addEventListener('focus', function () {
        this.classList.remove('campo-invalido', 'campo-valido');
        document.getElementById('erroEmail').textContent = '';
    });
    // -- Fim: limpar feedback do e-mail --
}
// -- Fim: feedback visual do e-mail ao sair do campo --


// -- Início: bloqueio do envio do formulário se CPF ou e-mail forem inválidos --
const formCliente = document.querySelector('#tab-cliente form');
if (formCliente) {
    formCliente.addEventListener('submit', function (event) {
        let bloqueado = false;

        const cpfVal = campoCpf ? campoCpf.value : '';
        if (!validarCPF(cpfVal)) {
            event.preventDefault(); // Impede o envio ao servidor
            bloqueado = true;
            campoCpf.classList.add('campo-invalido');
            document.getElementById('erroCpf').textContent = 'CPF inválido. Verifique os números.';
            campoCpf.focus();
        }

        const emailVal = campoEmail ? campoEmail.value : '';
        if (!bloqueado && !validarEmail(emailVal)) {
            event.preventDefault();
            campoEmail.classList.add('campo-invalido');
            document.getElementById('erroEmail').textContent = 'Formato de e-mail inválido.';
            campoEmail.focus();
        }
    });
}
// -- Fim: bloqueio do envio do formulário --
</script>
</body>
</html>
