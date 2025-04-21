<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use Admin\Auth;
use Admin\WhitelistManager;

Auth::getInstance()->requireLogin();
$whitelistManager = WhitelistManager::getInstance();

$message = '';
$error = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $urlPattern = $_POST['url_pattern'] ?? '';
            $fileType = $_POST['file_type'] ?? '';
            $description = $_POST['description'] ?? '';
            
            if ($whitelistManager->addRule($urlPattern, $fileType, $description)) {
                $message = '规则添加成功';
            } else {
                $error = '规则添加失败';
            }
            break;
            
        case 'update':
            $id = $_POST['id'] ?? '';
            $urlPattern = $_POST['url_pattern'] ?? '';
            $fileType = $_POST['file_type'] ?? '';
            $description = $_POST['description'] ?? '';
            
            if ($whitelistManager->updateRule($id, $urlPattern, $fileType, $description)) {
                $message = '规则更新成功';
            } else {
                $error = '规则更新失败';
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? '';
            if ($whitelistManager->deleteRule($id)) {
                $message = '规则删除成功';
            } else {
                $error = '规则删除失败';
            }
            break;
    }
}

// 获取所有规则
$rules = $whitelistManager->getAllRules();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>白名单规则管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>白名单规则管理</h2>
            <a href="logout.php" class="btn btn-outline-danger">退出登录</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- 添加新规则表单 -->
        <div class="card mb-4">
            <div class="card-header">添加新规则</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="url_pattern" class="form-label">URL模式 (正则表达式)</label>
                            <input type="text" class="form-control" id="url_pattern" name="url_pattern" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="file_type" class="form-label">允许的文件类型 (用逗号分隔)</label>
                            <input type="text" class="form-control" id="file_type" name="file_type" placeholder="例如: pdf,doc,docx">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="description" class="form-label">描述</label>
                            <input type="text" class="form-control" id="description" name="description">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">添加规则</button>
                </form>
            </div>
        </div>

        <!-- 规则列表 -->
        <div class="card">
            <div class="card-header">现有规则</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>URL模式</th>
                                <th>文件类型</th>
                                <th>描述</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rules as $rule): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rule['id']); ?></td>
                                <td><?php echo htmlspecialchars($rule['url_pattern']); ?></td>
                                <td><?php echo htmlspecialchars($rule['file_type']); ?></td>
                                <td><?php echo htmlspecialchars($rule['description']); ?></td>
                                <td><?php echo htmlspecialchars($rule['created_at']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $rule['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('确定要删除这条规则吗？')">删除</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>