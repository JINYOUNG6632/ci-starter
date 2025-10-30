<?php
/**
 * Optimizer
 *
 * CSS, JS 파일들의 경로를 생성해 <link>/<script> 태그를 만든다.
 * - 기본 자산 경로: /ci-starter/assets/{css|js}/...
 * - 외부 URL(https://...)은 그대로 출력
 * - "../"로 시작하면 기존 /resource 하위 자산도 그대로 지원(레거시 호환)
 */
class Optimizer
{
    private $ci = NULL;

    /** @var string 웹 기준 자산 베이스 경로 */
    private $assetBase = '/ci-starter/assets';

    /** @var array<string> 등록된 JS 파일명(or URL) */
    private $aJs = array();

    /** @var array<string> 등록된 외부 JS URL */
    private $aExternalJs = array();

    /** @var array<string> 등록된 CSS 파일명(or URL) */
    private $aCss = array();

    public function __construct()
    {
        $this->ci =& get_instance();
    }

    /** 컨트롤러에서 최종 태그 만들 때 호출 */
    public function makeOptimizerScriptTag()
    {
        $sJsTag  = $this->_makeTagJs();
        $sCssTag = $this->_makeTagCss();
        return array('js_optimizer' => $sJsTag, 'css_optimizer' => $sCssTag);
    }

    /* ===================== 태그 생성부 ===================== */

    private function _makeTagJs()
    {
        if (empty($this->aJs)) return '';

        $sResult = '';
        foreach ($this->aJs as $sValue) {
            // 외부 URL은 그대로
            if (preg_match('#^(https?:)?//#', $sValue)) {
                $src = $sValue;
            }
            // 레거시: "../"로 시작하면 /resource 하위로 보냄
            else if (substr($sValue, 0, 3) === '../') {
                $resource = substr($sValue, 3); // ex) ../common/js/file.js -> common/js/file.js
                $src = "/resource/{$resource}";
            }
            // 기본: /ci-starter/assets/js/{file}
            else {
                $src = "{$this->assetBase}/js/{$sValue}";
            }

            $sResult .= "<script type='text/javascript' src='{$src}'></script>";
        }

        // 외부 JS (등록된 것 그대로)
        if (!empty($this->aExternalJs)) {
            foreach ($this->aExternalJs as $sValue) {
                $sResult .= "<script type='text/javascript' src='{$sValue}'></script>";
            }
        }

        return $sResult;
    }

    private function _makeTagCss()
    {
        if (empty($this->aCss)) return '';

        $sResult = '';
        foreach ($this->aCss as $sValue) {
            // 파일명(베이스네임)과 stem 안전 추출 (PHP8 safe)
            $basename = basename(is_string($sValue) ? $sValue : '');
            $dotpos   = strrpos($basename, '.');
            $stem     = ($dotpos !== false) ? substr($basename, 0, $dotpos) : $basename;

            // 외부 URL은 그대로
            if (preg_match('#^(https?:)?//#', $sValue)) {
                $href = $sValue;
            }
            // 레거시: "../"는 /resource 하위로
            else if (substr($sValue, 0, 3) === '../') {
                $resource = substr($sValue, 3);
                $href = "/resource/{$resource}";
            }
            // 기본: /ci-starter/assets/css/{file}
            else {
                $href = "{$this->assetBase}/css/{$sValue}";
            }

            if ($stem === 'print') {
                $sResult .= "<link rel='stylesheet' type='text/css' href='{$href}' media='print' />";
            } else {
                $sResult .= "<link rel='stylesheet' type='text/css' href='{$href}' />";
            }
        }

        return $sResult;
    }

    /* ===================== 등록부 ===================== */

    /**
     * JS 등록
     * - 외부 URL 또는 "../"는 존재 확인 스킵
     * - 기본 경로는 DOCROOT + /ci-starter/assets/js/{file}
     */
    public function setJs($file, $v = '')
    {
        if (empty($file)) return;

        // 외부 URL 또는 ../ → 바로 등록
        if (preg_match('#^(https?:)?//#', $file) || substr($file, 0, 3) === '../') {
            if ($v && strpos($file, '?') === false) $file .= "?v={$v}";
            $this->aJs[] = $file;
            return;
        }

        // 실제 파일 존재 체크 (로컬만)
        $full = rtrim($_SERVER['DOCUMENT_ROOT'], '/')."{$this->assetBase}/js/{$file}";
        if (file_exists($full)) {
            if ($v) $file .= "?v={$v}";
            $this->aJs[] = $file;
        } else {
            echo "[ADMIN] OPTIMIZER_<label style='color:red;'>JS</label>_NULL - {$full}<br>";
        }
    }

    /** 외부 JS(URL) 등록 */
    public function setExternalJs($file)
    {
        if (empty($file)) return;
        $this->aExternalJs[] = $file;
    }

    /**
     * CSS 등록
     * - 외부 URL 또는 "../"는 존재 확인 스킵
     * - 기본 경로는 DOCROOT + /ci-starter/assets/css/{file}
     */
    public function setCss($file, $v = '')
    {
        if (empty($file)) return;

        // 외부 URL 또는 ../ → 바로 등록
        if (preg_match('#^(https?:)?//#', $file) || substr($file, 0, 3) === '../') {
            if ($v && strpos($file, '?') === false) $file .= "?v={$v}";
            $this->aCss[] = $file;
            return;
        }

        $full = rtrim($_SERVER['DOCUMENT_ROOT'], '/')."{$this->assetBase}/css/{$file}";
        if (file_exists($full)) {
            if ($v) $file .= "?v={$v}";
            $this->aCss[] = $file;
        } else {
            echo "[ADMIN] OPTIMIZER_<label style='color:blue;'>CSS</label>_NULL - {$full}<br>";
        }
    }
}
