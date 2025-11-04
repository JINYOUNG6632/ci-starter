<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_DB_query_builder $db
 * 안전 업로드/다운로드용 File_model (복붙용 완성본)
 */
class File_model extends MY_Model
{
    /** 업로드 루트 (웹루트 밖 권장) */
    private $upload_root = 'C:/ci-storage/uploads';

    /** 허용 MIME (화이트리스트) */
    private $allowed_mimes = [
        'image/jpeg','image/png','image/gif',
        'application/pdf',
        'text/plain',
        'application/zip','application/x-zip-compressed',
    ];

    /** 허용 확장자 (화이트리스트) */
    private $allowed_exts = ['jpg','jpeg','png','gif','pdf','txt','zip'];

    /** 위험 확장자 (블랙리스트) */
    private $blocked_exts = [
        'php','phtml','phar','php3','php4','php5','php7','php8',
        'cgi','pl','exe','dll','sh','bat','cmd','com','jar',
        'svg','html','htm','js'
    ];

    /** 1개 파일 최대 크기 (bytes) */
    private $max_each = 20 * 1024 * 1024; // 20MB

    /** 한 요청 내 총 업로드 허용 합계 (bytes) */
    private $max_total = 50 * 1024 * 1024; // 50MB

    public function __construct()
    {
        parent::__construct();

        // 업로드 루트 보장 (윈도우/리눅스 모두 OK)
        $this->upload_root = rtrim($this->upload_root, DIRECTORY_SEPARATOR);

        // 디렉토리 없으면 생성 (0750 권장)
        if (!is_dir($this->upload_root)) {
            mkdir($this->upload_root, 0750, true);
        }

        // 권한 설정 시도 (윈도우는 자동 무시됨)
        @chmod($this->upload_root, 0750);
    }

    /** $_FILES[multiple] 평탄화 */
    private function normalize_files_array(array $file): array
    {
        $out = [];
        if (is_array($file['name'])) {
            $count = count($file['name']);
            for ($i = 0; $i < $count; $i++) {
                $out[] = [
                    'name'     => $file['name'][$i],
                    'type'     => $file['type'][$i],
                    'tmp_name' => $file['tmp_name'][$i],
                    'error'    => $file['error'][$i],
                    'size'     => $file['size'][$i],
                ];
            }
        } else {
            $out[] = $file;
        }
        return $out;
    }

    /** 원본 파일명 정규화 (제어문자 제거/길이 제한) */
    private function sanitize_original_name(string $name, int $max = 200): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F]/', '', $name);
        // 너무 길면 잘라내기
        if (function_exists('mb_strimwidth')) {
            $name = mb_strimwidth($name, 0, $max, '...', 'UTF-8');
        } else {
            if (strlen($name) > $max) $name = substr($name, 0, $max - 3) . '...';
        }
        return $name;
    }

    /** storage_key -> 절대 경로 (경로 역참조 방지) */
    private function absolute_path_from_key(string $storage_key, bool $create_dir = false): ?string
    {
        $base = rtrim($this->upload_root, '/');
        if ($create_dir && !is_dir($base)) @mkdir($base, 0750, true);

        // storage_key에 경로문자 금지(이중 방어)
        if (preg_match('/[\/\\\\]/', $storage_key)) return null;

        $candidate = $base . '/' . $storage_key;

        $root_real = realpath($base);
        $dir_real  = realpath(dirname($candidate)) ?: $root_real;
        if ($root_real === false || $dir_real === false) return null;
        if (strpos($dir_real, $root_real) !== 0) return null;

        return $candidate;
    }

    /** 업로드 & DB 저장 (다중 처리) */
    public function upload_and_attach($post_id, $fieldName = 'attachments'): array
    {
        if (empty($_FILES[$fieldName]) || empty($_FILES[$fieldName]['name'])) {
            return [];
        }

        $saved = [];
        $total_size = 0;
        $files = $this->normalize_files_array($_FILES[$fieldName]);

        foreach ($files as $f) {
            // 기본 에러 체크
            if (empty($f['name']) || $f['error'] === UPLOAD_ERR_NO_FILE) continue;
            if ($f['error'] !== UPLOAD_ERR_OK) {
                log_message('error', 'Upload error code: '.$f['error']);
                continue;
            }

            // 파일 크기 제한
            if ($f['size'] <= 0 || $f['size'] > $this->max_each) {
                log_message('error', 'Upload blocked: size invalid ('.$f['size'].')');
                continue;
            }
            $total_size += $f['size'];
            if ($total_size > $this->max_total) {
                log_message('error', 'Upload blocked: total size exceeded');
                break;
            }

            // 서버측 MIME 판별 (클라 type 불신)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);
            if (!$detected) $detected = 'application/octet-stream';

            if (!in_array($detected, $this->allowed_mimes, true)) {
                log_message('error', 'Blocked upload mime: '.$detected);
                continue;
            }

            // 확장자 화이트/블랙 리스트
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!$ext || !in_array($ext, $this->allowed_exts, true) || in_array($ext, $this->blocked_exts, true)) {
                log_message('error', 'Blocked upload ext: '.$ext);
                continue;
            }

            // 이미지면 2차 검증 (픽셀체크)
            if (strpos($detected, 'image/') === 0) {
                $info = @getimagesize($f['tmp_name']);
                if (!$info || $info[0] <= 0 || $info[1] <= 0 || $info[0] > 10000 || $info[1] > 10000) {
                    log_message('error', 'Invalid image content or too large dimension');
                    continue;
                }
            }

            // storage_key 생성 (랜덤 + 소문자 확장자)
            $storage_key = bin2hex(random_bytes(16));
            if ($ext) $storage_key .= '.' . $ext;

            $dest_abs = $this->absolute_path_from_key($storage_key, true);
            if ($dest_abs === null) {
                log_message('error', 'Invalid upload path (absolute_path_from_key)');
                continue;
            }

            if (!@move_uploaded_file($f['tmp_name'], $dest_abs)) {
                log_message('error', 'move_uploaded_file failed to: '.$dest_abs);
                continue;
            }
            // 권한 타이트하게
            @chmod($dest_abs, 0640);

            // 체크섬
            $checksum = hash_file('sha256', $dest_abs);

            // 원본 파일명 정규화
            $original = $this->sanitize_original_name($f['name']);

            // DB INSERT (네 스키마 필드명에 맞춰 기록)
            $row = [
                'post_id'           => (int)$post_id,
                'original_filename' => $original,
                'storage_key'       => $storage_key,
                'content_type'      => $detected,
                'file_size'         => (int)filesize($dest_abs),
                'checksum'          => $checksum,
                'is_deleted'        => 0,
                'created_at'        => date('Y-m-d H:i:s'),
                // 'saved_filename'  => null,
                // 'filepath'        => null,
            ];
            $this->db->insert('files', $row);
            $row['id'] = (int)$this->db->insert_id();

            $saved[] = $row;
        }

        return $saved;
    }

    /** 첨부 목록 */
    public function list_by_post($post_id): array
    {
        return $this->db->from('files')
            ->where('post_id', (int)$post_id)
            ->where('is_deleted', 0)
            ->order_by('created_at', 'asc')
            ->get()->result();
    }

    /** 다운로드 전 조회 (절대경로 포함) */
    public function get_for_download($file_id)
    {
        $row = $this->db->from('files')
            ->where('id', (int)$file_id)
            ->where('is_deleted', 0)
            ->get()->row_array();

        if (!$row) return false;

        $abs = $this->absolute_path_from_key($row['storage_key']);
        if ($abs === null || !is_file($abs)) return false;

        $row['_abs_path'] = $abs;
        return $row;
    }

    /** 게시글 단위 소프트 삭제 */
    public function soft_delete_by_post($post_id): void
    {
        $this->db->set('is_deleted', 1)
                 ->where('post_id', (int)$post_id)
                 ->where('is_deleted', 0)
                 ->update('files');
    }

    /** 개별 파일 소프트 삭제 */
    public function soft_delete_one($file_id): bool
    {
        return (bool)$this->db->set('is_deleted', 1)
            ->where('id', (int)$file_id)
            ->where('is_deleted', 0)
            ->update('files');
    }

    /** 파일 + 게시글 소유자 함께 조회 (권한검사용) */
    public function get_one_with_owner($file_id)
    {
        return $this->db->select('f.*, p.user_id AS post_owner_id')
            ->from('files AS f')
            ->join('posts AS p', 'p.id = f.post_id')
            ->where('f.id', (int)$file_id)
            ->where('f.is_deleted', 0)
            ->get()->row();
    }
}
