<?php
// -- Início: configuração da API (cabeçalho e sessão) --
require_once 'conexao.php'; // Importa a conexão com o banco SQLite
header('Content-Type: application/json'); // Todas as respostas desta API são JSON

session_start();

// Bloqueia acesso direto à API sem estar logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado.']);
    exit;
}

// Recupera dados da sessão para controle de permissões
$papelUsuario = $_SESSION['usuario_papel'] ?? 'cliente'; // 'admin' ou 'cliente'
$usuarioLogado = $_SESSION['usuario_nome'] ?? '';

$method = $_SERVER['REQUEST_METHOD'];                         // GET, POST, PUT ou DELETE
$input  = json_decode(file_get_contents('php://input'), true); // Lê o corpo JSON das requisições
// -- Fim: configuração da API --


// ================================================================
// -- Início: GET — listar todas as reservas --
// ================================================================
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM reservas ORDER BY data, horaInicio");
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // -- Início: proteção de dados pessoais para clientes --
    // O cliente vê os status de todos os horários, mas só os dados pessoais das próprias reservas
    if ($papelUsuario !== 'admin') {
        foreach ($reservas as &$res) {
            if ($res['nome'] !== $usuarioLogado) {
                $res['nome']     = 'Horário Indisponível'; // Esconde o nome de outros clientes
                $res['cpf']      = '***';
                $res['email']    = '***';
                $res['telefone'] = '***';
            }
        }
    }
    // -- Fim: proteção de dados pessoais --

    echo json_encode($reservas);
    exit;
}
// -- Fim: GET --


// ================================================================
// -- Início: POST — criar nova reserva ou solicitação --
// ================================================================
if ($method === 'POST') {
    // -- Início: leitura e limpeza dos dados recebidos --
    $sala       = trim($input['sala'] ?? '');
    $data       = trim($input['data'] ?? '');
    $horaInicio = trim($input['horaInicio'] ?? '');
    $horaFim    = trim($input['horaFim'] ?? '');
    // -- Fim: leitura dos dados --

    // -- Início: validação dos campos obrigatórios --
    if (!$sala || !$data || !$horaInicio || !$horaFim) {
        http_response_code(400);
        echo json_encode(['erro' => 'Campos obrigatórios faltando.']);
        exit;
    }
    // -- Fim: validação dos campos obrigatórios --

    // -- Início: bloqueio de datas passadas --
    $hoje = date('Y-m-d');
    if ($data < $hoje) {
        http_response_code(400);
        echo json_encode(['erro' => 'Não é possível reservar em datas passadas.']);
        exit;
    }
    // -- Fim: bloqueio de datas passadas --

    // -- Início: definição de dados e status conforme o papel do usuário --
    if ($papelUsuario === 'cliente') {
        // Cliente: usa dados já salvos na sessão no momento do login
        $nome     = $_SESSION['usuario_nome'];
        $cpf      = $_SESSION['cliente_cpf'];
        $email    = $_SESSION['cliente_email'];
        $telefone = $_SESSION['cliente_telefone'];
        $status   = 'pendente'; // Solicitação — precisa de aprovação do admin
    } else {
        // Admin: recebe os dados do cliente pelo corpo da requisição
        $nome     = trim($input['nome'] ?? '');
        $cpf      = trim($input['cpf'] ?? '');
        $email    = trim($input['email'] ?? '');
        $telefone = trim($input['telefone'] ?? '');
        $status   = 'ativa'; // Admin cria reserva já aprovada diretamente

        if (!$nome || !$cpf) {
            http_response_code(400);
            echo json_encode(['erro' => 'Nome e CPF são obrigatórios.']);
            exit;
        }
    }
    // -- Fim: definição de dados e status --

    // -- Início: verificação de conflito de horário no banco --
    // Impede reservas sobrepostas (ativas ou pendentes) no mesmo horário e sala
    $sqlConflito = "SELECT id FROM reservas
                    WHERE sala = ? AND data = ? AND status IN ('ativa', 'pendente')
                    AND (horaInicio < ? AND horaFim > ?)";
    $stmt = $pdo->prepare($sqlConflito);
    $stmt->execute([$sala, $data, $horaFim, $horaInicio]);

    if ($stmt->fetch()) {
        http_response_code(409); // 409 Conflict
        echo json_encode(['erro' => 'Horário já reservado ou com solicitação em andamento.']);
        exit;
    }
    // -- Fim: verificação de conflito --

    // -- Início: inserção da reserva no banco de dados --
    $sqlInsert = "INSERT INTO reservas (nome, cpf, email, telefone, sala, data, horaInicio, horaFim, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([$nome, $cpf, $email, $telefone, $sala, $data, $horaInicio, $horaFim, $status]);
    // -- Fim: inserção no banco --

    http_response_code(201); // 201 Created
    $msg = ($papelUsuario === 'cliente') ? 'Solicitação enviada! Aguarde aprovação.' : 'Reserva criada com sucesso.';
    echo json_encode(['mensagem' => $msg]);
    exit;
}
// -- Fim: POST --


// ================================================================
// -- Início: PUT — aprovar ou cancelar reserva (somente admin) --
// ================================================================
if ($method === 'PUT') {
    // -- Início: bloqueio de acesso para não-admin --
    if ($papelUsuario !== 'admin') {
        http_response_code(403); // 403 Forbidden
        echo json_encode(['erro' => 'Acesso negado. Apenas administradores podem alterar reservas.']);
        exit;
    }
    // -- Fim: bloqueio de acesso --

    // -- Início: validação dos parâmetros recebidos --
    $id   = intval($input['id'] ?? 0);
    $acao = trim($input['acao'] ?? ''); // 'aprovar' ou 'cancelar'

    if (!$id || !in_array($acao, ['aprovar', 'cancelar'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Parâmetros inválidos.']);
        exit;
    }
    // -- Fim: validação --

    // -- Início: atualização do status da reserva no banco --
    $novoStatus = ($acao === 'aprovar') ? 'ativa' : 'cancelada';
    $stmt = $pdo->prepare("UPDATE reservas SET status = ? WHERE id = ?");
    $stmt->execute([$novoStatus, $id]);
    // -- Fim: atualização do status --

    $msg = ($acao === 'aprovar') ? 'Reserva aprovada com sucesso.' : 'Reserva cancelada.';
    echo json_encode(['mensagem' => $msg]);
    exit;
}
// -- Fim: PUT --


// ================================================================
// -- Início: DELETE — remover registro cancelado (somente admin) --
// ================================================================
if ($method === 'DELETE') {
    // -- Início: bloqueio de acesso para não-admin --
    if ($papelUsuario !== 'admin') {
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso negado.']);
        exit;
    }
    // -- Fim: bloqueio de acesso --

    // -- Início: remoção do registro no banco (somente se já estiver cancelado) --
    $id = intval($input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID inválido.']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM reservas WHERE id = ? AND status = 'cancelada'");
    $stmt->execute([$id]);
    echo json_encode(['mensagem' => 'Registro removido.']);
    exit;
    // -- Fim: remoção do registro --
}
// -- Fim: DELETE --


// Método HTTP não suportado
http_response_code(405);
echo json_encode(['erro' => 'Método não permitido.']);
?>
