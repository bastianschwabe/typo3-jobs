<?php

/*
 * Files Tailor leaves out of the TER artefact.
 *
 * A custom list REPLACES Tailor's defaults (conf/ExcludeFromPackaging.php in
 * typo3/tailor), it does not extend them. The defaults are therefore repeated
 * here, followed by what is specific to this repository. Tailor does NOT read
 * .gitattributes, so the export-ignore list there only applies to
 * "git archive" and Composer.
 *
 * Wired in through the TYPO3_EXCLUDE_FROM_PACKAGING environment variable in
 * .github/workflows/release.yml. Entries are regular expressions, matched
 * case-insensitively: directories against the beginning of the relative
 * path, files against the end of the file name.
 */
return [
    'directories' => [
        // Tailor defaults
        '.build',
        '.ddev',
        '.git',
        '.github',
        '.gitlab',
        '.gitlab-ci',
        '.idea',
        '.phive',
        'bin',
        'build',
        'public',
        'tailor-version-artefact',
        'tailor-version-upload',
        'tests',
        'tools',
        'vendor',
        // This repository. Entries are regular expressions anchored at the
        // start of the path, so "config" alone would also swallow
        // "Configuration"; the "$" pins them to the top-level directory.
        'config$',
        'var$',
        'Documentation-GENERATED-temp$',
        '\.phpunit\.cache$',
    ],
    'files' => [
        // Tailor defaults
        'CODE_OF_CONDUCT.md',
        'DS_Store',
        'Dockerfile',
        'ExtensionBuilder.json',
        'Makefile',
        'bower.json',
        'codeception.yml',
        'composer.lock',
        'crowdin.yaml',
        'docker-compose.yml',
        'dynamicReturnTypeMeta.json',
        'editorconfig',
        'env',
        'eslintignore',
        'eslintrc.json',
        'gitattributes',
        'gitignore',
        'gitlab-ci.yml',
        'gitmodules',
        'gitreview',
        'package-lock.json',
        'package.json',
        'phive.xml',
        'php-cs-fixer.dist.php',
        'php-cs-fixer.php',
        'php_cs',
        'php_cs.php',
        'phpcs.xml',
        'phpcs.xml.dist',
        'phplint.yml',
        'phpstan-baseline.neon',
        'phpstan.neon',
        'phpstan.neon.dist',
        'phpstorm.meta.php',
        'phpunit.xml',
        'phpunit.xml.dist',
        'prettierrc.json',
        'rector.php',
        'scrutinizer.yml',
        'styleci.yml',
        'stylelint.config.js',
        'stylelintrc',
        'travis.yml',
        'tslint.yaml',
        'tslint.yml',
        'typoscript-lint.yaml',
        'typoscript-lint.yml',
        'typoscriptlint.yaml',
        'typoscriptlint.yml',
        'webpack.config.js',
        'webpack.mix.js',
        'yarn.lock',
        // This repository
        'DEVELOPMENT\.md',
        'php-cs-fixer\.cache',
    ],
];
