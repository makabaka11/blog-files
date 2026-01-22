<?php
// 开启错误显示（测试环境必备，排查语法/执行错误）
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<h1><center>DNSLog注入测试</center></h1>';

// 1. 配置连接
$servername = "127.0.0.1";
$username = "root";
$password = "root";
$dbname = "test";		//数据库库名

// 2. 创建链接并设置字符集
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4"); // 关键：设置字符集，避免拼接字符串乱码

// 3. 检测连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 4. 验证load_file函数是否可用（关键前置检查）
$check_sql = "SELECT @@secure_file_priv;";
$check_result = $conn->query($check_sql);
$secure_file_priv = $check_result->fetch_row()[0];
echo "<p><b>secure_file_priv参数值：</b>" . htmlspecialchars($secure_file_priv) . "</p>";
if ($secure_file_priv === NULL) {
    echo "<p style='color:red;'>警告：secure_file_priv=NULL，load_file函数被禁用！</p>";
} elseif ($secure_file_priv === "") {
    echo "<p style='color:green;'>提示：secure_file_priv为空，load_file函数可用！</p>";
} else {
    echo "<p style='color:orange;'>提示：load_file仅允许访问路径：" . htmlspecialchars($secure_file_priv) . "</p>";
}

// 5. 处理SQL注入测试
$id = isset($_GET['id']) ? $_GET['id'] : '';
// 拼接SQL语句（保留注入漏洞，用于测试）
$sql = "select * from db1 where id='{$id}'";
// 打印实际执行的SQL（调试关键，确认payload是否正确拼接）
echo "<p><b>执行的SQL语句：</b>" . htmlspecialchars($sql) . "</p>";

// 执行SQL并处理错误
$result = $conn->query($sql);
if ($result === false) {
    // 打印SQL语法错误（关键：排查payload语法问题）
    echo "<p style='color:red;'>SQL执行错误：" . $conn->error . "</p>";
} else {
    // 正常处理查询结果
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()){
            echo $row['id'].'-----'.$row['uid'].'-----'.$row['job'].'<br/>';
        }
    } else {
        echo "<p>无查询结果（但SQL已执行，需检查DNSLog）</p>";
    }
    $result->close();
}

$conn->close();
?>