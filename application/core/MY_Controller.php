<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    /** Request-scoped parameters */
    public $params  = array();
    public $cookies = array();

    protected $models        = [];
    protected $libraries     = [];
    protected $helpers       = [];
    protected $commonModules = [];

    public function __construct()
    {
        parent::__construct();

        $this->params  = $this->getParams();
        $this->cookies = $this->getCookies();
        $this->autoloadDeclared();
    }

    protected function autoloadDeclared(): void
    {
        // Models
        foreach ((array)$this->models as $m) {
            if (is_string($m) && $m !== '') {
                $this->load->model($m);
            }
        }

        // Libraries
        foreach ((array)$this->libraries as $lib) {
            if (is_string($lib) && $lib !== '') {
                $this->load->library($lib);
            }
        }

        // Helpers
        if (!empty($this->helpers)) {
            if (is_array($this->helpers)) {
                $this->load->helper($this->helpers);
            } elseif (is_string($this->helpers)) {
                $this->load->helper([$this->helpers]);
            }
        }

        // Common Modules
        if (!empty($this->commonModules)) {
            $this->load->library('common_modules', $this->commonModules);
        }
    }

    protected function addModels(array $list): void
    {
        $this->models = array_unique(array_merge((array)$this->models, $list));
        foreach ($list as $m) {
            if (is_string($m) && $m !== '') $this->load->model($m);
        }
    }

    protected function addLibraries(array $list): void
    {
        $this->libraries = array_unique(array_merge((array)$this->libraries, $list));
        foreach ($list as $lib) {
            if (is_string($lib) && $lib !== '') $this->load->library($lib);
        }
    }

    protected function addHelpers($list): void
    {
        $arr = is_array($list) ? $list : [$list];
        $this->helpers = array_unique(array_merge((array)$this->helpers, $arr));
        if (!empty($arr)) $this->load->helper($arr);
    }

    protected function addCommonModules(array $list): void
    {
        $this->commonModules = array_unique(array_merge((array)$this->commonModules, $list));
        if (!empty($list)) $this->load->library('common_modules', $list);
    }

    private function getParams()
    {
        $aParams = array_merge($this->doGet(), $this->doPost());
        // $this->sql_injection_filter($aParams);
        return $aParams;
    }

    private function getCookies()
    {
        return $this->doCookie();
    }

    private function doGet()
    {
        $aGetData = $this->input->get(NULL, TRUE);
        return (empty($aGetData)) ? array() : $aGetData;
    }

    private function doPost()
    {
        $aPostData = $this->input->post(NULL, TRUE);
        return (empty($aPostData)) ? array() : $aPostData;
    }

    private function doCookie()
    {
        $aCookieData = $this->input->cookie(NULL, TRUE);
        return (empty($aCookieData)) ? array() : $aCookieData;
    }

    public function js($file, $v = '')
    {
        if (is_array($file)) {
            foreach ($file as $sValue) $this->optimizer->setJs($sValue, $v);
        } else {
            $this->optimizer->setJs($file, $v);
        }
    }

    public function externaljs($file)
    {
        if (is_array($file)) {
            foreach ($file as $sValue) $this->optimizer->setExternalJs($sValue);
        } else {
            $this->optimizer->setExternalJs($file);
        }
    }

    public function css($file, $v = '')
    {
        if (is_array($file)) {
            foreach ($file as $sValue) $this->optimizer->setCss($sValue, $v);
        } else {
            $this->optimizer->setCss($file, $v);
        }
    }

    /**
     * 변수 셋팅
     */
    public function setVars($arr = array())
    {
        // ⚠️ 기존 코드에 $aVars 미정의 버그 → 수정
        $aVars = [];
        foreach ((array)$arr as $k => $v) {
            $aVars[$k] = $v;
        }
        if (!empty($aVars)) {
            $this->load->vars($aVars);
        }
    }

    /**
     * 공통 전역 변수 셋팅
     */
    public function setCommonVars()
    {
        $aVars = array();
        $aVars['test'] = array("test1" => "test1");
        $this->load->vars($aVars);
    }

    protected function _check_login()
    {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_flashdata('error', '로그인이 필요합니다.');
            redirect('auth/login');
        }
    }
}
