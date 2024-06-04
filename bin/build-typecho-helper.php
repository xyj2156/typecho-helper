<?php

$dir = realpath(__DIR__ . '/../..');

$inc = $dir . '/config.inc.php';

if (!file_exists($inc)) {
    echo 'typecho 可能未安装', PHP_EOL;
    exit(1);
}

$content = @file_get_contents($inc);
if (!$content) {
    echo '读取typecho配置失败', PHP_EOL;
    exit(1);
}


/**
 * 扫描目录
 *
 * @param string $path
 *
 * @return Generator
 */
function scan(string $path) :Generator
{
    $handle = opendir($path);
    if (!$path) {
        yield ['type' => 'error', 'basename' => '', 'fullname' => ''];
    }
    while (false !== ($basename = readdir($handle))) {
        if ($basename == '.' or $basename == '..') {
            continue;
        }
        $fullname = implode(DIRECTORY_SEPARATOR, [$path, $basename]);
        $type     = @filetype($fullname);
        if (!$type) {
            $type = 'error';
        }
        $info             = pathinfo($fullname);
        $info['type']     = $type;
        $info['fullname'] = $fullname;
        yield $info;
    }
    closedir($handle);
}


(new class {
    private $root;
    private $filename = 'typecho-helper.php';

    private $dir;

    private $cls    = [];
    private $spider = '_';

    public function __invoke($dir = null)
    {
        if (empty($this->dir)) {
            $this->root = $dir;
            $this->dir  = $dir . '/var/';
            $dir        = $this->dir . '/Typecho';
        }
        foreach (scan($dir) as $file_info) {
            /**
             * @var string $dirname
             * @var string $basename
             * @var string $extension
             * @var string $filename
             * @var string $type
             * @var string $fullname
             */
            extract($file_info);
            if ($type === 'dir') {
//                目录继续处理
                $this($fullname);
                continue;
            }
            if ($type !== 'file' and $extension !== 'php') {
                continue;
            }

            $cls = ltrim($fullname, $this->dir);
            $cls = rtrim($cls, '.php');

            $this->cls[] = $cls;
        }
    }

    public function __destruct()
    {
        $now     = date('Y/m/d H:i:s');
        $content = <<<CONTENT
<?php
/**
 * @author 阿杰
 * @coding_time 2024年6月4日08:45:07
 * @build_time {$now}
 */


CONTENT;

        foreach ($this->cls as &$cls) {
            $t   = str_replace(DIRECTORY_SEPARATOR, '\\', $cls);
            $m   = str_replace(DIRECTORY_SEPARATOR, $this->spider, $cls);
            $cls = "class_alias('{$t}','{$m}');";
        }

        $content .= implode(PHP_EOL, $this->cls);

        file_put_contents($this->root . '/' . $this->filename, $content);
    }
})($dir);
