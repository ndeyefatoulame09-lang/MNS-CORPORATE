<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../models/Payment.php';
require_once __DIR__.'/../models/Invoice.php';
require_once __DIR__.'/../models/AuditLog.php';
require_once __DIR__.'/../includes/role_check.php';
require_once __DIR__.'/invoice_controller.php';

const PAYMENT_LIST_URL='/MNS_CORPORATE/frontend/views/payments/list.php';
function handlePaymentRequest(): void {startSecureSession(); requireRole(['EXPERT']); $a=$_GET['action']??'list'; if($a==='store')storePayment(); elseif($a==='create')showPaymentCreate(); elseif($a==='balance')showBalanceAged(); else listPayments();}
function listPayments(): void {$pdo=getDatabaseConnection();$m=new Payment($pdo);$filters=paymentFilters();$page=max(1,(int)($_GET['page']??1));$perPage=20;$payments=$m->findAll($filters,$perPage,($page-1)*$perPage);$total=$m->countAll($filters);$invoices=paymentInvoices($pdo);$clients=fetchInvoiceClients($pdo);renderPaymentView('list.php',compact('payments','total','filters','page','perPage','invoices','clients'));}
function showPaymentCreate(): void {$pdo=getDatabaseConnection();$invoices=paymentInvoices($pdo);renderPaymentView('create.php',compact('invoices'));}
function storePayment(): void {paymentPostOnly();$pdo=getDatabaseConnection();$data=paymentInput($_POST)+['received_by'=>currentUserId()];$invoice=(new Invoice($pdo))->findById((int)$data['invoice_id']);$errors=validatePayment($pdo,$data,$invoice); if($errors){setFlashMessage('error',implode(' ',$errors));showPaymentCreate();return;} $pdo->beginTransaction(); try{$id=(new Payment($pdo))->create($data);(new Invoice($pdo))->refreshPaymentStatus((int)$data['invoice_id']);$pdo->commit();paymentAudit($pdo,'ENREGISTREMENT_PAIEMENT','Paiement #'.$id);paymentAudit($pdo,'MISE_A_JOUR_STATUT_FACTURE','Facture #'.$data['invoice_id']);notifyInvoiceClient($pdo,(int)$data['invoice_id'],'Paiement enregistre','Un paiement a ete enregistre sur votre facture.');redirect(PAYMENT_LIST_URL);}catch(Throwable $e){$pdo->rollBack();setFlashMessage('error','Paiement impossible.');showPaymentCreate();}}
function showBalanceAged(): void {$rows=(new Invoice(getDatabaseConnection()))->getBalanceAgedSummary();$totals=['non_echue'=>0,'1_30'=>0,'31_60'=>0,'60_plus'=>0];foreach($rows as $r){$b=(float)$r['balance_due'];$d=(int)$r['days_late'];if($d<=0)$totals['non_echue']+=$b;elseif($d<=30)$totals['1_30']+=$b;elseif($d<=60)$totals['31_60']+=$b;else $totals['60_plus']+=$b;}renderPaymentView('balance_aged.php',compact('rows','totals'));}
function paymentInput(array $in): array {foreach(['invoice_id','payment_date','amount','payment_method','reference_number','notes'] as $k)$d[$k]=trim((string)($in[$k]??''));return $d;}
function validatePayment(PDO $pdo,array $d,?array $invoice): array {$e=[]; if(!$invoice)$e[]='Facture obligatoire.'; elseif($invoice['status']==='ANNULEE')$e[]='Facture annulee.'; if($d['payment_date']==='')$e[]='Date obligatoire.'; if(!is_numeric($d['amount'])||(float)$d['amount']<=0)$e[]='Montant invalide.'; if(!in_array($d['payment_method'],Payment::METHODS,true))$e[]='Moyen invalide.'; if($invoice&&(float)$d['amount']>(new Invoice($pdo))->getRemainingBalance((int)$invoice['id']))$e[]='Paiement superieur au solde.';return $e;}
function paymentFilters(): array {$f=[];foreach(['invoice_id','client_id','payment_method','date_from','date_to'] as $k){$v=trim((string)($_GET[$k]??''));if($v!=='')$f[$k]=$v;}return $f;}
function paymentInvoices(PDO $pdo): array {$s=$pdo->prepare("SELECT id, invoice_number FROM invoices WHERE status <> 'ANNULEE' ORDER BY invoice_number");$s->execute();return $s->fetchAll(PDO::FETCH_ASSOC);}
function paymentPostOnly(): void {if(!isPostRequest())redirect(PAYMENT_LIST_URL);}
function paymentAudit(PDO $pdo,string $a,string $d): void {(new AuditLog($pdo))->log(['user_id'=>currentUserId(),'action'=>$a,'description'=>$d,'ip_address'=>$_SERVER['REMOTE_ADDR']??null]);}
function renderPaymentView(string $view,array $vars=[]): void {extract($vars,EXTR_SKIP);if(!defined('MNS_CONTROLLER_RENDER'))define('MNS_CONTROLLER_RENDER',true);require __DIR__.'/../../frontend/views/payments/'.$view;}
