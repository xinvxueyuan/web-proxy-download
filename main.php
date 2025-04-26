<?php if (!isset($whitelist) || !is_array($whitelist)) { $whitelist = []; } ?>
<div class="center-container">
    <div class="main-title text-primary text-center">简单代理下载</div>
    <form method="post" class="download-form">
        <div class="mb-3">
            <input type="url" class="form-control" id="url" name="url" placeholder="请输入文件下载链接" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-100">下载</button>
    </form>
    <?php if ($error): ?>
        <div class="alert alert-danger mt-3 w-100 text-center" role="alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <div class="alert alert-secondary mt-4 p-2 small text-center w-100" role="alert">
        仅支持以下白名单域名：<br><?php echo implode(', ', $whitelist); ?>
    </div>
</div>