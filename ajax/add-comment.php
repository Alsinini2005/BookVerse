<?php
require_once "../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to comment.']);
    exit();
}

$commentText = trim($_POST['comment_text'] ?? '');
$book_id     = (int)($_POST['book_id'] ?? 0);
$user_id     = (int)$_SESSION['user_id'];

if (empty($commentText)) {
    echo json_encode(['success' => false, 'message' => 'Comment cannot be empty.']);
    exit();
}

if (strlen($commentText) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Comment must be under 1000 characters.']);
    exit();
}

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid book.']);
    exit();
}

$sql  = "INSERT INTO dbproj_comments (book_id, user_id, comment_text) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $book_id, $user_id, $commentText);

if ($stmt->execute()) {
    echo json_encode([
        'success'    => true,
        'message'    => 'Comment posted!',
        'name'       => $_SESSION['name'],
        'comment'    => $commentText,
        'created_at' => date('M j, Y g:i a'),
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to post comment.']);
}
?>
