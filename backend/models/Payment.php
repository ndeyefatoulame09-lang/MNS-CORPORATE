<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Payment extends BaseModel
{
    public const METHODS = ['ESPECES', 'VIREMENT', 'CHEQUE', 'WAVE', 'ORANGE_MONEY', 'AUTRE'];

    public function findById(int $id): ?array { return $this->fetchOne('SELECT * FROM payments WHERE id = :id', ['id'=>$id]); }
    public function findByInvoice(int $invoiceId): array { return $this->fetchAll('SELECT p.*, u.full_name AS receiver_name FROM payments p INNER JOIN users u ON u.id = p.received_by WHERE p.invoice_id = :id ORDER BY p.payment_date DESC', ['id'=>$invoiceId]); }
    public function getTotalByInvoice(int $invoiceId): float { $r=$this->fetchOne('SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE invoice_id = :id',['id'=>$invoiceId]); return $r ? (float)$r['total'] : 0.0; }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params=[]; $where=$this->where($filters,$params); $params['limit']=$limit; $params['offset']=$offset;
        return $this->fetchAll("SELECT p.*, i.invoice_number, c.company_name, u.full_name AS receiver_name FROM payments p INNER JOIN invoices i ON i.id=p.invoice_id INNER JOIN clients c ON c.id=i.client_id INNER JOIN users u ON u.id=p.received_by {$where} ORDER BY p.payment_date DESC LIMIT :limit OFFSET :offset", $params);
    }
    public function countAll(array $filters=[]): int { $params=[]; $where=$this->where($filters,$params); $r=$this->fetchOne("SELECT COUNT(*) AS total FROM payments p INNER JOIN invoices i ON i.id=p.invoice_id INNER JOIN clients c ON c.id=i.client_id {$where}",$params); return $r?(int)$r['total']:0; }
    public function create(array $data): int { return $this->insert('payments',['invoice_id'=>(int)$data['invoice_id'],'payment_date'=>$data['payment_date'],'amount'=>(float)$data['amount'],'payment_method'=>$data['payment_method'],'reference_number'=>($data['reference_number']??'')===''?null:$data['reference_number'],'notes'=>($data['notes']??'')===''?null:$data['notes'],'received_by'=>(int)$data['received_by']]); }
    private function where(array $filters, array &$params): string { $w=[]; foreach(['invoice_id','client_id','payment_method'] as $f){ if(($filters[$f]??'')!==''){ $col=$f==='client_id'?'i.client_id':"p.$f"; $w[]="$col = :$f"; $params[$f]=$filters[$f]; }} if(($filters['date_from']??'')!==''){ $w[]='p.payment_date >= :date_from'; $params['date_from']=$filters['date_from']; } if(($filters['date_to']??'')!==''){ $w[]='p.payment_date <= :date_to'; $params['date_to']=$filters['date_to']; } return $w?' WHERE '.implode(' AND ',$w):''; }
}
