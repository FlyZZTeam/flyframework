<div>
    <h2>用户资料</h2>
    <?php foreach ($userInfos as $val): ?>
        <div>用户名:<?php echo $val['username'] ?></div>
        <div>密码:<?php echo $val['password']; ?></div>
    <?php endforeach; ?>
</div>