<?php
class post_controller
{
    private $ci = NULL;

    public function init()
    {
        $this->ci =& get_instance();
        $this->_view();
    }

    private function _view()
    {
        if ($this->ci->template_->defined('layout_common')) {

            // 어떤 레이아웃 tpl을 쓸지 지정
            $this->ci->template_->viewDefine('layout', 'common/layout_common.tpl');

            // 공통 모듈 로드 (원래 있던 로직 유지)
            $aCommonModules = $this->getCommonModules();
            $this->ci->load->library('common_modules', $aCommonModules);

            // 공통 CSS 기본값만 보정 (CSS/JS는 더 이상 손대지 않는다)
            $this->ensureBaseCssOnly();

            // optimizer 등 공통 assign 그대로 유지
            $this->ci->template_->viewAssign($this->ci->optimizer->makeOptimizerScriptTag());

            // 최종 출력
            $this->ci->template_->viewPrint('layout');

        } else if ($this->ci->template_->defined('layout_empty')) {

            $this->ci->output->enable_profiler(false);

            $this->ci->template_->viewDefine('layout', 'common/layout_empty.tpl');

            $this->ensureBaseCssOnly();

            $this->ci->template_->viewAssign($this->ci->optimizer->makeOptimizerScriptTag());

            $this->ci->template_->viewPrint('layout');

        } else {
            $this->ci->output->enable_profiler(false);
        }
    }

    private function ensureBaseCssOnly()
    {

        $assigned = method_exists($this->ci->template_, 'getAssign')
            ? $this->ci->template_->getAssign()
            : [];

        if (!array_key_exists('BASE_CSS', $assigned)) {
            $this->ci->template_->viewAssign([
                'BASE_CSS' => '<link rel="stylesheet" href="/ci-starter/assets/css/layout_common.css">'
            ]);
        }
    }

    private function getCommonModules()
    {
        return array();
    }
}
