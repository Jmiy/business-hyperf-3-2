<?php

declare(strict_types=1); // 开启严格类型模式，保证此配置文件中函数调用和返回值类型严格匹配。
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use PhpCsFixer\Config; // PHP-CS-Fixer 的主配置类。
use PhpCsFixer\Finder; // 用于查找需要修复的 PHP 文件。
use PhpCsFixer\Runner\Parallel\ParallelConfig; // 配置并行修复的参数（多进程处理，提高速度）。

// 3. 定义文件头注释内容 定义一个多行字符串 $header，后续通过 header_comment 规则自动插入到每个 PHP 文件的开头（放在 declare(strict_types=1); 之后）。
$header = <<<'EOF'
This file is part of Hyperf.

@link     https://www.hyperf.io
@document https://hyperf.wiki
@contact  group@hyperf.io
@license  https://github.com/hyperf/hyperf/blob/master/LICENSE
EOF;

// 4. 创建配置对象并设置并行处理
return (new Config())
    ->setParallelConfig(new ParallelConfig(swoole_cpu_num(), 20)) // setParallelConfig：启用并行修复。swoole_cpu_num() 获取 CPU 核心数作为并行进程数，第二个参数 20 是每个进程处理的文件数上限。这可以大幅提升修复速度（尤其在多核服务器上）。
    ->setRiskyAllowed(true) // 5. 允许“冒险”规则 (允许执行可能改变代码逻辑的规则（例如 declare_strict_types 会自动添加 declare(strict_types=1);）。一般建议开启，但需要人工确认修改是否安全。)
    ->setRules([ // 6. 定义具体规则集（setRules） 6.1 预设规则集 (这些 @ 开头的规则是预定义集合，包含了多个细化规则。开启它们等于一次性采用了多种编码规范。)
//        '@PSR2' => true, // 遵循 PSR-2 编码风格（缩进、大括号等）
//        '@PER-CS2.0' => true, // PER Coding Style 2.0（PSR-2 的进化版）
        '@PER-CS2x0:risky' => true, // PER Coding Style 2.0（PSR-2 的进化版）
        '@Symfony' => true, // Symfony 项目规则集（更严格，如方法名小写开头）
        '@DoctrineAnnotation' => true, // 针对 Doctrine 注解的格式规则
        '@PhpCsFixer' => true, // PHP-CS-Fixer 官方推荐的全部规则
        'header_comment' => [ // 6.2 单项规则（部分关键规则解析）在每个 PHP 文件中 declare(strict_types=1); 之后添加 $header 定义的注释块。
            'comment_type' => 'PHPDoc',
            'header' => $header,
            'separate' => 'none',
            'location' => 'after_declare_strict',
        ],
        'array_syntax' => [ // 强制使用 [] 短数组语法，禁止 array()。
            'syntax' => 'short',
        ],
        'list_syntax' => [ // 强制使用 [...] 短列表语法，禁止 list()。
            'syntax' => 'short',
        ],
        'concat_space' => [ // 字符串拼接符 . 前后各加一个空格：$a . $b。
            'spacing' => 'one', // 拼接操作符 . 前后各一个空格，如 $a . $b
        ],
        'blank_line_before_statement' => [ // 在 declare 语句之前强制添加一个空行，增加可读性。
            'statements' => [
                'declare',
            ],
        ],
        'general_phpdoc_annotation_remove' => [ // 自动删除 @author 注解，鼓励团队统一管理代码归属。
            'annotations' => [
                'author',
            ],
        ],
        'ordered_imports' => [ // 将 use 语句按 类 → 函数 → 常量 分组，每组内按字母顺序排序。
            'imports_order' => [ // 将 use 语句按 类 → 函数 → 常量 分组
                'class', 'function', 'const',
            ],
            'sort_algorithm' => 'alpha', // 每组内按字母顺序排序。
        ],
        'single_line_comment_style' => [ // 单行注释风格
            'comment_types' => [ // 空数组表示不强制更改单行注释的风格（保留 // 或 #）。
            ],
        ],
        'yoda_style' => [ // Yoda 条件 禁止使用 Yoda 风格（变量放右边，常量放左边），例如不要求写成 null === $var，允许 $var === null。
            'always_move_variable' => false,
            'equal' => false,
            'identical' => false,
        ],
        'phpdoc_align' => [ // PHPDoc 对齐
            'align' => 'left', // PHPDoc 注释中的 @param、@var 等标签按左对齐（而非某些风格中按冒号对齐）。
        ],
        'multiline_whitespace_before_semicolons' => [ // 多行空白与分号
            'strategy' => 'no_multi_line', // 禁止分号前有多行空白字符（即分号必须紧跟在上一行结束处）。
        ],
        'constant_case' => [ // 常量命名大小写
            'case' => 'lower', // 强制类常量名使用小写（通常常量大写，但此规则会影响 true/false/null 等，将它们转为小写）。
        ],
        'global_namespace_import' => [ // 全局命名空间导入 自动将全局的类、函数、常量添加 use 语句（例如 use function count;），使代码中不用写反斜杠 \。
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'ordered_types' => [ // 类型排序
            'null_adjustment' => 'always_first', // 在联合类型（|）中，将 null 放在第一位，例如 ?string 自动调整为 null|string。
        ],
        'phpdoc_to_comment' => false, // 保留PHPDoc注释（不转为普通注释）
        'class_attributes_separation' => true, // 类成员分隔 类的属性、方法之间用一个空行分隔。
        'combine_consecutive_unsets' => true, // 合并连续的 unset 将连续多个 unset($a); unset($b); 合并为 unset($a, $b);。
        'declare_strict_types' => true, // 强制 declare(strict_types=1) 自动在文件开头添加 declare(strict_types=1);（如果尚未存在）。
        'linebreak_after_opening_tag' => true, // 标签后换行 在 <?php 标签后添加一个空行。
        'lowercase_static_reference' => true, // 静态引用小写 强制 self、static、parent 关键字小写。
        'no_useless_else' => true, // 移除无用 else 如果 if 块中直接 return，则移除后面的 else。
        'no_unused_imports' => true, // 删除未使用的 use 语句。
        'not_operator_with_successor_space' => true, // 非运算符空格 允许 ! $foo（感叹号后有空格），禁止 !$foo（无空格）
        'not_operator_with_space' => false,
        'ordered_class_elements' => true, // 类元素排序 按标准顺序排列类内部元素：use、属性、构造函数、魔术方法、其他方法等。
        'php_unit_strict' => false, // PHPUnit 严格模式 不强制 PHPUnit 测试中的严格比较（如 assertSame 替换 assertEquals）。
        'phpdoc_separation' => false, // PHPDoc 分隔 (不强制 PHPDoc 中不同注解组之间有空行。)
        'single_quote' => true, // 单引号 (优先使用单引号字符串，除非字符串内包含单引号需要转义。)
        'standardize_not_equals' => true, // 标准化不等号 (将 <> 统一替换为 !=。)
        'multiline_comment_opening_closing' => true, // 多行注释起止 (确保多行注释 /* ... */ 的起始和结束各占一行，且内容前有空格。)
        'single_line_empty_body' => false, // 单行空主体 (禁止方法或类空主体写成单行 {}，必须换行书写（例如 function foo() { 换行后 }）。)
    ])
    ->setFinder( // 7. 设置文件查找器（setFinder）
        Finder::create()
            ->exclude('Rpc/Grpc/Busi/Protobuf')
            ->exclude('Rpc/Grpc/Protobuf')
            ->exclude('Rpc/Grpc/busi.proto')
            ->exclude('Rpc/Grpc/grpc.proto')
            ->in(__DIR__.'/src') // 创建 Finder 实例，扫描项目根目录（__DIR__）下的所有 .php 文件。
    )
    ->setUsingCache(false); // 8. 禁用缓存 不使用缓存。每次运行都会重新扫描所有文件并应用规则。在 CI 环境或确保规则立即生效时常用；若频繁运行，开启缓存可提速。
