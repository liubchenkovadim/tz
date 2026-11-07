<?php

declare(strict_types=1);

namespace App\Core;

use Smarty\Smarty;

class View
{
    private static ?Smarty $smarty = null;

    public static function getSmarty(): Smarty
    {
        if (self::$smarty === null) {
            $smarty = new Smarty();

            $baseDir = dirname(__DIR__, 2);
            $templateDir = $baseDir . '/templates';
            $compileDir = $baseDir . '/var/templates_c';

            if (!is_dir($compileDir)) {
                mkdir($compileDir, 0777, true);
            }

            $smarty->setTemplateDir($templateDir);
            $smarty->setCompileDir($compileDir);
            $smarty->setEscapeHtml(true);

            // Register helper variables/functions if needed
            $smarty->assign('currentYear', date('Y'));

            self::$smarty = $smarty;
        }

        return self::$smarty;
    }

    public static function render(string $template, array $data = []): string
    {
        $smarty = self::getSmarty();

        foreach ($data as $key => $value) {
            $smarty->assign($key, $value);
        }

        return $smarty->fetch($template);
    }
}

