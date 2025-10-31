<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 여러 공통 모듈을 한 번에 로드하는 집합 로더
 *
 * 사용 예:
 *   $this->load->library('common_modules', ['pagination_module']);
 */
class Common_modules
{
    /** @var CI_Controller */
    private $ci = NULL;

    /**
     * @param array|string|null $common_modules
     */
    public function __construct($common_modules = null)
    {
        $this->ci =& get_instance();

        if (empty($common_modules)) {
            return;
        }

        $toLoad = array_unique(array_map('strval', (array)$common_modules));

        foreach ($toLoad as $value) {
            // 실제 파일: application/libraries/common_modules/<Name>.php
            // 클래스명: <Name> (예: Pagination_module → class Pagination_module)
            $this->ci->load->library('common_modules/'.$value);
        }
    }
}
