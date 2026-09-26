<?php
namespace App\Controllers;

/**
 * Jurnal joburi de sincronizare + acoperire API zilnica.
 * Surse: sync_job_runs (rulari), api_pod_reads (ce POD s-a citit prin API/zi).
 * Universul de referinta = PODuri ACTIVE ale clientilor activi CU consum recent (consumatori reali).
 */
class Sincronizari extends BaseController
{
    private $data = ['title' => 'Sincronizari', 'menu' => '', 'card' => '', 'jsFiles' => []];

    /** prag "consum recent": inceputul lunii de acum ~4 luni */
    private function recent() { return date('Y-m-01', strtotime('-4 months')); }

    /** subquery: PODurile consumatorilor reali (POD activ + client activ + CONTRACT activ + consum recent) */
    private function universeSql() {
        return "SELECT DISTINCT p.pod_no
                  FROM pods p
                  JOIN customers c ON c.customer_id=p.customer_id AND c.customer_status='Activ'
                  JOIN consumptions co ON co.pod=p.pod_no AND co.consumption_date >= " . $this->db()->escape($this->recent()) . "
                  JOIN (SELECT DISTINCT customer_id FROM contracts
                         WHERE contract_stop IS NULL OR contract_stop >= NOW()) ct ON ct.customer_id=p.customer_id
                 WHERE p.pod_status='Activ'";
    }
    private function db() { return \Config\Database::connect(); }

    public function index()
    {
        $session = \Config\Services::session();
        if ($session->get('user') === NULL) return view('login.php');
        $db = $this->db();

        // --- jurnal rulari ---
        $job = $_GET['job'] ?? '';
        $where = ($job !== '' && preg_match('/^[a-z_]+$/', $job)) ? "WHERE job=" . $db->escape($job) : '';
        $this->data['runs'] = $db->query(
            "SELECT id, job, started_at, finished_at,
                    TIMESTAMPDIFF(SECOND, started_at, COALESCE(finished_at, NOW())) durata_s, status, summary
               FROM sync_job_runs $where ORDER BY started_at DESC LIMIT 200")->getResultArray();
        $this->data['jobs']      = $db->query("SELECT DISTINCT job FROM sync_job_runs ORDER BY job")->getResultArray();
        $this->data['filterJob'] = $job;

        // --- acoperire API ---
        $days = (int)($_GET['days'] ?? 7); if (!in_array($days, [7, 30], true)) $days = 7;
        $this->data['days'] = $days;
        $uni = $this->universeSql();
        $this->data['universe'] = (int)($db->query("SELECT COUNT(*) n FROM ($uni) u")->getRow()->n);

        $this->data['coverage'] = $db->query(
            "SELECT read_date d, COUNT(DISTINCT pod) citite FROM api_pod_reads
              WHERE read_date >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
                AND pod IN ($uni)
              GROUP BY read_date ORDER BY read_date")->getResultArray();

        // ziua selectata pt lista necitite (implicit ultima zi cu date)
        $day = $_GET['day'] ?? ($db->query("SELECT MAX(read_date) d FROM api_pod_reads")->getRow()->d);
        $this->data['day'] = $day;
        $this->data['missByDist'] = $day ? $db->query(
            "SELECT p.distributor_id, COALESCE(d.distributor_name,'(neconfigurat)') dist,
                    COUNT(DISTINCT p.pod_no) necitite
               FROM pods p
               LEFT JOIN distributors d ON d.distributor_id=p.distributor_id
              WHERE p.pod_no IN ($uni)
                AND p.pod_no NOT IN (SELECT pod FROM api_pod_reads WHERE read_date=" . $db->escape($day) . ")
              GROUP BY p.distributor_id ORDER BY necitite DESC")->getResultArray() : [];

        $out['data'] = $this->data;
        return view('sincronizari', $out);
    }

    /** Lista PODurilor necitite intr-o zi, pt un distribuitor (modal). */
    public function necitite()
    {
        $session = \Config\Services::session();
        if ($session->get('user') === NULL) return $this->response->setStatusCode(403)->setBody('');
        $db   = $this->db();
        $day  = $_GET['day'] ?? '';
        $dist = $_GET['dist'] ?? '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) return $this->response->setBody('zi invalida');
        $distCond = ($dist === '' || $dist === 'null') ? "(p.distributor_id IS NULL OR p.distributor_id=0)"
                                                       : "p.distributor_id=" . (int)$dist;
        $rows = $db->query(
            "SELECT p.pod_no, cu.customer_name, cu.customer_vat_code cui,
                    COALESCE(d.distributor_name,'(neconfigurat)') dist,
                    CASE WHEN ctr.open_cnt > 0 THEN 'nedeterminat'
                         ELSE DATE_FORMAT(ctr.exp, '%Y-%m-%d') END AS contract_exp
               FROM pods p
               JOIN customers cu ON cu.customer_id=p.customer_id
               LEFT JOIN distributors d ON d.distributor_id=p.distributor_id
               LEFT JOIN (SELECT customer_id, MAX(contract_stop) exp, SUM(contract_stop IS NULL) open_cnt
                            FROM contracts WHERE contract_stop IS NULL OR contract_stop >= NOW()
                           GROUP BY customer_id) ctr ON ctr.customer_id=p.customer_id
              WHERE p.pod_no IN (" . $this->universeSql() . ") AND $distCond
                AND p.pod_no NOT IN (SELECT pod FROM api_pod_reads WHERE read_date=" . $db->escape($day) . ")
              GROUP BY p.pod_no ORDER BY cu.customer_name, p.pod_no")->getResultArray();

        $h = '<table class="table table-sm"><thead><tr><th>POD</th><th>Client</th><th>CUI</th><th>Distribuitor</th><th>Contract expiră</th></tr></thead><tbody>';
        foreach ($rows as $r) $h .= '<tr><td>' . esc($r['pod_no']) . '</td><td>' . esc($r['customer_name']) . '</td><td>' . esc($r['cui'])
              . '</td><td class="small">' . esc($r['dist']) . '</td><td>' . esc($r['contract_exp'] ?? '—') . '</td></tr>';
        $h .= '</tbody></table>';
        return $this->response->setBody('<p class="small text-muted">' . count($rows) . ' PODuri necitite pe ' . esc($day) . '</p>' . $h);
    }

    /** Coada de log pentru o rulare (modal). */
    public function detail()
    {
        $session = \Config\Services::session();
        if ($session->get('user') === NULL) return $this->response->setStatusCode(403)->setBody('');
        $id  = (int)($_GET['id'] ?? 0);
        $row = $this->db()->query("SELECT job, started_at, details FROM sync_job_runs WHERE id=?", [$id])->getRowArray();
        $body = $row ? "[{$row['job']}  {$row['started_at']}]\n\n{$row['details']}" : '(rulare inexistenta)';
        return $this->response->setContentType('text/plain; charset=utf-8')->setBody($body);
    }
}
