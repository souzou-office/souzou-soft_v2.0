<?php

namespace App\Services\Documents;

/**
 * 6.2.2 条件分岐記法の独自プリプロセッサ。
 *
 * phpword の TemplateProcessor は ${var}, cloneRow, cloneBlock を持つが、
 * ${IF_xxx}〜${ENDIF_xxx} のような条件分岐記法は持たないため、テンプレ
 * 文字列を読み込む直前にこの段階で評価・除去する。
 *
 * 評価対象:
 *   ${IF_設定金融機関あり}...${ENDIF_設定金融機関あり}
 *   ${IF_住所変更あり}...${ELSE_住所変更あり}...${ENDIF_住所変更あり}
 *
 * 真偽値は $context['flags'] で渡される。
 */
class ConditionalPreprocessor
{
    /**
     * @param string $content   テンプレ XML（docx の word/document.xml）
     * @param array<string,bool> $flags ${IF_xxx} の xxx をキーとする真偽値
     */
    public function process(string $content, array $flags): string
    {
        $content = $this->processIfElse($content, $flags);
        $content = $this->processIf($content, $flags);
        return $content;
    }

    private function processIfElse(string $content, array $flags): string
    {
        $pattern = '/\$\{IF_(?<key>[^\}]+)\}(?<then>.*?)\$\{ELSE_\k<key>\}(?<else>.*?)\$\{ENDIF_\k<key>\}/su';

        return preg_replace_callback($pattern, function ($m) use ($flags) {
            $key   = $m['key'];
            $cond  = $flags[$key] ?? false;
            return $cond ? $m['then'] : $m['else'];
        }, $content) ?? $content;
    }

    private function processIf(string $content, array $flags): string
    {
        $pattern = '/\$\{IF_(?<key>[^\}]+)\}(?<then>.*?)\$\{ENDIF_\k<key>\}/su';

        return preg_replace_callback($pattern, function ($m) use ($flags) {
            $key   = $m['key'];
            $cond  = $flags[$key] ?? false;
            return $cond ? $m['then'] : '';
        }, $content) ?? $content;
    }
}
