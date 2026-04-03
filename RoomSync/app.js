// ================================================================
// RoomSync — app.js
// ================================================================

let todasReservasCache = [];
let filtroAtual = "todos";

// ── Toast ────────────────────────────────────────────────────────
function toast(msg, tipo = "sucesso") {
  const container = document.getElementById("toastContainer");
  if (!container) return;

  const el = document.createElement("div");
  el.className = `toast-item toast-${tipo}`;
  el.textContent = msg;
  container.appendChild(el);

  setTimeout(() => {
    el.style.transition = "opacity 0.3s";
    el.style.opacity = "0";
    setTimeout(() => el.remove(), 300);
  }, 3500);
}

// ── Montar grade estilo cinema ───────────────────────────────────
function montarGrade() {
  const salaInput = document.getElementById("salaBusca");
  const dataInput = document.getElementById("dataBusca");
  if (!salaInput || !dataInput) return;

  const sala = salaInput.value;
  const data = dataInput.value;
  if (!data) return;

  const container = document.getElementById("gradeHorarios");
  container.innerHTML = '<div class="grade-loading">Buscando horários...</div>';

  fetch("api.php")
    .then((res) => res.json())
    .then((reservas) => {
      todasReservasCache = reservas;

      const reservasDaSala = reservas.filter(
        (r) => r.sala === sala && r.data === data && r.status !== "cancelada",
      );

      container.innerHTML = "";

      blocosFixos.forEach((bloco) => {
        const div = document.createElement("div");
        const reserva = reservasDaSala.find(
          (r) => r.horaInicio === bloco.inicio,
        );

        if (reserva) {
          if (reserva.status === "pendente") {
            const texto =
              papelAtual === "admin"
                ? `Solicitado por ${reserva.nome}`
                : "Aguardando Aprovação";

            div.innerHTML = `
                            <div class="bloco bloco-pendente">
                                <div class="bloco-periodo">${bloco.nome}</div>
                                <div class="bloco-horario-txt">${bloco.inicio} – ${bloco.fim}</div>
                                <span class="bloco-status">⏳ ${texto}</span>
                            </div>`;
          } else {
            const texto =
              papelAtual === "admin" ? `${reserva.nome}` : "Indisponível";

            div.innerHTML = `
                            <div class="bloco bloco-ocupado">
                                <div class="bloco-periodo">${bloco.nome}</div>
                                <div class="bloco-horario-txt">${bloco.inicio} – ${bloco.fim}</div>
                                <span class="bloco-status">✗ ${texto}</span>
                            </div>`;
          }
        } else {
          if (papelAtual === "admin") {
            div.innerHTML = `
                            <div class="bloco bloco-admin-livre" onclick="abrirModal('${sala}', '${data}', '${bloco.inicio}', '${bloco.fim}', '${bloco.nome}')">
                                <div class="bloco-periodo">${bloco.nome}</div>
                                <div class="bloco-horario-txt">${bloco.inicio} – ${bloco.fim}</div>
                                <span class="bloco-status">+ Criar reserva</span>
                            </div>`;
          } else {
            div.innerHTML = `
                            <div class="bloco bloco-livre" onclick="enviarSolicitacao('${sala}', '${data}', '${bloco.inicio}', '${bloco.fim}', '${bloco.nome}')">
                                <div class="bloco-periodo">${bloco.nome}</div>
                                <div class="bloco-horario-txt">${bloco.inicio} – ${bloco.fim}</div>
                                <span class="bloco-status">✓ Disponível</span>
                            </div>`;
          }
        }

        container.appendChild(div);
      });

      atualizarListaLateral(reservas);
    })
    .catch(() => {
      container.innerHTML =
        '<div class="grade-loading" style="color:#ef4444;">Erro ao carregar horários.</div>';
    });
}

// ── Cliente: enviar solicitação ──────────────────────────────────
function enviarSolicitacao(sala, data, horaInicio, horaFim, nome) {
  if (
    !confirm(
      `Confirmar solicitação para ${sala} das ${horaInicio} às ${horaFim}?`,
    )
  )
    return;

  fetch("api.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ sala, data, horaInicio, horaFim }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.erro) toast(data.erro, "erro");
      else {
        toast(data.mensagem, "sucesso");
        montarGrade();
      }
    })
    .catch(() => toast("Erro ao enviar solicitação.", "erro"));
}

// ── Admin: abrir modal ───────────────────────────────────────────
function abrirModal(sala, data, horaInicio, horaFim, nomeBloco) {
  document.getElementById("modalSala").value = sala;
  document.getElementById("modalData").value = data;
  document.getElementById("modalHoraInicio").value = horaInicio;
  document.getElementById("modalHoraFim").value = horaFim;
  document.getElementById("modalNome").value = "";
  document.getElementById("modalCpf").value = "";
  document.getElementById("modalEmail").value = "";
  document.getElementById("modalTelefone").value = "";

  document.getElementById("modalInfoReserva").innerHTML =
    `<strong>${sala}</strong> &nbsp;·&nbsp; ${data} &nbsp;·&nbsp; ${nomeBloco} (${horaInicio} – ${horaFim})`;

  document.getElementById("modalOverlay").style.display = "flex";
  setTimeout(() => document.getElementById("modalNome").focus(), 100);
}

function fecharModal() {
  document.getElementById("modalOverlay").style.display = "none";
}

// Fecha modal clicando fora
document.addEventListener("click", function (e) {
  const overlay = document.getElementById("modalOverlay");
  if (overlay && e.target === overlay) fecharModal();
});

// ── Admin: salvar reserva via modal ─────────────────────────────
function salvarReservaModal() {
  const nome = document.getElementById("modalNome").value.trim();
  const cpf = document.getElementById("modalCpf").value.trim();
  const email = document.getElementById("modalEmail").value.trim();
  const telefone = document.getElementById("modalTelefone").value.trim();
  const sala = document.getElementById("modalSala").value;
  const data = document.getElementById("modalData").value;
  const horaInicio = document.getElementById("modalHoraInicio").value;
  const horaFim = document.getElementById("modalHoraFim").value;

  if (!nome || !cpf) {
    toast("Nome e CPF são obrigatórios.", "erro");
    return;
  }

  fetch("api.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      nome,
      cpf,
      email,
      telefone,
      sala,
      data,
      horaInicio,
      horaFim,
    }),
  })
    .then((res) => res.json())
    .then((resp) => {
      if (resp.erro) {
        toast(resp.erro, "erro");
        return;
      }
      fecharModal();
      toast(resp.mensagem, "sucesso");
      montarGrade();
    })
    .catch(() => toast("Erro ao salvar reserva.", "erro"));
}

// ── Admin: aprovar ou cancelar ───────────────────────────────────
function mudarStatus(id, acao) {
  const msg =
    acao === "aprovar" ? "Aprovar esta solicitação?" : "Cancelar esta reserva?";
  if (!confirm(msg)) return;

  fetch("api.php", {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ id, acao }),
  })
    .then((res) => res.json())
    .then((resp) => {
      if (resp.erro) {
        toast(resp.erro, "erro");
        return;
      }
      toast(resp.mensagem, "sucesso");
      montarGrade();
    })
    .catch(() => toast("Erro ao atualizar reserva.", "erro"));
}

// ── Filtro lateral ───────────────────────────────────────────────
function filtrarLista(filtro, btn) {
  filtroAtual = filtro;
  document
    .querySelectorAll(".btn-filtro")
    .forEach((b) => b.classList.remove("ativo"));
  if (btn) btn.classList.add("ativo");
  atualizarListaLateral(todasReservasCache);
}

// ── Atualizar painel lateral ─────────────────────────────────────
function atualizarListaLateral(reservas) {
  const container = document.getElementById("listaReservas");
  const papel = papelAtual;

  // Conta pendentes para badge do admin
  if (papel === "admin") {
    const pendentes = reservas.filter((r) => r.status === "pendente").length;
    const badge = document.getElementById("contadorPendentes");
    if (badge) {
      badge.textContent = pendentes;
      badge.style.display = pendentes > 0 ? "inline" : "none";
    }
  }

  // Filtra conforme papel e filtro ativo
  let lista = reservas;
  if (papel !== "admin") {
    lista = reservas.filter((r) => r.nome === usuarioAtual);
  } else if (filtroAtual !== "todos") {
    lista = reservas.filter((r) => r.status === filtroAtual);
  }

  if (lista.length === 0) {
    container.innerHTML =
      '<div class="lista-vazia">Nenhuma reserva encontrada.</div>';
    return;
  }

  container.innerHTML = "";

  lista.forEach((item) => {
    const div = document.createElement("div");
    div.className = "reserva-item";

    const badgeClass = `badge-${item.status}`;
    const badgeTexto =
      { ativa: "Ativa", pendente: "Pendente", cancelada: "Cancelada" }[
        item.status
      ] || item.status;

    let detalhesAdmin = "";
    if (papel === "admin") {
      detalhesAdmin = `<div class="reserva-nome">Por: ${item.nome}</div>`;
      if (item.cpf && item.cpf !== "***") {
        detalhesAdmin += `<div class="reserva-info" style="font-size:0.72rem; margin-top:0.1rem;">CPF: ${item.cpf} | Tel: ${item.telefone}</div>`;
      }
    }

    let acoes = "";
    if (papel === "admin" && item.status !== "cancelada") {
      if (item.status === "pendente") {
        acoes += `<button class="btn-acao btn-aprovar" onclick="mudarStatus(${item.id}, 'aprovar')">✓ Aprovar</button>`;
      }
      acoes += `<button class="btn-acao btn-cancelar" onclick="mudarStatus(${item.id}, 'cancelar')">✗ Cancelar</button>`;
    }

    div.innerHTML = `
            <div class="reserva-sala">
                ${item.sala}
                <span class="badge-status ${badgeClass}">${badgeTexto}</span>
            </div>
            <div class="reserva-info">${item.data} · ${item.horaInicio} – ${item.horaFim}</div>
            ${detalhesAdmin}
            ${acoes ? `<div class="reserva-actions">${acoes}</div>` : ""}
        `;

    container.appendChild(div);
  });
}

window.onload = montarGrade;
