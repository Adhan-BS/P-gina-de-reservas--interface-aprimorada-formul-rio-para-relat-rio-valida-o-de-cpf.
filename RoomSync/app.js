// ================================================================
// RoomSync — app.js
// Lógica principal do front-end: monta a grade, gerencia reservas,
// exibe toasts e controla o modal do admin.
// ================================================================

// -- Início: variáveis globais de estado --
let todasReservasCache = []; // Guarda a última lista de reservas recebida da API
let filtroAtual = 'todos';   // Filtro ativo no painel lateral do admin
// -- Fim: variáveis globais --


// ================================================================
// -- Início: sistema de toast (notificações temporárias) --
// ================================================================
function toast(msg, tipo = 'sucesso') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const el = document.createElement('div');
    el.className = `toast-item toast-${tipo}`; // Classes CSS: toast-sucesso, toast-erro, toast-info
    el.textContent = msg;
    container.appendChild(el);

    // Remove o toast após 3,5 segundos com fade out
    setTimeout(() => {
        el.style.transition = 'opacity 0.3s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 300);
    }, 3500);
}
// -- Fim: sistema de toast --


// ================================================================
// -- Início: montagem da grade de horários (estilo cinema) --
// ================================================================
function montarGrade() {
    const salaInput = document.getElementById('salaBusca');
    const dataInput = document.getElementById('dataBusca');
    if (!salaInput || !dataInput) return;

    const sala = salaInput.value;
    const data = dataInput.value;
    if (!data) return;

    const container = document.getElementById('gradeHorarios');
    container.innerHTML = '<div class="grade-loading">Buscando horários...</div>';

    // -- Início: busca das reservas na API --
    fetch('api.php')
        .then(res => res.json())
        .then(reservas => {
            todasReservasCache = reservas; // Atualiza o cache global

            // Filtra apenas as reservas da sala e data selecionadas (exclui canceladas)
            const reservasDaSala = reservas.filter(r =>
                r.sala === sala &&
                r.data === data &&
                r.status !== 'cancelada'
            );
            // -- Fim: busca das reservas --

            container.innerHTML = '';

            // -- Início: geração dos blocos de horário --
            blocosFixos.forEach(bloco => {
                const div = document.createElement('div');

                // Verifica se alguma reserva ocupa este bloco de horário
                const reserva = reservasDaSala.find(r => r.horaInicio === bloco.inicio);

                if (reserva) {
                    // -- Início: bloco amarelo — solicitação pendente (aguardando aprovação) --
                    if (reserva.status === 'pendente') {
                        const texto = papelAtual === 'admin'
                            ? `Solicitado por ${reserva.nome}` // Admin vê o nome de quem solicitou
                            : 'Aguardando Aprovação';          // Cliente vê mensagem genérica

                        div.innerHTML = `
                            <div class="bloco bloco-pendente">
                                <div class="bloco-periodo">${bloco.nome}</div>
                                <div class="bloco-horario-txt">${bloco.inicio} – ${bloco.fim}</div>
                                <span class="bloco-status">⏳ ${texto}</span>
                            </div>`;
                    }
                    // -- Fim: bloco pendente --

                    // -- Início: bloco vermelho — horário com reserva ativa (confirmada) --
                    else {
                        const texto = papelAtual === 'admin'
                            ? `${reserva.nome}`      // Admin vê o nome de quem reservou
                            : 'Indisponível';        // Cliente vê apenas "Indisponível"

                        div.innerHTML = `
                            <div class="bloco bloco-ocupado">
                                <div class="bloco-periodo">${bloco.nome}</div>
                                <div class="bloco-horario-txt">${bloco.inicio} – ${bloco.fim}</div>
                                <span class="bloco-status">✗ ${texto}</span>
                            </div>`;
                    }
                    // -- Fim: bloco ocupado --

                } else {
                    // -- Início: bloco azul — horário livre visto pelo admin (abre modal para criar reserva) --
                    if (papelAtual === 'admin') {
                        div.innerHTML = `
                            <div class="bloco bloco-admin-livre" onclick="abrirModal('${sala}', '${data}', '${bloco.inicio}', '${bloco.fim}', '${bloco.nome}')">
                                <div class="bloco-periodo">${bloco.nome}</div>
                                <div class="bloco-horario-txt">${bloco.inicio} – ${bloco.fim}</div>
                                <span class="bloco-status">+ Criar reserva</span>
                            </div>`;
                    }
                    // -- Fim: bloco livre admin --

                    // -- Início: bloco verde — horário livre visto pelo cliente (envia solicitação) --
                    else {
                        div.innerHTML = `
                            <div class="bloco bloco-livre" onclick="enviarSolicitacao('${sala}', '${data}', '${bloco.inicio}', '${bloco.fim}', '${bloco.nome}')">
                                <div class="bloco-periodo">${bloco.nome}</div>
                                <div class="bloco-horario-txt">${bloco.inicio} – ${bloco.fim}</div>
                                <span class="bloco-status">✓ Disponível</span>
                            </div>`;
                    }
                    // -- Fim: bloco livre cliente --
                }

                container.appendChild(div);
            });
            // -- Fim: geração dos blocos --

            atualizarListaLateral(reservas); // Atualiza o painel lateral com os dados frescos
        })
        .catch(() => {
            container.innerHTML = '<div class="grade-loading" style="color:#ef4444;">Erro ao carregar horários.</div>';
        });
}
// -- Fim: montagem da grade --


// ================================================================
// -- Início: envio de solicitação pelo cliente --
// ================================================================
// Chamado quando o cliente clica em um bloco verde (horário disponível)
function enviarSolicitacao(sala, data, horaInicio, horaFim, nome) {
    if (!confirm(`Confirmar solicitação para ${sala} das ${horaInicio} às ${horaFim}?`)) return;

    // -- Início: requisição POST para a API --
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sala, data, horaInicio, horaFim })
        // Nota: nome, cpf, email e telefone do cliente vêm da sessão PHP na api.php
    })
    .then(res => res.json())
    .then(data => {
        if (data.erro) toast(data.erro, 'erro');
        else { toast(data.mensagem, 'sucesso'); montarGrade(); } // Recarrega a grade após sucesso
    })
    .catch(() => toast('Erro ao enviar solicitação.', 'erro'));
    // -- Fim: requisição POST --
}
// -- Fim: envio de solicitação pelo cliente --


// ================================================================
// -- Início: abertura do modal de nova reserva (somente admin) --
// ================================================================
// Chamado quando o admin clica em um bloco azul (horário disponível para admin)
function abrirModal(sala, data, horaInicio, horaFim, nomeBloco) {
    // -- Início: preenchimento dos campos ocultos e limpeza do formulário --
    document.getElementById('modalSala').value      = sala;
    document.getElementById('modalData').value      = data;
    document.getElementById('modalHoraInicio').value = horaInicio;
    document.getElementById('modalHoraFim').value   = horaFim;
    document.getElementById('modalNome').value      = '';
    document.getElementById('modalCpf').value       = '';
    document.getElementById('modalEmail').value     = '';
    document.getElementById('modalTelefone').value  = '';
    // -- Fim: preenchimento dos campos ocultos --

    // -- Início: exibição do resumo do horário no topo do modal --
    document.getElementById('modalInfoReserva').innerHTML =
        `<strong>${sala}</strong> &nbsp;·&nbsp; ${data} &nbsp;·&nbsp; ${nomeBloco} (${horaInicio} – ${horaFim})`;
    // -- Fim: resumo do horário --

    document.getElementById('modalOverlay').style.display = 'flex';
    setTimeout(() => document.getElementById('modalNome').focus(), 100); // Foca no primeiro campo
}
// -- Fim: abertura do modal --


// -- Início: fechamento do modal --
function fecharModal() {
    document.getElementById('modalOverlay').style.display = 'none';
}

// Permite fechar o modal clicando na área escura fora dele
document.addEventListener('click', function(e) {
    const overlay = document.getElementById('modalOverlay');
    if (overlay && e.target === overlay) fecharModal();
});
// -- Fim: fechamento do modal --


// ================================================================
// -- Início: salvar reserva pelo modal do admin --
// ================================================================
function salvarReservaModal() {
    // -- Início: leitura dos campos do modal --
    const nome     = document.getElementById('modalNome').value.trim();
    const cpf      = document.getElementById('modalCpf').value.trim();
    const email    = document.getElementById('modalEmail').value.trim();
    const telefone = document.getElementById('modalTelefone').value.trim();
    const sala     = document.getElementById('modalSala').value;
    const data     = document.getElementById('modalData').value;
    const horaInicio = document.getElementById('modalHoraInicio').value;
    const horaFim    = document.getElementById('modalHoraFim').value;
    // -- Fim: leitura dos campos --

    // -- Início: validação mínima antes de enviar --
    if (!nome || !cpf) {
        toast('Nome e CPF são obrigatórios.', 'erro');
        return;
    }
    // -- Fim: validação mínima --

    // -- Início: requisição POST para criar reserva (admin reserva diretamente como 'ativa') --
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nome, cpf, email, telefone, sala, data, horaInicio, horaFim })
    })
    .then(res => res.json())
    .then(resp => {
        if (resp.erro) { toast(resp.erro, 'erro'); return; }
        fecharModal();
        toast(resp.mensagem, 'sucesso');
        montarGrade(); // Recarrega a grade para refletir a nova reserva
    })
    .catch(() => toast('Erro ao salvar reserva.', 'erro'));
    // -- Fim: requisição POST --
}
// -- Fim: salvar reserva pelo modal --


// ================================================================
// -- Início: aprovar ou cancelar reserva (somente admin) --
// ================================================================
function mudarStatus(id, acao) {
    const msg = acao === 'aprovar' ? 'Aprovar esta solicitação?' : 'Cancelar esta reserva?';
    if (!confirm(msg)) return;

    // -- Início: requisição PUT para atualizar o status da reserva --
    fetch('api.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, acao })
    })
    .then(res => res.json())
    .then(resp => {
        if (resp.erro) { toast(resp.erro, 'erro'); return; }
        toast(resp.mensagem, 'sucesso');
        montarGrade(); // Recarrega para refletir o novo status
    })
    .catch(() => toast('Erro ao atualizar reserva.', 'erro'));
    // -- Fim: requisição PUT --
}
// -- Fim: aprovar ou cancelar reserva --


// ================================================================
// -- Início: filtro do painel lateral por status (somente admin) --
// ================================================================
function filtrarLista(filtro, btn) {
    filtroAtual = filtro;
    document.querySelectorAll('.btn-filtro').forEach(b => b.classList.remove('ativo'));
    if (btn) btn.classList.add('ativo');
    atualizarListaLateral(todasReservasCache); // Reaplica o filtro nos dados já em cache
}
// -- Fim: filtro do painel lateral --


// ================================================================
// -- Início: atualização do painel lateral de reservas --
// ================================================================
function atualizarListaLateral(reservas) {
    const container = document.getElementById('listaReservas');

    // -- Início: atualização do badge contador de pendentes (admin) --
    if (papelAtual === 'admin') {
        const pendentes = reservas.filter(r => r.status === 'pendente').length;
        const badge = document.getElementById('contadorPendentes');
        if (badge) {
            badge.textContent = pendentes;
            badge.style.display = pendentes > 0 ? 'inline' : 'none';
        }
    }
    // -- Fim: atualização do badge --

    // -- Início: filtragem da lista conforme papel e filtro ativo --
    let lista = reservas;
    if (papelAtual !== 'admin') {
        // Cliente vê apenas as próprias solicitações
        lista = reservas.filter(r => r.nome === usuarioAtual);
    } else if (filtroAtual !== 'todos') {
        // Admin com filtro ativo vê apenas o status selecionado
        lista = reservas.filter(r => r.status === filtroAtual);
    }
    // -- Fim: filtragem --

    if (lista.length === 0) {
        container.innerHTML = '<div class="lista-vazia">Nenhuma reserva encontrada.</div>';
        return;
    }

    container.innerHTML = '';

    // -- Início: geração dos itens da lista lateral --
    lista.forEach(item => {
        const div = document.createElement('div');
        div.className = 'reserva-item';

        const badgeClass  = `badge-${item.status}`;
        const badgeTexto  = { ativa: 'Ativa', pendente: 'Pendente', cancelada: 'Cancelada' }[item.status] || item.status;

        // -- Início: informações extras visíveis somente para o admin --
        let detalhesAdmin = '';
        if (papelAtual === 'admin') {
            detalhesAdmin = `<div class="reserva-nome">Por: ${item.nome}</div>`;
            if (item.cpf && item.cpf !== '***') {
                detalhesAdmin += `<div class="reserva-info" style="font-size:0.72rem; margin-top:0.1rem;">CPF: ${item.cpf} | Tel: ${item.telefone}</div>`;
            }
        }
        // -- Fim: informações extras do admin --

        // -- Início: botões de ação do admin (Aprovar / Cancelar) --
        let acoes = '';
        if (papelAtual === 'admin' && item.status !== 'cancelada') {
            if (item.status === 'pendente') {
                acoes += `<button class="btn-acao btn-aprovar" onclick="mudarStatus(${item.id}, 'aprovar')">✓ Aprovar</button>`;
            }
            acoes += `<button class="btn-acao btn-cancelar" onclick="mudarStatus(${item.id}, 'cancelar')">✗ Cancelar</button>`;
        }
        // -- Fim: botões de ação --

        div.innerHTML = `
            <div class="reserva-sala">
                ${item.sala}
                <span class="badge-status ${badgeClass}">${badgeTexto}</span>
            </div>
            <div class="reserva-info">${item.data} · ${item.horaInicio} – ${item.horaFim}</div>
            ${detalhesAdmin}
            ${acoes ? `<div class="reserva-actions">${acoes}</div>` : ''}
        `;

        container.appendChild(div);
    });
    // -- Fim: geração dos itens --
}
// -- Fim: atualização do painel lateral --

// -- Início: inicialização — carrega a grade ao abrir a página --
window.onload = montarGrade;
// -- Fim: inicialização --
