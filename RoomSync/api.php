<?php
require_once 'conexao.php';
header('Content-Type: application/json');

session_start();

// Redireciona se não estiver logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado.']);
    exit;
}

$papelUsuario = $_SESSION['usuario_papel'] ?? 'cliente';
$usuarioLogado = $_SESSION['usuario_nome'] ?? '';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// --- GET: Listar Reservas ---
if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM reservas ORDER BY data, horaInicio");
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($papelUsuario !== 'admin') {
        foreach ($reservas as &$res) {
            if ($res['nome'] !== $usuarioLogado) {
                $res['nome'] = 'Horário Indisponível';
            }
            $res['cpf']      = '***';
            $res['email']    = '***';
            $res['telefone'] = '***';
        }
    }
    echo json_encode($reservas);
    exit;
}

// --- POST: Criar Reserva / Solicitação ---
if ($method === 'POST') {
    $sala       = trim($input['sala'] ?? '');
    $data       = trim($input['data'] ?? '');
    $horaInicio = trim($input['horaInicio'] ?? '');
    $horaFim    = trim($input['horaFim'] ?? '');

    // Valida campos obrigatórios
    if (!$sala || !$data || !$horaInicio || !$horaFim) {
        http_response_code(400);
        echo json_encode(['erro' => 'Campos obrigatórios faltando.']);
        exit;
    }

    // Bloqueia datas passadas
    $hoje = date('Y-m-d');
    if ($data < $hoje) {
        http_response_code(400);
        echo json_encode(['erro' => 'Não é possível reservar em datas passadas.']);
        exit;
    }

    // Define dados e status conforme o papel
    if ($papelUsuario === 'cliente') {
        $nome     = $_SESSION['usuario_nome'];
        $cpf      = $_SESSION['cliente_cpf'];
        $email    = $_SESSION['cliente_email'];
        $telefone = $_SESSION['cliente_telefone'];
        $status   = 'pendente';
    } else {
        $nome     = trim($input['nome'] ?? '');
        $cpf      = trim($input['cpf'] ?? '');
        $email    = trim($input['email'] ?? '');
        $telefone = trim($input['telefone'] ?? '');
        $status   = 'ativa';

        if (!$nome || !$cpf) {
            http_response_code(400);
            echo json_encode(['erro' => 'Nome e CPF são obrigatórios.']);
            exit;
        }
    }

    // Verifica conflito de horário (ativa ou pendente)
    $sqlConflito = "SELECT id FROM reservas 
                    WHERE sala = ? AND data = ? AND status IN ('ativa', 'pendente') 
                    AND (horaInicio < ? AND horaFim > ?)";
    $stmt = $pdo->prepare($sqlConflito);
    $stmt->execute([$sala, $data, $horaFim, $horaInicio]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['erro' => 'Horário já reservado ou com solicitação em andamento.']);
        exit;
    }

    $sqlInsert = "INSERT INTO reservas (nome, cpf, email, telefone, sala, data, horaInicio, horaFim, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sqlInsert);
    $stmt->execute([$nome, $cpf, $email, $telefone, $sala, $data, $horaInicio, $horaFim, $status]);

    http_response_code(201);
    $msg = ($papelUsuario === 'cliente') ? 'Solicitação enviada! Aguarde aprovação.' : 'Reserva criada com sucesso.';
    echo json_encode(['mensagem' => $msg]);
    exit;
}

// --- PUT: Aprovar ou Cancelar (somente admin) ---
if ($method === 'PUT') {
    if ($papelUsuario !== 'admin') {
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso negado. Apenas administradores podem alterar reservas.']);
        exit;
    }

    $id   = intval($input['id'] ?? 0);
    $acao = trim($input['acao'] ?? '');

    if (!$id || !in_array($acao, ['aprovar', 'cancelar'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Parâmetros inválidos.']);
        exit;
    }

    $novoStatus = ($acao === 'aprovar') ? 'ativa' : 'cancelada';
    $stmt = $pdo->prepare("UPDATE reservas SET status = ? WHERE id = ?");
    $stmt->execute([$novoStatus, $id]);

    $msg = ($acao === 'aprovar') ? 'Reserva aprovada com sucesso.' : 'Reserva cancelada.';
    echo json_encode(['mensagem' => $msg]);
    exit;
}

// --- DELETE: Remover reserva cancelada (somente admin) ---
if ($method === 'DELETE') {
    if ($papelUsuario !== 'admin') {
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso negado.']);
        exit;
    }

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
}

http_response_code(405);
echo json_encode(['erro' => 'Método não permitido.']);
?>