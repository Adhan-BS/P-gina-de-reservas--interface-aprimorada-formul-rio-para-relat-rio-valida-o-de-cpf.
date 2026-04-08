<?php
// -- Início: verificação de sessão (protege a página de acesso direto sem login) --
session_start();
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: login.php"); // Redireciona para o login se não estiver logado
    exit;
}

// Recupera dados do usuário logado para uso no PHP e no JS
$papel       = $_SESSION['usuario_papel']; // 'admin' ou 'cliente'
$nomeUsuario = $_SESSION['usuario_nome'];
$hoje        = date('Y-m-d'); // Data atual no formato que o input date e o banco esperam
// -- Fim: verificação de sessão --
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoomSync — Reservas</title>

    <!-- -- Início: frameworks externos -- -->
    <!-- Bootstrap 5.3: utilitários CSS de base -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: DM Sans (textos) e DM Mono (horários na grade) -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono&display=swap" rel="stylesheet">
    <!-- -- Fim: frameworks externos -- -->

    <style>
        /* -- Início: configuração base da página -- */
        body {
            font-family: 'DM Sans', sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
        }
        /* -- Fim: configuração base da página -- */

        /* -- Início: estilos da navbar -- */
        .navbar-roomsync {
            background: #0f1117;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; /* Fica presa no topo ao rolar a página */
            top: 0;
            z-index: 100;
        }

        .navbar-brand-custom {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: #f1f5f9;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: -0.01em;
        }

        .navbar-icon {
            width: 32px; height: 32px;
            background: #2563eb;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }

        .navbar-icon svg { width: 18px; height: 18px; fill: white; }

        .navbar-user {
            display: flex; align-items: center; gap: 0.75rem;
        }

        /* Badge colorido que mostra 'admin' ou 'cliente' na navbar */
        .badge-papel {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.2rem 0.55rem;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: <?= $papel === 'admin' ? '#7c3aed' : '#0369a1' ?>; /* Roxo para admin, azul para cliente */
            color: white;
        }

        .navbar-nome { font-size: 0.875rem; color: #94a3b8; }

        .btn-sair {
            font-size: 0.8rem;
            padding: 0.35rem 0.9rem;
            border: 1px solid #252836;
            border-radius: 7px;
            background: transparent;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-sair:hover { border-color: #ef4444; color: #ef4444; }
        /* -- Fim: estilos da navbar -- */

        /* -- Início: estilo do layout principal de duas colunas -- */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 1fr 380px; /* Coluna da grade | Coluna do painel lateral */
            gap: 1.25rem;
        }

        /* Em telas menores que 900px, empilha as colunas verticalmente */
        @media (max-width: 900px) {
            .main-container { grid-template-columns: 1fr; }
        }
        /* -- Fim: estilo do layout principal -- */

        /* -- Início: estilo do card base (usado tanto na grade quanto no painel lateral) -- */
        .card-custom {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
        }

        .card-header-custom {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header-custom h5 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
        }

        .card-body-custom { padding: 1.25rem; }
        /* -- Fim: estilo do card base -- */

        /* -- Início: estilo dos filtros de sala e data -- */
        .filtros-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }

        .filtros-row label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.35rem;
        }

        .filtros-row select,
        .filtros-row input {
            width: 100%;
            padding: 0.55rem 0.8rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            color: #0f172a;
            background: #f8fafc;
            transition: border-color 0.2s;
            outline: none;
        }

        .filtros-row select:focus,
        .filtros-row input:focus { border-color: #2563eb; background: white; }
        /* -- Fim: estilo dos filtros -- */

        /* -- Início: estilo da grade de horários (estilo cinema) -- */
        .grade-titulo {
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            text-align: center;
            margin-bottom: 0.9rem;
        }

        /* Grid 2 colunas que mostra os blocos de horário */
        .grade-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        /* Bloco base — cada horário é um desses */
        .bloco {
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            border: 2px solid transparent;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .bloco-periodo {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.3rem;
        }

        /* Usa DM Mono para os horários ficarem com espaçamento fixo (estilo relógio) */
        .bloco-horario-txt {
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            font-family: 'DM Mono', monospace;
        }

        .bloco-status {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            display: inline-block;
        }

        /* Verde: horário disponível para reserva */
        .bloco-livre {
            background: #f0fdf4;
            border-color: #86efac;
            cursor: pointer;
        }
        .bloco-livre .bloco-periodo { color: #15803d; }
        .bloco-livre .bloco-horario-txt { color: #14532d; }
        .bloco-livre .bloco-status { background: #dcfce7; color: #15803d; }
        .bloco-livre:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(21,128,61,0.15); }

        /* Vermelho: horário com reserva ativa (confirmada pelo admin) */
        .bloco-ocupado {
            background: #fef2f2;
            border-color: #fca5a5;
            cursor: not-allowed;
        }
        .bloco-ocupado .bloco-periodo { color: #b91c1c; }
        .bloco-ocupado .bloco-horario-txt { color: #7f1d1d; }
        .bloco-ocupado .bloco-status { background: #fee2e2; color: #b91c1c; }

        /* Amarelo: horário com solicitação pendente (aguardando aprovação do admin) */
        .bloco-pendente {
            background: #fffbeb;
            border-color: #fcd34d;
            cursor: not-allowed;
        }
        .bloco-pendente .bloco-periodo { color: #b45309; }
        .bloco-pendente .bloco-horario-txt { color: #78350f; }
        .bloco-pendente .bloco-status { background: #fef3c7; color: #b45309; }

        /* Azul: horário livre visto pelo admin (opção de criar reserva diretamente) */
        .bloco-admin-livre {
            background: #eff6ff;
            border-color: #93c5fd;
            cursor: pointer;
        }
        .bloco-admin-livre .bloco-periodo { color: #1d4ed8; }
        .bloco-admin-livre .bloco-horario-txt { color: #1e3a8a; }
        .bloco-admin-livre .bloco-status { background: #dbeafe; color: #1d4ed8; }
        .bloco-admin-livre:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37,99,235,0.15); }

        /* Texto exibido enquanto a grade está sendo carregada via fetch */
        .grade-loading {
            grid-column: 1/-1;
            text-align: center;
            padding: 2rem;
            color: #94a3b8;
            font-size: 0.875rem;
        }
        /* -- Fim: estilo da grade de horários -- */

        /* -- Início: estilos do painel lateral de reservas -- */
        .filtro-status-btns {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        /* Botões de filtro por status (Todos / Pendentes / Ativas / Canceladas) */
        .btn-filtro {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.25rem 0.65rem;
            border-radius: 20px;
            border: 1.5px solid #e2e8f0;
            background: white;
            color: #64748b;
            cursor: pointer;
            transition: all 0.15s;
        }

        .btn-filtro.ativo { border-color: #2563eb; background: #eff6ff; color: #2563eb; }

        /* Cada item de reserva na lista lateral */
        .reserva-item {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
        }

        .reserva-item:last-child { border-bottom: none; }
        .reserva-item:hover { background: #f8fafc; }

        .reserva-sala {
            font-weight: 600;
            font-size: 0.875rem;
            color: #0f172a;
        }

        .reserva-info {
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 0.15rem;
        }

        .reserva-nome {
            font-size: 0.78rem;
            color: #475569;
            margin-top: 0.1rem;
        }

        /* Botões de ação do admin (Aprovar / Cancelar) dentro do item de reserva */
        .reserva-actions {
            margin-top: 0.5rem;
            display: flex;
            gap: 0.4rem;
        }

        .btn-acao {
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: opacity 0.15s;
        }

        .btn-acao:hover { opacity: 0.8; }
        .btn-aprovar  { background: #dcfce7; color: #15803d; } /* Verde */
        .btn-cancelar { background: #fee2e2; color: #b91c1c; } /* Vermelho */

        /* Badge de status exibido ao lado do nome da sala */
        .badge-status {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-left: 0.4rem;
        }

        .badge-pendente  { background: #fef3c7; color: #b45309; }
        .badge-ativa     { background: #dcfce7; color: #15803d; }
        .badge-cancelada { background: #fee2e2; color: #b91c1c; }

        /* Mensagem quando a lista não tem nenhuma reserva para mostrar */
        .lista-vazia {
            text-align: center;
            padding: 2rem 1rem;
            color: #94a3b8;
            font-size: 0.875rem;
        }

        /* Badge vermelho com contador de reservas pendentes no cabeçalho do painel admin */
        .badge-contador {
            font-size: 0.65rem;
            font-weight: 700;
            background: #ef4444;
            color: white;
            border-radius: 20px;
            padding: 0.1rem 0.5rem;
            margin-left: 0.4rem;
        }
        /* -- Fim: estilos do painel lateral -- */

        /* -- Início: estilos do sistema de toast (notificações temporárias) -- */
        .toast-container-custom {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .toast-item {
            padding: 0.75rem 1.1rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 500;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
            max-width: 320px;
        }

        /* Variações de cor por tipo de mensagem */
        .toast-sucesso { background: #0f172a; color: #4ade80; border-left: 3px solid #22c55e; }
        .toast-erro    { background: #0f172a; color: #f87171; border-left: 3px solid #ef4444; }
        .toast-info    { background: #0f172a; color: #60a5fa; border-left: 3px solid #3b82f6; }

        /* Animação de entrada do toast vindo da direita */
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to   { transform: translateX(0);    opacity: 1; }
        }
        /* -- Fim: estilos do toast -- */

        /* -- Início: estilos do modal de nova reserva (exclusivo do admin) -- */
        .modal-content {
            border: none;
            border-radius: 14px;
            overflow: hidden;
        }

        .modal-header-custom {
            background: #0f1117;
            color: #f1f5f9;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header-custom h5 { font-size: 0.95rem; font-weight: 600; margin: 0; }

        .btn-fechar-modal {
            background: none;
            border: none;
            color: #64748b;
            font-size: 1.2rem;
            cursor: pointer;
            line-height: 1;
        }

        .btn-fechar-modal:hover { color: #f1f5f9; }

        .modal-body { padding: 1.25rem; }

        .modal-field { margin-bottom: 1rem; }

        .modal-field label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .modal-field input {
            width: 100%;
            padding: 0.6rem 0.85rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            color: #0f172a;
            transition: border-color 0.2s;
            outline: none;
        }

        .modal-field input:focus { border-color: #2563eb; }

        /* Dois campos lado a lado dentro do modal */
        .modal-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

        .modal-footer-custom {
            padding: 1rem 1.25rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 0.6rem;
        }

        .btn-modal-cancelar {
            padding: 0.55rem 1.1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            color: #64748b;
            cursor: pointer;
        }

        .btn-modal-confirmar {
            padding: 0.55rem 1.1rem;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-modal-confirmar:hover { background: #1d4ed8; }

        /* Caixa de resumo da reserva exibida no topo do modal (sala, data, horário) */
        .reserva-info-modal {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            font-size: 0.85rem;
            color: #475569;
        }

        .reserva-info-modal strong { color: #0f172a; }
        /* -- Fim: estilos do modal -- */
    </style>
</head>
<body>

<!-- -- Início: variáveis PHP passadas para o JavaScript -- -->
<!-- Exporta dados da sessão e configurações para que o app.js possa usá-los -->
<script>
    const usuarioAtual = "<?= addslashes($nomeUsuario) ?>"; // Nome do usuário logado
    const papelAtual   = "<?= $papel ?>";                   // 'admin' ou 'cliente'
    const dataHoje     = "<?= $hoje ?>";                    // Data atual (YYYY-MM-DD)
    const salas        = ["Sala 101","Sala 102","Laboratório A","Laboratório B","Auditório","Sala de Reunião"];
    const blocosFixos  = [ // Horários disponíveis para reserva
        { inicio: "08:00", fim: "10:00", nome: "Manhã 1" },
        { inicio: "10:00", fim: "12:00", nome: "Manhã 2" },
        { inicio: "14:00", fim: "16:00", nome: "Tarde 1" },
        { inicio: "16:00", fim: "18:00", nome: "Tarde 2" }
    ];
</script>
<!-- -- Fim: variáveis PHP para o JavaScript -- -->


<!-- -- Início: navbar -- -->
<nav class="navbar-roomsync">
    <!-- Lado esquerdo: logo + nome do sistema -->
    <div class="navbar-brand-custom">
        <div class="navbar-icon">
            <svg viewBox="0 0 24 24"><path d="M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7zm2 0v10h14V7H5zm2 2h10v2H7V9zm0 4h6v2H7v-2z"/></svg>
        </div>
        RoomSync
    </div>
    <!-- Lado direito: nome do usuário, badge de papel e botão de sair -->
    <div class="navbar-user">
        <span class="navbar-nome"><?= htmlspecialchars($nomeUsuario) ?></span>
        <span class="badge-papel"><?= $papel ?></span>
        <a href="logout.php" class="btn-sair">Sair</a>
    </div>
</nav>
<!-- -- Fim: navbar -- -->


<!-- -- Início: layout principal de duas colunas -- -->
<div class="main-container">

    <!-- -- Início: coluna esquerda — grade de horários -- -->
    <div>
        <div class="card-custom">
            <div class="card-header-custom">
                <h5>Disponibilidade de Salas</h5>
            </div>
            <div class="card-body-custom">

                <!-- -- Início: filtros de sala e data -- -->
                <div class="filtros-row">
                    <div>
                        <label>Sala</label>
                        <!-- Ao mudar a sala, o JS recarrega a grade automaticamente -->
                        <select id="salaBusca" onchange="montarGrade()">
                            <?php foreach(['Sala 101','Sala 102','Laboratório A','Laboratório B','Auditório','Sala de Reunião'] as $s): ?>
                                <option value="<?= $s ?>"><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Data</label>
                        <!-- min="<?= $hoje ?>" impede selecionar datas passadas -->
                        <input type="date" id="dataBusca" value="<?= $hoje ?>" min="<?= $hoje ?>" onchange="montarGrade()">
                    </div>
                </div>
                <!-- -- Fim: filtros de sala e data -- -->

                <!-- -- Início: grade de horários (preenchida pelo app.js) -- -->
                <div class="grade-titulo">Grade de horários</div>
                <div class="grade-grid" id="gradeHorarios">
                    <div class="grade-loading">Carregando horários...</div>
                </div>
                <!-- -- Fim: grade de horários -- -->

            </div>
        </div>
    </div>
    <!-- -- Fim: coluna esquerda -- -->


    <!-- -- Início: coluna direita — painel de reservas -- -->
    <div>
        <div class="card-custom" style="height: 100%;">
            <div class="card-header-custom">
                <h5>
                    <!-- Título diferente para admin e cliente -->
                    <?= $papel === 'admin' ? 'Painel de Reservas' : 'Minhas Solicitações' ?>
                    <!-- -- Início: badge contador de pendentes (somente admin) -- -->
                    <?php if ($papel === 'admin'): ?>
                        <span class="badge-contador" id="contadorPendentes" style="display:none">0</span>
                    <?php endif; ?>
                    <!-- -- Fim: badge contador de pendentes -- -->
                </h5>
                <!-- -- Início: botões de filtro por status (somente admin) -- -->
                <?php if ($papel === 'admin'): ?>
                <div class="filtro-status-btns">
                    <button class="btn-filtro ativo" onclick="filtrarLista('todos', this)">Todos</button>
                    <button class="btn-filtro" onclick="filtrarLista('pendente', this)">Pendentes</button>
                    <button class="btn-filtro" onclick="filtrarLista('ativa', this)">Ativas</button>
                    <button class="btn-filtro" onclick="filtrarLista('cancelada', this)">Canceladas</button>
                </div>
                <?php endif; ?>
                <!-- -- Fim: botões de filtro por status -- -->
            </div>
            <!-- Lista de reservas preenchida pelo app.js -->
            <div id="listaReservas" style="overflow-y: auto; max-height: 600px;">
                <div class="lista-vazia">Carregando...</div>
            </div>
        </div>
    </div>
    <!-- -- Fim: coluna direita -- -->

</div>
<!-- -- Fim: layout principal -- -->


<!-- -- Início: container dos toasts (notificações de sucesso/erro) -- -->
<!-- Toasts são criados e injetados aqui dinamicamente pelo app.js -->
<div class="toast-container-custom" id="toastContainer"></div>
<!-- -- Fim: container dos toasts -- -->


<!-- -- Início: modal de nova reserva (renderizado SOMENTE para o admin) -- -->
<?php if ($papel === 'admin'): ?>
<div id="modalOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:14px; width:100%; max-width:480px; margin:1rem; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.3);">

        <!-- -- Início: cabeçalho do modal -- -->
        <div class="modal-header-custom">
            <h5>Nova Reserva</h5>
            <button class="btn-fechar-modal" onclick="fecharModal()">✕</button>
        </div>
        <!-- -- Fim: cabeçalho do modal -- -->

        <div class="modal-body">
            <!-- -- Início: resumo do horário escolhido (preenchido pelo JS ao abrir o modal) -- -->
            <div class="reserva-info-modal" id="modalInfoReserva"></div>
            <!-- -- Fim: resumo do horário -- -->

            <!-- -- Início: campos do formulário do modal -- -->
            <div class="modal-field-row">
                <div class="modal-field">
                    <label>Nome completo</label>
                    <input type="text" id="modalNome" placeholder="Nome do cliente">
                </div>
                <div class="modal-field">
                    <label>CPF</label>
                    <input type="text" id="modalCpf" placeholder="000.000.000-00">
                </div>
            </div>
            <div class="modal-field-row">
                <div class="modal-field">
                    <label>E-mail</label>
                    <input type="email" id="modalEmail" placeholder="email@exemplo.com">
                </div>
                <div class="modal-field">
                    <label>Telefone</label>
                    <input type="text" id="modalTelefone" placeholder="(00) 00000-0000">
                </div>
            </div>
            <!-- -- Fim: campos do modal -- -->

            <!-- -- Início: campos ocultos que guardam sala/data/horário selecionados na grade -- -->
            <input type="hidden" id="modalSala">
            <input type="hidden" id="modalData">
            <input type="hidden" id="modalHoraInicio">
            <input type="hidden" id="modalHoraFim">
            <!-- -- Fim: campos ocultos -- -->
        </div>

        <!-- -- Início: rodapé do modal com botões de ação -- -->
        <div class="modal-footer-custom">
            <button class="btn-modal-cancelar" onclick="fecharModal()">Cancelar</button>
            <button class="btn-modal-confirmar" onclick="salvarReservaModal()">Confirmar Reserva</button>
        </div>
        <!-- -- Fim: rodapé do modal -- -->

    </div>
</div>
<?php endif; ?>
<!-- -- Fim: modal de nova reserva -- -->


<!-- -- Início: carregamento do app.js (lógica principal do front-end) -- -->
<!-- ?v=time() força o browser a não usar cache velho do arquivo -->
<script src="app.js?v=<?= time() ?>"></script>
<!-- -- Fim: carregamento do app.js -- -->

</body>
</html>
