<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 공통 페이지네이션 모듈
 * - 고정 숫자 창(window) 페이지네이션 지원 (예: 12345, 23456, 34567 ...)
 * - 처음/이전/다음/끝 버튼 항상 표시(비활성 시 disabled)
 * - 전체 크기 축소(기본 60% = 기존 대비 40% 감소)
 */
class Pagination_module
{
    /** @var CI_Controller */
    protected $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->library('pagination');
        $this->ci->load->helper('url');
    }

    /**
     * @param array $opts
     *   필수: base_url, total_rows, per_page
     *   선택: query_string_segment(기본 'page'), page_query_string(TRUE 권장), reuse_query_string(TRUE 권장)
     *   고정창 옵션:
     *     - fixed_window (bool)        : TRUE면 커스텀 고정 창 렌더링
     *     - window_size (int)          : 숫자 버튼 개수 (기본 5)
     *     - scale_percent (int|float)  : 전체 크기 % (기본 60 → 40% 감소)
     *     - labels (array)             : 'first','prev','next','last' 라벨 커스텀
     * @return array {links, limit, offset, page}
     */
    public function init(array $opts): array
    {
        $segment = $opts['query_string_segment'] ?? 'page';
        $page    = (int) $this->ci->input->get($segment, TRUE);
        if ($page < 1) $page = 1;

        $per_page   = max(1, (int)($opts['per_page']   ?? 20));
        $total_rows = max(0, (int)($opts['total_rows'] ?? 0));

        // limit/offset 계산
        $offset = ($page - 1) * $per_page;

        // 기본 라벨
        $labels = array_merge([
            'first' => '처음',
            'prev'  => '이전',
            'next'  => '다음',
            'last'  => '끝',
        ], $opts['labels'] ?? []);

        // 고정창 모드면 커스텀 렌더링
        if (!empty($opts['fixed_window'])) {
            $windowSize   = max(1, (int)($opts['window_size'] ?? 5)); // 5개 고정
            $scalePercent = (float)($opts['scale_percent'] ?? 60);    // 60% (40% 감소)

            $links = $this->render_fixed_window(
                base_url:            $opts['base_url'] ?? current_url(),
                total_rows:          $total_rows,
                per_page:            $per_page,
                current_page:        $page,
                window_size:         $windowSize,
                query_string_segment:$segment,
                scale_percent:       $scalePercent,
                labels:              $labels,
                reuse_query_string:  $opts['reuse_query_string'] ?? TRUE
            );

            return [
                'links'  => $links,
                'limit'  => $per_page,
                'offset' => $offset,
                'page'   => $page,
            ];
        }

        // 기본(CI 내장) 모드: 요청 시 전달한 옵션을 그대로 사용
        $config = array_merge([
            'base_url'             => $opts['base_url'] ?? current_url(),
            'total_rows'           => $total_rows,
            'per_page'             => $per_page,
            'reuse_query_string'   => TRUE,
            'page_query_string'    => TRUE,
            'query_string_segment' => $segment,
            'use_page_numbers'     => TRUE,
            'num_links'            => 2,

            // 부트스트랩 유사 마크업
            'full_tag_open'  => '<ul class="pagination">',
            'full_tag_close' => '</ul>',
            'first_tag_open' => '<li class="page-item"><span class="page-link">',
            'first_tag_close'=> '</span></li>',
            'last_tag_open'  => '<li class="page-item"><span class="page-link">',
            'last_tag_close' => '</span></li>',
            'next_tag_open'  => '<li class="page-item"><span class="page-link">',
            'next_tag_close' => '</span></li>',
            'prev_tag_open'  => '<li class="page-item"><span class="page-link">',
            'prev_tag_close' => '</span></li>',
            'cur_tag_open'   => '<li class="page-item active"><span class="page-link">',
            'cur_tag_close'  => '</span></li>',
            'num_tag_open'   => '<li class="page-item"><span class="page-link">',
            'num_tag_close'  => '</span></li>',

            'first_link'     => $labels['first'],
            'last_link'      => $labels['last'],
            'prev_link'      => $labels['prev'],
            'next_link'      => $labels['next'],
        ], $opts);

        $this->ci->pagination->initialize($config);

        return [
            'links'  => $this->ci->pagination->create_links(),
            'limit'  => $per_page,
            'offset' => $offset,
            'page'   => $page,
        ];
    }

    /**
     * 고정 창(숫자 5개 등) 페이지네이션 렌더링
     */
    private function render_fixed_window(
        string $base_url,
        int $total_rows,
        int $per_page,
        int $current_page,
        int $window_size,
        string $query_string_segment,
        float $scale_percent,
        array $labels,
        bool $reuse_query_string = TRUE
    ): string {
        $total_pages = (int) ceil($total_rows / max(1, $per_page));
        if ($total_pages <= 1) {
            // 그래도 사이즈 줄이기 요구가 있어, 빈 UL이라도 래핑
            return '<div class="pg-wrap" style="font-size:'.floatval($scale_percent).'%;"><ul class="pagination"></ul></div>';
        }

        // 현재 페이지 보정
        if ($current_page < 1)            $current_page = 1;
        if ($current_page > $total_pages) $current_page = $total_pages;

        // 윈도우 시작/끝 계산: 항상 window_size개 유지(끝단 보정)
        $half    = (int) floor($window_size / 2);
        $start   = $current_page - $half;
        $end     = $current_page + ($window_size - $half - 1);

        if ($start < 1) {
            $end   += (1 - $start);
            $start  = 1;
        }
        if ($end > $total_pages) {
            $start -= ($end - $total_pages);
            $end    = $total_pages;
            if ($start < 1) $start = 1;
        }
        // 최종적으로 개수 보장
        if (($end - $start + 1) < $window_size && $total_pages >= $window_size) {
            $end = min($total_pages, $start + $window_size - 1);
        }

        // 현재 쿼리 보존
        $params = $reuse_query_string ? ($this->ci->input->get() ?? []) : [];
        unset($params[$query_string_segment]);

        $buildUrl = function(int $page) use ($base_url, $params, $query_string_segment): string {
            $qs = http_build_query(array_merge($params, [$query_string_segment => $page]));
            // base_url에 이미 '?'가 거의 없지만, 혹시를 대비
            return rtrim($base_url, '?&') . (strpos($base_url, '?') === false ? '?' : '&') . $qs;
        };

        // 렌더링 시작 (전체 크기 축소: font-size 퍼센트)
        $html = '<div class="pg-wrap" style="font-size:'.floatval($scale_percent).'%;"><ul class="pagination">';

        // 처음
        $disabled = ($current_page <= 1) ? ' disabled' : '';
        $html .= '<li class="page-item'.$disabled.'"><a class="page-link" href="' . ($disabled ? '#' : $buildUrl(1)) . '" aria-label="first">'.$labels['first'].'</a></li>';

        // 이전
        $prevPage = max(1, $current_page - 1);
        $disabled = ($current_page <= 1) ? ' disabled' : '';
        $html .= '<li class="page-item'.$disabled.'"><a class="page-link" href="' . ($disabled ? '#' : $buildUrl($prevPage)) . '" aria-label="previous">'.$labels['prev'].'</a></li>';

        // 숫자
        for ($i = $start; $i <= $end; $i++) {
            if ($i === $current_page) {
                $html .= '<li class="page-item active" aria-current="page"><span class="page-link">'.$i.'</span></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="'.$buildUrl($i).'">'.$i.'</a></li>';
            }
        }

        // 다음
        $nextPage = min($total_pages, $current_page + 1);
        $disabled = ($current_page >= $total_pages) ? ' disabled' : '';
        $html .= '<li class="page-item'.$disabled.'"><a class="page-link" href="' . ($disabled ? '#' : $buildUrl($nextPage)) . '" aria-label="next">'.$labels['next'].'</a></li>';

        // 끝
        $disabled = ($current_page >= $total_pages) ? ' disabled' : '';
        $html .= '<li class="page-item'.$disabled.'"><a class="page-link" href="' . ($disabled ? '#' : $buildUrl($total_pages)) . '" aria-label="last">'.$labels['last'].'</a></li>';

        $html .= '</ul></div>';

        return $html;
    }
}
