<?php
// 这是我们要测试的文件名（必须和数据库里一模一样）
$test_file = 'images/infinity_gauntlet.jpg';

echo "<h1>Jarvis System Diagnostics</h1>";
echo "<hr>";

// 1. 检查当前 PHP 在哪个文件夹运行
echo "<p><strong>Current Working Directory:</strong> " . getcwd() . "</p>";

// 2. 检查 PHP 试图寻找的完整路径
$full_path = getcwd() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $test_file);
echo "<p><strong>Looking for file at:</strong> " . $full_path . "</p>";

// 3. 检查文件是否存在
if (file_exists($test_file)) {
    echo "<h2 style='color:green'>SUCCESS: File Found!</h2>";
    echo "<p>PHP can see the file.</p>";
    echo "<p>Let's try to display it:</p>";
    echo "<img src='$test_file' width='200' />";
} else {
    echo "<h2 style='color:red'>FAILURE: File Not Found</h2>";
    echo "<p>PHP cannot find the file. Please check:</p>";
    echo "<ul>";
    echo "<li>Is the file name exactly <code>infinity_gauntlet.jpg</code>? (Check for .jpg.png)</li>";
    echo "<li>Is it inside the <code>images</code> folder?</li>";
    echo "</ul>";
    
    // 列出 images 文件夹里到底有什么
    if (is_dir('images')) {
        echo "<p><strong>Files actually found inside 'images/' folder:</strong></p>";
        $files = scandir('images');
        echo "<pre>";
        print_r($files);
        echo "</pre>";
    } else {
        echo "<p style='color:red'>CRITICAL: The 'images' folder itself was not found!</p>";
    }
}
?>