let todasReservasCache = [];
let filtroAtual = "todos";

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

function montarGrade() {
  const sala = document.getElementById("salaBusca").value;
  const data = document.getElementById("dataBusca").value;
  if (!sala || !data) return;

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
            div.innerHTML = `<div class="bloco bloco-pendente"><div class="bloco-periodo">${bloco.nome}</div><div class="bloco-horario-txt">${bloco.inicio} – ${bloco.fim}</div><span class="bloco-status">⏳ ${texto}</span></div>`;
          } else {
            const texto =
              papelAtual === "admin" ? `${reserva.nome}` : "Indisponível";
            div.innerHTML = `<div class="bloco bloco-ocupado"><div class="bloco-periodo">${bloco.nome}</div><div class="bloco-horario-txt">${bloco.inicio} – ${bloco.fim}</div><span class="bloco-status">✗ ${texto}</span></div>`;
          }
        } else {
          if (papelAtual === "admin") {
            div.innerHTML = `<div class="bloco bloco-admin-livre" onclick="abrirModal('${sala}', '${data}', '${bloco.inicio}', '${bloco.fim}', '${bloco.nome}')"><div class="bloco-periodo">${bloco.nome}</div><div class="bloco-horario-txt">${bloco.inicio} – ${bloco.fim}</div><span class="bloco-status">+ Criar reserva</span></div>`;
          } else {
            div.innerHTML = `<div class="bloco bloco-livre" onclick="enviarSolicitacao('${sala}', '${data}', '${bloco.inicio}', '${bloco.fim}', '${bloco.nome}')"><div class="bloco-periodo">${bloco.nome}</div><div class="bloco-horario-txt">${bloco.inicio} – ${bloco.fim}</div><span class="bloco-status">✓ Disponível</span></div>`;
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

function abrirModal(sala, data, horaInicio, horaFim, nomeBloco) {
  document.getElementById("modalSala").value = sala;
  document.getElementById("modalData").value = data;
  document.getElementById("modalHoraInicio").value = horaInicio;
  document.getElementById("modalHoraFim").value = horaFim;
  document.getElementById("modalInfoReserva").innerHTML =
    `<strong>${sala}</strong> &nbsp;·&nbsp; ${data} &nbsp;·&nbsp; ${nomeBloco} (${horaInicio} – ${horaFim})`;
  document.getElementById("modalOverlay").style.display = "flex";
}

function fecharModal() {
  document.getElementById("modalOverlay").style.display = "none";
}

function salvarReservaModal() {
  const sala = document.getElementById("modalSala").value;
  const data = document.getElementById("modalData").value;
  const horaInicio = document.getElementById("modalHoraInicio").value;
  const horaFim = document.getElementById("modalHoraFim").value;

  fetch("api.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ sala, data, horaInicio, horaFim }),
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
    });
}

function mudarStatus(id, acao) {
  let msg = "";
  if (acao === "aprovar") msg = "Aprovar esta solicitação?";
  else if (acao === "cancelar") msg = "Cancelar esta reserva?";
  else if (acao === "concluir") msg = "Marcar esta reserva como concluída?";

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
    });
}

function filtrarLista(filtro, btn) {
  filtroAtual = filtro;
  document
    .querySelectorAll(".btn-filtro")
    .forEach((b) => b.classList.remove("ativo"));
  if (btn) btn.classList.add("ativo");
  atualizarListaLateral(todasReservasCache);
}

function atualizarListaLateral(reservas) {
  const container = document.getElementById("listaReservas");
  if (papelAtual === "admin") {
    const pendentes = reservas.filter((r) => r.status === "pendente").length;
    const badge = document.getElementById("contadorPendentes");
    if (badge) {
      badge.textContent = pendentes;
      badge.style.display = pendentes > 0 ? "inline" : "none";
    }
  }

  let lista = reservas;
  if (papelAtual !== "admin")
    lista = reservas.filter((r) => r.nome === usuarioAtual);
  else if (filtroAtual !== "todos")
    lista = reservas.filter((r) => r.status === filtroAtual);

  if (lista.length === 0) {
    container.innerHTML =
      '<div class="lista-vazia">Nenhuma reserva encontrada.</div>';
    return;
  }
  container.innerHTML = "";

  lista.forEach((item) => {
    const div = document.createElement("div");
    div.className = "reserva-item";

    // Suporte ao status Concluída
    const badgeClass = `badge-${item.status}`;
    const badgeTexto =
      {
        ativa: "Ativa",
        pendente: "Pendente",
        cancelada: "Cancelada",
        concluída: "Concluída",
      }[item.status] || item.status;

    let detalhesAdmin = "";
    if (papelAtual === "admin") {
      detalhesAdmin = `<div class="reserva-nome">Por: ${item.nome}</div>`;
      if (item.cpf && item.cpf !== "***") {
        detalhesAdmin += `<div class="reserva-info" style="font-size:0.72rem; margin-top:0.1rem;">Tel: ${item.telefone}</div>`;
      }
    }

    let acoes = "";
    if (
      papelAtual === "admin" &&
      item.status !== "cancelada" &&
      item.status !== "concluída"
    ) {
      if (item.status === "pendente")
        acoes += `<button class="btn-acao btn-aprovar" onclick="mudarStatus(${item.id}, 'aprovar')">✓ Aprovar</button>`;
      if (item.status === "ativa")
        acoes += `<button class="btn-acao btn-aprovar" style="background:#dbeafe; color:#1d4ed8;" onclick="mudarStatus(${item.id}, 'concluir')">✔ Concluir</button>`;
      acoes += `<button class="btn-acao btn-cancelar" onclick="mudarStatus(${item.id}, 'cancelar')">✗ Cancelar</button>`;
    }

    div.innerHTML = `<div class="reserva-sala">${item.sala} <span class="badge-status ${badgeClass}">${badgeTexto}</span></div><div class="reserva-info">${item.data} · ${item.horaInicio} – ${item.horaFim}</div>${detalhesAdmin}${acoes ? `<div class="reserva-actions">${acoes}</div>` : ""}`;
    container.appendChild(div);
  });
}
window.onload = montarGrade;
