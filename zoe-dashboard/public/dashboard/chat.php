<?php
$pageTitle = 'AI Chat';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/deepseek.php';
require_once __DIR__ . '/../includes/layout.php';

requireLogin();
$base = getenv('APP_URL') ?: '';
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || $_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }

    $action = $_POST['action'] ?? 'send';

    if ($action === 'clear') {
        $stmt = $pdo->prepare("DELETE FROM chat_history WHERE user_id = ?");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true]);
        exit;
    }

    if (!isset($_POST['message']) || trim($_POST['message']) === '') {
        echo json_encode(['error' => 'Message cannot be empty']);
        exit;
    }

    $message = trim($_POST['message']);

    $stmt = $pdo->prepare("INSERT INTO chat_history (user_id, role, content) VALUES (?, 'user', ?)");
    $stmt->execute([$userId, $message]);

    try {
        $historyStmt = $pdo->prepare("SELECT role, content FROM chat_history WHERE user_id = ? ORDER BY created_at ASC");
        $historyStmt->execute([$userId]);
        $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

        $response = getDeepSeek()->withHistory($history);

        $stmt = $pdo->prepare("INSERT INTO chat_history (user_id, role, content) VALUES (?, 'assistant', ?)");
        $stmt->execute([$userId, $response]);

        echo json_encode(['response' => $response]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to get response: ' . $e->getMessage()]);
    }
    exit;
}

$historyStmt = $pdo->prepare("SELECT role, content, created_at FROM chat_history WHERE user_id = ? ORDER BY created_at ASC");
$historyStmt->execute([$userId]);
$chatHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1 class="page-title">AI Chat</h1>
    <button class="btn btn-danger btn-sm" onclick="clearChat()">Clear History</button>
</div>

<div class="chat-box">
    <div class="chat-header">
        <span>DeepSeek AI Assistant</span>
    </div>
    <div class="chat-messages" id="chatMessages">
        <?php if (empty($chatHistory)): ?>
            <div class="chat-msg">
                <div class="chat-bubble assistant">Hello! How can I help you today?</div>
            </div>
        <?php else: ?>
            <?php foreach ($chatHistory as $msg): ?>
                <div class="chat-msg <?= htmlspecialchars($msg['role']) ?>">
                    <div class="chat-bubble <?= htmlspecialchars($msg['role']) ?>">
                        <?= nl2br(htmlspecialchars($msg['content'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <form class="chat-input" id="chatForm" onsubmit="sendMessage(event)">
        <input type="text" id="messageInput" placeholder="Type your message..." autocomplete="off" required>
        <button type="submit" class="btn btn-primary">Send</button>
    </form>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const chatMessages = document.getElementById('chatMessages');
const messageInput = document.getElementById('messageInput');

function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

scrollToBottom();

async function sendMessage(e) {
    e.preventDefault();
    const message = messageInput.value.trim();
    if (!message) return;

    appendMessage('user', message);
    messageInput.value = '';

    showTyping();

    try {
        const formData = new FormData();
        formData.append('message', message);

        const response = await fetch('', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
        });

        const data = await response.json();
        hideTyping();

        if (data.error) {
            appendMessage('assistant', 'Error: ' + data.error);
        } else {
            appendMessage('assistant', data.response);
        }
    } catch (err) {
        hideTyping();
        appendMessage('assistant', 'Network error. Please try again.');
    }
}

function appendMessage(role, content) {
    const div = document.createElement('div');
    div.className = 'chat-msg ' + role;
    div.innerHTML = '<div class="chat-bubble ' + role + '">' + escapeHtml(content).replace(/\n/g, '<br>') + '</div>';
    chatMessages.appendChild(div);
    scrollToBottom();
}

function showTyping() {
    const div = document.createElement('div');
    div.className = 'chat-msg assistant';
    div.id = 'typingIndicator';
    div.innerHTML = '<div class="typing">Thinking...</div>';
    chatMessages.appendChild(div);
    scrollToBottom();
}

function hideTyping() {
    const el = document.getElementById('typingIndicator');
    if (el) el.remove();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function clearChat() {
    if (!confirm('Clear all chat history?')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'clear');

        await fetch('', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
        });

        chatMessages.innerHTML = '';
        appendMessage('assistant', 'Hello! How can I help you today?');
    } catch (err) {
        alert('Failed to clear chat.');
    }
}
</script>

</div>
</body>
</html>
