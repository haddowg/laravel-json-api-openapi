<?php
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new Finder())
    ->in(__DIR__)
    ->exclude('build')
    ->exclude('vendor')
    ->notName('.phpstorm.meta.php')
    ->notName('_ide_*.php');

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        // this includes the PER and Symfony standards as well as some additional rules
        '@PhpCsFixer' => true,
        '@PHP82Migration' => true,
        // compatibility issue with ide-helper
        'phpdoc_separation' => false,
        // compatibility issue with ide-helper and preference
        'phpdoc_align' => false,
        // restore symfony behaviour
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        // aliases are needed for class shortnames when the same classname is used from two namespaces
        'phpdoc_no_alias_tag' => false,
        // some @var annotations for phpstan/ide will get modified to a comment otherwise
        'phpdoc_to_comment' => false,
        'php_unit_test_class_requires_covers' => false,
        // tests are implicitly internal
        'php_unit_internal_class' => ['types' => ['final']],
        // single line throw can get very long
        'single_line_throw' => false,
        // prevent unwanted annotations in phpdoc comments
        'general_phpdoc_annotation_remove' => ['annotations' => ['codeCoverageIgnore', 'codeCoverageIgnoreStart', 'codeCoverageIgnoreEnd', 'runInSeparateProcess', 'preserveGlobalState', 'infection-ignore-all']],
       // ignore octal notation errors
        'octal_notation' => false,
        // nulls should be last
        'ordered_types' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        // because yuk!
        'yoda_style' => false,
        // restore default
        'concat_space' => ['spacing' => 'one'],
        // restore default as its weird otherwise!
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        // restore symfony behaviour
        'blank_line_before_statement' => ['statements' => ['return']],
        'trailing_comma_in_multiline' => [
            'elements' => ['arguments', 'arrays', 'match', 'parameters'],
        ],
    ])
    ->setFinder($finder);
