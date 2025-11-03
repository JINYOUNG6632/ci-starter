<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pagination_module
{
    protected $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->helper('url');
    }

    public function for_posts(int $category_id, string $q = '', int $defaultPerPage = 10): array
    {
        $perPage = (int)($this->ci->input->get('per_page', TRUE) ?: $defaultPerPage);
        if ($perPage <= 0) $perPage = $defaultPerPage;

        $total = $this->ci->Post_model->count_by_title($q, $category_id);

        return $this->init([
            'base_url'   => site_url('posts/index/' . $category_id),
            'total_rows' => $total,
            'per_page'   => $perPage,
        ]);
    }

    /**
     * 공통 init
     */
    public function init(array $opts): array
    {
        $segment = $opts['query_string_segment'] ?? 'page';
        $page    = (int)$this->ci->input->get($segment, TRUE);
        if ($page < 1) $page = 1;

        $per_page   = max(1, (int)($opts['per_page'] ?? 20));
        $total_rows = max(0, (int)($opts['total_rows'] ?? 0));
        $offset     = ($page - 1) * $per_page;

        $labels = [
            'first' => '처음',
            'prev'  => '이전',
            'next'  => '다음',
            'last'  => '끝',
        ];

        $links = $this->render_fixed_window(
            $opts['base_url'],
            $total_rows,
            $per_page,
            $page,
            5,           // 숫자 버튼 5개
            $segment,
            80,          // scale percent (80%)
            $labels,
            TRUE
        );

        return [
            'links'  => $links,
            'limit'  => $per_page,
            'offset' => $offset,
            'page'   => $page,
            'total'  => $total_rows,
        ];
    }

    /**
     * 고정 창 페이지네이션 HTML
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
        bool $reuse_query_string
    ): string {
        $total_pages = (int) ceil($total_rows / max(1, $per_page));
        if ($total_pages <= 1) {
            return '<div class="pg-wrap" style="font-size:'.$scale_percent.'%;"><ul class="pagination"></ul></div>';
        }

        if ($current_page < 1)            $current_page = 1;
        if ($current_page > $total_pages) $current_page = $total_pages;

        $half  = floor($window_size / 2);
        $start = $current_page - $half;
        $end   = $current_page + ($window_size - $half - 1);

        if ($start < 1) {
            $end += (1 - $start);
            $start = 1;
        }
        if ($end > $total_pages) {
            $start -= ($end - $total_pages);
            $end = $total_pages;
            if ($start < 1) $start = 1;
        }

        if (($end - $start + 1) < $window_size && $total_pages >= $window_size) {
            $end = min($total_pages, $start + $window_size - 1);
        }

        $params = $reuse_query_string ? ($this->ci->input->get() ?? []) : [];
        unset($params[$query_string_segment]);

        $buildUrl = function($page) use ($base_url, $params, $query_string_segment) {
            $qs = http_build_query(array_merge($params, [$query_string_segment => $page]));
            return rtrim($base_url, '?&') . (strpos($base_url, '?') === false ? '?' : '&') . $qs;
        };

        $html = '<div class="pg-wrap" style="font-size:'.$scale_percent.'%;"><ul class="pagination">';

        // 첫 페이지
        $html .= $this->makeLink(1,  $current_page <= 1, $labels['first'], $buildUrl);
        // 이전
        $html .= $this->makeLink(max(1, $current_page - 1), $current_page <= 1, $labels['prev'], $buildUrl);

        // 숫자 페이지
        for ($i = $start; $i <= $end; $i++) {
            if ($i === $current_page) {
                $html .= '<li class="page-item active"><span class="page-link">'.$i.'</span></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="'.$buildUrl($i).'">'.$i.'</a></li>';
            }
        }

        // 다음
        $html .= $this->makeLink(min($total_pages, $current_page + 1), $current_page >= $total_pages, $labels['next'], $buildUrl);
        // 끝
        $html .= $this->makeLink($total_pages, $current_page >= $total_pages, $labels['last'], $buildUrl);

        return $html.'</ul></div>';
    }

    private function makeLink(int $page, bool $disabled, string $label, callable $buildUrl): string
    {
        $href = $disabled ? '#' : $buildUrl($page);
        $cls  = $disabled ? 'page-item disabled' : 'page-item';
        return '<li class="'.$cls.'"><a class="page-link" href="'.$href.'">'.$label.'</a></li>';
    }
}
