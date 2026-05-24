<?php
require_once 'conexao.php';
header('Content-Type: application/json');
session_start();

// Bloqueia se não estiver logado
if (!isset($_SESSION['logado'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado.']);
    exit;
}

$papelUsuario = $_SESSION['usuario_papel'] ?? 'cliente';
$usuarioId = $_SESSION['usuario_id'] ?? 0;
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// 1. BUSCAR RESERVAS (GET)
if ($method === 'GET') {
    // Faz um JOIN para pegar o nome do usuário que fez a reserva
    $sql = "SELECT r.*, u.nome, u.cpf, u.telefone 
            FROM reservas r 
            JOIN usuarios u ON r.usuario_id = u.id 
            ORDER BY r.data, r.horaInicio";
    
    $stmt = $pdo->query($sql);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Esconde os dados de outros clientes se não for admin
    if ($papelUsuario !== 'admin') {
        foreach ($reservas as &$r) {
            if ($r['usuario_id'] != $usuarioId) {
                $r['nome'] = 'Ocupado';
                $r['cpf'] = '***';
                $r['telefone'] = '***';
            }
        }
    }
    echo json_encode($reservas);
    exit;
}

// 2. CRIAR RESERVA (POST)
if ($method === 'POST') {
    $sala = $input['sala'] ?? '';
    $data = $input['data'] ?? '';
    $horaInicio = $input['horaInicio'] ?? '';
    $horaFim = $input['horaFim'] ?? '';

    if (!$sala || !$data || !$horaInicio || !$horaFim) {
        http_response_code(400);
        echo json_encode(['erro' => 'Dados incompletos.']);
        exit;
    }

    try {
        // Insere a reserva usando apenas o ID do usuário logado
        $stmt = $pdo->prepare("INSERT INTO reservas (usuario_id, sala, data, horaInicio, horaFim, status) VALUES (?, ?, ?, ?, ?, 'pendente')");
        $stmt->execute([$usuarioId, $sala, $data, $horaInicio, $horaFim]);
        echo json_encode(['mensagem' => 'Solicitação enviada com sucesso!']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao salvar reserva no banco.']);
    }
    exit;
}

// 3. ATUALIZAR STATUS (PUT)
if ($method === 'PUT') {
    if ($papelUsuario !== 'admin') {
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso negado.']);
        exit;
    }

    $id = $input['id'] ?? 0;
    $acao = $input['acao'] ?? ''; 

    $novoStatus = '';
    if ($acao === 'aprovar') $novoStatus = 'ativa';
    elseif ($acao === 'cancelar') $novoStatus = 'cancelada';
    elseif ($acao === 'concluir') $novoStatus = 'concluída'; // O novo status da professora!

    if (!$id || !$novoStatus) {
        http_response_code(400);
        echo json_encode(['erro' => 'Ação inválida.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE reservas SET status = ? WHERE id = ?");
        $stmt->execute([$novoStatus, $id]);
        echo json_encode(['mensagem' => "Reserva marcada como $novoStatus!"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao atualizar status.']);
    }
    exit;
}