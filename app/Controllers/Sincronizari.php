<?php
namespace App\Controllers;

/**
 * Jurnal joburi de sincronizare (cron): la ce ora au rulat, durata, status si ce/cat au procesat.
 * Sursa: tabela `sync_job_runs` (populata de scripturile din /sync).
 */
class Sincronizari extends BaseController
{
    private $data = [
        'title'   => 'Sincronizari',
        'menu'    => '',
        'card'    => '',
        'jsFiles' => [],
    ];

    public function index()
    {
        $session = \Config\Services::session();
        if ($session->get('user') === NULL) return view('login.php');

        $db  = \Config\Database::connect();
        $job = $_GET['job'] ?? '';
        $where = '';
        if ($job !== '' && preg_match('/^[a-z_]+$/', $job)) $where = "WHERE job = " . $db->escape($job);

        $this->data['runs'] = $db->query(
            "SELECT id, job, started_at, finished_at,
                    TIMESTAMPDIFF(SECOND, started_at, COALESCE(finished_at, NOW())) AS durata_s,
                    status, summary
               FROM sync_job_runs $where
              ORDER BY started_at DESC LIMIT 300")->getResultArray();

        $this->data['jobs']      = $db->query("SELECT DISTINCT job FROM sync_job_runs ORDER BY job")->getResultArray();
        $this->data['filterJob'] = $job;

        $out['data'] = $this->data;
        return view('sincronizari', $out);
    }

    /** Coada de log pentru o rulare (text simplu, incarcat in modal). */
    public function detail()
    {
        $session = \Config\Services::session();
        if ($session->get('user') === NULL) return $this->response->setStatusCode(403)->setBody('');
        $id  = (int)($_GET['id'] ?? 0);
        $db  = \Config\Database::connect();
        $row = $db->query("SELECT job, started_at, details FROM sync_job_runs WHERE id = ?", [$id])->getRowArray();
        $body = $row ? "[{$row['job']}  {$row['started_at']}]\n\n{$row['details']}" : '(rulare inexistenta)';
        return $this->response->setContentType('text/plain; charset=utf-8')->setBody($body);
    }
}
