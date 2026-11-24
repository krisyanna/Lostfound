<?php require 'inc.php';need();$u=me();

$q=trim($_GET['q']??'');$st=$_GET['s']??'';$cat=(int)($_GET['c']??0);

$w=['i.verified=1'];$p=[];

if($q!==''){$w[]='(i.name LIKE ? OR i.description LIKE ? OR i.location LIKE ?)';array_push($p,"%$q%","%$q%","%$q%");}
if(in_array($st,['Lost','Found','Claimed','Returned'],true)){$w[]='i.status=?';$p[]=$st;}
if($cat){$w[]='i.category_id=?';$p[]=$cat;}

$loc=trim($_GET['loc']??'');$from=$_GET['from']??'';$to=$_GET['to']??'';$sort=$_GET['sort']??'new';
$okd=fn($d)=>($x=DateTime::createFromFormat('Y-m-d',$d))&&$x->format('Y-m-d')===$d;
if($loc!==''){$w[]='i.location LIKE ?';$p[]="%$loc%";}
if($okd($from)){$w[]='i.event_date>=?';$p[]=$from;}
if($okd($to)){$w[]='i.event_date<=?';$p[]=$to;}
$orders=['new'=>'i.created_at DESC, i.id DESC','old'=>'i.created_at ASC, i.id ASC','date'=>'i.event_date DESC, i.id DESC'];
$order=$orders[$sort]??$orders['new'];

$s=db()->prepare('SELECT i.*,c.name cat FROM items i JOIN categories c ON c.id=i.category_id WHERE '.implode(' AND ',$w).' ORDER BY '.$order.' LIMIT 60');$s->execute($p);$items=$s->fetchAll();
$cats=db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$pend=[];if($u['role']==='student'){$r=db()->prepare("SELECT item_id FROM claims WHERE user_id=? AND status='Pending'");$r->execute([$u['id']]);$pend=$r->fetchAll(PDO::FETCH_COLUMN);}
head('Browse');?>

<section class="hero"><h1>Find it. Claim it. Get it back.</h1>

<form class="search" method="get"><input name="q" value="<?=e($q)?>" placeholder="Search by item, description or place, e.g. “black umbrella library”">

<select name="c" data-auto><option value="0">All categories</option><?php foreach($cats as $c)echo '<option value="'.$c['id'].'"'.($cat==$c['id']?' selected':'').'>'.e($c['name']).'</option>';?></select>

<select name="s" data-auto><option value="">Any status</option><?php foreach(['Lost','Found','Claimed','Returned'] as $x)echo '<option'.($st===$x?' selected':'').'>'.$x.'</option>';?></select>

<button class="btn">Search</button>
<div class="filters">
<input name="loc" value="<?=e($loc)?>" placeholder="Place contains…" aria-label="Place">
<input type="date" name="from" value="<?=e($from)?>" title="Event date from" aria-label="Event date from">
<input type="date" name="to" value="<?=e($to)?>" title="Event date to" aria-label="Event date to">
<select name="sort" data-auto aria-label="Sort by">
<option value="new">Newest posted</option>
<option value="old"<?=$sort==='old'?' selected':''?>>Oldest posted</option>
<option value="date"<?=$sort==='date'?' selected':''?>>Latest event date</option>
</select>
<a class="btn ghost sm" href="index.php">Clear</a>
</div></form></section>


<?php if(!$items)echo '<div class="empty"><h3>Nothing matches yet</h3><p>Try fewer words or clear the filters. New reports appear once the administrator verifies them.</p></div>';?>

<div class="grid"><?php foreach($items as $i):?>

    <article class="tk <?=e($i['status'])?>"><div class="stub"><b><?=e($i['status'])?></b><span><?=tno($i['id'])?></span></div>

    <div class="body"><?php if($i['photo'])echo '<img src="uploads/'.e($i['photo']).'" alt="Photo of '.e($i['name']).'" loading="lazy">';?>

    <h3><?=e($i['name'])?></h3><p><?=e($i['description'])?></p>

    <dl><dt>Category</dt><dd><?=e($i['cat'])?></dd><dt>Place</dt><dd><?=e($i['location'])?></dd><dt>Date</dt><dd><?=e($i['event_date'])?></dd></dl>

<?php if($u['role']==='student'&&$i['status']==='Found'&&$i['user_id']!=$u['id']):

if(in_array($i['id'],$pend)):echo '<span class="mut">Claim pending review</span>';else:?>

<details><summary class="btn sm">This is mine</summary><form method="post" action="claim.php"><?=tok()?><input type="hidden" name="item" value="<?=$i['id']?>">

<label>Describe something only the owner would know<textarea name="proof" required minlength="15" maxlength="600" placeholder="Marks, contents, serial number, lock-screen photo…"></textarea></label><button class="btn sm">Send claim</button></form></details><?php endif;endif;?></div></article>

<?php endforeach;?></div><?php foot();
