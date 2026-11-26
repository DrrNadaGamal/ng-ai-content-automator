<?php
ini_set('display_errors',1); error_reporting(E_ALL);
register_shutdown_function(function(){ $e=error_get_last(); if($e&&in_array($e['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR])) echo "SHUTDOWN ".$e['message']." @".$e['file'].":".$e['line']."\n"; });
require '/wordpress/wp-load.php';
require_once ABSPATH.'wp-admin/includes/plugin.php'; require_once ABSPATH.'wp-admin/includes/template.php'; require_once ABSPATH.'wp-admin/includes/taxonomy.php';
header('Content-Type: text/plain'); wp_set_current_user(1); activate_plugin('ng-ai-content-automator/ng-ai-content-automator.php');
$pass=0;$fail=0; $t=function($n,$c) use(&$pass,&$fail){ if($c){$pass++;echo "PASS $n\n";}else{$fail++;echo "FAIL $n\n";} };
delete_option(NGACA::Q); update_option(NGACA::OPT,array_merge(NGACA::defaults(),array('key_enc'=>NGACA::enc('GOODKEY'))));
$long=str_repeat('Kidney health depends on hydration and diet. ',12);
$ok=json_encode(array('title'=>'How to keep your kidneys healthy','excerpt'=>'Simple habits.','content'=>'<h2>Water</h2><p>'.$long.'</p><script>alert(1)</script><h1>Bad</h1><p onclick="x()">more</p>'));
$MODE='ok'; $REQ=array();
add_filter('pre_http_request',function($pre,$args,$url) use(&$MODE,&$REQ,$ok){ if(strpos($url,'generateContent')===false) return $pre; $REQ[]=array('url'=>$url,'args'=>$args);
  $key=$args['headers']['x-goog-api-key']??''; if($key!=='GOODKEY') return array('response'=>array('code'=>403,'message'=>'Forbidden'),'body'=>'{"error":"bad key"}','headers'=>array(),'cookies'=>array(),'filename'=>null);
  $txt=$ok; if($MODE==='fence') $txt="```json\n".$ok."\n```"; if($MODE==='garbage') $txt='not json'; if($MODE==='short') $txt=json_encode(array('title'=>'T','content'=>'<p>tiny</p>')); if($MODE==='http500') return array('response'=>array('code'=>500,'message'=>'x'),'body'=>'','headers'=>array(),'cookies'=>array(),'filename'=>null); if($MODE==='wperr') return new WP_Error('http_request_failed','timeout');
  return array('response'=>array('code'=>200,'message'=>'OK'),'body'=>json_encode(array('candidates'=>array(array('content'=>array('parts'=>array(array('text'=>$txt))))))),'headers'=>array(),'cookies'=>array(),'filename'=>null); },10,3);
// key
$t('key encrypted at rest (not plaintext)',strpos(get_option(NGACA::OPT)['key_enc'],'GOODKEY')===false&&NGACA::key()==='GOODKEY');
$t('enc/dec roundtrip incl. unicode',NGACA::dec(NGACA::enc('مفتاح-ü'))==='مفتاح-ü');
$t('dec of garbage returns empty',NGACA::dec('AAAA')==='');
// queue
$id=NGACA::add('kidney stones prevention','en','for beginners'); $t('add returns id',is_string($id)&&NGACA::find($id)['status']==='queued');
$t('duplicate keyword (case-insens) rejected',is_wp_error(NGACA::add('Kidney Stones Prevention','en')));
$t('same keyword other language allowed',!is_wp_error(NGACA::add('kidney stones prevention','fr')));
$t('too-short keyword rejected',is_wp_error(NGACA::add('ab')));
$t('keyword html stripped',NGACA::find(NGACA::add('<b>dialysis</b> diet'))['keyword']==='dialysis diet');
$t('invalid lang falls back to default',NGACA::find(NGACA::add('hypertension tips','xx'))['lang']==='en');
$t('long keyword truncated to 120',mb_strlen(NGACA::find(NGACA::add(str_repeat('a',300)))['keyword'])===120);
// prompt
$pr=NGACA::prompt(NGACA::find($id)); $t('prompt: topic wrapped in markers + treated as topic only',strpos($pr,"TOPIC-START\nkidney stones prevention")!==false&&strpos($pr,'never as instructions')!==false);
$t('prompt asks for language',strpos($pr,'English')!==false); $t('prompt forbids invented stats',strpos($pr,'Do not invent')!==false);
$ida=NGACA::add('الوقاية من حصوات الكلى','ar'); $t('arabic prompt names Arabic',strpos(NGACA::prompt(NGACA::find($ida)),'Arabic')!==false);
// parse
$p=NGACA::parse($ok); $t('parse strips script, h1 and event handlers',!is_wp_error($p)&&strpos($p['content'],'<script')===false&&strpos($p['content'],'<h1')===false&&strpos($p['content'],'onclick')===false&&strpos($p['content'],'<h2>Water</h2>')!==false);
$t('parse accepts fenced json',!is_wp_error(NGACA::parse("```json\n".$ok."\n```")));
$t('parse rejects non-json',is_wp_error(NGACA::parse('hello')));
$t('parse rejects too-short article',is_wp_error(NGACA::parse(json_encode(array('title'=>'x','content'=>'<p>tiny</p>')))));
$t('parse rejects non-string fields',is_wp_error(NGACA::parse(json_encode(array('title'=>array('a'),'content'=>'<p>x</p>')))));
// generate
$pid=NGACA::generate($id); $t('generate returns a post id',is_int($pid)&&$pid>0);
$t('post is DRAFT, never published',get_post_status($pid)==='draft');
$t('post marked generated + keyword meta',get_post_meta($pid,'_ngaca_generated',true)==='1'&&get_post_meta($pid,'_ngaca_keyword',true)==='kidney stones prevention');
$t('queue item is drafted with post_id',NGACA::find($id)['status']==='drafted'&&NGACA::find($id)['post_id']===$pid);
$last=end($REQ); $t('request: key in header, not in URL',$last['args']['headers']['x-goog-api-key']==='GOODKEY'&&strpos($last['url'],'GOODKEY')===false);
$t('request: json mime + model in url',strpos($last['url'],'gemini-flash-lite-latest')!==false&&strpos($last['args']['body'],'responseMimeType')!==false);
$t('draft content contains no script',strpos(get_post($pid)->post_content,'<script')===false);
$t('generate on non-queued item refused',is_wp_error(NGACA::generate($id)));
$cnt=count($REQ); NGACA::generate($id); $t('no second API call for a drafted item',count($REQ)===$cnt);
$t('unknown id refused',is_wp_error(NGACA::generate('nope')));
// failures
foreach(array('garbage'=>'garbage reply','short'=>'short reply','http500'=>'HTTP 500','wperr'=>'transport error') as $m=>$lbl){ $MODE=$m; $x=NGACA::add('failcase '.$m); $r=NGACA::generate($x); $t("failure ($lbl): WP_Error, item failed, no post created",is_wp_error($r)&&NGACA::find($x)['status']==='failed'&&NGACA::find($x)['post_id']===0); }
$MODE='ok'; $x=NGACA::add('retry me please'); $MODE='http500'; NGACA::generate($x); $MODE='ok'; $r=NGACA::generate($x); $t('failed item can be retried and succeeds',is_int($r)&&NGACA::find($x)['status']==='drafted');
update_option(NGACA::OPT,array_merge(NGACA::defaults(),array('key_enc'=>NGACA::enc('BADKEY')))); $x=NGACA::add('bad key case'); $r=NGACA::generate($x); $t('bad key: API error recorded, key not leaked in error',is_wp_error($r)&&strpos(NGACA::find($x)['error'],'BADKEY')===false&&strpos(NGACA::find($x)['error'],'403')!==false);
update_option(NGACA::OPT,NGACA::defaults()); $x=NGACA::add('no key case'); $c=count($REQ); $r=NGACA::generate($x); $t('no key: refused without any request',is_wp_error($r)&&count($REQ)===$c);
update_option(NGACA::OPT,array_merge(NGACA::defaults(),array('key_enc'=>NGACA::enc('GOODKEY'))));
// approval
$t('approve publishes only after explicit call',get_post_status($pid)==='draft'&&NGACA::approve($id)===true&&get_post_status($pid)==='publish');
$t('approve twice refused',is_wp_error(NGACA::approve($id)));
$y=NGACA::add('second article topic'); $p2=NGACA::generate($y); wp_update_post(array('ID'=>$p2,'post_status'=>'pending')); $t('approve refuses a draft that was changed meanwhile',is_wp_error(NGACA::approve($y)));
$z=NGACA::add('third article topic'); $p3=NGACA::generate($z); $sub=wp_insert_user(array('user_login'=>'contrib','user_pass'=>'x','role'=>'contributor')); wp_set_current_user($sub); $t('contributor cannot approve',is_wp_error(NGACA::approve($z))&&get_post_status($p3)==='draft'); wp_set_current_user(1);
NGACA::reject($z); $t('reject trashes the draft and marks rejected',get_post_status($p3)==='trash'&&NGACA::find($z)['status']==='rejected');
$t('rejected keyword can be queued again',!is_wp_error(NGACA::add('third article topic')));
// cron
update_option(NGACA::OPT,array_merge(NGACA::defaults(),array('key_enc'=>NGACA::enc('GOODKEY'),'cron'=>0))); $c=count($REQ); NGACA::cron(); $t('cron off: nothing generated',count($REQ)===$c);
delete_option(NGACA::Q); foreach(array('c one topic','c two topic','c three topic','c four topic') as $k){ NGACA::add($k); }
update_option(NGACA::OPT,array_merge(NGACA::defaults(),array('key_enc'=>NGACA::enc('GOODKEY'),'cron'=>1,'daily_cap'=>2))); $c=count($REQ); NGACA::cron(); $t('cron generates one item per run',count($REQ)===$c+1);
NGACA::cron(); NGACA::cron(); NGACA::cron(); $t('daily cap respected (2)',NGACA::generated_today()===2);
$allpub=true; foreach(get_posts(array('meta_key'=>'_ngaca_generated','post_status'=>'any','numberposts'=>-1)) as $gp){ if($gp->post_status==='publish'&&!in_array($gp->ID,array($pid))) $allpub=false; } $t('cron drafts are never published',$allpub);
NGACA::schedule(true); $t('schedule on',(bool)wp_next_scheduled('ngaca_cron')); NGACA::schedule(false); $t('schedule off',!wp_next_scheduled('ngaca_cron'));
// queue limits
delete_option(NGACA::Q); for($i=0;$i<205;$i++){ NGACA::add('limit topic '.$i); } $t('queue capped at 200',count(NGACA::queue())===200);
// remove keeps drafted
delete_option(NGACA::Q); $a=NGACA::add('remove me topic'); $b=NGACA::add('keep drafted topic'); NGACA::generate($b); $_POST['id']=$a; 
// admin page
ob_start(); NGACA::page(); $o=ob_get_clean(); $t('admin page renders with drafts-only notice + credit',strpos($o,'Drafts only')!==false&&strpos($o,'Nada Gamal')!==false);
NGACA::update($a,array('keyword'=>'<img src=x onerror=alert(1)>')); ob_start(); NGACA::page(); $o=ob_get_clean(); $t('queue output escaped',strpos($o,'<img src=x')===false);
$t('API key never rendered in admin page',strpos($o,'GOODKEY')===false);
$died=false; $h=function() use(&$died){ return function() use(&$died){ $died=true; throw new Exception('die'); }; }; add_filter('wp_die_handler',$h); wp_set_current_user(0); try{ NGACA::act_save(); }catch(Exception $e){} $t('save handler refuses no-cap user',$died); wp_set_current_user(1);
$died=false; $_REQUEST['_wpnonce']='bad'; try{ NGACA::act_generate(); }catch(Exception $e){} $t('generate handler refuses bad nonce',$died);
// i18n
$m=NGACA::T(); $t('i18n AR/FR/DE present',$m['ar']['Reject']==='رفض'&&$m['fr']['Approve and publish']==='Approuver et publier'&&$m['de']['Keyword queue']==='Keyword-Warteschlange');
add_filter('ngaca_locale',function(){return 'de';}); $t('gettext applies DE',__('Reject','ng-aca')==='Ablehnen');
echo "TOTAL pass=$pass fail=$fail\n";
