<?php

namespace app\api\controller\v1\ai;

/**
 * Markdown -> HTML（基于 Parsedown）
 * 完整支持 GFM：标题、粗体/斜体/删除线、行内代码、代码块、
 * 有序/无序/嵌套列表、任务列表、表格、引用、分隔线、链接、图片。
 */
class Markdown
{
    /** @var \Parsedown|null */
    private static $parsedown = null;

    private static function pd(): \Parsedown
    {
        if (self::$parsedown === null) {
            require_once __DIR__ . '/Parsedown.php';
            self::$parsedown = new \Parsedown();
            self::$parsedown->setSafeMode(true); // 防 XSS，转义原始 HTML
            self::$parsedown->setBreaksEnabled(true); // 单换行转 <br>，适配聊天输出
        }
        return self::$parsedown;
    }

    public static function toHtml(string $md): string
    {
        $md = str_replace(["\r\n", "\r"], "\n", trim($md));
        if ($md === '') {
            return '';
        }

        // 模型有时会把整段回答用 ``` 包起来，先剥掉外层围栏（最多 2 层）
        for ($i = 0; $i < 2; $i++) {
            if (preg_match('/^```[\w#+.\-]*\n([\s\S]*?)\n```$/', trim($md), $m)) {
                $md = trim($m[1]);
            } else {
                break;
            }
        }

        // 提取代码块：语言标签为 markdown/md/gfm 时，说明是被误包起来的正文，
        // 递归当 Markdown 渲染；其余语言交给 Parsedown 原生处理（输出 <pre><code class="language-xxx">）。
        $codes = [];
        $md = preg_replace_callback('/```([\w#+.\-]*)\n([\s\S]*?)```/', function ($m) use (&$codes) {
            $lang = strtolower(trim($m[1]));
            $inner = rtrim($m[2], "\n");
            if (in_array($lang, ['markdown', 'md', 'gfm'], true)) {
                $codes[] = self::toHtml($inner);
                return "\n\n@@MDCODE" . (count($codes) - 1) . "@@\n\n";
            }
            // 其它语言原样保留，由 Parsedown 解析
            return $m[0];
        }, $md);

        // 折叠独占多行的块级公式 $$...$$ 为单行：避免 Parsedown 插入 <br> 把 $$ 拆到不同文本节点，
        // 导致 KaTeX auto-render 无法跨行匹配。仅匹配行首行尾精确为 $$ 的行，不影响代码块内文本。
        $md = preg_replace_callback('/^[ \t]*\$\$[ \t]*\n([\s\S]*?)\n[ \t]*\$\$[ \t]*$/m', function ($m) {
            $inner = trim(preg_replace('/\s*\n\s*/', ' ', $m[1]));
            return '$$ ' . $inner . ' $$';
        }, $md);

        $html = self::pd()->text($md);

        // 回填递归渲染的 markdown 代码块（去掉占位符被 Parsedown 套上的 <p>）
        if ($codes) {
            $html = preg_replace_callback('/<p>\s*@@MDCODE(\d+)@@\s*<\/p>/', function ($m) use (&$codes) {
                return $codes[(int)$m[1]];
            }, $html);
            $html = preg_replace_callback('/@@MDCODE(\d+)@@/', function ($m) use (&$codes) {
                return $codes[(int)$m[1]];
            }, $html);
        }

        // GFM 任务列表（Parsedown 1.8.0 不原生支持）：把 <li>[ ]/[x] ...</li> 转成带 checkbox 的任务项
        $html = preg_replace_callback(
            '/<li>\s*(<p>)?\[([ xX])\]\s+(.*?)(<\/p>)?\s*<\/li>/s',
            function ($m) {
                $checked = strtolower($m[2]) === 'x' ? ' checked' : '';
                return '<li class="task-list-item"><input type="checkbox" disabled' . $checked . '> ' . $m[3] . '</li>';
            },
            $html
        );

        return $html;
    }
}
