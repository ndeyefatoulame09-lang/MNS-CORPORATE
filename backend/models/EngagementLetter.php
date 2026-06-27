<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class EngagementLetter extends BaseModel
{
    public const STATUSES = ['BROUILLON', 'ENVOYEE', 'SIGNEE'];

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT el.*, c.company_name, m.title AS mission_title, u.full_name AS creator_name
             FROM engagement_letters el
             INNER JOIN clients c ON c.id = el.client_id
             LEFT JOIN missions m ON m.id = el.mission_id
             INNER JOIN users u ON u.id = el.created_by
             WHERE el.id = :id',
            ['id'=>$id]
        );
    }

    public function findAll(array $filters=[], int $limit=20, int $offset=0): array
    {
        $params=[]; $where=$this->where($filters,$params); $params['limit']=$limit; $params['offset']=$offset;
        return $this->fetchAll("SELECT el.*, c.company_name, m.title AS mission_title FROM engagement_letters el INNER JOIN clients c ON c.id=el.client_id LEFT JOIN missions m ON m.id=el.mission_id {$where} ORDER BY el.created_at DESC LIMIT :limit OFFSET :offset",$params);
    }

    public function countAll(array $filters=[]): int
    {
        $params=[]; $where=$this->where($filters,$params); $r=$this->fetchOne("SELECT COUNT(*) AS total FROM engagement_letters el INNER JOIN clients c ON c.id=el.client_id LEFT JOIN missions m ON m.id=el.mission_id {$where}",$params);
        return $r?(int)$r['total']:0;
    }

    public function create(array $data): int
    {
        return $this->insert('engagement_letters', [
            'client_id'=>(int)$data['client_id'],
            'mission_id'=>($data['mission_id']??'')===''?null:(int)$data['mission_id'],
            'title'=>$data['title'],
            'file_path'=>$data['file_path']??null,
            'status'=>'BROUILLON',
            'created_by'=>(int)$data['created_by'],
        ]);
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateRecord('engagement_letters',$id,[
            'client_id'=>(int)$data['client_id'],
            'mission_id'=>($data['mission_id']??'')===''?null:(int)$data['mission_id'],
            'title'=>$data['title'],
            'file_path'=>$data['file_path']??null,
            'status'=>$data['status']??'BROUILLON',
            'sent_at'=>null,
            'signed_at'=>null,
            'signed_by_name'=>null,
            'signature_text'=>null,
        ]);
    }

    public function markAsSent(int $id): bool { return $this->updateRecord('engagement_letters',$id,['status'=>'ENVOYEE','sent_at'=>date('Y-m-d H:i:s')]); }
    public function sign(int $id, string $name, string $text): bool { return $this->updateRecord('engagement_letters',$id,['status'=>'SIGNEE','signed_at'=>date('Y-m-d H:i:s'),'signed_by_name'=>$name,'signature_text'=>$text]); }
    public function findByClient(int $clientId): array { return $this->findAll(['client_id'=>$clientId],100,0); }

    public function canUserAccessLetter(array $user, array $letter): bool
    {
        if (($user['role']??'') === 'EXPERT') { return true; }
        if (($user['role']??'') === 'CLIENT') {
            $client = $this->fetchOne('SELECT id FROM clients WHERE user_id = :user_id', ['user_id'=>(int)$user['id']]);
            return $client && (int)$client['id'] === (int)$letter['client_id'];
        }
        return false;
    }

    private function where(array $filters, array &$params): string
    {
        $w=[];
        if(($filters['q']??'')!==''){ $w[]='el.title LIKE :q'; $params['q']='%'.$filters['q'].'%'; }
        foreach(['client_id','mission_id','status'] as $f){ if(($filters[$f]??'')!==''){ $w[]="el.$f = :$f"; $params[$f]=$filters[$f]; }}
        if(($filters['visible_client_id']??'')!==''){ $w[]='el.client_id = :visible_client_id'; $params['visible_client_id']=$filters['visible_client_id']; }
        return $w?' WHERE '.implode(' AND ',$w):'';
    }
}
