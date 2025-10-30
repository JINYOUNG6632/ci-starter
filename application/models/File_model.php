<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property CI_DB_query_builder $db
 */
class File_model extends MY_Model
{
    private $upload_root;
    private $allowed_mimes;

    public function __construct()
    {
        parent::__construct();

        // 업로드 루트
        $this->upload_root = rtrim(FCPATH, '/').'/uploads';

        // 화이트리스트 (필요시 확장)
        $this->allowed_mimes = [
            'image/jpeg','image/png','image/gif',
            'application/pdf',
            'text/plain',
            'application/zip','application/x-zip-compressed',
        ];

        if (!is_dir($this->upload_root)) {
            @mkdir($this->upload_root, 0755, true);
        }
    }

    /** $_FILES[multiple] 평탄화 */
    private function normalize_files_array(array $file): array
    {
        $out = [];
        if (is_array($file['name'])) {
            $count = count($file['name']);
            for ($i=0; $i<$count; $i++) {
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

    /** storage_key -> 절대 경로(역참조 방지) */
    private function absolute_path_from_key($storage_key, $create_dir=false)
    {
        $base = rtrim($this->upload_root, '/');
        if ($create_dir && !is_dir($base)) @mkdir($base, 0755, true);

        $candidate = $base.'/'.$storage_key;

        $root_real = realpath($base);
        $dir_real  = realpath(dirname($candidate)) ?: $root_real;
        if ($root_real === false || $dir_real === false) return null;
        if (strpos($dir_real, $root_real) !== 0) return null;

        return $candidate;
    }

    /** 업로드 & DB 저장 (⚠️ 네 스키마 필드명과 인덱스에 100% 맞춤) */
    public function upload_and_attach($post_id, $fieldName = 'attachments'): array
    {
        if (empty($_FILES[$fieldName]) || empty($_FILES[$fieldName]['name'])) {
            return [];
        }

        $saved = [];
        $files = $this->normalize_files_array($_FILES[$fieldName]);

        foreach ($files as $f) {
            if (empty($f['name']) || $f['error'] === UPLOAD_ERR_NO_FILE) continue;
            if ($f['error'] !== UPLOAD_ERR_OK) continue;

            // MIME 판별 (서버측)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected = finfo_file($finfo, $f['tmp_name']);
            finfo_close($finfo);

            if (!in_array($detected, $this->allowed_mimes, true)) {
                log_message('error', 'Blocked upload mime: '.$detected);
                continue;
            }

            // storage_key (충돌 방지) — 네 스키마는 VARCHAR(512), 유니크 인덱스 존재
            $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
            $storage_key = bin2hex(random_bytes(16));
            if ($ext) $storage_key .= '.'.strtolower($ext);

            $dest_abs = $this->absolute_path_from_key($storage_key, true);
            if ($dest_abs === null) {
                log_message('error', 'Invalid upload path');
                continue;
            }

            if (!@move_uploaded_file($f['tmp_name'], $dest_abs)) {
                log_message('error', 'move_uploaded_file failed');
                continue;
            }
            @chmod($dest_abs, 0644);

            $checksum = hash_file('sha256', $dest_abs);

            // ✅ 네 스키마에 맞춘 컬럼명으로 INSERT
            //    original_filename, file_size 사용
            $row = [
                'post_id'           => (int)$post_id,
                'original_filename' => $f['name'],
                'storage_key'       => $storage_key,
                'content_type'      => $detected ?: ($f['type'] ?: 'application/octet-stream'),
                'file_size'         => (int)filesize($dest_abs),
                'checksum'          => $checksum,
                'is_deleted'        => 0,
                'created_at'        => date('Y-m-d H:i:s'),
                // 'saved_filename'  => null,   // 네 스키마에 있지만 현재 사용 안 함
                // 'filepath'        => null,   // 네 스키마에 있지만 현재 사용 안 함
            ];

            $this->db->insert('files', $row);
            $row['id'] = $this->db->insert_id();

            $saved[] = $row;
        }

        return $saved;
    }

    /** 첨부 목록 (네 인덱스: idx_files_post_list(post_id, is_deleted) 활용) */
    public function list_by_post($post_id): array
    {
        return $this->db->from('files')
            ->where('post_id', (int)$post_id)
            ->where('is_deleted', 0)
            ->order_by('created_at', 'asc') // idx_files_post_created_at(post_id, created_at) 도움
            ->get()->result();
    }

    /** 다운로드 전 조회 */
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

    /** 게시글 단위 소프트 삭제 (네 인덱스: idx_files_post_list 사용) */
    public function soft_delete_by_post($post_id): void
    {
        $this->db->set('is_deleted', 1)
                 ->where('post_id', (int)$post_id)
                 ->where('is_deleted', 0)
                 ->update('files');
    }

    /** (옵션) 개별 파일 소프트 삭제 */
    public function soft_delete_one($file_id): bool
    {
        return $this->db->set('is_deleted', 1)
                 ->where('id', (int)$file_id)
                 ->where('is_deleted', 0)
                 ->update('files');
    }

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
